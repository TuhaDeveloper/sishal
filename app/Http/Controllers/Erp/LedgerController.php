<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $accountId = $request->get('account_id');
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $accountType = $request->get('account_type');
        $export = $request->get('export');

        // Build the query
        $query = JournalEntry::with(['journal', 'chartOfAccount.type', 'financialAccount'])
            ->whereHas('journal', function ($journalQuery) use ($startDate, $endDate) {
                $journalQuery->whereBetween('entry_date', [$startDate, $endDate]);
            });

        // Filter by specific account
        if ($accountId) {
            $query->where('chart_of_account_id', $accountId);
        }

        // Filter by account type
        if ($accountType) {
            $query->whereHas('chartOfAccount.type', function ($typeQuery) use ($accountType) {
                $typeQuery->where('name', $accountType);
            });
        }

        // Get chart accounts for filter dropdown
        $chartAccounts = ChartOfAccount::with('type')->get();

        // Get ledger entries with pagination
        $ledgerEntries = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate totals
        $totalDebits = $ledgerEntries->sum('debit');
        $totalCredits = $ledgerEntries->sum('credit');
        $totalEntries = $ledgerEntries->count();

        // Calculate running balance for each entry
        $runningBalance = 0;
        foreach ($ledgerEntries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            $entry->running_balance = $runningBalance;
        }

        // Handle export requests
        if ($export) {
            return $this->exportLedger($ledgerEntries, $request);
        }

        return view('erp.doubleEntry.ledgersummery', compact(
            'ledgerEntries',
            'chartAccounts',
            'totalDebits',
            'totalCredits',
            'totalEntries'
        ));
    }

    public function show($id)
    {
        $entry = JournalEntry::with(['journal', 'chartOfAccount.type', 'financialAccount'])
            ->findOrFail($id);

        return response()->json(['entry' => $entry]);
    }

    public function accountLedger($accountId, Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $export = $request->get('export');

        $account = ChartOfAccount::with('type')->findOrFail($accountId);

        $entries = JournalEntry::with(['journal', 'financialAccount'])
            ->where('chart_of_account_id', $accountId)
            ->whereHas('journal', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate running balance
        $runningBalance = 0;
        foreach ($entries as $entry) {
            $runningBalance += $entry->debit - $entry->credit;
            $entry->running_balance = $runningBalance;
        }

        $totalDebits = $entries->sum('debit');
        $totalCredits = $entries->sum('credit');

        // Handle export requests
        if ($export) {
            return $this->exportAccountLedger($entries, $account, $request);
        }

        return view('erp.doubleEntry.accountLedger', compact(
            'account',
            'entries',
            'totalDebits',
            'totalCredits',
            'runningBalance'
        ));
    }

    private function exportLedger($ledgerEntries, Request $request)
    {
        $exportType = $request->get('export');
        
        // Prepare data for export
        $data = [];
        foreach ($ledgerEntries as $entry) {
            $data[] = [
                'Date' => $entry->journal->entry_date->format('Y-m-d'),
                'Voucher No' => $entry->journal->voucher_no,
                'Account' => $entry->chartOfAccount->name . ' (' . $entry->chartOfAccount->code . ')',
                'Description' => $entry->journal->description,
                'Memo' => $entry->memo,
                'Debit' => $entry->debit,
                'Credit' => $entry->credit,
                'Balance' => $entry->running_balance ?? 0,
            ];
        }

        if ($exportType === 'excel') {
            return $this->exportToExcel($data, 'Ledger_Report_' . date('Y-m-d'));
        } elseif ($exportType === 'pdf') {
            return $this->exportToPDF($data, 'Ledger_Report_' . date('Y-m-d'));
        }

        return back()->with('error', 'Invalid export type');
    }

    private function exportAccountLedger($entries, $account, Request $request)
    {
        $exportType = $request->get('export');
        
        // Prepare data for export
        $data = [];
        foreach ($entries as $entry) {
            $data[] = [
                'Date' => $entry->journal->entry_date->format('Y-m-d'),
                'Voucher No' => $entry->journal->voucher_no,
                'Account' => $account->name . ' (' . $account->code . ')',
                'Description' => $entry->journal->description,
                'Memo' => $entry->memo,
                'Debit' => $entry->debit,
                'Credit' => $entry->credit,
                'Balance' => $entry->running_balance ?? 0,
            ];
        }

        $filename = 'Account_Ledger_' . $account->code . '_' . date('Y-m-d');

        if ($exportType === 'excel') {
            return $this->exportToExcel($data, $filename);
        } elseif ($exportType === 'pdf') {
            return $this->exportToPDF($data, $filename);
        }

        return back()->with('error', 'Invalid export type');
    }

    private function exportToPDF($data, $filename)
    {
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.doubleEntry.ledgerPdf', compact('data', 'filename'));
            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            Log::error('PDF export failed: ' . $e->getMessage());
            return response('PDF export failed: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '_error.txt"');
        }
    }

    private function exportToExcel($data, $filename)
    {
        try {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ];
            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                if (!empty($data)) {
                    fputcsv($file, array_keys($data[0]));
                }
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Excel export failed: ' . $e->getMessage());
            return response('Excel export failed: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '_error.txt"');
        }
    }
}
