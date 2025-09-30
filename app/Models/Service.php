<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'service_number',
        'user_id',
        'product_service_id',
        'service_type',
        'requested_date',
        'preferred_time',
        'status',
        'technician_id',
        'service_notes',
        'admin_notes',
        'service_fee',
        'travel_fee',
        'discount',
        'total',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id');
    }

    public function productService()
    {
        return $this->belongsTo(Product::class, 'product_service_id');
    }

    public function serviceProvidedParts()
    {
        return $this->hasMany(ServiceProvidedPart::class, 'service_id');
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class, 'pos_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(Product::class, 'product_service_id');
    }
}
