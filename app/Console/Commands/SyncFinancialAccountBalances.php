<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;

class SyncFinancialAccountBalances extends Command
{
    protected $signature = 'accounts:sync-balances';
    protected $description = 'Sync financial_accounts table balance column with actual journal ledger totals';

    public function handle()
    {
        $this->info('Starting financial accounts balance synchronization with journal ledger...');

        $accounts = FinancialAccount::all();
        $updated = 0;

        foreach ($accounts as $account) {
            $ledgerBalance = JournalEntry::where('financial_account_id', $account->id)
                ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as net')
                ->value('net') ?? 0;

            $oldBalance = (float) $account->balance;
            $newBalance = (float) $ledgerBalance;

            $account->balance = $newBalance;
            $account->save();

            if (abs($oldBalance - $newBalance) > 0.001) {
                $this->info(sprintf(
                    "Account #%d (%s): ৳%s → ৳%s",
                    $account->id,
                    $account->provider_name,
                    number_format($oldBalance, 2),
                    number_format($newBalance, 2)
                ));
                $updated++;
            }
        }

        $this->info("Financial accounts balance sync complete! Synchronized {$updated} out of " . $accounts->count() . " accounts.");
        return 0;
    }
}
