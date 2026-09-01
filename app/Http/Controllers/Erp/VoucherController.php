<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\FinancialAccount;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view vouchers')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        $now = Carbon::now();

        if ($reportType == 'monthly') {
            $month = $request->input('month', date('n'));
            $year = $request->input('year', date('Y'));
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->input('year', date('Y'));
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : $now->copy()->startOfDay();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : $now->copy()->endOfDay();
        }

        $query = Journal::with(['branch', 'customer', 'supplier', 'expenseAccount', 'creator', 'entries.chartOfAccount'])
            ->whereBetween('entry_date', [$startDate, $endDate]);

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->where('branch_id', $restrictedBranchId);
        }

        if ($request->filled('customer_id') && $request->customer_id != 'all') {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('supplier_id') && $request->supplier_id != 'all') {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('voucher_type') && $request->voucher_type != 'all') {
            $query->where('type', $request->voucher_type);
        }
        if ($request->filled('account_id') && $request->account_id != 'all') {
            $query->where(function($q) use ($request) {
                $q->where('expense_account_id', $request->account_id)
                  ->orWhereHas('entries', function($q2) use ($request) {
                      $q2->where('chart_of_account_id', $request->account_id);
                  });
            });
        }

        // Calculate Totals for all filtered results (not just current page)
        $totals = (clone $query)->selectRaw('SUM(voucher_amount) as total_voucher, SUM(paid_amount) as total_paid')->first();
        $vouchers = $query->latest()->paginate(50)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('erp.vouchers.table_rows', compact('vouchers'))->render(),
                'total_voucher' => number_format(optional($totals)->total_voucher ?? 0, 2),
                'total_paid' => number_format(optional($totals)->total_paid ?? 0, 2),
                'pagination' => (string) $vouchers->links('vendor.pagination.bootstrap-5')
            ]);
        }

        $customers = Customer::orderBy('name')->take(200)->get();
        $suppliers = Supplier::orderBy('name')->take(200)->get();
        $expenseAccounts = ChartOfAccount::whereHas('type', function($q) {
            $q->whereIn('name', ['Expense', 'Revenue', 'Liability', 'Equity']);
        })->take(200)->get();

        return view('erp.vouchers.index', compact('vouchers', 'startDate', 'endDate', 'customers', 'suppliers', 'expenseAccounts', 'reportType', 'totals'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('manage vouchers')) {
            abort(403, 'Unauthorized action.');
        }
        $voucherNo = 'V-' . date('Ymd') . '-' . str_pad(Journal::count() + 1, 4, '0', STR_PAD_LEFT);
        
        // Fetch Expense/Revenue/Liability/Equity Accounts
        $expenseTypeIds = ChartOfAccountType::whereIn('name', ['Expense', 'Revenue', 'Liability', 'Equity'])->pluck('id');
        
        $expenseAccounts = ChartOfAccount::whereIn('type_id', $expenseTypeIds)
            ->orWhereHas('parent', function($q) use ($expenseTypeIds) {
                $q->whereIn('type_id', $expenseTypeIds);
            })->get();
        
        // Fetch Financial Accounts with their corresponding ChartOfAccount
        $financialAccountsQuery = FinancialAccount::with('chartOfAccount');
        
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $financialAccountsQuery->where(function($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                  ->orWhereNull('branch_id');
            });
        }
        
        $paymentAccounts = $financialAccountsQuery->get();

        $expenseTypeId = ChartOfAccountType::where('name', 'Expense')->first()->id ?? 15;

        if ($restrictedBranchId) {
            $branches = Branch::where('id', $restrictedBranchId)->get();
        } else {
            $branches = Branch::all();
        }
        $customers = Customer::all();
        $suppliers = Supplier::all();
        
        // Pass account types for the modal
        $accountTypes = \App\Models\ChartOfAccountType::all();
        $parentAccounts = \App\Models\ChartOfAccountParent::all();
        $subTypes = \App\Models\ChartOfAccountSubType::all();
        
        // Map Type ID -> First Parent ID (for Simplified UX)
        $defaultParents = $parentAccounts->groupBy('type_id')->map(function($group) {
            return $group->first()->id;
        });

        return view('erp.vouchers.create', compact('voucherNo', 'expenseAccounts', 'paymentAccounts', 'branches', 'customers', 'suppliers', 'expenseTypeId', 'accountTypes', 'parentAccounts', 'subTypes', 'defaultParents'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage vouchers')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'entry_date' => 'required|date',
            'voucher_no' => 'required|unique:journals,voucher_no',
            'expense_account_id' => 'required|exists:chart_of_accounts,id',
            'account_id' => 'required|exists:financial_accounts,id',
            'particulars' => 'required|array',
            'amounts' => 'required|array',
            'amounts.*' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $totalAmount = array_sum($request->amounts);

            $restrictedBranchId = $this->getRestrictedBranchId();
            $branchId = $restrictedBranchId ?: $request->branch_id;

            $journal = Journal::create([
                'voucher_no' => $request->voucher_no,
                'type' => $request->voucher_type ?? 'Payment',
                'entry_date' => $request->entry_date,
                'description' => $request->note,
                'branch_id' => $branchId,
                'customer_id' => $request->customer_id,
                'supplier_id' => $request->supplier_id,
                'expense_account_id' => $request->expense_account_id,
                'voucher_amount' => $totalAmount,
                'paid_amount' => $totalAmount, // Assuming full payment for now as per UI
                'reference' => $request->reference,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Retrieve financial account
            $finAcc = FinancialAccount::findOrFail($request->account_id);

            // Handle Entries based on Voucher Type
            if ($request->voucher_type == 'Receipt') {
                // RECEIPTS: Money coming IN
                // Debit: Cash/Bank (Asset)
                // Credit: Revenue (Income)

                // 1. Credit Entry (Revenue/Income) - Multiple Lines
                foreach ($request->particulars as $index => $part) {
                    if ($request->amounts[$index] > 0) {
                        JournalEntry::create([
                            'journal_id' => $journal->id,
                            'chart_of_account_id' => $request->expense_account_id, // Revenue Account
                            'debit' => 0,
                            'credit' => $request->amounts[$index],
                            'memo' => $part,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                    }
                }

                // 2. Debit Entry (Cash/Bank) - Total Amount
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $finAcc->account_id,
                    'financial_account_id' => $finAcc->id,
                    'debit' => $totalAmount,
                    'credit' => 0,
                    'memo' => 'Receipt from ' . ($journal->expenseAccount->name ?? 'Voucher'),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

            } else {
                // PAYMENTS (Default): Money going OUT
                // Debit: Expense
                // Credit: Cash/Bank (Asset)

                // 1. Debit Entry (Expense) - Multiple Lines
                foreach ($request->particulars as $index => $part) {
                    if ($request->amounts[$index] > 0) {
                        JournalEntry::create([
                            'journal_id' => $journal->id,
                            'chart_of_account_id' => $request->expense_account_id,
                            'debit' => $request->amounts[$index],
                            'credit' => 0,
                            'memo' => $part,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                    }
                }

                // 2. Credit Entry (Cash/Bank) - Total Amount
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $finAcc->account_id,
                    'financial_account_id' => $finAcc->id,
                    'debit' => 0,
                    'credit' => $totalAmount,
                    'memo' => 'Payment for ' . ($journal->expenseAccount->name ?? 'Voucher'),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            \App\Http\Controllers\Erp\SuperAdminDashboardController::clearCache();
            DB::commit();
            return redirect()->route('vouchers.index')->with('success', 'Voucher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasPermissionTo('manage vouchers')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            DB::beginTransaction();
            $journal = Journal::findOrFail($id);
            // Delete associated entries first (though database cascade should handle it)
            $journal->entries()->delete();
            $journal->delete();
            \App\Http\Controllers\Erp\SuperAdminDashboardController::clearCache();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Voucher deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function buildFilteredQuery(Request $request)
    {
        $reportType = $request->get('report_type', 'yearly');
        $now = Carbon::now();

        if ($reportType == 'monthly') {
            $month = $request->input('month', date('n'));
            $year  = $request->input('year', date('Y'));
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate   = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year  = $request->input('year', date('Y'));
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate   = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : $now->copy()->startOfDay();
            $endDate   = $request->filled('end_date')   ? Carbon::parse($request->end_date)->endOfDay()   : $now->copy()->endOfDay();
        }

        $query = Journal::with(['branch', 'customer', 'supplier', 'expenseAccount', 'creator', 'entries.chartOfAccount'])
            ->whereBetween('entry_date', [$startDate, $endDate]);

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->where('branch_id', $restrictedBranchId);
        }
        if ($request->filled('customer_id') && $request->customer_id != 'all') {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('supplier_id') && $request->supplier_id != 'all') {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('voucher_type') && $request->voucher_type != 'all') {
            $query->where('type', $request->voucher_type);
        }
        if ($request->filled('account_id') && $request->account_id != 'all') {
            $query->where(function($q) use ($request) {
                $q->where('expense_account_id', $request->account_id)
                  ->orWhereHas('entries', function($q2) use ($request) {
                      $q2->where('chart_of_account_id', $request->account_id);
                  });
            });
        }

        return $query;
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view vouchers')) {
            abort(403, 'Unauthorized action.');
        }

        $vouchers = $this->buildFilteredQuery($request)->latest()->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header row
        $headers = ['#', 'Voucher No', 'Date', 'Type', 'Account', 'Customer/Supplier', 'Branch', 'Amount', 'Paid', 'Created By'];
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $rowNum = 2;
        $totalAmount = 0;
        $totalPaid   = 0;
        foreach ($vouchers as $i => $v) {
            $party = optional($v->customer)->name ?? optional($v->supplier)->name ?? '—';
            $row = [
                $i + 1,
                $v->voucher_no,
                \Carbon\Carbon::parse($v->entry_date)->format('d/m/Y'),
                $v->type ?? '—',
                optional($v->expenseAccount)->name ?? '—',
                $party,
                optional($v->branch)->name ?? '—',
                (float) $v->voucher_amount,
                (float) $v->paid_amount,
                optional($v->creator)->name ?? '—',
            ];
            $sheet->fromArray([$row], null, 'A' . $rowNum);
            $totalAmount += $v->voucher_amount;
            $totalPaid   += $v->paid_amount;
            $rowNum++;
        }

        // Totals row
        $sheet->fromArray([['', '', '', '', '', '', 'TOTAL', $totalAmount, $totalPaid, '']], null, 'A' . $rowNum);
        $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('H2:I' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'vouchers_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view vouchers')) {
            abort(403, 'Unauthorized action.');
        }

        $vouchers = $this->buildFilteredQuery($request)->latest()->get();
        $totalAmount = $vouchers->sum('voucher_amount');
        $totalPaid   = $vouchers->sum('paid_amount');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.vouchers.report-pdf', compact('vouchers', 'totalAmount', 'totalPaid'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('vouchers_' . date('Y-m-d_His') . '.pdf');
    }
}
