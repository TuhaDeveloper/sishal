<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProductStock extends Model
{
    protected $fillable = [
        'employee_id',
        'product_id',
        'quantity',
        'issued_by',
        'issued_at',
        'last_updated_at'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }
}
