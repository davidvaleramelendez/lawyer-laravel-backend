<?php

namespace App\Jobs;

use App\Jobs\SynchronizeGoogleResource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SynchronizeGoogleCalendars extends SynchronizeGoogleResource implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function getGoogleRequest($service, $options)
    {

//      return  $service->calendarList->get('c_jatjf5fc6u4srbo01mp1abukgo@group.calendar.google.com');
        return $service->calendarList->listCalendarList($options);
    }

    public function syncItem($googleCalendar)
    {
        $userGoogleCalendar = auth()->user()->googleAccounts()->first()->calendars()->first();
        if ($userGoogleCalendar) {
            if ($googleCalendar->id ==$userGoogleCalendar->google_id) {
                $this->synchronizable->calendars()->updateOrCreate(
                    [
                        'google_id' => $googleCalendar->id,
                    ],
                    [
                        'name' => $googleCalendar->summary,
                        'color' => $googleCalendar->backgroundColor,
                        'timezone' => $googleCalendar->timeZone,
                    ]
                );
            }
        } else {
            $user = \auth()->user();

            $google_account =\auth()->user()->googleAccounts()->first();
            $client = new \App\Services\Google();
            $token = [
                'access_token' => $google_account->token['access_token'],
                'expires_in' => $google_account->token['expires_in'],
                'refresh_token' => $google_account->token['refresh_token']
            ];
            $google_client = $client->connectUsing($token)->getClient();

            if ($google_client->isAccessTokenExpired()) {
                $google_account->token = $google_client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                $google_account->save();
                $account =   $client->connectUsing($token['access_token']);
            } else {
                $account =   $client->connectUsing($google_account->token['access_token']);
            }




            $service = $account->service('Calendar');

            $calendar = new \Google_Service_Calendar_Calendar();

            $calendar->setSummary('Anwalt');

            $createdCalendar = $service->calendars->insert($calendar);
            $newCalendar = $service->calendarList->get($createdCalendar->getId());
            $this->synchronizable->calendars()->updateOrCreate(
                [
                    'google_id' => $newCalendar->id,
                ],
                [
                    'name' => $newCalendar->summary,
                    'color' => $newCalendar->backgroundColor,
                    'timezone' => $newCalendar->timeZone,
                ]
            );
        }
        if ($googleCalendar->deleted) {
            return $this->synchronizable->calendars()
                ->where('google_id', $googleCalendar->id)
                ->get()->each->delete();
        }
    }

    public function dropAllSyncedItems()
    {
        // Here we use `each->delete()` to make sure model listeners are called.
        $this->synchronizable->calendars->each->delete();
    }
}
