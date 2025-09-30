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

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $export = $request->get('export');

        // Get all account types
        $accountTypes = ChartOfAccountType::with('accounts')->get();

        // Calculate profit and loss for the period
        $profitLossData = $this->calculateProfitLoss($startDate, $endDate);

        // Handle export requests
        if ($export) {
            return $this->exportProfitLoss($profitLossData, $startDate, $endDate, $request);
        }

        return view('erp.doubleEntry.profitloss', compact(
            'profitLossData',
            'accountTypes',
            'startDate',
            'endDate'
        ));
    }

    private function calculateProfitLoss($startDate, $endDate)
    {
        $data = [
            'revenue' => [],
            'expenses' => [],
            'totals' => [
                'revenue' => 0,
                'expenses' => 0,
                'gross_profit' => 0,
                'net_profit' => 0
            ]
        ];

        // Get all chart of accounts with their types
        $accounts = ChartOfAccount::with('type')->get();

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalanceForPeriod($account->id, $startDate, $endDate);
            
            if ($balance != 0) { // Only include accounts with activity
                $accountData = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'code' => $account->code,
                    'type' => $account->type->name,
                    'balance' => $balance,
                    'formatted_balance' => number_format(abs($balance), 2)
                ];

                // Categorize by account type - handle multiple possible names
                $accountType = strtolower($account->type->name);
                
                // Revenue/Income accounts
                if (in_array($accountType, ['income', 'revenue', 'sales', 'revenues'])) {
                    $data['revenue'][] = $accountData;
                    $data['totals']['revenue'] += $balance;
                }
                // Expense accounts
                elseif (in_array($accountType, ['expense', 'expenses', 'cost', 'costs'])) {
                    $data['expenses'][] = $accountData;
                    $data['totals']['expenses'] += abs($balance); // Expenses are positive for P&L
                }
            }
        }

        // Calculate profit metrics
        $data['totals']['gross_profit'] = $data['totals']['revenue'] - $data['totals']['expenses'];
        $data['totals']['net_profit'] = $data['totals']['gross_profit']; // For simple P&L, gross = net
        
        // Format totals
        $data['totals']['revenue_formatted'] = number_format($data['totals']['revenue'], 2);
        $data['totals']['expenses_formatted'] = number_format($data['totals']['expenses'], 2);
        $data['totals']['gross_profit_formatted'] = number_format($data['totals']['gross_profit'], 2);
        $data['totals']['net_profit_formatted'] = number_format($data['totals']['net_profit'], 2);

        // Calculate percentages
        if ($data['totals']['revenue'] > 0) {
            $data['totals']['expense_percentage'] = number_format(($data['totals']['expenses'] / $data['totals']['revenue']) * 100, 1);
            $data['totals']['profit_percentage'] = number_format(($data['totals']['gross_profit'] / $data['totals']['revenue']) * 100, 1);
        } else {
            $data['totals']['expense_percentage'] = 0;
            $data['totals']['profit_percentage'] = 0;
        }

        return $data;
    }

    private function calculateAccountBalanceForPeriod($accountId, $startDate, $endDate)
    {
        // Get all journal entries for this account within the date range
        $entries = JournalEntry::where('chart_of_account_id', $accountId)
            ->whereHas('journal', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->get();

        $balance = 0;

        foreach ($entries as $entry) {
            $amount = $entry->debit - $entry->credit;
            
            // Get account type to determine normal balance
            $account = ChartOfAccount::with('type')->find($accountId);
            $accountType = strtolower($account->type->name);

            // Apply normal balance rules for P&L - handle multiple possible names
            // Revenue/Income accounts
            if (in_array($accountType, ['income', 'revenue', 'sales', 'revenues'])) {
                // Income normally has credit balances (revenue increases with credits)
                $balance -= $amount; // Reverse for P&L display
            }
            // Expense accounts
            elseif (in_array($accountType, ['expense', 'expenses', 'cost', 'costs'])) {
                // Expenses normally have debit balances (expenses increase with debits)
                $balance += $amount;
            }
        }

        return $balance;
    }

    private function exportProfitLoss($profitLossData, $startDate, $endDate, Request $request)
    {
        $exportType = $request->get('export');
        $filename = 'Profit_Loss_' . date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate));

        if ($exportType === 'excel') {
            return $this->exportToExcel($profitLossData, $startDate, $endDate, $filename);
        } elseif ($exportType === 'pdf') {
            return $this->exportToPDF($profitLossData, $startDate, $endDate, $filename);
        }

        return back()->with('error', 'Invalid export type');
    }

    private function exportToExcel($profitLossData, $startDate, $endDate, $filename)
    {
        try {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ];

            $callback = function() use ($profitLossData, $startDate, $endDate) {
                $file = fopen('php://output', 'w');
                
                // Header
                fputcsv($file, ['PROFIT & LOSS STATEMENT']);
                fputcsv($file, ['Period: ' . date('F d, Y', strtotime($startDate)) . ' to ' . date('F d, Y', strtotime($endDate))]);
                fputcsv($file, []);
                
                // Revenue
                fputcsv($file, ['REVENUE']);
                fputcsv($file, ['Account Code', 'Account Name', 'Amount']);
                foreach ($profitLossData['revenue'] as $revenue) {
                    fputcsv($file, [$revenue['code'], $revenue['name'], $revenue['formatted_balance']]);
                }
                fputcsv($file, ['', 'Total Revenue', $profitLossData['totals']['revenue_formatted']]);
                fputcsv($file, []);
                
                // Expenses
                fputcsv($file, ['EXPENSES']);
                fputcsv($file, ['Account Code', 'Account Name', 'Amount']);
                foreach ($profitLossData['expenses'] as $expense) {
                    fputcsv($file, [$expense['code'], $expense['name'], $expense['formatted_balance']]);
                }
                fputcsv($file, ['', 'Total Expenses', $profitLossData['totals']['expenses_formatted']]);
                fputcsv($file, []);
                
                // Net Profit/Loss
                fputcsv($file, ['', 'Net Profit/Loss', $profitLossData['totals']['net_profit_formatted']]);
                fputcsv($file, ['', 'Profit Margin', $profitLossData['totals']['profit_percentage'] . '%']);
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Profit & Loss Excel export failed: ' . $e->getMessage());
            return response('Excel export failed: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '_error.txt"');
        }
    }

    private function exportToPDF($profitLossData, $startDate, $endDate, $filename)
    {
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.doubleEntry.profitLossPdf', compact('profitLossData', 'startDate', 'endDate', 'filename'));
            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            Log::error('Profit & Loss PDF export failed: ' . $e->getMessage());
            return response('PDF export failed: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '_error.txt"');
        }
    }
} 