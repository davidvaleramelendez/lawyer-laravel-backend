<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasesType extends Model
{
    use HasFactory;


       /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'case_type';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'CaseTypeID';
    protected $guarded = [];
    public $timestamps = false;
     protected $hidden = [
        'CreatedAt'
    ];

   

}
