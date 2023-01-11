<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InquiryImap extends Model
{
    use HasFactory;

    protected $fillable = [
        'imap_host',
        'imap_email',
        'imap_password',
        'imap_port',
        'imap_ssl',
    ];

    protected $casts = [
        'imap_ssl' => 'boolean',
    ];
}
