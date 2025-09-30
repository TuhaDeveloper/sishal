<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseBill extends Model
{
    protected $fillable = [
        'bill_number',
        'supplier_id',
        'purchase_id',
        'bill_date',
        'total_amount',
        'paid_amount',
        'due_amount',
        'status',
        'created_by',
        'description',
    ];

    public function items()
    {
        return $this->hasMany(BillItem::class,'bill_id');
    }

    public function payments()
    {
        return $this->hasMany(PurchaseBillPayment::class,'bill_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Supplier::class,'supplier_id');
    }
}
