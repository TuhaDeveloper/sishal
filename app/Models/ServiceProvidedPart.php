<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvidedPart extends Model
{
    protected $fillable = [
        'service_id',
        'product_type',
        'product_id',
        'qty',
        'price',
        'current_position_type',
        'current_position_id'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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
