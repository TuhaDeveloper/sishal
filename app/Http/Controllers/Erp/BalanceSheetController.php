<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\JournalEntry;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BalanceSheetController extends Controller
{
    public function index(Request $request)
    {
        $asOfDate = $request->get('as_of_date', date('Y-m-d'));
        $export = $request->get('export');

        // Get all account types
        $accountTypes = ChartOfAccountType::with('accounts')->get();

        // Calculate balances for each account
        $balanceSheetData = $this->calculateBalanceSheet($asOfDate);

        // Handle export requests
        if ($export) {
            return $this->exportBalanceSheet($balanceSheetData, $asOfDate, $request);
        }

        return view('erp.doubleEntry.balancesheet', compact(
            'balanceSheetData',
            'accountTypes',
            'asOfDate'
        ));
    }

    private function calculateBalanceSheet($asOfDate)
    {
        $data = [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'totals' => [
                'assets' => 0,
                'liabilities' => 0,
                'equity' => 0
            ]
        ];

        // Get all chart of accounts with their types
        $accounts = ChartOfAccount::with('type')->get();

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalance($account->id, $asOfDate);
            
            $accountData = [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'type' => $account->type->name,
                'balance' => $balance,
                'formatted_balance' => number_format($balance, 2)
            ];

            // Categorize by account type
            switch (strtolower($account->type->name)) {
                case 'asset':
                    $data['assets'][] = $accountData;
                    $data['totals']['assets'] += $balance;
                    break;
                case 'liability':
                    $data['liabilities'][] = $accountData;
                    $data['totals']['liabilities'] += $balance;
                    break;
                case 'equity':
                    $data['equity'][] = $accountData;
                    $data['totals']['equity'] += $balance;
                    break;
            }
        }

        // Calculate net worth
        $data['net_worth'] = $data['totals']['assets'] - $data['totals']['liabilities'] - $data['totals']['equity'];
        
        // Format totals
        $data['totals']['assets_formatted'] = number_format($data['totals']['assets'], 2);
        $data['totals']['liabilities_formatted'] = number_format($data['totals']['liabilities'], 2);
        $data['totals']['equity_formatted'] = number_format($data['totals']['equity'], 2);
        $data['net_worth_formatted'] = number_format($data['net_worth'], 2);

        return $data;
    }

    private function calculateAccountBalance($accountId, $asOfDate)
    {
        // Get all journal entries for this account up to the as-of date
        $entries = JournalEntry::where('chart_of_account_id', $accountId)
            ->whereHas('journal', function ($query) use ($asOfDate) {
                $query->where('entry_date', '<=', $asOfDate);
            })
            ->get();

        $balance = 0;

        foreach ($entries as $entry) {
            $amount = $entry->debit - $entry->credit;
            
            // Get account type to determine normal balance
            $account = ChartOfAccount::with('type')->find($accountId);
            $accountType = strtolower($account->type->name);

            // Apply normal balance rules
            switch ($accountType) {
                case 'asset':
                case 'expense':
                    // Assets and expenses normally have debit balances
                    $balance += $amount;
                    break;
                case 'liability':
                case 'equity':
                case 'income':
                    // Liabilities, equity, and income normally have credit balances
                    $balance -= $amount;
                    break;
            }
        }

        return $balance;
    }

    private function exportBalanceSheet($balanceSheetData, $asOfDate, Request $request)
    {
        $exportType = $request->get('export');
        $filename = 'Balance_Sheet_' . date('Y-m-d', strtotime($asOfDate));

        if ($exportType === 'excel') {
            return $this->exportToExcel($balanceSheetData, $asOfDate, $filename);
        } elseif ($exportType === 'pdf') {
            return $this->exportToPDF($balanceSheetData, $asOfDate, $filename);
        }

        return back()->with('error', 'Invalid export type');
    }

    private function exportToExcel($balanceSheetData, $asOfDate, $filename)
    {
        try {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ];

            $callback = function() use ($balanceSheetData, $asOfDate) {
                $file = fopen('php://output', 'w');
                
                // Header
                fputcsv($file, ['BALANCE SHEET']);
                fputcsv($file, ['As of: ' . date('F d, Y', strtotime($asOfDate))]);
                fputcsv($file, []);
                
                // Assets
                fputcsv($file, ['ASSETS']);
                fputcsv($file, ['Account Code', 'Account Name', 'Balance']);
                foreach ($balanceSheetData['assets'] as $asset) {
                    fputcsv($file, [$asset['code'], $asset['name'], $asset['formatted_balance']]);
                }
                fputcsv($file, ['', 'Total Assets', $balanceSheetData['totals']['assets_formatted']]);
                fputcsv($file, []);
                
                // Liabilities
                fputcsv($file, ['LIABILITIES']);
                fputcsv($file, ['Account Code', 'Account Name', 'Balance']);
                foreach ($balanceSheetData['liabilities'] as $liability) {
                    fputcsv($file, [$liability['code'], $liability['name'], $liability['formatted_balance']]);
                }
                fputcsv($file, ['', 'Total Liabilities', $balanceSheetData['totals']['liabilities_formatted']]);
                fputcsv($file, []);
                
                // Equity
                fputcsv($file, ['EQUITY']);
                fputcsv($file, ['Account Code', 'Account Name', 'Balance']);
                foreach ($balanceSheetData['equity'] as $equity) {
                    fputcsv($file, [$equity['code'], $equity['name'], $equity['formatted_balance']]);
                }
                fputcsv($file, ['', 'Total Equity', $balanceSheetData['totals']['equity_formatted']]);
                fputcsv($file, []);
                
                // Net Worth
                fputcsv($file, ['', 'Net Worth', $balanceSheetData['net_worth_formatted']]);
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Balance sheet Excel export failed: ' . $e->getMessage());
            return response('Excel export failed: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '_error.txt"');
        }
    }

    private function exportToPDF($balanceSheetData, $asOfDate, $filename)
    {
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.doubleEntry.balanceSheetPdf', compact('balanceSheetData', 'asOfDate', 'filename'));
            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            Log::error('Balance sheet PDF export failed: ' . $e->getMessage());
            return response('PDF export failed: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '_error.txt"');
        }
    }
}
