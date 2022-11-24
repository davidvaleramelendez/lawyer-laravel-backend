<?php

namespace App\Jobs;

use App\Jobs\WatchGoogleResource;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WatchGoogleCalendars extends WatchGoogleResource implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function getGoogleRequest($service, $channel)
    {
        return $service->calendarList->watch($channel);
    }
}
