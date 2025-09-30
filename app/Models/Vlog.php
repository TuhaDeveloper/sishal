<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vlog extends Model
{
    protected $fillable = [
        'frame_code',
        'is_featured',
        'is_active',
    ];
}
