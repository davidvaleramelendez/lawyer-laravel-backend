<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'UserId',
        'title',
        'Assign',
        'due_date',
        'tag',
        'description',
        'complete'
    ];

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'UserId');
    }

    public function assign()
    {
        return $this->hasOne('App\Models\User', 'id', 'Assign');
    }
}
