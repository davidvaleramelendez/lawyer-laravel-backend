<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlacetelAcceptedNotification extends Model
{
    use HasFactory;

    protected $with = ['user'];
    
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
