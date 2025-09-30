<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'purchase_id',
        'supplier_id',
        'bill_id',
        'return_date',
        'return_type',
        'status',
        'reason',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'return_date' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the purchase that this return belongs to
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * Get the supplier that this return is for
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the bill associated with this return (if any)
     */
    public function bill()
    {
        return $this->belongsTo(PurchaseBill::class, 'bill_id');
    }

    /**
     * Get the user who created this return
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this return (if any)
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all items in this purchase return
     */
    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id');
    }

    /**
     * Get the total amount of this purchase return
     */
    public function getTotalAmountAttribute()
    {
        return $this->items->sum('total_price');
    }

    /**
     * Get the total quantity of items returned
     */
    public function getTotalQuantityAttribute()
    {
        return $this->items->sum('returned_qty');
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by return type
     */
    public function scopeByReturnType($query, $returnType)
    {
        return $query->where('return_type', $returnType);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('return_date', [$startDate, $endDate]);
    }
}
