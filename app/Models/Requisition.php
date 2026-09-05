<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requisition extends Model
{
    protected $fillable = [
        'requisition_number',
        'branch_id',
        'warehouse_id',
        'requisition_date',
        'status',
        'notes',
        'created_by',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Branch::class, 'warehouse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function transfers()
    {
        return $this->hasManyThrough(StockTransfer::class, RequisitionItem::class, 'requisition_id', 'requisition_item_id');
    }

    public function getDisplayStatusAttribute()
    {
        if ($this->status === 'pending' || $this->status === 'rejected') {
            return $this->status;
        }

        $transfers = $this->relationLoaded('transfers') ? $this->transfers : $this->transfers()->get();

        if ($transfers->isEmpty()) {
            return $this->status;
        }

        $allDelivered = $transfers->every(fn($t) => $t->status === 'delivered');
        $hasInTransit = $transfers->contains(fn($t) => in_array($t->status, ['approved', 'pending']));

        if ($this->status === 'fulfilled') {
            return $allDelivered ? 'fulfilled' : 'dispatched';
        }

        if ($this->status === 'partially_fulfilled') {
            return $hasInTransit ? 'partially_dispatched' : 'partially_fulfilled';
        }

        return $this->status;
    }
}
