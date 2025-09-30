<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'parent_id',
        'type_id',
        'sub_type_id',
        'name',
        'code',
        'description',
        'is_cash_account',
        'created_by'
    ];

    public function parent()
    {
        return $this->belongsTo(ChartOfAccountParent::class, 'parent_id');
    }

    public function type()
    {
        return $this->belongsTo(ChartOfAccountType::class, 'type_id');
    }

    public function subType()
    {
        return $this->belongsTo(ChartOfAccountSubType::class, 'sub_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
