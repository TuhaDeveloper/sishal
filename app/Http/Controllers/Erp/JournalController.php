<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JournalController extends Controller
{
    public function list()
    {
        $journals = Journal::with(['entries.chartOfAccount', 'entries.financialAccount', 'createdBy'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $chartAccounts = ChartOfAccount::with(['parent', 'type', 'subType'])->get();
        $financialAccounts = FinancialAccount::with('account')->get();
        
        return view('erp.doubleEntry.journalaccount', compact('journals', 'chartAccounts', 'financialAccounts'));
    }

    public function show($id)
    {
        $journal = Journal::with(['entries.chartOfAccount', 'entries.financialAccount', 'createdBy', 'updatedBy'])
            ->findOrFail($id);
        
        // Get chart accounts and financial accounts for dropdowns
        $chartAccounts = ChartOfAccount::with('parent')->get();
        $financialAccounts = FinancialAccount::all();
            
        return view('erp.doubleEntry.journaldetails', compact('journal', 'chartAccounts', 'financialAccounts'));
    }

    public function getEntries($id)
    {
        $journal = Journal::with(['entries.chartOfAccount', 'entries.financialAccount'])->findOrFail($id);
        return response()->json(['entries' => $journal->entries]);
    }

    public function getEntry($id)
    {
        $entry = JournalEntry::with(['chartOfAccount', 'financialAccount'])->findOrFail($id);
        return response()->json(['entry' => $entry]);
    }

    public function storeEntry(Request $request, $journalId)
    {
        $request->validate([
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'financial_account_id' => 'nullable|exists:financial_accounts,id',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'memo' => 'nullable|string|max:500',
        ]);

        // Ensure at least one of debit or credit is provided
        $debit = !empty($request->debit) ? floatval($request->debit) : 0;
        $credit = !empty($request->credit) ? floatval($request->credit) : 0;

        if ($debit == 0 && $credit == 0) {
            return back()->withErrors(['amount' => 'Either debit or credit must be greater than zero.']);
        }

        try {
            DB::beginTransaction();

            $journal = Journal::findOrFail($journalId);

            $entry = JournalEntry::create([
                'journal_id' => $journalId,
                'chart_of_account_id' => $request->chart_of_account_id,
                'financial_account_id' => $request->financial_account_id,
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $request->memo,
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]);

            // Update financial account balance if it's a cash account
            $chartAccount = ChartOfAccount::with('type')->find($request->chart_of_account_id);
            if ($chartAccount && $chartAccount->is_cash_account && $request->financial_account_id) {
                $amount = $debit - $credit;
                $financialAccount = FinancialAccount::find($request->financial_account_id);
                
                if ($financialAccount) {
                    // Apply balance based on account type
                    switch ($chartAccount->type->name) {
                        case 'Asset':
                        case 'Expense':
                            $financialAccount->balance += $amount; // Debit increases, Credit decreases
                            break;
                        
                        case 'Liability':
                        case 'Equity':
                        case 'Income':
                            $financialAccount->balance -= $amount; // Debit decreases, Credit increases
                            break;
                    }
                    $financialAccount->save();
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Journal entry added successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Journal entry creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create journal entry: ' . $e->getMessage()]);
        }
    }

    public function updateEntry(Request $request, $id)
    {
        $request->validate([
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'financial_account_id' => 'nullable|exists:financial_accounts,id',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'memo' => 'nullable|string|max:500',
        ]);

        // Ensure at least one of debit or credit is provided
        $debit = !empty($request->debit) ? floatval($request->debit) : 0;
        $credit = !empty($request->credit) ? floatval($request->credit) : 0;

        if ($debit == 0 && $credit == 0) {
            return back()->withErrors(['amount' => 'Either debit or credit must be greater than zero.']);
        }

        try {
            DB::beginTransaction();

            $entry = JournalEntry::with(['chartOfAccount.type', 'financialAccount'])->findOrFail($id);
            
            // First, reverse the impact of existing entry on financial account balance
            if ($entry->chartOfAccount && $entry->chartOfAccount->is_cash_account && $entry->financialAccount) {
                $oldAmount = $entry->debit - $entry->credit;
                
                // Reverse balance based on account type
                switch ($entry->chartOfAccount->type->name) {
                    case 'Asset':
                    case 'Expense':
                        $entry->financialAccount->balance -= $oldAmount; // Reverse: Credit increases, Debit decreases
                        break;
                    
                    case 'Liability':
                    case 'Equity':
                    case 'Income':
                        $entry->financialAccount->balance += $oldAmount; // Reverse: Debit increases, Credit decreases
                        break;
                }
                $entry->financialAccount->save();
            }
            
            $entry->update([
                'chart_of_account_id' => $request->chart_of_account_id,
                'financial_account_id' => $request->financial_account_id,
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $request->memo,
                'updated_by' => Auth::user()->id,
            ]);

            // Apply new impact to financial account balance
            $chartAccount = ChartOfAccount::with('type')->find($request->chart_of_account_id);
            if ($chartAccount && $chartAccount->is_cash_account && $request->financial_account_id) {
                $newAmount = $debit - $credit;
                $financialAccount = FinancialAccount::find($request->financial_account_id);
                
                if ($financialAccount) {
                    // Apply balance based on account type
                    switch ($chartAccount->type->name) {
                        case 'Asset':
                        case 'Expense':
                            $financialAccount->balance += $newAmount; // Debit increases, Credit decreases
                            break;
                        
                        case 'Liability':
                        case 'Equity':
                        case 'Income':
                            $financialAccount->balance -= $newAmount; // Debit decreases, Credit increases
                            break;
                    }
                    $financialAccount->save();
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Journal entry updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Journal entry update failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update journal entry: ' . $e->getMessage()]);
        }
    }

    public function destroyEntry($id)
    {
        try {
            DB::beginTransaction();

            $entry = JournalEntry::with(['chartOfAccount.type', 'financialAccount'])->findOrFail($id);
            
            // Reverse the impact of entry on financial account balance
            if ($entry->chartOfAccount && $entry->chartOfAccount->is_cash_account && $entry->financialAccount) {
                $amount = $entry->debit - $entry->credit;
                
                // Reverse balance based on account type
                switch ($entry->chartOfAccount->type->name) {
                    case 'Asset':
                    case 'Expense':
                        $entry->financialAccount->balance -= $amount; // Reverse: Credit increases, Debit decreases
                        break;
                    
                    case 'Liability':
                    case 'Equity':
                    case 'Income':
                        $entry->financialAccount->balance += $amount; // Reverse: Debit increases, Credit decreases
                        break;
                }
                $entry->financialAccount->save();
            }
            
            $entry->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Journal entry deleted successfully']);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Journal entry deletion failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete journal entry: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|in:Journal,Payment,Receipt,Contra,Adjustment',
            'entries' => 'required|array',
            'entries.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'entries.*.financial_account_id' => 'nullable|exists:financial_accounts,id',
            'entries.*.debit' => 'required_without:entries.*.credit|nullable|numeric|min:0',
            'entries.*.credit' => 'required_without:entries.*.debit|nullable|numeric|min:0',
            'entries.*.memo' => 'nullable|string|max:255',
        ]);

        // Validate that total debit equals total credit
        $totalDebit = collect($request->entries)->sum('debit');
        $totalCredit = collect($request->entries)->sum('credit');
        

        try {
            DB::beginTransaction();

            $journal = Journal::create([
                'entry_date' => $request->entry_date,
                'description' => $request->description,
                'type' => $request->type,
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]);

            foreach ($request->entries as $entry) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $entry['chart_of_account_id'],
                    'financial_account_id' => $entry['financial_account_id'] ?? null,
                    'debit' => !empty($entry['debit']) ? floatval($entry['debit']) : 0,
                    'credit' => !empty($entry['credit']) ? floatval($entry['credit']) : 0,
                    'memo' => $entry['memo'] ?? null,
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                ]);

                $chartAccount = ChartOfAccount::with('type')->find($entry['chart_of_account_id']);
                if ($chartAccount && $chartAccount->is_cash_account && isset($entry['financial_account_id'])) {
                    $amount = floatval($entry['debit'] ?? 0) - floatval($entry['credit'] ?? 0);
                    
                    $financialAccount = FinancialAccount::find($entry['financial_account_id']);
                    if ($financialAccount) {
                        // Apply balance based on account type
                        switch ($chartAccount->type->name) {
                            case 'Asset':
                            case 'Expense':
                                $financialAccount->balance += $amount; // Debit increases, Credit decreases
                                break;
                            
                            case 'Liability':
                            case 'Equity':
                            case 'Income':
                                $financialAccount->balance -= $amount; // Debit decreases, Credit increases
                                break;
                        }
                        $financialAccount->save();
                    }
                }
            }

            DB::commit();
            return redirect()->route('journal.list')->with('success', 'Journal entry created successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create journal entry: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|in:Journal,Payment,Receipt,Contra,Adjustment',
            'entries' => 'required|array',
            'entries.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'entries.*.financial_account_id' => 'nullable|exists:financial_accounts,id',
            'entries.*.debit' => 'required_without:entries.*.credit|nullable|numeric|min:0',
            'entries.*.credit' => 'required_without:entries.*.debit|nullable|numeric|min:0',
            'entries.*.memo' => 'nullable|string|max:255',
        ]);

        // Validate that total debit equals total credit
        $totalDebit = collect($request->entries)->sum('debit');
        $totalCredit = collect($request->entries)->sum('credit');

        try {
            DB::beginTransaction();

            $journal = Journal::findOrFail($id);
            
            // First, reverse the impact of existing entries on financial account balances
            $existingEntries = $journal->entries()->with(['chartOfAccount.type', 'financialAccount'])->get();
            foreach ($existingEntries as $existingEntry) {
                if ($existingEntry->chartOfAccount && $existingEntry->chartOfAccount->is_cash_account && $existingEntry->financialAccount) {
                    $amount = $existingEntry->debit - $existingEntry->credit;
                    
                    // Reverse balance based on account type
                    switch ($existingEntry->chartOfAccount->type->name) {
                        case 'Asset':
                        case 'Expense':
                            $existingEntry->financialAccount->balance -= $amount; // Reverse: Credit increases, Debit decreases
                            break;
                        
                        case 'Liability':
                        case 'Equity':
                        case 'Income':
                            $existingEntry->financialAccount->balance += $amount; // Reverse: Debit increases, Credit decreases
                            break;
                    }
                    $existingEntry->financialAccount->save();
                }
            }

            $journal->update([
                'entry_date' => $request->entry_date,
                'description' => $request->description,
                'type' => $request->type,
                'updated_by' => Auth::user()->id,
            ]);

            // Delete existing entries and create new ones
            $journal->entries()->delete();

            foreach ($request->entries as $entry) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $entry['chart_of_account_id'],
                    'financial_account_id' => $entry['financial_account_id'] ?? null,
                    'debit' => !empty($entry['debit']) ? floatval($entry['debit']) : 0,
                    'credit' => !empty($entry['credit']) ? floatval($entry['credit']) : 0,
                    'memo' => $entry['memo'] ?? null,
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                ]);

                // Apply new entries to financial account balances
                $chartAccount = ChartOfAccount::with('type')->find($entry['chart_of_account_id']);
                if ($chartAccount && $chartAccount->is_cash_account && isset($entry['financial_account_id'])) {
                    $amount = floatval($entry['debit'] ?? 0) - floatval($entry['credit'] ?? 0);
                    
                    $financialAccount = FinancialAccount::find($entry['financial_account_id']);
                    if ($financialAccount) {
                        // Apply balance based on account type
                        switch ($chartAccount->type->name) {
                            case 'Asset':
                            case 'Expense':
                                $financialAccount->balance += $amount; // Debit increases, Credit decreases
                                break;
                            
                            case 'Liability':
                            case 'Equity':
                            case 'Income':
                                $financialAccount->balance -= $amount; // Debit decreases, Credit increases
                                break;
                        }
                        $financialAccount->save();
                    }
                }
            }

            DB::commit();
            return redirect()->route('journal.list')->with('success', 'Journal entry updated successfully');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Journal update failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update journal entry: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $journal = Journal::findOrFail($id);
            
            // First, reverse the impact of existing entries on financial account balances
            $existingEntries = $journal->entries()->with(['chartOfAccount.type', 'financialAccount'])->get();
            foreach ($existingEntries as $existingEntry) {
                if ($existingEntry->chartOfAccount && $existingEntry->chartOfAccount->is_cash_account && $existingEntry->financialAccount) {
                    $amount = $existingEntry->debit - $existingEntry->credit;
                    
                    // Reverse balance based on account type
                    switch ($existingEntry->chartOfAccount->type->name) {
                        case 'Asset':
                        case 'Expense':
                            $existingEntry->financialAccount->balance -= $amount; // Reverse: Credit increases, Debit decreases
                            break;
                        
                        case 'Liability':
                        case 'Equity':
                        case 'Income':
                            $existingEntry->financialAccount->balance += $amount; // Reverse: Debit increases, Credit decreases
                            break;
                    }
                    $existingEntry->financialAccount->save();
                }
            }
            
            $journal->entries()->delete();
            $journal->delete();
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Journal entry deleted successfully']);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Journal deletion failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete journal entry: ' . $e->getMessage()]);
        }
    }
}
