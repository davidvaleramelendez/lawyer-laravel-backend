<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\AddEvent;
use App\Models\User;
use Carbon\Carbon;
use Google_Service_Calendar_Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddEventController extends Controller
{
    public function getUsers(Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->user()->id;

            if ($userId != auth()->user()->id) {
                if (!Helper::get_user_permissions(14)) {
                    $response = array();
                    $response['flag'] = false;
                    $response['message'] = "";
                    $response['data'] = [];
                    return response()->json($response);
                }
            }

            $users = User::whereNotIn('role_id', [12])
                ->whereNot('id', $userId)
                ->get();

            $filterUsers = User::whereNotIn('role_id', [11])
                ->whereNot('id', $userId)
                ->get();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = ["users" => $users, "filterUsers" => $filterUsers];
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function getEventFilter($userId, $search, $filter)
    {
        $events = AddEvent::select(
            [
                'id as id',
                'event_url as event_url',
                'title as title',
                'start_date as start',
                'end_date as end',
                'allDay as allDay',
                'business as calendar',
                'guest as guest',
                'location as location',
                'description as description',
            ]
        );

        if ($userId) {
            // if (auth()->user()->role_id != 10) {
            // $events = $events->whereJsonContains('guest', [$userId]);
            $events = $events->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereJsonContains('guest', [$userId]);
            });
            // }
        }

        if ($filter) {
            $events = $events->whereIn('business', json_decode($filter));
        }

        if ($search) {
            $events = $events->where(function ($query) use ($search) {
                $query->Where('title', 'LIKE', "%{$search}%")
                    ->orWhere('business', 'LIKE', "%{$search}%")
                    ->orWhere('event_url', 'LIKE', "%{$search}%")
                    ->orWhere('location', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $events = $events->get();
        $eventData = [];

        if ($events && count($events) > 0) {
            foreach ($events as $event) {
                $data = [];
                $data['id'] = $event->id;
                $data['title'] = $event->title;
                $data['business'] = $event->calendar;
                $data['start'] = Carbon::parse($event->start)->format('Y-m-d H:i');
                $data['end'] = Carbon::parse($event->end)->format('Y-m-d H:i');
                $data['allDay'] = $event->allDay == 1 ? true : false;
                $data['start_date'] = $event->start;
                $data['end_date'] = $event->end;
                $data['location'] = $event->location;
                $data['guest'] = $event->guest;
                $data['description'] = $event->description;
                $user = [];

                if ($event->guest) {
                    $guestIds = json_decode($event->guest);
                    $user = User::whereIn('id', $guestIds)->get();
                }

                $data['extendedProps'] = ['calendar' => $event->calendar,
                    'allDay' => $event->allDay == 1 ? true : false,
                    'description' => $event->description,
                    'guest' => $event->guest,
                    'location' => $event->location,
                    'user' => $user,
                ];
                $data['user'] = $user;
                $data['event_url'] = $event->event_url;
                array_push($eventData, $data);
            }
        }

        return ['data' => $eventData ?? []];
    }

    public function getEvents(Request $request)
    {
        try {
            $search = $request->search ?? '';
            $filter = $request->filter ?? '';
            $userId = $request->user_id ? (int) $request->user_id : auth()->user()->id;

            if ($userId != auth()->user()->id) {
                if (!Helper::get_user_permissions(14)) {
                    $response = array();
                    $response['flag'] = false;
                    $response['message'] = "You do not have permission.";
                    $response['data'] = [];
                    return response()->json($response);
                }
            }

            $events = $this->getEventFilter($userId, $search, $filter);
            $eventData = $events['data'] ?? [];

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $eventData;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function addEvent(Request $request)
    {

        DB::beginTransaction();
        try {
            $validation = \Validator::make($request->all(), [
                'title' => 'required',
                'business' => 'required',
                'startdate' => 'required',
                'enddate' => 'required',
                'guest' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = [];
                $error = $validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }

            $myEvent = AddEvent::create([
                'title' => $request->title,
                'business' => $request->business,
                'start_date' => $request->startdate,
                'end_date' => $request->enddate,
                'allDay' => $request->allDay,
                'event_url' => $request->event_url,
                'user_id' => $request->user_id ?? auth()->user()->id,
                'guest' => json_encode($request->guest),
                'location' => $request->location,
                'description' => $request->description,
            ]);

            $user = \auth()->user();
            $google_account = \auth()->user()->googleAccounts()->first();

            if ($google_account) {
                $client = new \App\Services\Google();
                $token = [
                    'access_token' => $google_account->token['access_token'],
                    'expires_in' => $google_account->token['expires_in'],
                    'refresh_token' => $google_account->token['refresh_token'],
                ];
                $google_client = $client->connectUsing($token)->getClient();

                if ($google_client->isAccessTokenExpired()) {
                    $google_account->token = $google_client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                    $google_account->save();
                    $account = $client->connectUsing($token['access_token']);
                } else {
                    $account = $client->connectUsing($google_account->token['access_token']);
                }

                $userAttendees = [];
                if ($request->has('guest')) {
                    $attendees = User::whereIn('id', $request->guest)->pluck('email')->toArray();

                    foreach ($attendees as $attendee) {
                        $userAttendees[] = ['email' => $attendee];
                    }
                }

                $startDate = array(
                    'dateTime' => Carbon::parse($request->startdate)->toRfc3339String(),
                    'timeZone' => 'Europe/Zurich',
                );

                $endDate = array(
                    'dateTime' => Carbon::parse($request->enddate)->toRfc3339String(),
                    'timeZone' => 'Europe/Zurich',
                );

                $service = $account->service('Calendar');
                $event = new Google_Service_Calendar_Event(array(
                    'summary' => $request->title,
                    'location' => $request->location,
                    'description' => $request->description,
                    'start' => $startDate,
                    'sendNotifications' => true,
                    'sendUpdates' => 'all',
                    'end' => $endDate,
                    'attendees' => $userAttendees,
                    'reminders' => array(
                        'useDefault' => false,
                        'overrides' => array(
                            array('method' => 'email', 'minutes' => 24 * 60),
                            array('method' => 'popup', 'minutes' => 10),
                        ),
                    ),
                ));

                $calendarId = $google_account->calendars->first()->google_id;
                $event = $service->events->insert($calendarId, $event);
                $myEvent->google_id = $event->id;
                $myEvent->save();
            }

            DB::commit();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $myEvent;
            return response()->json($response);
        } catch (\Exception$e) {
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = [];
            return response()->json($response);
        }
    }

    public function updateEvent(Request $request)
    {
        DB::beginTransaction();
        try {
            $validation = \Validator::make($request->all(), [
                'id' => 'required',
                'title' => 'required',
                'business' => 'required',
                'startdate' => 'required',
                'enddate' => 'required',
                'guest' => 'required',
            ]);

            if ($validation->fails()) {
                $response = array();
                $response['flag'] = false;
                $response['message'] = "Failed";
                $response['data'] = [];
                $error = $validation->errors();
                $response['error'] = $error;
                return response()->json($response);
            }

            $myEvent = AddEvent::find($request->id);
            $eventUpdate = [
                'title' => $request->title,
                'business' => $request->business,
                'start_date' => $request->startdate,
                'end_date' => $request->enddate,
                'user_id' => $request->user_id ?? auth()->user()->id,
                'guest' => json_encode($request->guest),
            ];

            if (isset($request->location) || $request->location == '') {
                $eventUpdate['location'] = $request->location;
            }

            if (isset($request->allDay)) {
                $eventUpdate['allDay'] = $request->allDay;
            }

            if (isset($request->event_url) || $request->event_url == '') {
                $eventUpdate['event_url'] = $request->event_url;
            }

            if (isset($request->description) || $request->description == '') {
                $eventUpdate['description'] = $request->description;
            }

            $myEvent->update($eventUpdate);

            $user = \auth()->user();
            $google_account = \auth()->user()->googleAccounts()->first();

            if ($google_account) {
                $client = new \App\Services\Google();
                $token = [
                    'access_token' => $google_account->token['access_token'],
                    'expires_in' => $google_account->token['expires_in'],
                    'refresh_token' => $google_account->token['refresh_token'],
                ];
                $google_client = $client->connectUsing($token)->getClient();

                if ($google_client->isAccessTokenExpired()) {
                    $google_account->token = $google_client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                    $google_account->save();
                    $account = $client->connectUsing($token['access_token']);
                } else {
                    $account = $client->connectUsing($google_account->token['access_token']);
                }

                $userAttendees = [];
                if ($request->has('guest')) {
                    $attendees = User::whereIn('id', $request->guest)->pluck('email')->toArray();

                    foreach ($attendees as $attendee) {
                        $userAttendees[] = ['email' => $attendee];
                    }
                }

                $startDate = array(
                    'dateTime' => Carbon::parse($request->startdate)->toRfc3339String(),
                    'timeZone' => 'Europe/Zurich',
                );

                $endDate = array(
                    'dateTime' => Carbon::parse($request->enddate)->toRfc3339String(),
                    'timeZone' => 'Europe/Zurich',
                );

                $service = $account->service('Calendar');

                $event = new Google_Service_Calendar_Event(array(
                    'summary' => $request->title,
                    'location' => $request->location,
                    'description' => $request->description,
                    'start' => $startDate,
                    'sendNotifications' => true,
                    'sendUpdates' => 'all',
                    'end' => $endDate,
                    'attendees' => $userAttendees,
                    'reminders' => array(
                        'useDefault' => false,
                        'overrides' => array(
                            array('method' => 'email', 'minutes' => 24 * 60),
                            array('method' => 'popup', 'minutes' => 10),
                        ),
                    ),
                ));

                $calendarId = $google_account->calendars->first()->google_id;
                $event = $service->events->update($calendarId, $myEvent->google_id, $event);
                $myEvent->google_id = $event->id;
                $myEvent->save();
            }

            DB::commit();

            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = $myEvent;
            return response()->json($response);
        } catch (\Exception$e) {
            DB::rollBack();
            $response = array();
            $response['flag'] = false;
            $response['message'] = $e->getMessage();
            $response['data'] = null;
            return response()->json($response);
        }
    }

    public function deleteEvent($id)
    {
        DB::beginTransaction();
        try {
            $myEvent = AddEvent::find($id);

            $user = \auth()->user();
            $google_account = \auth()->user()->googleAccounts()->first();
            if ($google_account) {
                $client = new \App\Services\Google();

                $token = [
                    'access_token' => $google_account->token['access_token'],
                    'expires_in' => $google_account->token['expires_in'],
                    'refresh_token' => $google_account->token['refresh_token'],
                ];
                $google_client = $client->connectUsing($token)->getClient();

                if ($google_client->isAccessTokenExpired()) {
                    $google_account->token = $google_client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                    $google_account->save();
                    $account = $client->connectUsing($token['access_token']);
                } else {
                    $account = $client->connectUsing($google_account->token['access_token']);
                }

                $service = $account->service('Calendar');
                $calendarId = $google_account->calendars->first()->google_id;
                $service->events->delete($calendarId, $myEvent->google_id);
            }

            $myEvent->delete();

            DB::commit();
            $response = array();
            $response['flag'] = true;
            $response['message'] = "Success.";
            $response['data'] = [];
            return response()->json($response);
        } catch (\Exception$e) {
            DB::rollBack();
            $response = array();
            $response['flag'] = false;
            $response['message'] = "Failed.";
            $response['data'] = null;
            return response()->json($response);
        };
    }
}
