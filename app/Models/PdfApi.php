<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfApi extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
    ];

    public $timestamps = true;
}
