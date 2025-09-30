<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'current_position_type',
        'current_position_id',
    ];

    public function order()
    {
        return $this->belongsTo(\App\Models\Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'current_position_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'current_position_id');
    }

    public function technician()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'current_position_id');
    }
}
