<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AddEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Google_Service_Calendar_Event;

class CalendarController extends Controller
{
    public function get_events()
    {
        try {
            $events = AddEvent::select(
                [
                    'id as id',
                    'url as url',
                    'title as title',
                    'start_date as start',
                    'end_date as end',
                    'all_day as allDay',
                    'business as calendar',
                    'guest as guest',
                    'location as location',
                    'description as description',
                ]);

                if (auth()->user()->role_id != 10) {
    
                    $id = (string)auth()->id();
                    $events = $events->whereJsonContains('guest', ["".auth()->id().""]);
                }
    
            $events = $events->get();
    
            $eventData = [];
            foreach($events as $event)
            {
                $data = [];
                $data['id'] = $event->id;
                $data['title'] = $event->title;
                $data['start']= Carbon::parse($event->start)->format('Y-m-d H:m:s');
                $data['end']= Carbon::parse($event->end)->format('Y-m-d H:m:s');
                $data['all_day'] = $event->all_day== 'on' ? true : false;
                $data['extendedProps'] = ['calendar'=>$event->calendar];
                $data['location'] = $event->location;
                $data['guests'] = $event->guest;
                $data['description'] = $event->description;
    
                array_push($eventData,$data);
            }
            
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $eventData;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            $response['data'] = [];
            return response()->json($response);
        }
        
    }

    public function get_users() {
        try {
            $users = User::whereNotIn('role_id',[12])
                ->where('id','!=',auth()->id())
                ->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $users;
            return response()->json($response);
        } catch (\Exception $e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function addEvent(Request $request)
    {
        # code...

        DB::beginTransaction();

        try{

           $myEvent = AddEvent::create([
                'title'=>$request->title,
                'business'=>$request->business,
                'start_date'=>$request->startdate,
                'end_date'=>$request->enddate,
                'all_day'=>$request->allday,
                'url'=>$request->url,
                'guest'=>json_encode($request->guest),
                'location'=>$request->location,
                'description'=>$request->description,
            ]);


            $user = \auth()->user();
            $google_account =\auth()->user()->googleAccounts()->first();

            if ($google_account)
            {
                $client = new \App\Services\Google();


                $token = [
                    'access_token' => $google_account->token['access_token'],
                    'expires_in' => $google_account->token['expires_in'],
                    'refresh_token' => $google_account->token['refresh_token']
                ];
                $google_client = $client->connectUsing($token)->getClient();

                if ($google_client->isAccessTokenExpired())
                {
                    $google_account->token = $google_client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                    $google_account->save();
                    $account =   $client->connectUsing($token['access_token']);

                }else{
                    $account =   $client->connectUsing($google_account->token['access_token']);

                }



                $userAttendees = [];
                if ($request->has('guest'))
                {
                    $attendees = User::whereIn('id',$request->guest)->pluck('email')->toArray();


                    foreach ($attendees as $attendee)
                    {
                        $userAttendees[] = ['email'=> $attendee];
                    }


                }


                $startDate =array(
                    'dateTime' => Carbon::parse($request->startdate)->toRfc3339String(),
                    'timeZone' =>'Europe/Zurich',
                );

                $endDate =array(
                    'dateTime' =>  Carbon::parse($request->enddate)->toRfc3339String(),
                    'timeZone' =>'Europe/Zurich',
                );                $service = $account->service('Calendar');

                $event = new Google_Service_Calendar_Event(array(
                    'summary' => $request->title,
                    'location' => $request->location,
                    'description' => $request->description,
                    'start' => $startDate,
                    'sendNotifications'=>true,
                    'sendUpdates' => 'all',
                    'end' =>$endDate,
                    'attendees' =>$userAttendees,
                    'reminders' => array(
                        'useDefault' => FALSE,
                        'overrides' => array(
                            array('method' => 'email', 'minutes' => 24 * 60),
                            array('method' => 'popup', 'minutes' => 10),
                        ),
                    ),
                ));


                $calendarId =$google_account->calendars->first()->google_id;
                $event = $service->events->insert($calendarId, $event);

                $myEvent->google_id = $event->id;
            }

            $myEvent->save();

            DB::commit();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = [];
            return response()->json($response);
        } catch(\Exception $e) {
            DB::rollBack();
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function deleteEvent($id)
    {
        DB::beginTransaction();
        try {
            $myEvent =  AddEvent::find($id);

            $user = \auth()->user();
            $google_account =\auth()->user()->googleAccounts()->first();
            if ($google_account)
            {
                $client = new \App\Services\Google();

                $token = [
                    'access_token' => $google_account->token['access_token'],
                    'expires_in' => $google_account->token['expires_in'],
                    'refresh_token' => $google_account->token['refresh_token']
                ];
                $google_client = $client->connectUsing($token)->getClient();

                if ($google_client->isAccessTokenExpired())
                {
                    $google_account->token = $google_client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                    $google_account->save();
                    $account =   $client->connectUsing($token['access_token']);

                }else{
                    $account =   $client->connectUsing($google_account->token['access_token']);

                }


                $service = $account->service('Calendar');
                $calendarId =$google_account->calendars->first()->google_id;
                $service->events->delete($calendarId, $myEvent->google_id);
            }

            $myEvent->delete();

            DB::commit();
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = [];
            return response()->json($response);
        } catch(\Exception $e) {
            DB::rollBack();

            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            $response['data'] = [];
            return response()->json($response);
        };
    }
}
