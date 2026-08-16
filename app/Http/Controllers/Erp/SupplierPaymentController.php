<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierLedger;
use App\Models\PurchaseBill;
use App\Models\FinancialAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Services\PayOnSaleSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view payments')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = SupplierPayment::with('supplier', 'bill.purchase', 'financialAccount');

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->whereHas('bill.purchase', function($q) use ($restrictedBranchId) {
                $q->where('ship_location_type', 'branch')->where('location_id', $restrictedBranchId);
            });
        }

        if ($startDate) {
            $query->whereDate('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        // Payment number filter
        if ($request->filled('payment_no') && $request->payment_no != 'all') {
            $query->where('id', $request->payment_no);
        }

        // Challan/Bill filter
        if ($request->filled('challan_no') && $request->challan_no != 'all') {
            $query->where('purchase_bill_id', $request->challan_no);
        }

        // Supplier filter
        if ($request->filled('supplier_id') && $request->supplier_id != 'all') {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Payment method filter
        if ($request->filled('payment_method') && $request->payment_method != 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->latest()->paginate(20)->appends($request->all());
        
        // Get filter data
        $suppliers = Supplier::orderBy('name')->get();
        
        $paymentQuery = SupplierPayment::select('id', 'reference');
        $billQuery = PurchaseBill::select('id', 'bill_number');
        
        if ($restrictedBranchId) {
            $paymentQuery->whereHas('bill.purchase', function($q) use ($restrictedBranchId) {
                $q->where('ship_location_type', 'branch')->where('location_id', $restrictedBranchId);
            });
            $billQuery->whereHas('purchase', function($q) use ($restrictedBranchId) {
                $q->where('ship_location_type', 'branch')->where('location_id', $restrictedBranchId);
            });
        }
        
        $allPayments = $paymentQuery->get();
        $allBills = $billQuery->get();

        if ($request->ajax()) {
            return view('erp.supplier-payments.partials.table', compact('payments'))->render();
        }

        return view('erp.supplier-payments.index', compact(
            'payments', 'suppliers', 'allPayments', 'allBills', 
            'reportType', 'startDate', 'endDate'
        ));
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view payments')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = SupplierPayment::with('supplier', 'bill.purchase');
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->whereHas('bill.purchase', function($q) use ($restrictedBranchId) {
                $q->where('ship_location_type', 'branch')->where('location_id', $restrictedBranchId);
            });
        }
        if ($startDate) $query->whereDate('payment_date', '>=', $startDate);
        if ($endDate) $query->whereDate('payment_date', '<=', $endDate);
        
        if ($request->filled('payment_no') && $request->payment_no != 'all') $query->where('id', $request->payment_no);
        if ($request->filled('challan_no') && $request->challan_no != 'all') $query->where('purchase_bill_id', $request->challan_no);
        if ($request->filled('supplier_id') && $request->supplier_id != 'all') $query->where('supplier_id', $request->supplier_id);
        if ($request->filled('payment_method') && $request->payment_method != 'all') $query->where('payment_method', $request->payment_method);

        $payments = $query->latest()->get();

        $filename = 'supplier_payments_' . date('Y-m-d_H-i-s') . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Supplier Payment Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $headers = ['Voucher ID', 'Payment Date', 'Supplier', 'Current Balance', 'Bill No', 'Amount', 'Method', 'Recorded By'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . '3', $header);
            $sheet->getStyle(chr(65 + $index) . '3')->getFont()->setBold(true);
        }
        
        $dataRow = 4;
        foreach ($payments as $payment) {
            // Get supplier balance from Balance model
            $supplierBalance = \App\Models\Balance::where('source_type', 'supplier')->where('source_id', $payment->supplier_id)->first();
            $balance = $supplierBalance ? $supplierBalance->balance : 0;
            $balanceText = number_format(abs($balance), 2);
            if ($balance > 0) $balanceText .= ' (DUE)';
            elseif ($balance < 0) $balanceText .= ' (ADV)';

            $sheet->setCellValue('A' . $dataRow, 'SP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT));
            $sheet->setCellValue('B' . $dataRow, $payment->payment_date->format('d-m-Y'));
            $sheet->setCellValue('C' . $dataRow, $payment->supplier?->name ?? '-');
            $sheet->setCellValue('D' . $dataRow, $balanceText);
            $sheet->setCellValue('E' . $dataRow, $payment->bill->bill_number ?? 'Advance');
            $sheet->setCellValue('F' . $dataRow, $payment->amount);
            $sheet->setCellValue('G' . $dataRow, strtoupper($payment->payment_method));
            $sheet->setCellValue('H' . $dataRow, $payment->creator->name ?? 'System');
            $dataRow++;
        }
        
        foreach (range('A', 'H') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $filename);
        $writer->save($filePath);
        
        return response()->download($filePath, $filename)->deleteFileAfterSend();
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view payments')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = SupplierPayment::with('supplier', 'bill.purchase', 'creator');
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->whereHas('bill.purchase', function($q) use ($restrictedBranchId) {
                $q->where('ship_location_type', 'branch')->where('location_id', $restrictedBranchId);
            });
        }
        if ($startDate) $query->whereDate('payment_date', '>=', $startDate);
        if ($endDate) $query->whereDate('payment_date', '<=', $endDate);
        
        if ($request->filled('payment_no') && $request->payment_no != 'all') $query->where('id', $request->payment_no);
        if ($request->filled('challan_no') && $request->challan_no != 'all') $query->where('purchase_bill_id', $request->challan_no);
        if ($request->filled('supplier_id') && $request->supplier_id != 'all') $query->where('supplier_id', $request->supplier_id);
        if ($request->filled('payment_method') && $request->payment_method != 'all') $query->where('payment_method', $request->payment_method);

        $payments = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.supplier-payments.report-pdf', compact('payments', 'startDate', 'endDate'));
        return $pdf->download('supplier_payments_' . date('Y-m-d') . '.pdf');
    }

    public function getSupplierBills($supplierId)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        
        $billQuery = PurchaseBill::where('supplier_id', $supplierId)
            ->where('status', '!=', 'paid')
            ->select('id', 'bill_number', 'due_amount');
            
        if ($restrictedBranchId) {
            $billQuery->whereHas('purchase', function($q) use ($restrictedBranchId) {
                $q->where('ship_location_type', 'branch')->where('location_id', $restrictedBranchId);
            });
        }
        
        return response()->json($billQuery->get());
    }

    public function create(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage payments')) {
            abort(403, 'Unauthorized action.');
        }
        $suppliers = Supplier::all();
        $bankAccounts = FinancialAccount::orderBy('type')->orderBy('provider_name')->get();
        return view('erp.supplier-payments.create', compact('suppliers', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage payments')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Support both single bill (old) and multiple bills (new)
        $hasMultipleBills = $request->has('bills') && is_array($request->bills);
        
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'account_id' => 'required|exists:financial_accounts,id',
            'purchase_bill_id' => 'nullable|exists:purchase_bills,id',
            'reference' => 'nullable|string',
            'note' => 'nullable|string',
            'bills' => 'nullable|array',
            'bills.*.id' => 'nullable|exists:purchase_bills,id',
            'bills.*.amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $financialAccount = FinancialAccount::find($request->account_id);
            if (!$financialAccount) {
                throw new \Exception('Selected financial account not found.');
            }

            // Helper closure to record all related entries for a single payment
            $recordPaymentDetails = function($payment, $financialAccount) {
                // 1. Record in Ledger
                $description = 'Payment via ' . $financialAccount->provider_name;
                if ($payment->reference) {
                    $description .= ' (' . $payment->reference . ')';
                }
                if ($payment->purchase_bill_id && $payment->bill) {
                    $description .= ' - Bill: ' . $payment->bill->bill_number;
                } else {
                    $description .= ' - Advance Payment';
                }

                SupplierLedger::recordTransaction(
                    $payment->supplier_id,
                    'debit',
                    $payment->amount,
                    $description,
                    $payment->payment_date,
                    $payment
                );

                // 2. Update the supplier balance using the Balance model
                if ($payment->supplier_id) {
                    $balance = \App\Models\Balance::where('source_type', 'supplier')->where('source_id', $payment->supplier_id)->first();
                    if ($balance) {
                        $balance->balance -= $payment->amount;
                        $balance->save();
                    } else {
                        \App\Models\Balance::create([
                            'source_type' => 'supplier',
                            'source_id' => $payment->supplier_id,
                            'balance' => -$payment->amount,
                            'description' => 'Supplier Payment',
                        ]);
                    }
                }

                // 3. Update financial account balance
                $financialAccount->balance -= $payment->amount;
                $financialAccount->save();

                // 4. Auto Journal Entry
                $paymentChartAccountId = $financialAccount->account_id;
                $payableChartAccount = ChartOfAccount::where('name', 'like', '%payable%')
                    ->orWhere('name', 'like', '%creditor%')
                    ->first();

                if ($paymentChartAccountId && $payableChartAccount) {
                    $voucherNo = 'PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
                    while (Journal::where('voucher_no', $voucherNo)->exists()) {
                        $voucherNo = 'PAY-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) . '-' . rand(10, 99);
                    }

                    $journal = Journal::create([
                        'voucher_no'     => $voucherNo,
                        'entry_date'     => $payment->payment_date,
                        'type'           => 'Payment',
                        'description'    => 'Auto: Supplier Payment #' . $payment->id . ' to ' . ($payment->supplier?->name ?? 'Supplier'),
                        'supplier_id'    => $payment->supplier_id,
                        'branch_id'      => $payment->bill && $payment->bill->purchase ? ($payment->bill->purchase->location_id) : null,
                        'voucher_amount' => $payment->amount,
                        'paid_amount'    => $payment->amount,
                        'reference'      => $payment->reference,
                        'created_by'     => Auth::id(),
                        'updated_by'     => Auth::id(),
                    ]);

                    // DEBIT: Accounts Payable (Liability decreases)
                    JournalEntry::create([
                        'journal_id'           => $journal->id,
                        'chart_of_account_id'  => $payableChartAccount->id,
                        'financial_account_id' => null,
                        'debit'                => $payment->amount,
                        'credit'               => 0,
                        'memo'                 => 'Payment to ' . ($payment->supplier?->name ?? 'Supplier'),
                        'created_by'           => Auth::id(),
                        'updated_by'           => Auth::id(),
                    ]);

                    // CREDIT: Bank/Cash (Asset decreases)
                    JournalEntry::create([
                        'journal_id'           => $journal->id,
                        'chart_of_account_id'  => $paymentChartAccountId,
                        'financial_account_id' => $financialAccount->id,
                        'debit'                => 0,
                        'credit'               => $payment->amount,
                        'memo'                 => 'Payment via ' . $financialAccount->provider_name,
                        'created_by'           => Auth::id(),
                        'updated_by'           => Auth::id(),
                    ]);
                }
            };

            // Handle multiple bills payment
            if ($hasMultipleBills && count($request->bills) > 0) {
                foreach ($request->bills as $billData) {
                    if (empty($billData['id']) || empty($billData['amount'])) continue;
                    
                    $bill = PurchaseBill::find($billData['id']);
                    if (!$bill) continue;
                    
                    $payAmount = min($billData['amount'], $bill->due_amount);
                    if ($payAmount <= 0) continue;
                    
                    // Create individual payment for each bill
                    $payment = SupplierPayment::create([
                        'supplier_id'      => $request->supplier_id,
                        'purchase_bill_id' => $billData['id'],
                        'amount'           => $payAmount,
                        'payment_date'     => $request->payment_date,
                        'payment_method'   => $request->payment_method,
                        'account_id'       => $request->account_id,
                        'reference'        => $request->reference,
                        'note'             => $request->note,
                        'created_by'       => auth()->id(),
                    ]);
                    
                    // Update bill
                    $bill->paid_amount += $payAmount;
                    $bill->due_amount -= $payAmount;
                    
                    if ($bill->due_amount <= 0) {
                        $bill->status = 'paid';
                        $bill->due_amount = 0;
                    } elseif ($bill->paid_amount > 0) {
                        $bill->status = 'partial';
                    }
                    $bill->save();
                    
                    // Record all details for this payment
                    $recordPaymentDetails($payment, $financialAccount);
                }
            } 
            // Handle single bill payment (backward compatibility) or advance payment
            else {
                $payment = SupplierPayment::create([
                    'supplier_id'      => $request->supplier_id,
                    'purchase_bill_id' => $request->purchase_bill_id,
                    'amount'           => $request->amount,
                    'payment_date'     => $request->payment_date,
                    'payment_method'   => $request->payment_method,
                    'account_id'       => $request->account_id,
                    'reference'        => $request->reference,
                    'note'             => $request->note,
                    'created_by'       => auth()->id(),
                ]);
                
                // Update Purchase Bill if selected
                if ($request->purchase_bill_id) {
                    $bill = PurchaseBill::find($request->purchase_bill_id);
                    $bill->paid_amount += $request->amount;
                    $bill->due_amount -= $request->amount;
                    
                    if ($bill->due_amount <= 0) {
                        $bill->status = 'paid';
                        $bill->due_amount = 0;
                    } elseif ($bill->paid_amount > 0) {
                        $bill->status = 'partial';
                    }
                    $bill->save();
                }
                
                // Record all details for this payment
                $recordPaymentDetails($payment, $financialAccount);
            }

            DB::commit();
            return redirect()->route('supplier-payments.index')->with('success', 'Payment recorded and ledger updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function show(SupplierPayment $supplierPayment)
    {
        if (!auth()->user()->hasPermissionTo('view payments')) {
            abort(403, 'Unauthorized action.');
        }
        return view('erp.supplier-payments.show', compact('supplierPayment'));
    }

    public function destroy(SupplierPayment $supplierPayment)
    {
        if (!auth()->user()->hasPermissionTo('delete payments')) {
            abort(403, 'Unauthorized action.');
        }
        
        DB::beginTransaction();
        try {
            // Need to update bill back
            if ($supplierPayment->purchase_bill_id) {
                $bill = $supplierPayment->bill;
                if ($bill) {
                    $bill->paid_amount -= $supplierPayment->amount;
                    $bill->due_amount += $supplierPayment->amount;
                    if ($bill->paid_amount <= 0) {
                        $bill->status = 'unpaid';
                    } else {
                        $bill->status = 'partial';
                    }
                    $bill->save();
                }
            }

            // Delete ledger entry
            if ($supplierPayment->ledger()) {
                $supplierPayment->ledger()->delete();
            }

            // Reverse the balance update using the Balance model
            if ($supplierPayment->supplier_id) {
                $balance = \App\Models\Balance::where('source_type', 'supplier')->where('source_id', $supplierPayment->supplier_id)->first();
                if ($balance) {
                    $balance->balance += $supplierPayment->amount; // Add back the payment amount
                    $balance->save();
                }
            }
            
            // Reverse the financial account balance update
            if ($supplierPayment->account_id) {
                $financialAccount = FinancialAccount::find($supplierPayment->account_id);
                if ($financialAccount) {
                    $financialAccount->balance += $supplierPayment->amount; // Return payment amount back to bank/cash
                    $financialAccount->save();
                }
            }

            // Delete related journal entries
            $voucherNo = 'PAY-' . str_pad($supplierPayment->id, 6, '0', STR_PAD_LEFT);
            $posVoucherNo = 'POS-PAY-' . str_pad($supplierPayment->id, 6, '0', STR_PAD_LEFT);
            $journal = Journal::where('voucher_no', $voucherNo)
                ->orWhere('voucher_no', 'like', $voucherNo . '-%')
                ->orWhere('voucher_no', $posVoucherNo)
                ->orWhere('voucher_no', 'like', $posVoucherNo . '-%')
                ->first();

            if ($journal) {
                // Delete journal entries
                JournalEntry::where('journal_id', $journal->id)->delete();
                // Delete journal header
                $journal->delete();
            }
            
            $supplierPayment->delete();
            
            DB::commit();
            return redirect()->route('supplier-payments.index')->with('success', 'Payment deleted and ledger updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting payment: ' . $e->getMessage());
        }
    }

    /**
     * Display Pay-on-Sale Settlement View
     */
    public function payOnSale(Request $request, PayOnSaleSettlementService $service)
    {
        if (!auth()->user()->hasPermissionTo('view payments')) {
            abort(403, 'Unauthorized action.');
        }

        $supplierId = $request->get('supplier_id');
        $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : null;

        $suppliers = Supplier::orderBy('name')->get();
        $accounts = FinancialAccount::orderBy('type')->orderBy('provider_name')->get();

        $summary = $service->getSupplierPayOnSaleSummary($supplierId, $startDate, $endDate);

        return view('erp.supplier-payments.pay-on-sale', compact(
            'suppliers',
            'accounts',
            'supplierId',
            'summary'
        ));
    }

    /**
     * AJAX endpoint for Pay-on-Sale summary
     */
    public function getPayOnSaleSummary(Request $request, PayOnSaleSettlementService $service)
    {
        $supplierId = $request->get('supplier_id');
        $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : null;

        $summary = $service->getSupplierPayOnSaleSummary($supplierId, $startDate, $endDate);
        return response()->json($summary);
    }

    /**
     * Store Pay-on-Sale Settlement Payment
     */
    public function storePayOnSale(Request $request, PayOnSaleSettlementService $service)
    {
        if (!auth()->user()->hasPermissionTo('create payments')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'account_id' => 'required|exists:financial_accounts,id',
        ]);

        try {
            $service->processPayOnSaleSettlement(
                $request->supplier_id,
                $request->amount,
                $request->payment_date,
                $request->payment_method,
                $request->account_id,
                $request->reference,
                $request->note,
                auth()->id()
            );

            return redirect()->route('supplier-payments.pay-on-sale', ['supplier_id' => $request->supplier_id])
                ->with('success', 'Pay-on-Sale Settlement payment processed successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error processing Pay-on-Sale settlement: ' . $e->getMessage())->withInput();
        }
    }
}
