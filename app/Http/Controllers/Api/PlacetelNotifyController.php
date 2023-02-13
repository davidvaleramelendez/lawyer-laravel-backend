<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlacetelIncomingSip;
use App\Models\PlacetelAcceptedNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PlacetelNotifyController extends Controller
{
    public function index(Request $request) {
        if(!$this->checkSignature($request)) 
            return response('Signature not valid.');
        $event = $request->input('event');
        $message = 'Get notified.';
        switch($event) {
            case 'IncomingCall':
                $message = $this->incomingNotify($request);
                break;
            case 'CallAccepted':
                $message = $this->acceptedNotify($request);
                break;
            case 'HungUp':
                $message = $this->hungupNotify($request);
            default:
                break;
        }
        return response($message);
    }

    public function checkSignature($request) {
        $secret = env('PLACETEL_NOTIFY_SECRET');
        $payload = $request->getContent();
        $signature = $request->header('X-PLACETEL-SIGNATURE');
        return $signature == hash_hmac('sha256', $payload, $secret);
    }

    public function incomingNotify($request) {
        return 'Get incoming notification successfully.';
    }

    public function acceptedNotify($request) {
        // Get the user from peer
        $item = PlacetelIncomingSip::where('sipuid', $request->peer)->first();
        if(!$item) {
            return 'Get accepted notification successfully. But user not found.';
        } else {
            $from = $request->from;
            $photo = '';
            $user = User::where('Contact', $request->from)->first();
            
            // Save it to database
            $acceptedNotification = new PlacetelAcceptedNotification();
            $acceptedNotification->user_id = $user ? $user->id : null;
            $acceptedNotification->from_number = $request->from;
            $acceptedNotification->to_number = $request->to;
            $acceptedNotification->call_id = $request->call_id;
            $acceptedNotification->peer = $request->peer;
            $acceptedNotification->save();

            // Call websocket endpoint
            $response = Http::timeout(60)->post(env('PLACETEL_NOTIFY_ENDPOINT'), [
                'user_id' => $item->user_id,
                'from' => $from,
                'photo' => ($user && $user->profile_photo_path) ? $user->profile_photo_path : '',
                'name' => $user ? $user->name : '',
            ]);
            $result = json_decode($response->getBody()->getContents());
            if($result)
                return 'Send notification to '.$request->peer.' successfully.';
            else
                return 'The user is not available now.';
        }
    }

    public function hungupNotify($request) {
        PlacetelAcceptedNotification::where('call_id', $request->call_id)->delete();
        return 'Get Hungup notification successfully.';
    }

    public function getNotification() {
        $notification = PlacetelAcceptedNotification::with('user')->first();
        $user = $notification->user;
        $data = [
            'user_id' => $notification->user_id,
            'from' => $notification->from_number,
            'photo' => ($user && $user->profile_photo_path) ? $user->profile_photo_path : '',
            'name' => $user ? $user->name : '',
        ];
        $response['flag'] = true;
        $response['message'] = $notification ? 'Success.' : 'Not Found';
        $response['data'] = $data;
        return response()->json($response);
    }

    public function getDetail() {
        try {
            $flag = false;
            $data = null;
            $message = "Not found!";

            $userId = auth()->user()->id;
            $placetelIncomingSip = PlacetelIncomingSip::where('user_id', $userId)->orderBy('id', 'DESC')->first();
            if ($placetelIncomingSip && $placetelIncomingSip->id) {
                $flag = true;
                if ($placetelIncomingSip->response) {
                    $placetelIncomingSip->response = json_decode($placetelIncomingSip->response);
                }

                $data = $placetelIncomingSip;
                $message = "Success.";
            }

            $notAllowedSipList = PlacetelIncomingSip::select('sipuid')->whereNot('user_id', $userId)->get();

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            $response['data'] = $data;
            $response['notAllowed'] = $notAllowedSipList;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            $response['notAllowed'] = [];
            return response()->json($response);
        }
    }

    public function createOrUpdate(Request $request) {
        try {
            $validation = Validator::make($request->all(), [
                'sipuid' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Sipuid is required!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $data = $request->all();
            $updateData = array();

            $userId = auth()->user()->id;

            $updateData['user_id'] = $userId ?? null;
            if (isset($request->sipuid) || $request->sipuid == "") {
                $updateData['sipuid'] = $request->sipuid;
            }

            if (isset($request->response) && $request->response) {
                $updateData['response'] = json_encode($request->response);
            }

            $placetelIncomingSip = PlacetelIncomingSip::where('user_id', $userId)->orderBy('id', 'DESC')->first();
            if ($placetelIncomingSip) {
                $placetelIncomingSip->update($updateData);
            } else {
                $placetelIncomingSip = PlacetelIncomingSip::create($updateData);
            }

            if ($placetelIncomingSip && $placetelIncomingSip->response) {
                $placetelIncomingSip->response = json_decode($placetelIncomingSip->response);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Placetel incoming sipuid details updated!";
            $response['data'] = $placetelIncomingSip;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }
}
