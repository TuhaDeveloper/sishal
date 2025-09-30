<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductServiceCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'image', 'status', 'parent_id'
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
