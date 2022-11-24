<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'title',
        'business',
        'start_date',
        'end_date',
        'allDay',
        'event_url',
        'guest',
        'location',
        'description',
        'google_id'
    ];
        
}
