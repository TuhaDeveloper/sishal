<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'product_id',
        'quantity',
        'type',
        'status',
        'requested_by',
        'approved_by',
        'shipped_by',
        'delivered_by',
        'requested_at',
        'approved_at',
        'shipped_at',
        'delivered_at',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function fromBranch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'from_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'from_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'to_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'to_id');
    }

    public function requestedPerson()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approvedPerson()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
