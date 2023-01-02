<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'is_read' => 'boolean',
        'is_delete' => 'boolean',
        'is_trash' => 'boolean',
        'important' => 'boolean',
    ];

    public function sender()
    {
        return $this->hasOne('App\Models\User', 'id', 'from_id');
    }

    public function receiver()
    {
        return $this->hasOne('App\Models\User', 'id', 'to_id');
    }

    public function attachment()
    {
        return $this->hasMany('App\Models\Attachment', 'reference_id', 'id');
    }
}
