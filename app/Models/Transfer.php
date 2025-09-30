<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'from_financial_account_id',
        'to_financial_account_id',
        'chart_of_account_id',
        'amount',
        'transfer_date',
        'reference',
        'memo',
        'journal_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'date',
    ];

    public function fromFinancialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'from_financial_account_id');
    }

    public function toFinancialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'to_financial_account_id');
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
