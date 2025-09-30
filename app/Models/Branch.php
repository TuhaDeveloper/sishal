<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'location',
        'contact_info',
        'status',
        'manager_id',
    ];

    public function products()
    {
        return $this->hasMany(\App\Models\Product::class);
    }

    public function employees()
    {
        return $this->hasMany(\App\Models\Employee::class);
    }

    public function warehouses()
    {
        return $this->hasMany(\App\Models\Warehouse::class);
    }

    public function manager()
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    public function branchProductStocks()
    {
        return $this->hasMany(\App\Models\BranchProductStock::class);
    }

    public function pos()
    {
        return $this->hasMany(\App\Models\Pos::class);
    }
}
