<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountSubType extends Model
{
    protected $fillable = [
        'name',
        'type_id',
        'created_by'
    ];

    public function type()
    {
        return $this->belongsTo(ChartOfAccountType::class, 'type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parents()
    {
        return $this->hasMany(ChartOfAccountParent::class, 'sub_type_id');
    }

    public function accounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'sub_type_id');
    }
}
