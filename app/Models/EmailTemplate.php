<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    public function EmailTemplateAttachment()
    {
        return $this->hasMany(EmailTemplateAttachment::class);
    }
}
