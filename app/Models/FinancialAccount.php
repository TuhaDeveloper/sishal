<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialAccount extends Model
{
    const TYPE_BANK = 'bank';
    const TYPE_MOBILE = 'mobile';
    const TYPE_CASH = 'cash';

    protected $fillable = [
        'branch_id',
        'account_id',
        'type',
        'provider_name',
        'account_number',
        'account_holder_name',
        'currency',
        'branch_name',
        'swift_code',
        'mobile_number',
        'balance'
    ];

    /**
     * Get all available account types.
     */
    public static function getTypes()
    {
        return [
            self::TYPE_CASH => 'Cash',
            self::TYPE_BANK => 'Bank Account',
            self::TYPE_MOBILE => 'Mobile Banking',
        ];
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'financial_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function scopeWithCurrentBalance($query)
    {
        return $query->withSum(['journalEntries as total_debit' => function($q) {
            $q->select(\Illuminate\Support\Facades\DB::raw("COALESCE(SUM(debit), 0)"));
        }], 'debit')->withSum(['journalEntries as total_credit' => function($q) {
            $q->select(\Illuminate\Support\Facades\DB::raw("COALESCE(SUM(credit), 0)"));
        }], 'credit');
    }

    public function getCurrentBalanceAttribute()
    {
        if (isset($this->attributes['calculated_balance'])) {
            return (float) $this->attributes['calculated_balance'];
        }

        if (array_key_exists('total_debit', $this->attributes) && array_key_exists('total_credit', $this->attributes)) {
            return (float) (($this->total_debit ?? 0) - ($this->total_credit ?? 0));
        }

        if ($this->relationLoaded('journalEntries')) {
            return (float) ($this->journalEntries->sum('debit') - $this->journalEntries->sum('credit'));
        }

        $movement = JournalEntry::where('financial_account_id', $this->id)
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->first();

        return (float) ($movement->balance ?? 0);
    }
}
