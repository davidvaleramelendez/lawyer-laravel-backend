<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;

class CustomNotification extends DatabaseNotification
{
  
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    public function receiver()
    {
        return $this->belongsTo(User::class,'notifiable_id','id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class,'sender_id','id');
    }

    public function attachment()
    {
        return $this->hasMany('App\Models\Attachment', 'reference_id', 'id');
    }
}
