<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    public function steps()
    {
        return $this->hasMany(FormBuilder::class, 'form_id', 'id');
    }
}
