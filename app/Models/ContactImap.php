<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactImap extends Model
{
    use HasFactory;

    protected $table = 'contact_imap';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
