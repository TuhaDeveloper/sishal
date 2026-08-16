<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $fillable = [
        'supplier_id',
        'purchase_bill_id',
        'payment_type',
        'payment_date',
        'amount',
        'payment_method',
        'account_id',
        'reference',
        'note',
        'created_by',
    ];

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bill()
    {
        return $this->belongsTo(PurchaseBill::class, 'purchase_bill_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledger()
    {
        return $this->morphOne(SupplierLedger::class, 'transactionable');
    }
}
