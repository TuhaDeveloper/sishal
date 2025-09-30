<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalPage extends Model
{
     protected $fillable = [
        'title',
        'slug',
        'content',
        'is_active',
        'positioned_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
