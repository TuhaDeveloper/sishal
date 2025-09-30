<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\Transfer;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    public function list()
    {
        $transfers = Transfer::with(['fromFinancialAccount.account', 'toFinancialAccount.account', 'journal'])
            ->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $financialAccounts = FinancialAccount::with('account')->get();
        
        return view('erp.transfer.list', compact('transfers', 'financialAccounts'));
    }

    /**
     * Get existing journals for a specific date
     */
    public function getExistingJournals(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $journals = Journal::where('entry_date', $request->date)
            ->with(['entries.chartOfAccount', 'entries.financialAccount'])
            ->get();

        return response()->json([
            'journals' => $journals,
            'count' => $journals->count()
        ]);
    }

    /**
     * Store transfer with option to use existing journal
     */
    public function storeWithJournal(Request $request)
    {
        $request->validate([
            'from_financial_account_id' => 'required|exists:financial_accounts,id',
            'to_financial_account_id' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric|min:0',
            'transfer_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:255',
            'use_existing_journal' => 'nullable|boolean',
            'existing_journal_id' => 'nullable|exists:journals,id',
        ]);

        // Ensure from and to accounts are different
        if ($request->from_financial_account_id == $request->to_financial_account_id) {
            return back()->withErrors(['error' => 'From and To accounts must be different.']);
        }

        try {
            DB::beginTransaction();

            // Get financial accounts with their chart accounts
            $fromAccount = FinancialAccount::with('account')->find($request->from_financial_account_id);
            $toAccount = FinancialAccount::with('account')->find($request->to_financial_account_id);

            $journal = null;

            if ($request->use_existing_journal && $request->existing_journal_id) {
                // Use existing journal
                $journal = Journal::findOrFail($request->existing_journal_id);
                
                // Update journal description to include this transfer
                $existingDescription = $journal->description;
                $newDescription = 'Transfer from ' . $fromAccount->provider_name . 
                                 ' to ' . $toAccount->provider_name;
                
                if (!str_contains($existingDescription, $newDescription)) {
                    $journal->update([
                        'description' => $existingDescription . '; ' . $newDescription,
                        'updated_by' => Auth::user()->id,
                    ]);
                }
            } else {
                // Create new journal
                $journal = Journal::create([
                    'entry_date' => $request->transfer_date,
                    'description' => 'Transfer from ' . $fromAccount->provider_name . 
                                    ' to ' . $toAccount->provider_name,
                    'type' => 'Contra',
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                ]);
            }

            // Create journal entries for the transfer
            // Debit the "to" account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'chart_of_account_id' => $toAccount->account_id,
                'financial_account_id' => $request->to_financial_account_id,
                'debit' => $request->amount,
                'credit' => 0,
                'memo' => $request->memo ?? 'Transfer from ' . $fromAccount->provider_name,
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]);

            // Credit the "from" account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'chart_of_account_id' => $fromAccount->account_id,
                'financial_account_id' => $request->from_financial_account_id,
                'debit' => 0,
                'credit' => $request->amount,
                'memo' => $request->memo ?? 'Transfer to ' . $toAccount->provider_name,
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]);

            // Create the transfer record
            $transfer = Transfer::create([
                'from_financial_account_id' => $request->from_financial_account_id,
                'to_financial_account_id' => $request->to_financial_account_id,
                'chart_of_account_id' => $fromAccount->account_id,
                'amount' => $request->amount,
                'transfer_date' => $request->transfer_date,
                'reference' => $request->reference,
                'memo' => $request->memo,
                'journal_id' => $journal->id,
            ]);

            // Update financial account balances
            $fromAccount->balance -= $transfer->amount;
            $toAccount->balance += $transfer->amount;

            $fromAccount->save();
            $toAccount->save();

            DB::commit();

            $message = $request->use_existing_journal 
                ? 'Transfer added to existing journal successfully' 
                : 'Transfer created successfully with new journal entry';

            return redirect()->route('transfer.list')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Transfer creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create transfer: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_financial_account_id' => 'required|exists:financial_accounts,id',
            'to_financial_account_id' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric|min:0',
            'transfer_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:255',
        ]);

        // Ensure from and to accounts are different
        if ($request->from_financial_account_id == $request->to_financial_account_id) {
            return back()->withErrors(['error' => 'From and To accounts must be different.']);
        }

        try {
            DB::beginTransaction();

            // Get financial accounts with their chart accounts
            $fromAccount = FinancialAccount::with('account')->find($request->from_financial_account_id);
            $toAccount = FinancialAccount::with('account')->find($request->to_financial_account_id);

            // Check if there's already a journal for this date
            $existingJournal = Journal::where('entry_date', $request->transfer_date)
                ->where('type', 'Contra')
                ->first();

            $journal = null;
            
            if ($existingJournal) {
                // Option 1: Add to existing journal (if it's a contra journal)
                $journal = $existingJournal;
                
                // You might want to add some business logic here to decide
                // whether to add to existing journal or create new one
                // For example, if the existing journal has too many entries, create a new one
                if ($existingJournal->entries()->count() >= 10) {
                    // Create a new journal if existing one has too many entries
                    $journal = Journal::create([
                        'entry_date' => $request->transfer_date,
                        'description' => 'Transfer from ' . $fromAccount->provider_name . 
                                        ' to ' . $toAccount->provider_name,
                        'type' => 'Contra',
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                    ]);
                } else {
                    // Update the existing journal description to include this transfer
                    $existingDescription = $existingJournal->description;
                    $newDescription = 'Transfer from ' . $fromAccount->provider_name . 
                                     ' to ' . $toAccount->provider_name;
                    
                    if (!str_contains($existingDescription, $newDescription)) {
                        $existingJournal->update([
                            'description' => $existingDescription . '; ' . $newDescription,
                            'updated_by' => Auth::user()->id,
                        ]);
                    }
                }
            } else {
                // Create a new journal
                $journal = Journal::create([
                    'entry_date' => $request->transfer_date,
                    'description' => 'Transfer from ' . $fromAccount->provider_name . 
                                    ' to ' . $toAccount->provider_name,
                    'type' => 'Contra',
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                ]);
            }

            // Create journal entries for the transfer
            // Debit the "to" account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'chart_of_account_id' => $toAccount->account_id,
                'financial_account_id' => $request->to_financial_account_id,
                'debit' => $request->amount,
                'credit' => 0,
                'memo' => $request->memo ?? 'Transfer from ' . $fromAccount->provider_name,
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]);

            // Credit the "from" account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'chart_of_account_id' => $fromAccount->account_id,
                'financial_account_id' => $request->from_financial_account_id,
                'debit' => 0,
                'credit' => $request->amount,
                'memo' => $request->memo ?? 'Transfer to ' . $toAccount->provider_name,
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]);

            // Create the transfer record
            $transfer = Transfer::create([
                'from_financial_account_id' => $request->from_financial_account_id,
                'to_financial_account_id' => $request->to_financial_account_id,
                'chart_of_account_id' => $fromAccount->account_id,
                'amount' => $request->amount,
                'transfer_date' => $request->transfer_date,
                'reference' => $request->reference,
                'memo' => $request->memo,
                'journal_id' => $journal->id,
            ]);

            // Update financial account balances
            $fromAccount->balance -= $transfer->amount;
            $toAccount->balance += $transfer->amount;

            $fromAccount->save();
            $toAccount->save();

            DB::commit();

            $message = $existingJournal && $journal->id === $existingJournal->id 
                ? 'Transfer added to existing journal successfully' 
                : 'Transfer created successfully with new journal entry';

            return redirect()->route('transfer.list')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Transfer creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create transfer: ' . $e->getMessage()]);
        }
    }
}
