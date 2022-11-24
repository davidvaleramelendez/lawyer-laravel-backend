<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateAttachment extends Model
{
    use HasFactory;

    public function EmailTemplate()
    {
        return $this->hasMany(EmailTemplate::class, 'id', 'email_template_id');
    }
}
