<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceRecording extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'name',
    //     'first_name',
    //     'last_name',
    //     'email',
    //     'password',
    //     'role_id',
    // ];

    protected $with = ['user', 'cases'];

    protected $casts = [
        'isErledigt' => 'integer',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function cases()
    {
        return $this->hasOne(Cases::class, 'CaseID', 'case_id');
    }
}
