<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlacetelCall;
use App\Models\PlacetelCallApiToken;
use App\Models\PlacetelSipUserId;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PlacetelSipUserIdController extends Controller
{
    public $placetelApiUrl = "";
    public $placetelApiVersion = "";
    public $placetelToken = "";
    public $placetelFullUrl = "";

    public function __construct()
    {
        $this->placetelApiUrl = env('PLACETEL_CALL_API_URL', '');
        $this->placetelApiVersion = env('PLACETEL_CALL_API_VERSION', '');

        $placetelCallApiToken = PlacetelCallApiToken::orderBy('id', 'DESC')->first();
        if ($placetelCallApiToken && $placetelCallApiToken->token) {
            $this->placetelToken = $placetelCallApiToken->token;
        }

        $this->placetelFullUrl = $this->placetelApiUrl;
        if ($this->placetelApiVersion) {
            $this->placetelFullUrl = $this->placetelFullUrl . "/" . $this->placetelApiVersion;
        }
    }

    public function getPlacetelApiSipUserList()
    {
        try {
            $flag = true;
            $message = "Placetel api sip users received!";
            $data = [];

            $apiResponse = Http::timeout(60)->withHeaders([
                'Authorization' => "Bearer " . $this->placetelToken,
            ])->get($this->placetelFullUrl . "/sip_users");

            $apiResponse = $apiResponse->getBody()->getContents();
            $apiResponse = json_decode($apiResponse);

            if (isset($apiResponse->error)) {
                $flag = false;
                $message = $apiResponse->error;
                $data = [];
            } else {
                if ($apiResponse && count($apiResponse) > 0) {
                    $data = $apiResponse;
                }
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function getDetail()
    {
        try {
            $flag = false;
            $data = null;
            $message = "Not found!";

            $userId = auth()->user()->id;
            $placetelSipUserId = PlacetelSipUserId::where('user_id', $userId)->orderBy('id', 'DESC')->first();
            if ($placetelSipUserId && $placetelSipUserId->id) {
                $flag = true;
                if ($placetelSipUserId->response) {
                    $placetelSipUserId->response = json_decode($placetelSipUserId->response);
                }

                $data = $placetelSipUserId;
                $message = "Success.";
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            $response['data'] = $data;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function createOrUpdate(Request $request)
    {
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

            $placetelSipUserId = PlacetelSipUserId::where('user_id', $userId)->orderBy('id', 'DESC')->first();
            if ($placetelSipUserId) {
                $placetelSipUserId->update($updateData);
            } else {
                $placetelSipUserId = PlacetelSipUserId::create($updateData);
            }

            if ($placetelSipUserId && $placetelSipUserId->response) {
                $placetelSipUserId->response = json_decode($placetelSipUserId->response);
            }

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Placetel sipuid details updated!";
            $response['data'] = $placetelSipUserId;
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function deleteApiToken($id)
    {
        try {
            PlacetelSipUserId::where('id', $id)->delete();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Placetel sipuid details deleted!";
            $response['data'] = [];
            return response()->json($response);
        } catch (Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function initiatePlacetelApiCall(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = 'Placetel Call id is required!';
                $response['data'] = null;
                $response['error'] = $validation->errors();
                return response()->json($response);
            }

            $flag = false;
            $message = "Please choose your placetel VoIP from account setting!";
            $data = null;

            $id = $request->id;
            $placetelCall = PlacetelCall::where('id', $id)->first();

            if ($placetelCall && $placetelCall->from_number) {
                $userId = auth()->user()->id;
                $placetelSipUserId = PlacetelSipUserId::where('user_id', $userId)->orderBy('id', 'DESC')->first();

                if ($placetelSipUserId && $placetelSipUserId->sipuid) {
                    $flag = true;
                    $message = "";

                    $apiResponse = Http::connectTimeout(60)->timeout(60)->withHeaders([
                        'Authorization' => "Bearer " . $this->placetelToken,
                    ])->post($this->placetelFullUrl . "/calls", [
                        "sipuid" => $placetelSipUserId->sipuid,
                        "target" => $placetelCall->from_number,
                    ]);

                    $apiResponse = $apiResponse->getBody()->getContents();
                    $apiResponse = json_decode($apiResponse);

                    if (isset($apiResponse->error)) {
                        $flag = false;
                        $message = $apiResponse->error;
                        $data = [];
                    } else {
                        $flag = true;
                        $message = "Placetel call initiation in process!";

                        if ($apiResponse && isset($apiResponse->status) == "dialing") {
                            $message = "Placetel call initiated!";
                            $placetelCall['status'] = $apiResponse->status;
                            $data = $placetelCall;
                        }
                    }
                }
            }

            $response = array();
            $response['flag'] = $flag;
            $response['message'] = $message;
            $response['data'] = $data;
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
