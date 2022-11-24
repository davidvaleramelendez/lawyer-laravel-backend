<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'ContactID';
    protected $guarded = [];
    public $timestamps = false;
     protected $fillable = [
         'ContactID',
         'Name',
         'Email',
        'Subject',
        'PhoneNo',
        'IsCase',
        'CreatedAt'
    ];

    public function case()
    {
        return $this->hasOne('App\Models\Cases', 'ContactID', 'ContactID');
    }

 
    
}