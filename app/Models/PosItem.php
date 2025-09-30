<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosItem extends Model
{
    protected $fillable = [
        'pos_sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'current_position_type',
        'current_position_id'
    ];

    // Relationships
    public function pos()
    {
        return $this->belongsTo(\App\Models\Pos::class, 'pos_sale_id');
    }
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'current_position_id');
    }

    public function technician()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'current_position_id');
    }
}
