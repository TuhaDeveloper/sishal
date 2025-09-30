<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountType extends Model
{
    protected $fillable = [
        'name',
        'created_by'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subTypes()
    {
        return $this->hasMany(ChartOfAccountSubType::class, 'type_id');
    }

    public function parents()
    {
        return $this->hasMany(ChartOfAccountParent::class, 'type_id');
    }

    public function accounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'type_id');
    }
}
