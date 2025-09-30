<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccountParent extends Model
{
    protected $fillable = [
        'name',
        'type_id',
        'sub_type_id',
        'code',
        'description',
        'created_by'
    ];

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

    public function accounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }
}
