<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded =[];
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class,'customer_id','id');
        # code...
    }


    public function created_user()
    {
        return $this->belongsTo(User::class,'user_id','id');
        # code...
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class,'invoice_id','id');

    }

    public function case()
    {
        return $this->belongsTO(Cases::class, 'CaseID', 'CaseID');
    }

    public function userData()
    {
        return $this->belongsTo(User::class,'user_id','id');
        # code...
    }

    public function customer()
    {
        return $this->belongsTo(User::class,'customer_id','id');
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class,'invoice_id','id');
    }
}
