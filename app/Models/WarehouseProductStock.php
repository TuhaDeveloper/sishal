<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseProductStock extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'updated_by',
        'last_updated_at'
    ];

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}
