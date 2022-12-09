<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Letters extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'letters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;
    protected $hidden = [
        'CreatedAt',
    ];

    protected $casts = [
        'is_print' => 'integer',
        'deleted' => 'integer',
        'is_archived' => 'integer',
        'isErledigt' => 'integer',
    ];

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function cases()
    {
        return $this->hasOne('App\Models\Cases', 'CaseID', 'case_id');
    }
}
