<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Mail\SaleConfirmation;
use App\Models\Balance;
use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Customer;
use App\Models\EmployeeProductStock;
use App\Models\GeneralSetting;
use App\Models\Invoice;
use App\Models\InvoiceAddress;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Pos;
use App\Models\PosItem;
use App\Models\Product;
use App\Models\ProductServiceCategory;
use App\Models\InvoiceTemplate;
use App\Models\ProductVariationStock;
use App\Models\WarehouseProductStock;
use App\Models\Brand;
use App\Models\Season;
use App\Models\Gender;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;

class PosController extends Controller
{
    public function addPos()
    {
        if (!auth()->user()->can('use pos')) {
            abort(403, 'Unauthorized action.');
        }
        $categories = ProductServiceCategory::where('status', 'active')->get()->sortBy('full_path_name');
        $branches = Branch::where('status', 'active')->get();

        // Branch Isolation: Check if user is an employee with a branch
        $user = auth()->user();
        if ($user && $user->employee && $user->employee->branch_id) {
            $branches = $branches->where('id', $user->employee->branch_id);
        }

        $bankAccounts = FinancialAccount::all();
        if ($user && $user->employee && $user->employee->branch_id) {
            $bankAccounts = $bankAccounts->where('branch_id', $user->employee->branch_id);
        }

        $shippingMethods = \App\Models\ShippingMethod::orderBy('sort_order')->get();

        $customersQuery = Customer::query();
        if ($user && $user->isBranchRestricted()) {
            $customersQuery->where('branch_id', $user->employee->branch_id);
        }
        $customers = $customersQuery->orderBy('name')->take(200)->get();
        return view('erp.pos.addPos', compact('categories', 'branches', 'bankAccounts', 'shippingMethods', 'customers'));
    }

    public function makeSale(Request $request)
    {
        if (!auth()->user()->can('use pos')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'sale_date' => 'required|date',
            'estimated_delivery_date' => 'nullable|date',
            'estimated_delivery_time' => 'nullable',
            'sub_total' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'delivery' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'account_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'customer_city' => 'nullable|string|required_with:customer_address',
            'customer_state' => 'nullable|string|required_with:customer_address',
            'customer_zip_code' => 'nullable|string|required_with:customer_address',
            'customer_country' => 'nullable|string',
            'sale_type' => 'nullable|string',
            'courier_id' => 'nullable|exists:shipping_methods,id',
            'vat_rate' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Generate unique sale number
            $saleNumber = $this->generateSaleNumber();

            $pos = new Pos();
            $pos->sale_number = $saleNumber;
            $pos->customer_id = $request->customer_id;
            $pos->branch_id = $request->branch_id;
            $pos->sold_by = auth()->id();
            $pos->sale_date = $request->sale_date;
            $pos->sub_total = $request->sub_total;
            $pos->discount = $request->discount ?? 0;
            $pos->delivery = $request->delivery ?? 0;
            $pos->total_amount = $request->total_amount;
            $pos->estimated_delivery_date = $request->estimated_delivery_date;
            $pos->estimated_delivery_time = $request->estimated_delivery_time;
            $pos->status = 'pending'; // or 'pending' if you want manual approval
            $pos->notes = $request->notes;
            $pos->sale_type = $request->sale_type ?? 'MRP';
            $pos->courier_id = $request->courier_id;
            $pos->vat_rate = $request->vat_rate ?? 0;
            $pos->vat_amount = $request->vat_amount ?? 0;
            $pos->save();

            if ($request->customer_type == 'new-customer') {
                $customer = Customer::create([
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'email' => $request->customer_email,
                    'address_1' => $request->customer_address,
                    'city' => $request->customer_city,
                    'state' => $request->customer_state,
                    'zip_code' => $request->customer_zip_code,
                    'country' => $request->customer_country,
                    'created_by' => $pos->sold_by,
                    'branch_id' => $request->branch_id,
                ]);

                $pos->customer_id = $customer->id;
                $pos->save();
            }

            // --- Create Invoice ---

            $invTemplate = InvoiceTemplate::where('is_default', 1)->first();

            // Use submitted tax if provided, otherwise fallback to global setting
            $tax = $pos->vat_amount ?? 0;
            if ($tax <= 0) {
                $generalSettings = GeneralSetting::first();
                $taxRate = $generalSettings ? ($generalSettings->tax_rate / 100) : 0.00;
                $tax = round($pos->sub_total * $taxRate, 2);
            }

            $invoice = new Invoice();
            $invoice->invoice_number = $this->generateInvoiceNumber();
            $invoice->template_id = $invTemplate?->id;
            $invoice->customer_id = $pos->customer_id;
            $invoice->operated_by = $pos->sold_by;
            $invoice->issue_date = now()->toDateString();
            $invoice->due_date = now()->toDateString();
            $invoice->subtotal = $pos->sub_total;
            $invoice->tax = $tax;
            $invoice->total_amount = $pos->total_amount;
            $invoice->discount_apply = $pos->discount;
            $invoice->paid_amount = 0;
            $invoice->due_amount = $pos->total_amount;
            $invoice->status = 'unpaid';
            $invoice->note = $pos->notes;
            $invoice->footer_text = $invTemplate?->footer_note;
            $invoice->created_by = $pos->sold_by;
            $invoice->save();

            $pos->invoice_id = $invoice->id;
            $pos->save();

            // --- End Invoice ---

            // --- Proportional Discount Distribution Logic ---
            $totalInvoiceDiscount = floatval($pos->discount ?? 0);
            $invoiceSubtotal = floatval($pos->sub_total ?? 0);
            $discountRatio = ($invoiceSubtotal > 0) ? ($totalInvoiceDiscount / $invoiceSubtotal) : 0;

            // Save POS items with combo handling
            foreach ($request->items as $index => $item) {
                $product = Product::find($item['product_id']);

                // Calculate this item's share of the total discount
                $itemOriginalTotal = floatval($item['total_price']);
                $allocatedDiscount = round($itemOriginalTotal * $discountRatio, 2);
                $itemNetTotal = $itemOriginalTotal - $allocatedDiscount;

                // Check if this is a combo product
                if ($product && $product->isCombo()) {
                    // Create parent row for combo
                    $parentItem = PosItem::create([
                        'pos_sale_id' => $pos->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'unit_cost' => $this->calculateItemCost($product, $item['variation_id'] ?? null),
                        'total_price' => $itemNetTotal,
                        'current_position_type' => 'branch',
                        'current_position_id' => $request->branch_id,
                        'sort_order' => $index
                    ]);

                    // Create invoice item for parent combo
                    $invoiceItem = InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount' => $allocatedDiscount,
                        'total_price' => $itemNetTotal,
                    ]);

                    // Get combo items and create child rows
                    $comboItems = $product->comboItems()->with(['product', 'variation'])->get();
                    if ($comboItems->isNotEmpty()) {
                        foreach ($comboItems as $comboItem) {
                            // Validate and deduct stock for each combo item
                            $result = $this->deductStock(
                                $comboItem->product_id,
                                $comboItem->variation_id,
                                $comboItem->quantity * $item['quantity'],
                                $request->branch_id
                            );

                            if (!$result['success']) {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'message' => $result['message']
                                ], 400);
                            }

                            // Create child item with 0 price
                            PosItem::create([
                                'parent_item_id' => $parentItem->id,
                                'pos_sale_id' => $pos->id,
                                'product_id' => $comboItem->product_id,
                                'variation_id' => $comboItem->variation_id,
                                'quantity' => $comboItem->quantity * $item['quantity'],
                                'unit_price' => 0,
                                'unit_cost' => $this->calculateItemCost($comboItem->product, $comboItem->variation_id),
                                'total_price' => 0,
                                'current_position_type' => 'branch',
                                'current_position_id' => $request->branch_id,
                                'sort_order' => $index
                            ]);
                        }
                    }
                } else {
                    // Regular product - validate and deduct stock
                    $result = $this->deductStock(
                        $item['product_id'],
                        $item['variation_id'] ?? null,
                        $item['quantity'],
                        $request->branch_id
                    );

                    if (!$result['success']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => $result['message']
                        ], 400);
                    }

                    // Create regular item
                    $createdItem = PosItem::create([
                        'pos_sale_id' => $pos->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'unit_cost' => $this->calculateItemCost($product, $item['variation_id'] ?? null),
                        'total_price' => $itemNetTotal,
                        'current_position_type' => 'branch',
                        'current_position_id' => $request->branch_id,
                        'sort_order' => $index
                    ]);

                    $invoiceItem = InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount' => $allocatedDiscount,
                        'total_price' => $itemNetTotal,
                    ]);
                }
            }

            if ($request->customer_address) {
                InvoiceAddress::create([
                    'invoice_id' => $invoice->id,
                    'billing_address_1' => $request->customer_address,
                    'billing_city' => $request->customer_city,
                    'billing_state' => $request->customer_state,
                    'billing_zip_code' => $request->customer_zip_code,
                    'billing_country' => $request->customer_country,
                    'shipping_address_1' => $request->customer_address,
                    'shipping_city' => $request->customer_city,
                    'shipping_state' => $request->customer_state,
                    'shipping_zip_code' => $request->customer_zip_code,
                    'shipping_country' => $request->customer_country,
                ]);
            }


            // Save payment if paid_amount > 0
            if ($request->paid_amount > 0) {
                $accountId = $request->account_id;
                $finAcc = null;
                if ($accountId) {
                    $finAcc = FinancialAccount::find($accountId);
                } else {
                    $type = $request->payment_method ?? 'cash';
                    $finAcc = FinancialAccount::where('type', $type)->first();
                }

                if ($finAcc) {
                    $finAcc->balance += $request->paid_amount;
                    $finAcc->save();
                }

                Payment::create([
                    'payment_for' => 'pos',
                    'pos_id' => $pos->id,
                    'invoice_id' => $invoice->id,
                    'payment_date' => now()->toDateString(),
                    'amount' => $request->paid_amount,
                    'account_id' => $finAcc ? $finAcc->id : $request->account_id,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'reference' => null,
                    'note' => $request->notes,
                    'customer_id' => $pos->customer_id,
                ]);
                // Update invoice paid/due/status for upfront payment
                $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $request->paid_amount;
                $invoice->due_amount = max(0, ($invoice->total_amount ?? 0) - $invoice->paid_amount);
                if ($invoice->paid_amount >= ($invoice->total_amount ?? 0)) {
                    $invoice->status = 'paid';
                    $invoice->due_amount = 0;
                    // Auto-set POS status to delivered when fully paid
                    $pos->status = 'delivered';
                    // Move items to customer (delivered) - reload items to ensure they're available
                    $pos->load('items');
                    foreach ($pos->items as $item) {
                        $item->current_position_id = null;
                        $item->save();
                    }
                } elseif ($invoice->paid_amount > 0) {
                    $invoice->status = 'partial';
                } else {
                    $invoice->status = 'unpaid';
                }
                $invoice->save();
                $pos->save(); // Save the status change

                if ($pos->customer_id) {
                    Balance::create([
                        'source_type' => 'customer',
                        'source_id' => $pos->customer_id,
                        'balance' => $pos->total_amount - $request->paid_amount,
                        'description' => 'POS Sale',
                        'reference' => $pos->sale_number,
                    ]);
                }
            } else {
                if ($pos->customer_id) {
                    Balance::create([
                        'source_type' => 'customer',
                        'source_id' => $pos->customer_id,
                        'balance' => $pos->total_amount,
                        'description' => 'POS Sale',
                        'reference' => $pos->sale_number,
                    ]);
                }
            }

            // =====================================================
            // AUTO JOURNAL ENTRY (Double-Entry Accounting)
            // =====================================================

            // 1. Ensure Sales Account exists
            $salesAccount = ChartOfAccount::where('name', 'like', '%Sales%')->first();
            if (!$salesAccount) {
                $revenueType = \App\Models\ChartOfAccountType::where('name', 'Revenue')->first() ?? \App\Models\ChartOfAccountType::find(4);
                $revenueSubType = \App\Models\ChartOfAccountSubType::where('type_id', $revenueType->id)->first();
                if (!$revenueSubType) {
                    $revenueSubType = \App\Models\ChartOfAccountSubType::create(['name' => 'Sales Revenue', 'type_id' => $revenueType->id]);
                }
                $revenueParent = \App\Models\ChartOfAccountParent::where('type_id', $revenueType->id)->first();
                if (!$revenueParent) {
                    $revenueParent = \App\Models\ChartOfAccountParent::create([
                        'name' => 'Operating Revenue',
                        'type_id' => $revenueType->id,
                        'sub_type_id' => $revenueSubType->id,
                        'code' => '4000',
                        'created_by' => auth()->id()
                    ]);
                }

                $salesAccount = ChartOfAccount::create([
                    'name' => 'Product Sales',
                    'type_id' => $revenueType->id,
                    'sub_type_id' => $revenueSubType->id,
                    'parent_id' => $revenueParent->id,
                    'code' => '40001',
                    'status' => 'active',
                    'created_by' => auth()->id()
                ]);
            }

            $voucherNo = 'SAL-' . str_pad($pos->id, 6, '0', STR_PAD_LEFT);
            while (Journal::where('voucher_no', $voucherNo)->exists()) {
                $voucherNo = 'SAL-' . str_pad($pos->id, 6, '0', STR_PAD_LEFT) . '-' . rand(10, 99);
            }

            $journal = Journal::create([
                'voucher_no' => $voucherNo,
                'entry_date' => $pos->sale_date,
                'type' => 'Receipt',
                'description' => 'POS Sale #' . $pos->sale_number,
                'customer_id' => $pos->customer_id,
                'branch_id' => $pos->branch_id,
                'voucher_amount' => $pos->total_amount,
                'paid_amount' => $request->paid_amount,
                'reference' => $pos->sale_number,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // CREDIT Sales (Net Amount - Revenue increases)
            $netSalesAmount = $pos->total_amount - $pos->vat_amount - $pos->delivery;
            JournalEntry::create([
                'journal_id' => $journal->id,
                'chart_of_account_id' => $salesAccount->id,
                'debit' => 0,
                'credit' => $netSalesAmount,
                'memo' => 'Revenue from POS Sale (Net of Tax & Delivery)',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // CREDIT Delivery Expense (reduce expense)
            if ($pos->delivery > 0) {
                $deliveryExpenseAccount = ChartOfAccount::where('name', 'like', '%Delivery Expense%')->orWhere('name', 'like', '%Courier Expense%')->first();
                if (!$deliveryExpenseAccount) {
                    $expenseType = \App\Models\ChartOfAccountType::where('name', 'Expense')->first() ?? \App\Models\ChartOfAccountType::find(5);
                    $expenseSubType = \App\Models\ChartOfAccountSubType::where('type_id', $expenseType->id)->where('name', 'like', '%Operating%')->first() 
                        ?? \App\Models\ChartOfAccountSubType::where('type_id', $expenseType->id)->first();
                    if (!$expenseSubType) {
                        $expenseSubType = \App\Models\ChartOfAccountSubType::create(['name' => 'Operating Expenses', 'type_id' => $expenseType->id]);
                    }
                    $expenseParent = \App\Models\ChartOfAccountParent::where('type_id', $expenseType->id)->first();
                    if (!$expenseParent) {
                        $expenseParent = \App\Models\ChartOfAccountParent::create([
                            'name' => 'Operating Expenses Parent',
                            'type_id' => $expenseType->id,
                            'sub_type_id' => $expenseSubType->id,
                            'code' => '5000',
                            'created_by' => auth()->id()
                        ]);
                    }

                    $deliveryExpenseAccount = ChartOfAccount::create([
                        'name' => 'Delivery Expense',
                        'type_id' => $expenseType->id,
                        'sub_type_id' => $expenseSubType->id,
                        'parent_id' => $expenseParent->id,
                        'code' => '50005',
                        'status' => 'active',
                        'created_by' => auth()->id()
                    ]);
                }

                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $deliveryExpenseAccount->id,
                    'debit' => 0,
                    'credit' => $pos->delivery,
                    'memo' => 'Delivery charge collected from customer',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // CREDIT VAT Payable (Tax Amount - Liability increases)
            if ($pos->vat_amount > 0) {
                $vatAccount = ChartOfAccount::where('name', 'like', '%VAT%')->orWhere('name', 'like', '%Tax Payable%')->first();
                if (!$vatAccount) {
                    $liabType = \App\Models\ChartOfAccountType::where('name', 'Liability')->first() ?? \App\Models\ChartOfAccountType::find(2);
                    $liabSubType = \App\Models\ChartOfAccountSubType::where('type_id', $liabType->id)->first();
                    if (!$liabSubType) {
                        $liabSubType = \App\Models\ChartOfAccountSubType::create(['name' => 'Current Liabilities', 'type_id' => $liabType->id]);
                    }
                    $liabParent = \App\Models\ChartOfAccountParent::where('type_id', $liabType->id)->first();
                    if (!$liabParent) {
                        $liabParent = \App\Models\ChartOfAccountParent::create([
                            'name' => 'Tax Liabilities',
                            'type_id' => $liabType->id,
                            'sub_type_id' => $liabSubType->id,
                            'code' => '2000',
                            'created_by' => auth()->id()
                        ]);
                    }
                    $vatAccount = ChartOfAccount::create([
                        'name' => 'VAT Payable',
                        'type_id' => $liabType->id,
                        'sub_type_id' => $liabSubType->id,
                        'parent_id' => $liabParent->id,
                        'code' => '20001',
                        'status' => 'active',
                        'created_by' => auth()->id()
                    ]);
                }

                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $vatAccount->id,
                    'debit' => 0,
                    'credit' => $pos->vat_amount,
                    'memo' => 'VAT collected from POS Sale',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // DEBIT Cash/Bank (Paid Amount - Asset increases)
            if ($request->paid_amount > 0) {
                $financialAccount = null;
                if ($request->account_id) {
                    $financialAccount = FinancialAccount::find($request->account_id);
                } else {
                    $type = $request->payment_method ?? 'cash';
                    $financialAccount = FinancialAccount::where('type', $type)->first();
                }

                if ($financialAccount && $financialAccount->account_id) {
                    JournalEntry::create([
                        'journal_id' => $journal->id,
                        'chart_of_account_id' => $financialAccount->account_id,
                        'financial_account_id' => $financialAccount->id,
                        'debit' => $request->paid_amount,
                        'credit' => 0,
                        'memo' => 'Payment received via ' . ($financialAccount->provider_name ?? 'Cash/Bank'),
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            // DEBIT Accounts Receivable (Due Amount - Asset increases)
            $dueAmount = $pos->total_amount - $request->paid_amount;
            if ($dueAmount > 0) {
                $arAccount = ChartOfAccount::where('name', 'like', '%Receivable%')->first();
                if (!$arAccount) {
                    $assetType = \App\Models\ChartOfAccountType::where('name', 'Asset')->first() ?? \App\Models\ChartOfAccountType::find(1);
                    $assetSubType = \App\Models\ChartOfAccountSubType::where('type_id', $assetType->id)->first();
                    if (!$assetSubType) {
                        $assetSubType = \App\Models\ChartOfAccountSubType::create(['name' => 'Current Assets', 'type_id' => $assetType->id]);
                    }
                    $assetParent = \App\Models\ChartOfAccountParent::where('type_id', $assetType->id)->first();
                    if (!$assetParent) {
                        $assetParent = \App\Models\ChartOfAccountParent::create([
                            'name' => 'Accounts Receivable Parent',
                            'type_id' => $assetType->id,
                            'sub_type_id' => $assetSubType->id,
                            'code' => '1000',
                            'created_by' => auth()->id()
                        ]);
                    }

                    $arAccount = ChartOfAccount::create([
                        'name' => 'Accounts Receivable',
                        'type_id' => $assetType->id,
                        'sub_type_id' => $assetSubType->id,
                        'parent_id' => $assetParent->id,
                        'code' => '10002',
                        'status' => 'active',
                        'created_by' => auth()->id()
                    ]);
                }
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $arAccount->id,
                    'debit' => $dueAmount,
                    'credit' => 0,
                    'memo' => 'Due amount from customer',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
            // =====================================================

            \App\Http\Controllers\Erp\DashboardController::clearCache();
            DB::commit();

            // Send Sale Confirmation Email
            try {
                if ($pos->customer && $pos->customer->email) {
                    Mail::to($pos->customer->email)->send(new SaleConfirmation($pos));
                }
            } catch (\Exception $e) {
                // swallow
            }

            return response()->json(['success' => true, 'message' => 'Sale created successfully.', 'sale_id' => $pos->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        if (!auth()->user()->can('view sales')) {
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

        // Optimized query with specific columns
        $query = \App\Models\PosItem::select('pos_items.*')
            ->whereNull('pos_items.parent_item_id')
            ->with([
                'pos:id,sale_number,original_pos_id,customer_id,branch_id,sold_by,sale_date,delivery,discount,vat_amount,total_amount,exchange_amount,refund_amount,invoice_id,status',
                'pos.originalPos:id,sale_number',
                'pos.customer:id,name',
                'pos.invoice:id,total_amount,paid_amount,due_amount',
                'pos.branch:id,name',
                'pos.soldBy:id,first_name,last_name',
                'pos.items:id,pos_sale_id,product_id,quantity,unit_price,total_price,parent_item_id,sort_order',
                'pos.items.product:id,type',
                'pos.items.returnItems:id,sale_item_id,sale_return_id,returned_qty,total_price',
                'pos.items.returnItems.saleReturn:id,refund_type,status',
                'product:id,name,sku,style_number,category_id,brand_id,season_id,gender_id,image,type',
                'product.category:id,name',
                'product.brand:id,name',
                'product.season:id,name',
                'product.gender:id,name',
                'variation.attributeValues.attribute',
                'childItems.product:id,name',
                'childItems.variation.attributeValues',
                'returnItems:id,sale_item_id,sale_return_id,returned_qty,total_price',
                'returnItems.saleReturn:id,refund_type,status'
            ]);

        $query = $this->applyFilters($query, $request, $startDate, $endDate);

        // Comprehensive Big Data Optimization for ALL Totals
        $itemTotals = \DB::table(\DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query->getQuery())
            ->selectRaw("
                SUM(quantity) as total_qty, 
                SUM(quantity * unit_price) as gross_amount,
                SUM(total_price) as total_amount,
                SUM((quantity * unit_price) - total_price) as item_discount
            ")
            ->first();

        // 2. Sale-level totals (Delivery, Discount, Grand Total, Paid, Due)
        // We use a subquery to get unique POS IDs from the filtered items
        $filteredPosIds = clone $query;
        $saleTotals = \DB::table('pos')
            ->join('invoices', 'pos.invoice_id', '=', 'invoices.id')
            ->whereIn('pos.id', $filteredPosIds->select('pos_sale_id')->distinct())
            ->selectRaw("
                SUM(pos.delivery) as total_delivery,
                SUM(pos.discount) as total_discount,
                SUM(pos.vat_amount) as total_vat,
                SUM(pos.exchange_amount) as total_exchange,
                SUM(pos.refund_amount) as total_refund,
                SUM(invoices.total_amount) as final_total,
                SUM(LEAST(invoices.paid_amount, invoices.total_amount)) as total_paid,
                SUM(GREATEST(0, invoices.total_amount - LEAST(invoices.paid_amount, invoices.total_amount))) as total_due
            ")
            ->first();

        // Query return totals for the filtered items
        $filteredItemIds = clone $query;
        $returnTotals = \DB::table('sale_return_items')
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->whereIn('sale_return_items.sale_item_id', $filteredItemIds->select('pos_items.id'))
            ->selectRaw("
                SUM(CASE WHEN sale_returns.refund_type != 'exchange' THEN sale_return_items.returned_qty ELSE 0 END) as reg_ret_qty,
                SUM(CASE WHEN sale_returns.refund_type = 'exchange' THEN sale_return_items.returned_qty ELSE 0 END) as exch_ret_qty
            ")
            ->first();

        $filteredPosIdsForExchange = clone $query;
        $exchangeNewTotals = \DB::table('pos_exchange_items')
            ->join('pos_exchanges', 'pos_exchange_items.pos_exchange_id', '=', 'pos_exchanges.id')
            ->whereIn('pos_exchanges.original_pos_id', $filteredPosIdsForExchange->select('pos_items.pos_sale_id')->distinct())
            ->where('pos_exchange_items.type', 'new')
            ->where('pos_exchanges.status', 'completed')
            ->sum('pos_exchange_items.quantity');

        $totalQty = $itemTotals->total_qty ?? 0;
        $totalAmount = $itemTotals->total_amount ?? 0;

        // Accurate Physical Piece Totals for Single and Combo items
        $filteredItemIdsForChild = clone $query;
        $childQtySum = (float) \DB::table('pos_items')
            ->whereIn('parent_item_id', $filteredItemIdsForChild->select('pos_items.id'))
            ->sum('quantity');

        $filteredItemIdsForCombo = clone $query;
        $comboParentQty = (float) \DB::table('pos_items')
            ->join('products', 'pos_items.product_id', '=', 'products.id')
            ->whereIn('pos_items.id', $filteredItemIdsForCombo->select('pos_items.id'))
            ->where('products.type', 'combo')
            ->sum('pos_items.quantity');

        $singleParentQty = max(0, $totalQty - $comboParentQty);
        $totalPhysicalQty = $singleParentQty + $childQtySum;
        $actPhysicalQty = $totalPhysicalQty - ($returnTotals->reg_ret_qty ?? 0) - ($returnTotals->exch_ret_qty ?? 0) + $exchangeNewTotals;

        // Pass all totals to the view
        $reportTotals = [
            'sell_qty' => $totalQty,
            'combo_qty' => $comboParentQty,
            'single_qty' => $singleParentQty,
            'combo_child_qty' => $childQtySum,
            'total_physical_qty' => $totalPhysicalQty,
            'act_physical_qty' => $actPhysicalQty,
            'gross_amt' => $itemTotals->gross_amount ?? 0,
            'sell_amt' => $totalAmount,
            'delivery' => $saleTotals->total_delivery ?? 0,
            'discount' => $saleTotals->total_discount ?? 0,
            'vat_amt' => $saleTotals->total_vat ?? 0,
            'exchange' => $saleTotals->total_exchange ?? 0,
            'refund' => $saleTotals->total_refund ?? 0,
            'final_total' => $saleTotals->final_total ?? 0,
            'paid' => $saleTotals->total_paid ?? 0,
            'due' => $saleTotals->total_due ?? 0,
            'reg_ret_qty' => $returnTotals->reg_ret_qty ?? 0,
            'exch_ret_qty' => $returnTotals->exch_ret_qty ?? 0,
            'exch_new_qty' => $exchangeNewTotals,
            'act_qty' => $totalQty - ($returnTotals->reg_ret_qty ?? 0) - ($returnTotals->exch_ret_qty ?? 0) + $exchangeNewTotals,
        ];

        $items = $query->orderBy('pos_items.pos_sale_id', 'desc')->orderBy('pos_items.sort_order')->paginate(500)->appends($request->all());

        // Big Data Dropdown Optimization: Only load top 100 for initial view
        $restrictedBranchId = $this->getRestrictedBranchId();
        $branches = $restrictedBranchId ? Branch::where('id', $restrictedBranchId)->get() : Branch::all();

        $customersQuery = Customer::query();
        if ($restrictedBranchId) {
            $customersQuery->where('branch_id', $restrictedBranchId);
        }
        $customers = $customersQuery->orderBy('name')->limit(100)->get();
        $categories = \App\Models\ProductServiceCategory::whereNull('parent_id')->orderBy('name')->get();
        $brands = \App\Models\Brand::orderBy('name')->get();
        $seasons = \App\Models\Season::orderBy('name')->get();
        $genders = \App\Models\Gender::orderBy('name')->get();
        $products = \App\Models\Product::whereIn('type', ['product', 'combo'])
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(100)
            ->get();

        // Mark combo products
        foreach ($products as $product) {
            if ($product->type === 'combo') {
                $product->is_combo = true;
                $product->combo_items_count = $product->comboItems->count();
            }
        }

        if ($request->ajax()) {
            return view('erp.pos.partials.table', compact('items', 'reportTotals'));
        }

        return view('erp.pos.index', compact(
            'items',
            'branches',
            'customers',
            'categories',
            'brands',
            'seasons',
            'genders',
            'products',
            'reportType',
            'startDate',
            'endDate',
            'reportTotals'
        ));
    }

    private function applyFilters($query, Request $request, $startDate = null, $endDate = null)
    {
        // Date Filtering
        if ($startDate && $endDate) {
            $query->whereHas('pos', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('sale_date', [$startDate, $endDate]);
            });
        } elseif ($startDate) {
            $query->whereHas('pos', function ($q) use ($startDate) {
                $q->whereDate('sale_date', '>=', $startDate);
            });
        } elseif ($endDate) {
            $query->whereHas('pos', function ($q) use ($endDate) {
                $q->whereDate('sale_date', '<=', $endDate);
            });
        }

        // Search by sale number / invoice / customer / product / salesperson
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('pos', function ($pq) use ($search) {
                    $pq->where('sale_number', 'LIKE', "%$search%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'LIKE', "%$search%")
                                ->orWhere('phone', 'LIKE', "%$search%");
                        })
                        ->orWhereHas('soldBy', function ($sq) use ($search) {
                            $sq->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%");
                        });
                })
                    ->orWhereHas('product', function ($prq) use ($search) {
                        $prq->where('name', 'LIKE', "%$search%")
                            ->orWhere('style_number', 'LIKE', "%$search%")
                            ->orWhere('sku', 'LIKE', "%$search%");
                    });
            });
        }

        // Filters from dropdowns
        $restrictedBranchId = $this->getRestrictedBranchId();
        $selectedBranchId = $restrictedBranchId ?: $request->branch_id;

        if ($selectedBranchId) {
            $query->whereHas('pos', function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            });
        }
        if ($request->filled('customer_id')) {
            $query->whereHas('pos', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }
        if ($request->filled('status')) {
            $query->whereHas('pos', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        if ($request->filled('payment_status')) {
            $status = $request->payment_status;
            $query->whereHas('pos.invoice', function ($q) use ($status) {
                if ($status == 'due') {
                    $q->whereIn('status', ['partial', 'unpaid']);
                } else {
                    $q->where('status', $status);
                }
            });
        }

        // Filter by Product Type (Single / Combo)
        if ($request->filled('product_type')) {
            if ($request->product_type === 'combo') {
                $query->whereHas('product', function ($q) {
                    $q->where('type', 'combo');
                });
            } elseif ($request->product_type === 'single') {
                $query->whereHas('product', function ($q) {
                    $q->where('type', '!=', 'combo');
                });
            }
        }

        // Filter by Product/Style/Category/Brand/Season/Gender
        if ($request->filled('product_id'))
            $query->where('pos_items.product_id', $request->product_id);

        if (
            $request->filled('style_number') || $request->filled('category_id') ||
            $request->filled('brand_id') || $request->filled('season_id') || $request->filled('gender_id')
        ) {

            $query->whereHas('product', function ($q) use ($request) {
                if ($request->filled('style_number')) {
                    $q->where(function ($subQ) use ($request) {
                        $subQ->where('style_number', 'like', '%' . $request->style_number . '%')
                             ->orWhere('sku', 'like', '%' . $request->style_number . '%');
                    });
                }
                if ($request->filled('category_id'))
                    $q->where('category_id', $request->category_id);
                if ($request->filled('brand_id'))
                    $q->where('brand_id', $request->brand_id);
                if ($request->filled('season_id'))
                    $q->where('season_id', $request->season_id);
                if ($request->filled('gender_id'))
                    $q->where('gender_id', $request->gender_id);
            });
        }

        return $query;
    }

    public function show($id)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        $pos = Pos::where('id', $id)
            ->with([
                'customer',
                'invoice',
                'invoice.invoiceAddress',
                'branch',
                'soldBy',
                'items.product',
                'items.variation.attributeValues.attribute',
                'items.branch',
                'items.technician.user',
                'payments'
            ])
            ->first();

        if (!$pos) {
            return redirect()->route('pos.list')->with('error', 'Sale not found.');
        }

        $bankAccounts = collect(); // Empty collection since FinancialAccount model was removed
        return view('erp.pos.show', compact('pos', 'bankAccounts'));
    }

    /**
     * Get POS sale details as JSON (for API/AJAX calls)
     */
    public function getDetails($id)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        $pos = Pos::where('id', $id)
            ->with([
                'customer',
                'invoice',
                'branch',
                'items.product',
                'items.variation'
            ])
            ->first();

        if (!$pos) {
            return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pos->id,
                'sale_number' => $pos->sale_number,
                'customer_id' => $pos->customer_id,
                'customer_name' => $pos->customer ? $pos->customer->name : null,
                'branch_id' => $pos->branch_id,
                'branch_name' => $pos->branch ? $pos->branch->name : null,
                'invoice_id' => $pos->invoice_id,
                'items' => $pos->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product ? $item->product->name : null,
                        'variation_id' => $item->variation_id,
                        'variation_name' => $item->variation ? $item->variation->name : null,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                    ];
                })
            ]
        ]);
    }

    public function edit($id)
    {
        if (!auth()->user()->can('edit sales')) {
            abort(403, 'Unauthorized action.');
        }

        $pos = Pos::with(['customer', 'branch', 'items.product', 'items.variation.attributeValues.attribute'])->findOrFail($id);

        // Only allow editing if status is pending or delivered (not cancelled)
        if ($pos->status === 'cancelled') {
            return redirect()->route('pos.show', $id)->with('error', 'Cannot edit a cancelled sale.');
        }

        $categories = ProductServiceCategory::all();
        $branches = Branch::all();
        $customers = Customer::all();
        $bankAccounts = FinancialAccount::all();
        $shippingMethods = ShippingMethod::orderBy('sort_order')->get();

        return view('erp.pos.edit', compact('pos', 'categories', 'branches', 'customers', 'bankAccounts', 'shippingMethods'));
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->can('delete sales')) {
            abort(403, 'Unauthorized action.');
        }

        $pos = Pos::with(['items', 'invoice.items', 'invoice.payments', 'payments'])->findOrFail($id);

        // Check if there are any returns associated with this POS sale
        $hasReturns = \App\Models\SaleReturn::where('pos_sale_id', $pos->id)->exists();
        if ($hasReturns) {
            return redirect()->back()->with('error', 'Cannot delete this sale because it has associated returns. Please delete the return records first.');
        }

        DB::beginTransaction();
        try {
            // 1. Restore stock for all sold items
            foreach ($pos->items as $item) {
                if ($item->parent_item_id === null) {
                    // Only restore for root items (combo parent or regular)
                    $this->restoreStock(
                        $item->product_id,
                        $item->variation_id,
                        $item->quantity,
                        $pos->branch_id
                    );
                }
            }

            // 2. Reverse FinancialAccount balance for each payment
            foreach ($pos->payments as $payment) {
                if ($payment->account_id && $payment->amount > 0) {
                    $finAcc = FinancialAccount::find($payment->account_id);
                    if ($finAcc) {
                        $finAcc->balance -= $payment->amount;
                        if ($finAcc->balance < 0)
                            $finAcc->balance = 0;
                        $finAcc->save();
                    }
                }
            }

            // 3. Remove/reverse Customer Balance entries linked to this sale
            if ($pos->customer_id) {
                \App\Models\Balance::where('source_type', 'customer')
                    ->where('source_id', $pos->customer_id)
                    ->where('reference', $pos->sale_number)
                    ->delete();
            }

            // 4. Delete related Journal entries (Double-Entry Accounting)
            $voucherNo = 'SAL-' . str_pad($pos->id, 6, '0', STR_PAD_LEFT);
            $journal = Journal::where('voucher_no', 'like', $voucherNo . '%')
                ->orWhere('reference', $pos->sale_number)
                ->first();
            if ($journal) {
                $journal->entries()->delete();
                $journal->delete();
            }
            // Also handle manual sale journal
            $manualVoucherNo = 'SAL-M-' . str_pad($pos->id, 6, '0', STR_PAD_LEFT);
            $manualJournal = Journal::where('voucher_no', $manualVoucherNo)->first();
            if ($manualJournal) {
                $manualJournal->entries()->delete();
                $manualJournal->delete();
            }

            // 5. Delete invoice items, invoice payments, and the invoice itself
            if ($pos->invoice) {
                $pos->invoice->items()->delete();
                $pos->invoice->payments()->delete();
                $pos->invoice->delete();
            }

            // 6. Delete POS payments and items, then the POS record
            $pos->payments()->delete();
            $pos->items()->delete();
            $pos->delete();

            DB::commit();
            return redirect()->route('pos.list')->with('success', 'Sale #' . $pos->sale_number . ' deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete sale: ' . $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request)
    {
        if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->can('delete sales')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No sales selected.']);
        }

        // Check if any of the selected sales has associated returns
        $hasReturns = \App\Models\SaleReturn::whereIn('pos_sale_id', $ids)->exists();
        if ($hasReturns) {
            return response()->json(['success' => false, 'message' => 'One or more selected sales have associated returns. Please delete the returns first.']);
        }

        DB::beginTransaction();
        try {
            $sales = Pos::whereIn('id', $ids)->with(['items', 'invoice.items', 'invoice.payments', 'payments'])->get();
            foreach ($sales as $pos) {
                // 1. Restore stock for all sold items
                foreach ($pos->items as $item) {
                    if ($item->parent_item_id === null) {
                        $this->restoreStock(
                            $item->product_id,
                            $item->variation_id,
                            $item->quantity,
                            $pos->branch_id
                        );
                    }
                }

                // 2. Reverse FinancialAccount balance for each payment
                foreach ($pos->payments as $payment) {
                    if ($payment->account_id && $payment->amount > 0) {
                        $finAcc = FinancialAccount::find($payment->account_id);
                        if ($finAcc) {
                            $finAcc->balance -= $payment->amount;
                            if ($finAcc->balance < 0)
                                $finAcc->balance = 0;
                            $finAcc->save();
                        }
                    }
                }

                // 3. Remove Customer Balance entries linked to this sale
                if ($pos->customer_id) {
                    \App\Models\Balance::where('source_type', 'customer')
                        ->where('source_id', $pos->customer_id)
                        ->where('reference', $pos->sale_number)
                        ->delete();
                }

                // 4. Delete related Journal entries
                $voucherNo = 'SAL-' . str_pad($pos->id, 6, '0', STR_PAD_LEFT);
                $journal = Journal::where('voucher_no', 'like', $voucherNo . '%')
                    ->orWhere('reference', $pos->sale_number)
                    ->first();
                if ($journal) {
                    $journal->entries()->delete();
                    $journal->delete();
                }
                $manualVoucherNo = 'SAL-M-' . str_pad($pos->id, 6, '0', STR_PAD_LEFT);
                $manualJournal = Journal::where('voucher_no', $manualVoucherNo)->first();
                if ($manualJournal) {
                    $manualJournal->entries()->delete();
                    $manualJournal->delete();
                }

                // 5. Delete invoice
                if ($pos->invoice) {
                    $pos->invoice->items()->delete();
                    $pos->invoice->payments()->delete();
                    $pos->invoice->delete();
                }

                // 6. Delete POS payments, items, then the POS record
                $pos->payments()->delete();
                $pos->items()->delete();
                $pos->delete();
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Selected sales deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete sales: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit sales')) {
            abort(403, 'Unauthorized action.');
        }
        $pos = Pos::with(['items', 'invoice'])->findOrFail($id);

        // Only allow editing if status is pending or delivered (not cancelled)
        if ($pos->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Cannot edit a cancelled sale.'], 400);
        }

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'sale_date' => 'required|date',
            'estimated_delivery_date' => 'nullable|date',
            'estimated_delivery_time' => 'nullable',
            'sub_total' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'delivery' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'courier_id' => 'nullable|exists:shipping_methods,id',
            'vat_rate' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Store old items for stock restoration
            $oldItems = $pos->items;

            // Restore stock from old items
            foreach ($oldItems as $oldItem) {
                $this->restoreStock(
                    $oldItem->product_id,
                    $oldItem->variation_id,
                    $oldItem->quantity,
                    $pos->branch_id
                );
            }

            // Update POS sale
            $pos->customer_id = $request->customer_id;
            $pos->branch_id = $request->branch_id;
            $pos->sale_date = $request->sale_date;
            $pos->sub_total = $request->sub_total;
            $pos->discount = $request->discount ?? 0;
            $pos->delivery = $request->delivery ?? 0;
            $pos->total_amount = $request->total_amount;
            $pos->estimated_delivery_date = $request->estimated_delivery_date;
            $pos->estimated_delivery_time = $request->estimated_delivery_time;
            $pos->notes = $request->notes;
            $pos->courier_id = $request->courier_id;
            $pos->vat_rate = $request->vat_rate ?? 0;
            $pos->vat_amount = $request->vat_amount ?? 0;
            $pos->save();

            // Delete old items
            $pos->items()->delete();

            // --- Proportional Discount Distribution Logic ---
            $totalInvoiceDiscount = floatval($pos->discount ?? 0);
            $invoiceSubtotal = floatval($pos->sub_total ?? 0);
            $discountRatio = ($invoiceSubtotal > 0) ? ($totalInvoiceDiscount / $invoiceSubtotal) : 0;

            // Add new items and deduct stock
            foreach ($request->items as $item) {
                // Validate and deduct stock
                $stockResult = $this->deductStock(
                    $item['product_id'],
                    $item['variation_id'] ?? null,
                    $item['quantity'],
                    $request->branch_id
                );

                if (!$stockResult['success']) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => $stockResult['message']], 400);
                }

                // Calculate this item's share of the total discount
                $itemOriginalTotal = floatval($item['quantity'] * $item['unit_price']);
                $allocatedDiscount = round($itemOriginalTotal * $discountRatio, 2);
                $itemNetTotal = $itemOriginalTotal - $allocatedDiscount;

                // Create POS item
                $product = Product::find($item['product_id']);
                $posItem = new PosItem();
                $posItem->pos_sale_id = $pos->id;
                $posItem->product_id = $item['product_id'];
                $posItem->variation_id = $item['variation_id'] ?? null;
                $posItem->quantity = $item['quantity'];
                $posItem->unit_price = $item['unit_price'];
                $posItem->unit_cost = $this->calculateItemCost($product, $item['variation_id'] ?? null);
                $posItem->total_price = $itemNetTotal;
                $posItem->current_position_type = 'branch';
                $posItem->current_position_id = $request->branch_id;
                $posItem->save();
            }

            // Update invoice if exists
            if ($pos->invoice) {
                $invoice = $pos->invoice; // assign the related invoice model
                $tax = $pos->vat_amount;

                // Handle financial transaction for payment increase
                $oldPaidAmount = $invoice->getOriginal('paid_amount') ?? 0;
                $newPaidAmount = $request->paid_amount ?? $oldPaidAmount;
                $paymentDifference = $newPaidAmount - $oldPaidAmount;

                if ($paymentDifference > 0 && $request->account_id) {
                    $account = FinancialAccount::find($request->account_id);
                    if ($account) {
                        $account->balance += $paymentDifference;
                        $account->save();
                    }
                }

                $invoice->tax = $tax;
                $invoice->subtotal = $pos->sub_total;
                $invoice->discount_apply = $pos->discount;
                $invoice->total_amount = $pos->total_amount;

                $invoice->paid_amount = $newPaidAmount;
                $invoice->due_amount = max(0, $invoice->total_amount - $invoice->paid_amount);

                // Update invoice status based on payment
                if ($invoice->paid_amount >= $invoice->total_amount) {
                    $invoice->status = 'paid';
                    $invoice->due_amount = 0;
                } elseif ($invoice->paid_amount > 0) {
                    $invoice->status = 'partial';
                } else {
                    $invoice->status = 'unpaid';
                }
                $invoice->save();

                // Delete old invoice items
                $invoice->items()->delete();

                // Create new invoice items
                foreach ($request->items as $item) {
                    $itemOriginalTotal = floatval($item['quantity'] * $item['unit_price']);
                    $allocatedDiscount = round($itemOriginalTotal * $discountRatio, 2);
                    $itemNetTotal = $itemOriginalTotal - $allocatedDiscount;

                    $invoiceItem = new InvoiceItem();
                    $invoiceItem->invoice_id = $invoice->id;
                    $invoiceItem->product_id = $item['product_id'];
                    $invoiceItem->variation_id = $item['variation_id'] ?? null;
                    $invoiceItem->quantity = $item['quantity'];
                    $invoiceItem->unit_price = $item['unit_price'];
                    $invoiceItem->discount = $allocatedDiscount;
                    $invoiceItem->total_price = $itemNetTotal;
                    $invoiceItem->save();
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Sale updated successfully.', 'sale_id' => $pos->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function print($id)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        $pos = Pos::with([
            'customer',
            'branch',
            'items.product',
            'items.variation.attributeValues.attribute',
            'items.returnItems.saleReturn',
            'invoice.payments',
            'soldBy'
        ])->findOrFail($id);

        // Load sale returns for this POS sale
        $saleReturns = \App\Models\SaleReturn::with(['items.product', 'items.variation.attributeValues.attribute'])
            ->where('pos_sale_id', $pos->id)
            ->where('status', 'processed')
            ->where('refund_type', '!=', 'exchange')
            ->get();

        // Load exchanges for this POS sale
        $exchanges = \App\Models\PosExchange::with(['items.product', 'items.variation.attributeValues.attribute'])
            ->where('original_pos_id', $pos->id)
            ->get();

        $template = InvoiceTemplate::where('is_default', 1)->first();
        $general_settings = GeneralSetting::first();
        $action = request()->get('action', 'print');

        // Calculate tax if not already calculated
        if ($pos->invoice && !$pos->invoice->tax && $general_settings && $general_settings->tax_rate > 0) {
            $taxRate = $general_settings->tax_rate / 100;
            $pos->invoice->tax = round($pos->sub_total * $taxRate, 2);
        }

        // Generate QR code as SVG
        $printUrl = route('pos.print', ['id' => $pos->id]);
        $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate($printUrl);

        // PDF download logic
        if ($action == 'download') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.pos.print', compact('pos', 'template', 'action', 'qrCodeSvg', 'general_settings', 'saleReturns', 'exchanges'));
            // Increased width to 260 points (~91mm) to provide breathing room and prevent clipping
            $pdf->setPaper([0, 0, 260, 1000], 'portrait');
            return $pdf->download('pos-receipt-' . $pos->sale_number . '.pdf');
        }

        return view('erp.pos.print', compact('pos', 'template', 'action', 'qrCodeSvg', 'general_settings', 'saleReturns', 'exchanges'));
    }

    public function getMultiBranchStock($productId, $variationId = null)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        $product = Product::findOrFail($productId);
        $branches = Branch::all();
        $stockData = [];

        foreach ($branches as $branch) {
            if ($variationId) {
                $stock = ProductVariationStock::where('variation_id', $variationId)
                    ->where('branch_id', $branch->id)
                    ->whereNull('warehouse_id')
                    ->first();
                $quantity = $stock ? ($stock->available_quantity ?? ($stock->quantity - ($stock->reserved_quantity ?? 0))) : 0;
            } else {
                $stock = BranchProductStock::where('branch_id', $branch->id)
                    ->where('product_id', $productId)
                    ->first();
                $quantity = $stock ? $stock->quantity : 0;
            }

            $stockData[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'quantity' => $quantity,
            ];
        }

        return response()->json(['success' => true, 'data' => $stockData]);
    }

    public function getBranchStock($productId, $branchId, $variationId = null)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        if ($variationId && $variationId !== 'null') {
            $stock = ProductVariationStock::where('variation_id', $variationId)
                ->where('branch_id', $branchId)
                ->where(function ($q) {
                    $q->whereNull('warehouse_id')->orWhere('warehouse_id', 0);
                })
                ->first();
            // Use available_quantity accessor or calculate manually
            $quantity = $stock ? ($stock->quantity - ($stock->reserved_quantity ?? 0)) : 0;

            // Double check: if no variation stock record exists, strictly return 0
            // Do NOT fall back to product stock here
        } else {
            $stock = BranchProductStock::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->first();
            $quantity = $stock ? $stock->quantity : 0;
        }

        return response()->json(['success' => true, 'quantity' => $quantity]);
    }

    // Technician assignment removed - not needed for ecommerce-only business
    // public function assignTechnician($saleId, $techId)
    // {
    //     $pos = Pos::find($saleId);
    //     if (!$pos) {
    //         return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
    //     }
    //     $employee = \App\Models\Employee::find($techId);
    //     if (!$employee) {
    //         return response()->json(['success' => false, 'message' => 'Technician not found.'], 404);
    //     }
    //     $pos->employee_id = $techId;
    //     $pos->save();
    //     return response()->json(['success' => true, 'message' => 'Technician assigned successfully.']);
    // }

    public function updateNote($saleId, Request $request)
    {
        if (!auth()->user()->can('edit sales')) {
            abort(403, 'Unauthorized action.');
        }
        $pos = Pos::find($saleId);
        if (!$pos) {
            return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
        }
        $pos->notes = $request->input('note');
        $pos->save();
        return response()->json(['success' => true, 'message' => 'Note updated successfully.']);
    }

    public function addPayment($saleId, Request $request)
    {
        if (!auth()->user()->can('edit sales')) {
            abort(403, 'Unauthorized action.');
        }
        $pos = Pos::with('invoice')->find($saleId);
        if (!$pos) {
            return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
        }
        $invoice = $pos->invoice;
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'account_id' => 'nullable|integer',
            'note' => 'nullable|string',
        ]);
        // Create payment
        $payment = new Payment();
        $payment->payment_for = 'pos';
        $payment->pos_id = $pos->id;
        $payment->invoice_id = $invoice->id;
        $payment->payment_date = now()->toDateString();
        $payment->amount = $request->amount;
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->payment_method;
        $payment->note = $request->note;
        $payment->customer_id = $pos->customer_id;
        $payment->save();
        // Update invoice
        $invoice->paid_amount += $request->amount;
        $invoice->due_amount = max(0, $invoice->total_amount - $invoice->paid_amount);
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $invoice->status = 'paid';
            $invoice->due_amount = 0;
            // Auto-set POS status to delivered when fully paid
            $pos->status = 'delivered';
            // Move items to customer (delivered) - reload items to ensure they're available
            $pos->load('items');
            foreach ($pos->items as $item) {
                $item->current_position_id = null;
                $item->save();
            }
            $pos->save();
        } elseif ($invoice->paid_amount > 0) {
            $invoice->status = 'partial';
        } else {
            $invoice->status = 'unpaid';
        }
        $invoice->save();


        if ($request->payment_method == 'cash' && $pos->customer_id) {
            $balance = Balance::where('source_type', 'customer')->where('source_id', $pos->customer_id)->first();
            if ($balance) {
                $balance->balance -= $request->amount;
                $balance->save();
            } else {
                Balance::create([
                    'source_type' => 'customer',
                    'source_id' => $pos->customer_id,
                    'balance' => $invoice->due_amount,
                    'description' => 'POS Sale',
                    'reference' => $pos->sale_number,
                ]);
            }
        }

        if ($request->received_by) {
            $balance = Balance::where('source_type', 'employee')->where('source_id', $request->received_by)->first();
            if ($balance) {
                $balance->balance += $request->amount;
                $balance->save();
            } else {
                Balance::create([
                    'source_type' => 'employee',
                    'source_id' => $request->received_by,
                    'balance' => $request->amount,
                    'description' => 'POS Sale',
                    'reference' => $pos->sale_number,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Payment added successfully.']);
    }

    public function updateStatus($saleId, Request $request)
    {
        if (!auth()->user()->can('edit sales')) {
            abort(403, 'Unauthorized action.');
        }
        $pos = Pos::findOrFail($saleId);

        $request->validate([
            'status' => 'required|string',
        ]);

        // Prevent cancellation if already delivered
        if ($request->status == 'cancelled' && $pos->status == 'delivered') {
            return response()->json(['success' => false, 'message' => 'Cannot cancel a sale that has already been delivered.'], 400);
        }

        if ($request->status == 'pending') {
            $pos->status = $request->input('status');
        } else if ($request->status == 'delivered') {
            $pos->status = $request->input('status');
            foreach ($pos->items as $item) {
                $item->current_position_id = null;
                $item->save();
            }
        } else if ($request->status == 'cancelled') {
            $pos->status = $request->input('status');
            foreach ($pos->items as $item) {
                // Move back to branch
                $item->current_position_type = 'branch';
                $item->current_position_id = $pos->branch_id;
                $item->save();

                // Restore stock using helper method
                $this->restoreStock(
                    $item->product_id,
                    $item->variation_id,
                    $item->quantity,
                    $pos->branch_id
                );
            }
        }

        $pos->save();
        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function addAddress(Request $request, $id)
    {
        if (!auth()->user()->can('edit sales')) {
            abort(403, 'Unauthorized action.');
        }
        $existingInvoiceAddress = InvoiceAddress::where('invoice_id', $id)->first();

        if ($existingInvoiceAddress) {
            $existingInvoiceAddress->billing_address_1 = $request->billing_address_1;
            $existingInvoiceAddress->billing_address_2 = $request->billing_address_2;
            $existingInvoiceAddress->billing_city = $request->billing_city;
            $existingInvoiceAddress->billing_state = $request->billing_state;
            $existingInvoiceAddress->billing_country = $request->billing_country;
            $existingInvoiceAddress->billing_zip_code = $request->billing_zip_code;

            $existingInvoiceAddress->shipping_address_1 = $request->shipping_address_1;
            $existingInvoiceAddress->shipping_address_2 = $request->shipping_address_2;
            $existingInvoiceAddress->shipping_city = $request->shipping_city;
            $existingInvoiceAddress->shipping_state = $request->shipping_state;
            $existingInvoiceAddress->shipping_country = $request->shipping_country;
            $existingInvoiceAddress->shipping_zip_code = $request->shipping_zip_code;

            $existingInvoiceAddress->save();
        } else {
            $invoiceAddress = new InvoiceAddress();
            $invoiceAddress->invoice_id = $id;
            $invoiceAddress->billing_address_1 = $request->billing_address_1;
            $invoiceAddress->billing_address_2 = $request->billing_address_2;
            $invoiceAddress->billing_city = $request->billing_city;
            $invoiceAddress->billing_state = $request->billing_state;
            $invoiceAddress->billing_country = $request->billing_country;
            $invoiceAddress->billing_zip_code = $request->billing_zip_code;

            $invoiceAddress->shipping_address_1 = $request->shipping_address_1;
            $invoiceAddress->shipping_address_2 = $request->shipping_address_2;
            $invoiceAddress->shipping_city = $request->shipping_city;
            $invoiceAddress->shipping_state = $request->shipping_state;
            $invoiceAddress->shipping_country = $request->shipping_country;
            $invoiceAddress->shipping_zip_code = $request->shipping_zip_code;

            $invoiceAddress->save();
        }
    }

    public function posSearch(Request $request)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        $q = $request->input('q');
        $customerId = $request->input('customer_id');
        $query = \App\Models\Pos::with('customer');

        // Filter by customer if provided
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('sale_number', 'like', "%$q%")
                    ->orWhereHas('customer', function ($q2) use ($q) {
                        $q2->where('name', 'like', "%$q%")
                            ->orWhere('phone', 'like', "%$q%")
                            ->orWhere('email', 'like', "%$q%");
                    });
            });
        }
        $sales = $query->orderBy('sale_number', 'desc')->limit(20)->get();
        $results = $sales->map(function ($sale) use ($customerId) {
            // If customer is already selected, just show sale number
            // Otherwise show customer info for identification
            $text = $sale->sale_number;
            if (!$customerId) {
                $customer = $sale->customer;
                if ($customer) {
                    $text .= ' - ' . $customer->name;
                    if ($customer->phone)
                        $text .= ' (' . $customer->phone . ')';
                    if ($customer->email)
                        $text .= ' [' . $customer->email . ']';
                }
            }
            return [
                'id' => $sale->id,
                'text' => $text
            ];
        });
        return response()->json($results);
    }

    // Add this function to generate a unique invoice number
    private function generateInvoiceNumber()
    {
        $generalSettings = GeneralSetting::first();
        $prefix = $generalSettings ? $generalSettings->invoice_prefix : 'INV';

        $nextId = (\App\Models\Invoice::max('id') ?? 0) + 1;
        $number = $prefix . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

        while (\App\Models\Invoice::where('invoice_number', $number)->exists()) {
            $nextId++;
            $number = $prefix . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        }

        return $number;
    }


    /**
     * Get report data for the modal
     */
    public function getReportData(Request $request)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $query = Pos::with(['customer', 'invoice', 'branch']);

            // Date range filter
            $startDate = $request->input('start_date') ?? $request->input('date_from');
            $endDate = $request->input('end_date') ?? $request->input('date_to');
            if ($startDate) {
                $query->whereDate('sale_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('sale_date', '<=', $endDate);
            }

            // Status filter
            if ($request->filled('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Payment status filter
            $paymentStatus = $request->input('bill_status') ?? $request->input('payment_status');
            if ($paymentStatus && $paymentStatus !== '') {
                $query->whereHas('invoice', function ($q) use ($paymentStatus) {
                    $q->where('status', $paymentStatus);
                });
            }

            // Branch filter
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('sale_number', 'like', "%$search%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%$search%")
                                ->orWhere('phone', 'like', "%$search%")
                                ->orWhere('email', 'like', "%$search%");
                        });
                });
            }

            $sales = $query->orderBy('sale_date', 'desc')->get();

            // Transform data for frontend
            $transformedSales = $sales->map(function ($sale) {
                return [
                    'sale_number' => $sale->sale_number,
                    'sale_date' => $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d-m-Y') : '-',
                    'customer_name' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                    'customer_phone' => $sale->customer ? $sale->customer->phone : '-',
                    'branch_name' => $sale->branch ? $sale->branch->name : '-',
                    'status' => $sale->status,
                    'payment_status' => $sale->invoice ? $sale->invoice->status : '-',
                    'sub_total' => number_format($sale->sub_total, 2),
                    'discount' => number_format($sale->discount, 2),
                    'total_amount' => number_format($sale->total_amount, 2),
                    'paid_amount' => $sale->invoice ? number_format($sale->invoice->paid_amount, 2) : '0.00',
                    'due_amount' => $sale->invoice ? number_format($sale->invoice->due_amount, 2) : '0.00',
                ];
            });

            // Calculate summary statistics
            $summary = [
                'total_sales' => $sales->count(),
                'total_amount' => number_format($sales->sum('total_amount'), 2),
                'paid_sales' => $sales->filter(function ($sale) {
                    return $sale->invoice && $sale->invoice->status === 'paid';
                })->count(),
                'unpaid_sales' => $sales->filter(function ($sale) {
                    return $sale->invoice && $sale->invoice->status === 'unpaid';
                })->count(),
            ];

            return response()->json([
                'sales' => $transformedSales,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getReportData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'An error occurred while loading report data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $startDate = \Carbon\Carbon::createFromDate($request->get('year', date('Y')), $request->get('month', date('m')), 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $startDate = \Carbon\Carbon::createFromDate($request->get('year', date('Y')), 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = \App\Models\PosItem::select('pos_items.*')
            ->whereNull('pos_items.parent_item_id')
            ->with([
                'pos.customer',
                'pos.invoice',
                'pos.branch',
                'pos.soldBy',
                'product.category',
                'product.brand',
                'product.season',
                'product.gender',
                'variation.attributeValues.attribute',
                'childItems.product',
                'childItems.variation.attributeValues',
                'returnItems'
            ]);

        $query = $this->applyFilters($query, $request, $startDate, $endDate);
        $items = $query->orderBy('sort_order')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Serial No',
            'Invoice',
            'Date',
            'Customer',
            'Branch',
            'Created By',
            'Category',
            'Brand',
            'Season',
            'Gender',
            'Product Name',
            'Style #',
            'Color',
            'Size',
            'Unit Price',
            'Billed Qty',
            'Physical Pcs',
            'Total S-Qty',
            'Sales Amount',
            'Total Sales Amount',
            'Sales Return Qty',
            'Total SR-Qty',
            'Sales Return Amount',
            'Total Sales Return Amount',
            'Exchange Qty',
            'Total Exch-Qty',
            'Exchange Return Amount',
            'Total Exchange Return Amount',
            'Actual Sales Qty',
            'Total AS-Qty',
            'Delivery Charge Amount',
            'VAT Amount',
            'Discount Amount',
            'Exchange Amount',
            'Refund',
            'Gross Amount (with vat)',
            'Net Amount (without vat)',
            'Total Received Amount',
            'Total Due Amount'
        ];

        $sheet->fromArray([$headers], NULL, 'A1');
        $sheet->getStyle('A1:AM1')->getFont()->setBold(true);

        // Initialize totals
        $totalSellQty = 0;
        $totalPhysicalQty = 0;
        $totalGrossAmt = 0;
        $totalDelivery = 0;
        $totalVat = 0;
        $totalDiscount = 0;
        $totalExchange = 0;
        $totalRefund = 0;
        $totalFinalTotal = 0;
        $totalActualAmt = 0;
        $totalRegRetQty = 0;
        $totalExchRetQty = 0;
        $totalExchNewQty = 0;
        $totalPaid = 0;
        $totalDue = 0;

        $rowNum = 2;
        foreach ($items as $index => $item) {
            $sale = $item->pos;
            $invoice = $sale->invoice;
            $product = $item->product;
            $variation = $item->variation;

            $color = '-';
            $size = '-';
            if ($variation && $variation->attributeValues) {
                foreach ($variation->attributeValues as $val) {
                    $attrName = strtolower($val->attribute->name ?? '');
                    if (str_contains($attrName, 'color') || (isset($val->attribute) && $val->attribute->is_color))
                        $color = $val->value;
                    elseif (str_contains($attrName, 'size'))
                        $size = $val->value;
                }
            }

            $isFirst = ($index == 0 || $items[$index - 1]->pos_sale_id != $item->pos_sale_id);

            // Item Level
            $isCombo = ($product?->type === 'combo');
            $childItems = $item->childItems ?? collect();
            $comboItemsQty = $childItems->sum('quantity');
            $physicalQty = $isCombo ? ($comboItemsQty > 0 ? $comboItemsQty : $item->quantity) : $item->quantity;
            $totalPhysicalQty += $physicalQty;

            $grossAmt = $item->quantity * $item->unit_price;
            $regRetItems = $item->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') !== 'exchange');
            $exchRetItems = $item->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') === 'exchange');
            $regRetQty = $regRetItems->sum('returned_qty');
            $regRetAmt = $regRetItems->sum('total_price');
            $exchRetQty = $exchRetItems->sum('returned_qty');
            $exchRetAmt = $exchRetItems->sum('total_price');
            $retQty = $regRetQty + $exchRetQty;
            $retAmt = $regRetAmt + $exchRetAmt;
            $itemExchNewQty = \App\Models\PosExchangeItem::where('type', 'new')
                ->where('product_id', $item->product_id)
                ->where('variation_id', $item->variation_id)
                ->whereHas('exchange', function($q) use ($sale) {
                    $q->where('original_pos_id', $sale->id)->where('status', 'completed');
                })
                ->sum('quantity');

            $actualQty = $item->quantity - $retQty + $itemExchNewQty;

            // Invoice Level (Calculate once per sale)
            $invTotalQty = '';
            $invTotalSalesAmt = '';
            $invRetQty = '';
            $invRetAmt = '';
            $invActualQty = '';
            $invTotal = '';
            $invActualAmt = '';

            if ($isFirst) {
                $invItems = $sale->items->whereNull('parent_item_id');
                $i_TotalQty = $invItems->sum('quantity');
                $i_GrossAmt = $invItems->sum(fn($i) => $i->quantity * $i->unit_price);

                $i_RegRetQty = $invItems->sum(fn($i) => $i->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') !== 'exchange')->sum('returned_qty'));
                $i_RegRetAmt = $invItems->sum(fn($i) => $i->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') !== 'exchange')->sum('total_price'));

                $i_ExchRetQty = $invItems->sum(fn($i) => $i->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') === 'exchange')->sum('returned_qty'));
                $i_ExchRetAmt = $invItems->sum(fn($i) => $i->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') === 'exchange')->sum('total_price'));

                $i_ExchNewQty = \App\Models\PosExchangeItem::where('type', 'new')
                    ->whereHas('exchange', function($q) use ($sale) {
                        $q->where('original_pos_id', $sale->id)->where('status', 'completed');
                    })
                    ->sum('quantity');

                $i_RetQty = $i_RegRetQty + $i_ExchRetQty;
                $i_RetAmt = $i_RegRetAmt + $i_ExchRetAmt;
                $i_ActualQty = $i_TotalQty - $i_RetQty + $i_ExchNewQty;

                // Calculate proportional returned VAT and discount
                $i_ReturnedVat = 0;
                $i_ReturnedDiscount = 0;
                if ($i_GrossAmt > 0) {
                    foreach ($invItems as $invItem) {
                        foreach ($invItem->returnItems as $returnItem) {
                            if (($returnItem->saleReturn?->status ?? '') === 'processed') {
                                $itemGross = $invItem->quantity * $invItem->unit_price;
                                $itemProportion = $itemGross / $i_GrossAmt;
                                $qtyProportion = $returnItem->returned_qty / $invItem->quantity;
                                $i_ReturnedVat += round($itemProportion * $qtyProportion * ($sale->vat_amount ?? 0), 2);
                                $i_ReturnedDiscount += round($itemProportion * $qtyProportion * ($sale->discount ?? 0), 2);
                            }
                        }
                    }
                }

                // Net VAT and Discount after returns
                $i_NetVat = max(0, ($sale->vat_amount ?? 0) - $i_ReturnedVat);
                $i_NetDiscount = max(0, ($sale->discount ?? 0) - $i_ReturnedDiscount);

                // Gross Amount
                $i_GrossAmount = $i_GrossAmt + ($sale->vat_amount ?? 0) + $sale->delivery;

                // Net Amount: use invoice->total_amount
                $i_ActualAmt = $invoice ? floatval($invoice->total_amount ?? 0) : max(0, $i_GrossAmount - $i_RetAmt);

                $i_TotalSalesAmt = $i_GrossAmt;

                // Set strings for Excel
                $invTotalQty = $i_TotalQty;
                $invTotalSalesAmt = $i_TotalSalesAmt;
                $invRetQty = $i_RegRetQty;
                $invRetAmt = $i_RegRetAmt;
                $invActualQty = $i_ActualQty;
                $invTotal = $i_GrossAmount;
                $invActualAmt = $i_ActualAmt;
                $invNetVat = $i_NetVat;
                $invNetDiscount = $i_NetDiscount;
                $invExchRetQty = $i_ExchRetQty;
                $invExchRetAmt = $i_ExchRetAmt;
            }

            $productDisplayName = ($product->name ?? '-');
            if ($isCombo) {
                $productDisplayName .= " [COMBO: {$physicalQty} pcs]";
            }

            $data = [
                $index + 1,
                $sale->sale_number ?? '-',
                $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') : '-',
                $sale->customer->name ?? 'Walk-in',
                $sale->branch->name ?? '-',
                $sale->soldBy->name ?? '-',
                $product->category->name ?? '-',
                $product->brand->name ?? '-',
                $product->season->name ?? '-',
                $product->gender->name ?? '-',
                $productDisplayName,
                $product->style_number ?? '-',
                $color,
                $size,
                $item->unit_price,
                $item->quantity,
                $physicalQty,
                $invTotalQty,
                $grossAmt,
                $invTotalSalesAmt,
                $regRetQty,
                $invRetQty,
                $regRetAmt,
                $invRetAmt,
                $exchRetQty,
                $invExchRetQty,
                $exchRetAmt,
                $invExchRetAmt,
                $actualQty,
                $invActualQty,
                $isFirst ? ($sale->delivery ?? 0) : '',
                $isFirst ? $invNetVat : '',
                $isFirst ? $invNetDiscount : '',
                $isFirst ? ($sale->exchange_amount ?? 0) : '',
                $isFirst ? ($sale->refund_amount ?? 0) : '',
                $invTotal,
                $invActualAmt,
                $isFirst ? ($invoice->paid_amount ?? 0) : '',
                $isFirst ? ($invoice->due_amount ?? 0) : ''
            ];
            $sheet->fromArray([$data], NULL, 'A' . $rowNum);
            $rowNum++;

            // Accumulate totals (only for first row of each invoice)
            if ($isFirst) {
                $totalSellQty += $i_TotalQty;
                $totalGrossAmt += $i_GrossAmt;
                $totalDelivery += ($sale->delivery ?? 0);
                $totalVat += $i_NetVat;
                $totalDiscount += $i_NetDiscount;
                $totalExchange += ($sale->exchange_amount ?? 0);
                $totalRefund += ($sale->refund_amount ?? 0);
                $totalFinalTotal += $i_GrossAmount;
                $totalActualAmt += $i_ActualAmt;
                $totalRegRetQty += $i_RegRetQty;
                $totalExchRetQty += $i_ExchRetQty;
                $totalExchNewQty += $i_ExchNewQty;
                $totalPaid += ($invoice->paid_amount ?? 0);
                $totalDue += ($invoice->due_amount ?? 0);
            }
        }

        // Add footer row with totals
        // 39 columns total: A(0) to AM(38)
        $footerData = array_fill(0, 39, '');
        $footerData[0] = 'Total';
        $footerData[15] = $totalSellQty; // Billed Qty
        $footerData[16] = $totalPhysicalQty; // Physical Pcs
        $footerData[18] = $totalGrossAmt; // Sales Amount
        $footerData[21] = $totalRegRetQty; // Total SR-Qty
        $footerData[25] = $totalExchRetQty; // Total Exch-Qty
        $footerData[29] = $totalSellQty - $totalRegRetQty - $totalExchRetQty + $totalExchNewQty; // Total AS-Qty
        $footerData[30] = $totalDelivery; // Delivery Charge Amount
        $footerData[31] = $totalVat; // VAT Amount
        $footerData[32] = $totalDiscount; // Discount Amount
        $footerData[33] = $totalExchange; // Exchange Amount
        $footerData[34] = $totalRefund; // Refund
        $footerData[35] = $totalFinalTotal; // Gross Amount (with vat)
        $footerData[36] = $totalActualAmt; // Net Amount (without vat)
        $footerData[37] = $totalPaid; // Total Received Amount
        $footerData[38] = $totalDue; // Total Due Amount

        $sheet->fromArray([$footerData], NULL, 'A' . $rowNum);
        $sheet->getStyle('A' . $rowNum . ':AM' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowNum . ':AM' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8F9FA');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'pos_sales_report_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->can('view sales')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $startDate = \Carbon\Carbon::createFromDate($request->get('year', date('Y')), $request->get('month', date('m')), 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $startDate = \Carbon\Carbon::createFromDate($request->get('year', date('Y')), 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = \App\Models\PosItem::select('pos_items.*')
            ->whereNull('pos_items.parent_item_id')
            ->with([
                'pos.customer',
                'pos.invoice',
                'pos.branch',
                'pos.soldBy',
                'product.category',
                'product.brand',
                'product.season',
                'product.gender',
                'variation.attributeValues.attribute',
                'childItems.product',
                'childItems.variation.attributeValues',
                'returnItems'
            ]);

        $query = $this->applyFilters($query, $request, $startDate, $endDate);
        $items = $query->orderBy('sort_order')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.pos.export-pdf', compact('items', 'reportType', 'startDate', 'endDate'));
        $pdf->setPaper('A4', 'landscape');

        $filename = 'pos_sales_report_' . date('Ymd_His') . '.pdf';
        if ($request->input('action') === 'print') {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }

    private function generateSaleNumber()
    {
        $prefix = 'POS-';
        $lastPos = Pos::where('sale_number', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(sale_number) DESC')
            ->orderBy('sale_number', 'desc')
            ->first();

        if ($lastPos) {
            $numberPart = (int) str_replace($prefix, '', $lastPos->sale_number);
            $newSeq = $numberPart + 1;
        } else {
            $newSeq = 1;
        }

        $number = $prefix . str_pad($newSeq, 6, '0', STR_PAD_LEFT);

        while (Pos::where('sale_number', $number)->exists()) {
            $newSeq++;
            $number = $prefix . str_pad($newSeq, 6, '0', STR_PAD_LEFT);
        }

        return $number;
    }

    private function generateManualSaleNumber()
    {
        $prefix = 'MAN-';
        $lastPos = Pos::where('sale_number', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(sale_number) DESC')
            ->orderBy('sale_number', 'desc')
            ->first();

        if ($lastPos) {
            $numberPart = (int) str_replace($prefix, '', $lastPos->sale_number);
            $newSeq = $numberPart + 1;
        } else {
            $newSeq = 1;
        }

        $number = $prefix . str_pad($newSeq, 6, '0', STR_PAD_LEFT);

        while (Pos::where('sale_number', $number)->exists()) {
            $newSeq++;
            $number = $prefix . str_pad($newSeq, 6, '0', STR_PAD_LEFT);
        }

        return $number;
    }

    /**
     * Deduct stock for a product/variation from branch
     * 
     * @param int $productId
     * @param int|null $variationId
     * @param float $quantity
     * @param int $branchId
     * @return array ['success' => bool, 'message' => string]
     */
    private function deductStock($productId, $variationId, $quantity, $branchId)
    {
        $product = Product::find($productId);
        if ($product && $product->type === 'combo') {
            foreach ($product->comboItems as $comboItem) {
                $itemVariationId = $comboItem->variation_id;
                $itemQuantity = $comboItem->quantity * $quantity;
                $result = $this->deductStock($comboItem->product_id, $itemVariationId, $itemQuantity, $branchId);
                if (!$result['success']) {
                    return $result;
                }
            }
            return ['success' => true];
        }

        if ($variationId) {
            // Handle variation stock
            $vStock = ProductVariationStock::where('variation_id', $variationId)
                ->where('branch_id', $branchId)
                ->whereNull('warehouse_id')
                ->lockForUpdate()
                ->first();

            $availableQty = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;

            if (!$vStock || $availableQty < $quantity) {
                $product = Product::find($productId);
                $productName = $product ? $product->name : 'Product';
                return [
                    'success' => false,
                    'message' => "Insufficient stock for {$productName}. Available: {$availableQty}, Requested: {$quantity}"
                ];
            }

            // Deduct stock
            $vStock->quantity -= $quantity;
            if ($vStock->quantity < 0)
                $vStock->quantity = 0;
            $vStock->save();

            return ['success' => true];
        } else {
            // Handle regular product stock
            $branchStock = BranchProductStock::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$branchStock || $branchStock->quantity < $quantity) {
                $availableQty = $branchStock ? $branchStock->quantity : 0;
                $product = Product::find($productId);
                $productName = $product ? $product->name : 'Product';
                return [
                    'success' => false,
                    'message' => "Insufficient stock for {$productName}. Available: {$availableQty}, Requested: {$quantity}"
                ];
            }

            // Deduct stock
            $branchStock->quantity -= $quantity;
            if ($branchStock->quantity < 0)
                $branchStock->quantity = 0;
            $branchStock->save();

            return ['success' => true];
        }
    }

    /**
     * Restore stock for a product/variation to branch
     * 
     * @param int $productId
     * @param int|null $variationId
     * @param float $quantity
     * @param int $branchId
     * @return void
     */
    private function restoreStock($productId, $variationId, $quantity, $branchId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        if ($product->type === 'combo') {
            foreach ($product->comboItems as $comboItem) {
                $itemVariationId = $comboItem->variation_id;
                $itemQuantity = $comboItem->quantity * $quantity;
                $this->restoreStock($comboItem->product_id, $itemVariationId, $itemQuantity, $branchId);
            }
            return;
        }

        if ($variationId) {
            // Verify that the variation actually exists
            if (!\App\Models\ProductVariation::where('id', $variationId)->exists()) {
                return;
            }

            // Handle variation stock restoration
            $vStock = ProductVariationStock::where('variation_id', $variationId)
                ->where('branch_id', $branchId)
                ->whereNull('warehouse_id')
                ->lockForUpdate()
                ->first();

            if ($vStock) {
                $vStock->quantity += $quantity;
                $vStock->save();
            } else {
                // Create new variation stock record if it doesn't exist
                ProductVariationStock::create([
                    'variation_id' => $variationId,
                    'branch_id' => $branchId,
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            }
        } else {
            // Handle regular product stock restoration
            $branchStock = BranchProductStock::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if ($branchStock) {
                $branchStock->quantity += $quantity;
                $branchStock->save();
            } else {
                // Create new branch stock record if it doesn't exist
                BranchProductStock::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            }
        }
    }

    public function manualSaleCreate()
    {
        if (!auth()->user()->can('use pos')) {
            abort(403, 'Unauthorized action.');
        }
        $user = auth()->user();

        $customersQuery = Customer::query();
        if ($user && $user->isBranchRestricted()) {
            $customersQuery->where('branch_id', $user->employee->branch_id);
        }
        $customers = $customersQuery->orderBy('name')->take(200)->get();

        $branches = Branch::where('status', 'active')->get();

        // Branch Isolation
        if ($user && $user->employee && $user->employee->branch_id) {
            $branches = $branches->where('id', $user->employee->branch_id);
        }

        $products = Product::where('status', 'active')
            ->whereIn('type', ['product', 'combo'])
            ->get();

        // Mark combo products
        foreach ($products as $product) {
            if ($product->type === 'combo') {
                $product->is_combo = true;
                $product->combo_items_count = $product->comboItems->count();
            }
        }

        $brands = \App\Models\Brand::all();
        $seasons = \App\Models\Season::all();
        $genders = \App\Models\Gender::all();
        $categories = ProductServiceCategory::whereNull('parent_id')->get();
        $shippingMethods = \App\Models\ShippingMethod::orderBy('sort_order')->get();

        // Generate next numbers
        $invoiceNo = $this->generateInvoiceNumber();
        $challanNo = str_replace('INV', 'CHA', $invoiceNo);
        $saleNo = $this->generateSaleNumber();

        $bankAccounts = FinancialAccount::all();
        if ($user && $user->employee && $user->employee->branch_id) {
            $bankAccounts = $bankAccounts->where('branch_id', $user->employee->branch_id);
        }

        return view('erp.pos.manualSale.create', compact(
            'customers',
            'branches',
            'products',
            'brands',
            'seasons',
            'genders',
            'categories',
            'shippingMethods',
            'invoiceNo',
            'challanNo',
            'saleNo',
            'bankAccounts'
        ));
    }

    public function manualSaleStore(Request $request)
    {
        if (!auth()->user()->can('use pos')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'sale_date' => 'required|date',
            'sale_type' => 'required|string',
            'invoice_no' => 'required|string',
            'challan_no' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'vat_rate' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 1. STAGE 1: VALDIATE ALL STOCK FIRST
            // We check every item before creating any sale/invoice records
            foreach ($request->items as $item) {
                $productId = $item['product_id'];
                $variationId = $item['variation_id'] ?? null;
                $quantity = $item['quantity'];
                $branchId = $request->branch_id;

                $product = Product::with('comboItems')->find($productId);

                if ($product && $product->type === 'combo') {
                    foreach ($product->comboItems as $comboItem) {
                        $itemQuantity = $quantity * $comboItem->quantity;
                        if ($comboItem->variation_id) {
                            $vStock = ProductVariationStock::where('variation_id', $comboItem->variation_id)
                                ->where('branch_id', $branchId)
                                ->whereNull('warehouse_id')
                                ->first();
                            $availableQty = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                        } else {
                            $branchStock = BranchProductStock::where('branch_id', $branchId)
                                ->where('product_id', $comboItem->product_id)
                                ->first();
                            $availableQty = $branchStock ? $branchStock->quantity : 0;
                        }

                        if ($availableQty < $itemQuantity) {
                            $productName = $comboItem->product ? $comboItem->product->name : 'Combo Item';
                            return response()->json([
                                'success' => false,
                                'message' => "Insufficient stock for combo item ({$productName}). Available: {$availableQty}, Requested: {$itemQuantity}"
                            ], 400); // Return error before any DB records are created
                        }
                    }
                } else {
                    if ($variationId) {
                        $vStock = ProductVariationStock::where('variation_id', $variationId)
                            ->where('branch_id', $branchId)
                            ->whereNull('warehouse_id')
                            ->first();
                        $availableQty = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                    } else {
                        $branchStock = BranchProductStock::where('branch_id', $branchId)
                            ->where('product_id', $productId)
                            ->first();
                        $availableQty = $branchStock ? $branchStock->quantity : 0;
                    }

                    if ($availableQty < $quantity) {
                        $productName = $product ? $product->name : 'Product';
                        return response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for {$productName}. Available: {$availableQty}, Requested: {$quantity}"
                        ], 400); // Return error before any DB records are created
                    }
                }
            }

            // 2. STAGE 2: CREATE RECORDS (Only if Stage 1 passed)
            $pos = new Pos();

            // Generate numbers inside transaction to ensure uniqueness
            $saleNo = $request->input('sale_no');
            if (empty($saleNo) || Pos::where('sale_number', $saleNo)->exists()) {
                $saleNo = $this->generateManualSaleNumber();
            }
            $pos->sale_number = $saleNo;

            $challanNo = $request->challan_no;
            if (empty($challanNo) || Pos::where('challan_number', $challanNo)->exists()) {
                $challanNo = str_replace(['INV', 'POS', 'MAN'], 'CHA', $saleNo);
            }
            $pos->challan_number = $challanNo;

            $pos->customer_id = $request->customer_id;
            $pos->branch_id = $request->branch_id;
            $pos->sold_by = auth()->id();
            $pos->sale_date = $request->sale_date;
            $pos->sale_type = $request->sale_type;
            $pos->sub_total = $request->sub_total;
            $pos->discount = $request->discount ?? 0;
            $pos->delivery = $request->delivery_charge ?? 0;
            $pos->total_amount = $request->total_amount;
            $pos->status = 'delivered';
            $pos->remarks = $request->remarks;
            $pos->notes = $request->note;
            $pos->courier_id = $request->courier_id;
            $pos->vat_rate = $request->vat_rate ?? 0;
            $pos->vat_amount = $request->vat_amount ?? 0;
            $pos->save();

            // Create Invoice
            $invTemplate = InvoiceTemplate::where('is_default', 1)->first();
            $invoice = new Invoice();

            $invoiceNo = $request->invoice_no;
            if (empty($invoiceNo) || Invoice::where('invoice_number', $invoiceNo)->exists()) {
                $invoiceNo = $this->generateInvoiceNumber();
            }
            $invoice->invoice_number = $invoiceNo;

            $invoice->template_id = $invTemplate?->id;
            $invoice->customer_id = $pos->customer_id;
            $invoice->operated_by = auth()->id();
            $invoice->issue_date = $pos->sale_date;
            $invoice->due_date = $pos->sale_date;
            $invoice->subtotal = $pos->sub_total;
            $invoice->tax = $pos->vat_amount ?? 0;
            $invoice->total_amount = $pos->total_amount;
            $invoice->discount_apply = $pos->discount;
            $invoice->paid_amount = $request->paid_amount;
            $invoice->due_amount = max(0, $pos->total_amount - $request->paid_amount);
            $invoice->status = $invoice->due_amount <= 0 ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid');
            $invoice->note = $pos->notes;
            $invoice->created_by = auth()->id();
            $invoice->save();

            $pos->invoice_id = $invoice->id;
            $pos->save();

            // Handle Financials
            if ($request->paid_amount > 0 && $request->account_id) {
                $account = FinancialAccount::find($request->account_id);
                if ($account) {
                    $account->balance += $request->paid_amount;
                    $account->save();

                    // Create Payment Record
                    Payment::create([
                        'payment_for' => 'pos',
                        'pos_id' => $pos->id,
                        'invoice_id' => $invoice->id,
                        'payment_date' => $pos->sale_date,
                        'amount' => $request->paid_amount,
                        'account_id' => $account->id,
                        'payment_method' => $account->type ?? 'cash',
                        'note' => "Manual Sale Payment",
                        'customer_id' => $pos->customer_id,
                    ]);
                }
            }

            // Customer Balance Update
            if ($pos->customer_id) {
                \App\Models\Balance::create([
                    'source_type' => 'customer',
                    'source_id' => $pos->customer_id,
                    'balance' => $pos->total_amount - $request->paid_amount,
                    'description' => 'Manual Sale',
                    'reference' => $pos->sale_number,
                ]);
            }

            // --- Proportional Discount Logic ---
            $totalInvoiceDiscount = floatval($pos->discount ?? 0);
            $invoiceSubtotal = floatval($pos->sub_total ?? 0);
            $discountRatio = ($invoiceSubtotal > 0) ? ($totalInvoiceDiscount / $invoiceSubtotal) : 0;

            // 3. STAGE 3: PROCESS ITEMS AND DEDUCT STOCK
            foreach ($request->items as $index => $item) {
                $result = $this->deductStock(
                    $item['product_id'],
                    $item['variation_id'] ?? null,
                    $item['quantity'],
                    $request->branch_id
                );

                if (!$result['success']) {
                    throw new \Exception($result['message']);
                }

                // Calculate Net Total for Item
                $itemOriginalTotal = floatval($item['quantity'] * $item['unit_price']);
                $allocatedDiscount = round($itemOriginalTotal * $discountRatio, 2);
                $itemNetTotal = $itemOriginalTotal - $allocatedDiscount;

                // Save POS Item
                $product = Product::find($item['product_id']);
                PosItem::create([
                    'pos_sale_id' => $pos->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $this->calculateItemCost($product, $item['variation_id'] ?? null),
                    'total_price' => $itemNetTotal,
                    'current_position_type' => 'branch',
                    'current_position_id' => $request->branch_id,
                    'sort_order' => $index
                ]);

                // Create Invoice Item
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $allocatedDiscount,
                    'total_price' => $itemNetTotal,
                ]);
            }

            // --- DOUBLE ENTRY ACCOUNTING ---
            $salesAccount = ChartOfAccount::where('name', 'like', '%Sales%')->first();
            if ($salesAccount) {
                $voucherNo = 'SAL-M-' . str_pad($pos->id, 6, '0', STR_PAD_LEFT);
                $journal = Journal::create([
                    'voucher_no' => $voucherNo,
                    'entry_date' => $pos->sale_date,
                    'type' => 'Receipt',
                    'description' => 'Manual Sale #' . $pos->sale_number,
                    'customer_id' => $pos->customer_id,
                    'branch_id' => $pos->branch_id,
                    'voucher_amount' => $pos->total_amount,
                    'paid_amount' => $request->paid_amount,
                    'reference' => $pos->sale_number,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // Credit Sales (Net of Tax & Delivery)
                $netManualSales = $pos->total_amount - $pos->vat_amount - $pos->delivery;
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $salesAccount->id,
                    'debit' => 0,
                    'credit' => $netManualSales,
                    'memo' => 'Revenue from Manual Sale (Net of Tax & Delivery)',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // CREDIT Delivery Expense (reduce expense)
                if ($pos->delivery > 0) {
                    $deliveryExpenseAccount = ChartOfAccount::where('name', 'like', '%Delivery Expense%')->orWhere('name', 'like', '%Courier Expense%')->first();
                    if (!$deliveryExpenseAccount) {
                        $expenseType = \App\Models\ChartOfAccountType::where('name', 'Expense')->first() ?? \App\Models\ChartOfAccountType::find(5);
                        $expenseSubType = \App\Models\ChartOfAccountSubType::where('type_id', $expenseType->id)->where('name', 'like', '%Operating%')->first() 
                            ?? \App\Models\ChartOfAccountSubType::where('type_id', $expenseType->id)->first();
                        if (!$expenseSubType) {
                            $expenseSubType = \App\Models\ChartOfAccountSubType::create(['name' => 'Operating Expenses', 'type_id' => $expenseType->id]);
                        }
                        $expenseParent = \App\Models\ChartOfAccountParent::where('type_id', $expenseType->id)->first();
                        if (!$expenseParent) {
                            $expenseParent = \App\Models\ChartOfAccountParent::create([
                                'name' => 'Operating Expenses Parent',
                                'type_id' => $expenseType->id,
                                'sub_type_id' => $expenseSubType->id,
                                'code' => '5000',
                                'created_by' => auth()->id()
                            ]);
                        }

                        $deliveryExpenseAccount = ChartOfAccount::create([
                            'name' => 'Delivery Expense',
                            'type_id' => $expenseType->id,
                            'sub_type_id' => $expenseSubType->id,
                            'parent_id' => $expenseParent->id,
                            'code' => '50005',
                            'status' => 'active',
                            'created_by' => auth()->id()
                        ]);
                    }

                    JournalEntry::create([
                        'journal_id' => $journal->id,
                        'chart_of_account_id' => $deliveryExpenseAccount->id,
                        'debit' => 0,
                        'credit' => $pos->delivery,
                        'memo' => 'Delivery charge collected from customer',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }

                // Credit VAT Payable
                if ($pos->vat_amount > 0) {
                    $vatAccount = ChartOfAccount::where('name', 'like', '%VAT%')->orWhere('name', 'like', '%Tax Payable%')->first();
                    if ($vatAccount) {
                        JournalEntry::create([
                            'journal_id' => $journal->id,
                            'chart_of_account_id' => $vatAccount->id,
                            'debit' => 0,
                            'credit' => $pos->vat_amount,
                            'memo' => 'VAT collected from Manual Sale',
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    }
                }

                // Debit Bank/Cash
                if ($request->paid_amount > 0 && $request->account_id) {
                    $finAcc = FinancialAccount::find($request->account_id);
                    if ($finAcc && $finAcc->account_id) {
                        JournalEntry::create([
                            'journal_id' => $journal->id,
                            'chart_of_account_id' => $finAcc->account_id,
                            'financial_account_id' => $finAcc->id,
                            'debit' => $request->paid_amount,
                            'credit' => 0,
                            'memo' => 'Payment received for Manual Sale',
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    }
                }

                // Debit Accounts Receivable
                $dueAmt = $pos->total_amount - $request->paid_amount;
                if ($dueAmt > 0) {
                    $arAccount = ChartOfAccount::where('name', 'like', '%Receivable%')->first();
                    if ($arAccount) {
                        JournalEntry::create([
                            'journal_id' => $journal->id,
                            'chart_of_account_id' => $arAccount->id,
                            'debit' => $dueAmt,
                            'credit' => 0,
                            'memo' => 'Due amount from Manual Sale',
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Sale created successfully.', 'sale_id' => $pos->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function calculateItemCost($product, $variationId = null)
    {
        if ($variationId) {
            $variation = \App\Models\ProductVariation::find($variationId);
            if ($variation && $variation->cost > 0) {
                return (float) $variation->cost;
            }
        }

        if ($product->type === 'combo') {
            $comboCost = 0;
            foreach ($product->comboItems as $item) {
                $itemProduct = $item->product;
                $itemVariationId = $item->variation_id;
                $comboCost += $this->calculateItemCost($itemProduct, $itemVariationId) * $item->quantity;
            }
            return (float) $comboCost;
        }

        return (float) ($product->cost ?? 0);
    }
}




