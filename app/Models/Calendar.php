<?php

namespace App\Models;

use App\Concerns\Synchronizable;
use App\Jobs\SynchronizeGoogleEvents;
use App\Jobs\WatchGoogleEvents;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use Synchronizable;

    protected $fillable = [
        'google_id', 'name', 'color', 'timezone',
    ];

    public function googleAccount()
    {
        return $this->belongsTo(GoogleAccount::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function synchronize()
    {
        SynchronizeGoogleEvents::dispatch($this);
    }

    public function watch()
    {
        WatchGoogleEvents::dispatch($this);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($calendar) {
            SynchronizeGoogleEvents::dispatch($calendar);
        });
    }

}
