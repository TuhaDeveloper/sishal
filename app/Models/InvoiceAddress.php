<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceAddress extends Model
{
    protected $fillable = [
        'invoice_id',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_zip_code',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_zip_code',
    ];
}
