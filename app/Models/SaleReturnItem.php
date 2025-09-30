<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'product_id',
        'returned_qty',
        'unit_price',
        'total_price',
        'reason'
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}
