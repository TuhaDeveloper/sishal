<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'supplier_id',
        'ship_location_type',
        'location_id',
        'purchase_date',
        'status',
        'created_by',
        'notes',
        'bill_id',
        'is_billed',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class,'purchase_id');
    }

    public function bill()
    {
        return $this->hasOne(PurchaseBill::class, 'purchase_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
