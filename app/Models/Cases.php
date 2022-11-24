<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cases extends Model
{
    use HasFactory;


       /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cases';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'CaseID';
    protected $guarded = [];
    public $timestamps = false;

     public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'UserID');
    }

    public function contact()
    {
        return $this->hasOne('App\Models\Contact', 'ContactID', 'ContactID');
    }

    public function laywer()
    {
        return $this->hasOne('App\Models\User', 'id', 'LaywerID');
    }  
    
    public function type()
    {
        return $this->hasOne('App\Models\CasesType', 'CaseTypeID', 'CaseTypeID');
    }

}
