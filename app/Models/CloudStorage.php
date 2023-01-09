<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CloudStorage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cloud_storage';
    protected $casts = [
        'user_id' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(Self::class, 'parent_id');
    }

    public function children()
    {
        $children = $this->hasMany(CloudStorage::class, 'parent_id')->with('children');
        $children->getQuery()->select('*', 'id as value', 'name as label')->where('type', 'folder');
        return $children;
    }
}
