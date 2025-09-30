<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'employee_id',
        'name',
        'email',
        'phone',
        'subtotal',
        'vat',
        'discount',
        'delivery',
        'total',
        'estimated_delivery_date',
        'estimated_delivery_time',
        'status',
        'payment_method',
        'invoice_id',
        'notes',
        'created_by'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
