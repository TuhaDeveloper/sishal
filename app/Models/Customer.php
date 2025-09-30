<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'tax_number',
        'address_1',
        'address_2',
        'city',
        'state',
        'country',
        'zip_code',
        'is_active',
        'created_by',
        'is_premium',
        'notes'
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'created_by', 'user_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function posSales()
    {
        return $this->hasMany(Pos::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
