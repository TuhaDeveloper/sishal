<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $fillable = ['source_type', 'source_id', 'balance', 'description', 'reference'];

    public function user()
    {
        return $this->belongsTo(User::class, 'source_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'source_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'source_id');
    }
}
