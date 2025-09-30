<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = [
        'customer_id',
        'pos_sale_id',
        'invoice_id',
        'return_date',
        'status',
        'refund_type',
        'reason',
        'processed_by',
        'processed_at',
        'account_id',
        'return_to_type',
        'return_to_id',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }
    public function posSale()
    {
        return $this->belongsTo(\App\Models\Pos::class, 'pos_sale_id');
    }
    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\SaleReturnItem::class);
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'return_to_id');
    }
}
