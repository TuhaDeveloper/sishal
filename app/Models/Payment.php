<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'payment_for',
        'pos_id',
        'invoice_id',
        'payment_date',
        'amount',
        'account_id',
        'payment_method',
        'reference',
        'note',
    ];

    // Relationships
    public function pos()
    {
        return $this->belongsTo(\App\Models\Pos::class, 'pos_id');
    }
    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id');
    }
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }
}
