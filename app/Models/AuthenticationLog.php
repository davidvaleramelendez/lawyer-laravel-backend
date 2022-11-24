<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthenticationLog extends Model
{
    protected $table = 'authentication_log';

    use HasFactory;

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'authenticatable_id');
    }
}
