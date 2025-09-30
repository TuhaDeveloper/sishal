<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'location',
        'manager_id',
        'branch_id',
        'status'
    ];

    public function manager()
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }

    public function warehouseProductStocks()
    {
        return $this->hasMany(\App\Models\WarehouseProductStock::class);
    }

    // Employees are accessed through the branch relationship
    // Use $warehouse->branch->employees to get employees
}
