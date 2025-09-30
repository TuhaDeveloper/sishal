<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchProductStock extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity',
        'updated_by',
        'last_updated_at'
    ];

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}
