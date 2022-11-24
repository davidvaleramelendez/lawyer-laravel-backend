<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class fighter_info extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'CaseID',
        'name',
        'last_name',
        'email',
        'telefone',
        'city',
        'zip_code',
        'country',
        'address',
    ];
}
