<?php

namespace App\Jobs;

use App\Jobs\SynchronizeGoogleResource;
use App\Models\AddEvent;
use Google\Service\Dfareporting\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SynchronizeGoogleEvents extends SynchronizeGoogleResource implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function getGoogleRequest($service, $options)
    {
//
//        dd($service->events->listEvents(
//            'c_jatjf5fc6u4srbo01mp1abukgo@group.calendar.google.com', $options
//        ));
        return $service->events->listEvents(
            $this->synchronizable->google_id,
            $options
        );
    }

    public function syncItem($googleEvent)
    {
        if ($googleEvent->status === 'cancelled') {
            return $this->synchronizable->events()
                ->where('google_id', $googleEvent->id)
                ->delete();
        }

        AddEvent::updateOrCreate(
            [
                'google_id' => $googleEvent->id,
            ],
            [
                'title' => $googleEvent->summary ?? '(No title)',
                'description' => $googleEvent->description,
                'location' => $googleEvent->location,
                'allday' => $this->isAllDayEvent($googleEvent) ? 'on' : null,
                'start_date' => $this->parseDatetime($googleEvent->start),
                'end_date' => $this->parseDatetime($googleEvent->end),
                'created_at' => Carbon::now(),
                'business' => 'Business',
                'google_id' =>$googleEvent->id

            ]
        );
//        $this->synchronizable->events()->updateOrCreate(
//            [
//                'google_id' => $googleEvent->id,
//            ],
//            [
//                'name' => $googleEvent->summary ?? '(No title)',
//                'description' => $googleEvent->description,
//                'allday' => $this->isAllDayEvent($googleEvent),
//                'started_at' => $this->parseDatetime($googleEvent->start),
//                'ended_at' => $this->parseDatetime($googleEvent->end),
//            ]
//        );
    }

    public function dropAllSyncedItems()
    {
        $this->synchronizable->events()->delete();
    }

    protected function isAllDayEvent($googleEvent)
    {
        return ! $googleEvent->start->dateTime && ! $googleEvent->end->dateTime;
    }

    protected function parseDatetime($googleDatetime)
    {
        $rawDatetime = $googleDatetime->dateTime ?: $googleDatetime->date;

        return Carbon::parse($rawDatetime)->setTimezone('UTC');
    }
}
