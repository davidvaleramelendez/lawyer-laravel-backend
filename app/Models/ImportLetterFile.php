<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLetterFile extends Model
{
    use HasFactory;

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
