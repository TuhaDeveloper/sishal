<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $fillable = [
        'voucher_no',
        'type',
        'entry_date',
        'description',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'entry_date' => 'date'
    ];

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTotalDebitAttribute()
    {
        return $this->entries->sum('debit');
    }

    public function getTotalCreditAttribute()
    {
        return $this->entries->sum('credit');
    }

    public function isBalanced()
    {
        return $this->total_debit == $this->total_credit;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($journal) {
            if (empty($journal->voucher_no)) {
                $journal->voucher_no = self::generateVoucherNo();
            }
        });
    }

    public static function generateVoucherNo()
    {
        $lastJournal = self::whereYear('created_at', date('Y'))->latest()->first();
        $sequence = $lastJournal ? intval(substr($lastJournal->voucher_no, -3)) + 1 : 1;
        return 'JV-' . date('Y') . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}
