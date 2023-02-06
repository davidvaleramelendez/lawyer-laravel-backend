<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlacetelSipUserId extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sipuid',
        'response',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public $timestamps = true;
    protected $with = ['user'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
