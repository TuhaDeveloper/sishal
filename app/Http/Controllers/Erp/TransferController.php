<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\FinancialAccount;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view fund transfers') && !auth()->user()->hasPermissionTo('view transfers')) {
            abort(403, 'Unauthorized action.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $user = auth()->user();

        $query = Transfer::with(['fromAccount', 'toAccount', 'creator']);

        // Apply branch restrictions - only show transfers involving user's branch accounts
        if ($restrictedBranchId) {
            $query->where(function($q) use ($restrictedBranchId) {
                $q->whereHas('fromAccount', function($fq) use ($restrictedBranchId) {
                    $fq->where('branch_id', $restrictedBranchId)
                       ->orWhereHas('warehouse', function($wq) use ($restrictedBranchId) {
                           $wq->whereHas('branches', function($bq) use ($restrictedBranchId) {
                               $bq->where('id', $restrictedBranchId);
                           });
                       });
                })->orWhereHas('toAccount', function($tq) use ($restrictedBranchId) {
                    $tq->where('branch_id', $restrictedBranchId)
                       ->orWhereHas('warehouse', function($wq) use ($restrictedBranchId) {
                           $wq->whereHas('branches', function($bq) use ($restrictedBranchId) {
                               $bq->where('id', $restrictedBranchId);
                           });
                       });
                });
            });
        } elseif ($user->warehouse_id) {
            $query->where(function($q) use ($user) {
                $q->whereHas('fromAccount', function($fq) use ($user) {
                    $fq->where('warehouse_id', $user->warehouse_id);
                })->orWhereHas('toAccount', function($tq) use ($user) {
                    $tq->where('warehouse_id', $user->warehouse_id);
                });
            });
        }

        // Date filters
        $startDate = null;
        $endDate = null;
        $this->applyDateFilters($query, $request, $startDate, $endDate);

        // Account filters
        if ($request->filled('from_account_id')) {
            $query->where('from_financial_account_id', $request->from_account_id);
        }
        if ($request->filled('to_account_id')) {
            $query->where('to_financial_account_id', $request->to_account_id);
        }

        // Branch filter
        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->where(function($q) use ($branchId) {
                $q->whereHas('fromAccount', function($fq) use ($branchId) {
                    $fq->where('branch_id', $branchId);
                })->orWhereHas('toAccount', function($tq) use ($branchId) {
                    $tq->where('branch_id', $branchId);
                });
            });
        }

        $transfers = $query->latest('transfer_date')->latest('id')->paginate(20)->appends($request->all());

        $totalTransfers = (clone $query)->sum('amount');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('erp.transfers.partials.table', compact('transfers'))->render(),
                'totalAmount' => number_format($totalTransfers, 2) . '৳'
            ]);
        }

        // Get accounts for filter dropdown - filtered by branch
        $accountsQuery = FinancialAccount::orderBy('type')->orderBy('provider_name');
        if ($restrictedBranchId) {
            $accountsQuery->where(function($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                  ->orWhereHas('warehouse', function($wq) use ($restrictedBranchId) {
                      $wq->whereHas('branches', function($bq) use ($restrictedBranchId) {
                          $bq->where('id', $restrictedBranchId);
                      });
                  });
            });
        } elseif ($user->warehouse_id) {
            $accountsQuery->where('warehouse_id', $user->warehouse_id);
        }
        $accounts = $accountsQuery->get();
        
        // Get branches for filter
        if ($restrictedBranchId) {
            $branches = Branch::where('id', $restrictedBranchId)->get();
        } else {
            $branches = Branch::orderBy('name')->get();
        }

        $reportType = $request->get('report_type_active', 'yearly');

        return view('erp.transfers.index', compact('transfers', 'accounts', 'totalTransfers', 'branches', 'startDate', 'endDate', 'reportType'));
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view fund transfers') && !auth()->user()->hasPermissionTo('view transfers')) {
            abort(403, 'Unauthorized action.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $query = Transfer::with(['fromAccount.branch', 'fromAccount.warehouse', 'toAccount.branch', 'toAccount.warehouse', 'creator']);

        // Apply same filtering as index
        if ($restrictedBranchId) {
            $query->where(function($q) use ($restrictedBranchId) {
                $q->whereHas('fromAccount', function($fq) use ($restrictedBranchId) {
                    $fq->where('branch_id', $restrictedBranchId);
                })->orWhereHas('toAccount', function($tq) use ($restrictedBranchId) {
                    $tq->where('branch_id', $restrictedBranchId);
                });
            });
        }

        $this->applyDateFilters($query, $request);
        if ($request->filled('from_account_id')) $query->where('from_financial_account_id', $request->from_account_id);
        if ($request->filled('to_account_id')) $query->where('to_financial_account_id', $request->to_account_id);
        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->where(function($q) use ($branchId) {
                $q->whereHas('fromAccount', function($fq) use ($branchId) { $fq->where('branch_id', $branchId); })
                  ->orWhereHas('toAccount', function($tq) use ($branchId) { $tq->where('branch_id', $branchId); });
            });
        }

        $items = $query->latest('transfer_date')->latest('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = ['Date', 'From Account', 'From Location', 'To Account', 'To Location', 'Amount', 'Reference', 'Memo', 'Created By'];
        $sheet->fromArray([$headers], NULL, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $rowNum = 2;
        $totalAmount = 0;
        foreach ($items as $item) {
            $data = [
                $item->transfer_date->format('d/m/Y'),
                $item->fromAccount->provider_name ?? 'N/A',
                $item->from_location,
                $item->toAccount->provider_name ?? 'N/A',
                $item->to_location,
                $item->amount,
                $item->reference ?: '-',
                $item->memo ?: '-',
                $item->creator->name ?? 'N/A'
            ];
            $sheet->fromArray([$data], NULL, 'A' . $rowNum);
            $totalAmount += $item->amount;
            $rowNum++;
        }

        $totalRow = ['GRAND TOTAL', '', '', '', '', $totalAmount, '', '', ''];
        $sheet->fromArray([$totalRow], NULL, 'A' . $rowNum);
        $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);
        $filename = 'fund_transfers_' . date('Ymd_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view fund transfers') && !auth()->user()->hasPermissionTo('view transfers')) {
            abort(403, 'Unauthorized action.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $query = Transfer::with(['fromAccount.branch', 'fromAccount.warehouse', 'toAccount.branch', 'toAccount.warehouse', 'creator']);

        // Apply same filtering
        if ($restrictedBranchId) {
            $query->where(function($q) use ($restrictedBranchId) {
                $q->whereHas('fromAccount', function($fq) use ($restrictedBranchId) {
                    $fq->where('branch_id', $restrictedBranchId);
                })->orWhereHas('toAccount', function($tq) use ($restrictedBranchId) {
                    $tq->where('branch_id', $restrictedBranchId);
                });
            });
        }

        $this->applyDateFilters($query, $request);
        if ($request->filled('from_account_id')) $query->where('from_financial_account_id', $request->from_account_id);
        if ($request->filled('to_account_id')) $query->where('to_financial_account_id', $request->to_account_id);
        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->where(function($q) use ($branchId) {
                $q->whereHas('fromAccount', function($fq) use ($branchId) { $fq->where('branch_id', $branchId); })
                  ->orWhereHas('toAccount', function($tq) use ($branchId) { $tq->where('branch_id', $branchId); });
            });
        }

        $items = $query->latest('transfer_date')->latest('id')->get();
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $pdf = Pdf::loadView('erp.transfers.export-pdf', compact('items', 'startDate', 'endDate'));
        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download('fund_transfers_' . date('Ymd_His') . '.pdf');
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('create fund transfers') && !auth()->user()->hasPermissionTo('create transfers')) {
            abort(403, 'Unauthorized action.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $user = auth()->user();

        // FROM ACCOUNTS: User's branch accounts only (source)
        $fromAccountsQuery = FinancialAccount::with(['branch', 'warehouse'])
            ->whereNotNull('branch_id') // Only branch accounts
            ->orderBy('type')
            ->orderBy('provider_name');

        if ($restrictedBranchId) {
            // Only show user's branch accounts
            $fromAccountsQuery->where('branch_id', $restrictedBranchId);
        } elseif (!$user->hasRole('Super Admin')) {
            // Non-super admin without branch restriction - show all branch accounts
            // or could restrict further based on requirements
        }

        $fromAccounts = $fromAccountsQuery->get();

        // TO ACCOUNTS: Main/Central accounts, Warehouse accounts, AND Branch Warehouse accounts
        $toAccountsQuery = FinancialAccount::with(['branch', 'warehouse'])
            ->where(function($q) {
                $q->whereNull('branch_id') // Main/central accounts
                  ->orWhereNotNull('warehouse_id') // Warehouse accounts
                  ->orWhereHas('branch', function($bq) {
                      $bq->where('is_warehouse', true);
                  }); // Branch warehouse accounts
            })
            ->orderBy('type')
            ->orderBy('provider_name');

        $toAccounts = $toAccountsQuery->get();

        // Get branches and warehouses for location filter
        if ($restrictedBranchId) {
            $branches = Branch::where('id', $restrictedBranchId)->get();
            $warehouses = Branch::withoutGlobalScope('active')->where('is_warehouse', true)->where('status', 'active')->get();
        } else {
            $branches = Branch::orderBy('name')->get();
            $warehouses = Branch::withoutGlobalScope('active')->where('is_warehouse', true)->where('status', 'active')->get();
        }

        return view('erp.transfers.create', compact('fromAccounts', 'toAccounts', 'branches', 'warehouses'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create fund transfers') && !auth()->user()->hasPermissionTo('create transfers')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'from_financial_account_id' => 'required|exists:financial_accounts,id',
            'to_financial_account_id' => 'required|exists:financial_accounts,id|different:from_financial_account_id',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:500',
        ]);

        $restrictedBranchId = $this->getRestrictedBranchId();
        $user = auth()->user();

        // Security check: Verify user has access to both accounts
        $fromAccount = FinancialAccount::with('branch')->find($request->from_financial_account_id);
        $toAccount = FinancialAccount::with('branch')->find($request->to_financial_account_id);

        // Verify accounts are of correct type
        // FROM must be a branch account (since branches send money to warehouse/center)
        if (!$fromAccount->branch_id) {
            return back()->with('error', 'Source account must be a branch account.');
        }
        // TO must be a main/central account, warehouse account, or branch warehouse account
        $isToBranchWarehouse = $toAccount->branch && $toAccount->branch->is_warehouse;
        if ($toAccount->branch_id && !$toAccount->warehouse_id && !$isToBranchWarehouse) {
            return back()->with('error', 'Destination account must be a main/central account or warehouse account (branch marked as warehouse).');
        }

        if ($restrictedBranchId) {
            // Branch user: Can only transfer FROM their own branch accounts
            // TO account can be any warehouse account (warehouse controls branches)
            if ($fromAccount->branch_id != $restrictedBranchId) {
                abort(403, 'You can only transfer from your own branch accounts.');
            }
        } elseif ($user->warehouse_id) {
            // Warehouse manager: Can only transfer between warehouse accounts
            if ($fromAccount->warehouse_id != $user->warehouse_id || $toAccount->warehouse_id != $user->warehouse_id) {
                abort(403, 'You can only transfer between accounts in your warehouse.');
            }
        }

        // Verify from account has sufficient balance
        if ($fromAccount->current_balance < $request->amount) {
            return back()->with('error', 'Insufficient balance in source account. Available: ' . number_format($fromAccount->current_balance, 2));
        }

        DB::beginTransaction();
        try {
            // Create transfer record
            $transfer = Transfer::create([
                'from_financial_account_id' => $request->from_financial_account_id,
                'to_financial_account_id' => $request->to_financial_account_id,
                'amount' => $request->amount,
                'transfer_date' => $request->transfer_date,
                'reference' => $request->reference,
                'memo' => $request->memo,
                'created_by' => Auth::id(),
            ]);

            // Update account balances
            $fromAccount->balance -= $request->amount;
            $fromAccount->save();

            $toAccount = FinancialAccount::find($request->to_financial_account_id);
            $toAccount->balance += $request->amount;
            $toAccount->save();

            // Create Journal Entry for accounting
            $this->createJournalEntry($transfer, $fromAccount, $toAccount);

            DB::commit();
            return redirect()->route('transfers.index')->with('success', 'Fund transfer completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating transfer: ' . $e->getMessage());
        }
    }

    private function createJournalEntry($transfer, $fromAccount, $toAccount)
    {
        // Find chart of accounts
        $fromChartAccount = ChartOfAccount::find($fromAccount->account_id);
        $toChartAccount = ChartOfAccount::find($toAccount->account_id);

        if (!$fromChartAccount || !$toChartAccount) {
            return; // Skip journal if accounts not found
        }

        // Create Journal
        $voucherNo = 'TRF-' . str_pad($transfer->id, 6, '0', STR_PAD_LEFT);
        while (Journal::where('voucher_no', $voucherNo)->exists()) {
            $voucherNo = 'TRF-' . str_pad($transfer->id, 6, '0', STR_PAD_LEFT) . '-' . rand(10, 99);
        }

        $journal = Journal::create([
            'voucher_no' => $voucherNo,
            'entry_date' => $transfer->transfer_date,
            'branch_id' => $fromAccount->branch_id,
            'type' => 'Transfer',
            'description' => 'Fund Transfer: ' . $fromAccount->provider_name . ' to ' . $toAccount->provider_name,
            'voucher_amount' => $transfer->amount,
            'paid_amount' => $transfer->amount,
            'reference' => $transfer->reference,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // DEBIT: Destination Account (Money coming in)
        JournalEntry::create([
            'journal_id' => $journal->id,
            'chart_of_account_id' => $toChartAccount->id,
            'financial_account_id' => $toAccount->id,
            'debit' => $transfer->amount,
            'credit' => 0,
            'memo' => 'Received from: ' . $fromAccount->provider_name,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // CREDIT: Source Account (Money going out)
        JournalEntry::create([
            'journal_id' => $journal->id,
            'chart_of_account_id' => $fromChartAccount->id,
            'financial_account_id' => $fromAccount->id,
            'debit' => 0,
            'credit' => $transfer->amount,
            'memo' => 'Transferred to: ' . $toAccount->provider_name,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // Update transfer with journal ID
        $transfer->journal_id = $journal->id;
        $transfer->save();
    }

    public function show(Transfer $transfer)
    {
        if (!auth()->user()->hasPermissionTo('view fund transfers') && !auth()->user()->hasPermissionTo('view transfers')) {
            abort(403, 'Unauthorized action.');
        }

        return view('erp.transfers.show', compact('transfer'));
    }

    public function destroy(Transfer $transfer)
    {
        if (!auth()->user()->hasPermissionTo('delete fund transfers') && !auth()->user()->hasPermissionTo('delete transfers')) {
            abort(403, 'Unauthorized action.');
        }

        DB::beginTransaction();
        try {
            // Reverse account balances
            $fromAccount = $transfer->fromAccount;
            $toAccount = $transfer->toAccount;

            $fromAccount->balance += $transfer->amount;
            $fromAccount->save();

            $toAccount->balance -= $transfer->amount;
            $toAccount->save();

            // Delete related journal entries and journal
            if ($transfer->journal) {
                $transfer->journal->entries()->delete();
                $transfer->journal->delete();
            }

            $transfer->delete();

            DB::commit();
            return redirect()->route('transfers.index')->with('success', 'Transfer deleted and amounts reversed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting transfer: ' . $e->getMessage());
        }
    }

    // API endpoint to get accounts by location (branch/warehouse)
    public function getAccountsByLocation(Request $request)
    {
        $query = FinancialAccount::query();
        $restrictedBranchId = $this->getRestrictedBranchId();
        $user = auth()->user();

        // Apply branch restrictions
        if ($restrictedBranchId) {
            $query->where(function($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                  ->orWhereHas('warehouse', function($wq) use ($restrictedBranchId) {
                      $wq->whereHas('branches', function($bq) use ($restrictedBranchId) {
                          $bq->where('id', $restrictedBranchId);
                      });
                  });
            });
        } elseif ($user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        }

        // Additional filters from request
        if ($request->filled('branch_id')) {
            // Only allow filtering by the user's branch if restricted
            if (!$restrictedBranchId || $request->branch_id == $restrictedBranchId) {
                $query->where('branch_id', $request->branch_id);
            }
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $accounts = $query->with(['branch', 'warehouse'])->get()->map(function($account) {
            $location = '';
            if ($account->branch_id) {
                $location = ($account->branch && $account->branch->is_warehouse ? 'Branch Warehouse: ' : 'Branch: ') . ($account->branch->name ?? '');
            } elseif ($account->warehouse_id) {
                $location = 'Warehouse: ' . ($account->warehouse->name ?? '');
            } else {
                $location = 'Main / Central Account';
            }

            return [
                'id' => $account->id,
                'name' => $account->provider_name . ' - ' . $account->account_number,
                'type' => $account->type,
                'balance' => $account->current_balance,
                'location' => $location,
            ];
        });

        return response()->json($accounts);
    }

    private function applyDateFilters($query, Request $request, &$startDate = null, &$endDate = null)
    {
        $reportType = $request->get('report_type_active', 'yearly');

        if ($reportType === 'daily') {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date) : \Carbon\Carbon::today();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date) : \Carbon\Carbon::today();
            $query->whereBetween('transfer_date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
        } elseif ($reportType === 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $query->whereMonth('transfer_date', $month)->whereYear('transfer_date', $year);
        } elseif ($reportType === 'yearly') {
            $year = $request->get('year', date('Y'));
            $query->whereYear('transfer_date', $year);
        }
    }
}
