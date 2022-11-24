<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasesRecord extends Model
{
    use HasFactory;


       /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'case_records';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'RecordID';
    protected $guarded = [];
    public $timestamps = false;

    public function attachment()
    {
        return $this->hasMany('App\Models\Attachment', 'reference_id', 'RecordID');
    }

}
