<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DropboxApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'secret',
        'token',
        'access_type',
    ];

    public $timestamps = true;
}
