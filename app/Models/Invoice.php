<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'template_id',
        'customer_id',
        'operated_by',
        'issue_date',
        'due_date',
        'send_date',
        'subtotal',
        'total_amount',
        'discount_apply',
        'paid_amount',
        'due_amount',
        'status',
        'note',
        'footer_text',
        'created_by',
    ];

    // Relationships
    public function pos()
    {
        return $this->hasOne(\App\Models\Pos::class, 'invoice_id');
    }
    public function service()
    {
        return $this->hasOne(\App\Models\Service::class, 'invoice_id');
    }
    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class, 'invoice_id');
    }
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }
    public function invoiceAddress()
    {
        return $this->hasOne(\App\Models\InvoiceAddress::class, 'invoice_id');
    }

    public function salesman()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\InvoiceItem::class, 'invoice_id');
    }
}
