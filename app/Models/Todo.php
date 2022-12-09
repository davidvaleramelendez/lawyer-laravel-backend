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
        'is_important',
        'is_completed',
        'is_deleted',
    ];

    protected $casts = [
        'is_important' => 'boolean',
        'is_completed' => 'boolean',
        'is_deleted' => 'boolean',
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
