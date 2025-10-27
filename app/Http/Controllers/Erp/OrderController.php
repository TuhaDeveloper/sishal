<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\InvoiceAddress;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\BranchProductStock;
use App\Models\WarehouseProductStock;
use App\Models\EmployeeProductStock;
use App\Models\OrderItem;
use App\Models\Journal;
use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view order list')) {
            abort(403, 'Unauthorized action.');
        }
        $query = Order::query();

        // Search by order number, name, phone, email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                ;
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by estimated delivery date
        if ($request->filled('estimated_delivery_date')) {
            $query->whereDate('estimated_delivery_date', $request->estimated_delivery_date);
        }

        // Filter by bill status (invoice status)
        if ($request->filled('bill_status')) {
            $query->whereHas('invoice', function($q) use ($request) {
                $q->where('status', $request->bill_status);
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10)->appends($request->all());

        return view('erp.order.orderlist', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['invoice.payments', 'items.product', 'employee.user', 'customer'])->find($id);
        $bankAccounts = \App\Models\FinancialAccount::all();
        
        // If AJAX request, return JSON
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'id' => $order->id,
                'customer_id' => $order->customer_id,
                'order_number' => $order->order_number,
                'items' => $order->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'N/A',
                        'variation_id' => $item->variation_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price
                    ];
                })
            ]);
        }
        
        return view('erp.order.orderdetails',compact('order', 'bankAccounts'));
    }

    public function setEstimatedDelivery(Request $request, $id)
    {
        $validated = $request->validate([
            'estimated_delivery_date' => 'required|date',
            'estimated_delivery_time' => 'required',
        ]);

        $order = Order::findOrFail($id);
        $order->estimated_delivery_date = $validated['estimated_delivery_date'];
        $order->estimated_delivery_time = $validated['estimated_delivery_time'];
        $order->save();

        return response()->json(['success' => true, 'message' => 'Estimated delivery date and time updated.']);
    }

    // This function can be used for both add and edit
    public function updateEstimatedDelivery(Request $request, $id)
    {
        $validated = $request->validate([
            'estimated_delivery_date' => 'required|date',
            'estimated_delivery_time' => 'required',
        ]);

        $order = Order::findOrFail($id);
        $order->estimated_delivery_date = $validated['estimated_delivery_date'];
        $order->estimated_delivery_time = $validated['estimated_delivery_time'];
        $order->save();

        return response()->json(['success' => true, 'message' => 'Estimated delivery date and time updated.']);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::with('items')->findOrFail($id);

        if ($request->status === 'shipping') {
            // For e-commerce orders, we need to manage stock but don't require technician
            $isServiceOrder = $order->employee_id && $order->items->where('current_position_type')->count() > 0;
            
            if ($isServiceOrder) {
                // Service order: requires technician and complex stock management
                if (!$order->employee_id) {
                    return response()->json(['success' => false, 'message' => 'Assign a technician before shipping.']);
                }
                
                foreach ($order->items as $item) {
                    if (!$item->current_position_type || !$item->current_position_id) {
                        return response()->json(['success' => false, 'message' => 'All items must have a stock source before shipping.']);
                    }
                }

                foreach ($order->items as $item) {
                    $productId = $item->product_id;
                    $qty = $item->quantity;
                    $fromType = $item->current_position_type;
                    $fromId = $item->current_position_id;
                    $employeeId = $order->employee_id;

                    // Find or create employee stock
                    $employeeStock = EmployeeProductStock::firstOrCreate(
                        ['employee_id' => $employeeId, 'product_id' => $productId],
                        ['quantity' => 0, 'issued_by' => auth()->id() ?? 1]
                    );

                    // Get source stock
                    if ($fromType === 'branch') {
                        $fromStock = BranchProductStock::where('branch_id', $fromId)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();
                    } elseif ($fromType === 'warehouse') {
                        $fromStock = WarehouseProductStock::where('warehouse_id', $fromId)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();
                    } elseif ($fromType === 'employee') {
                        $fromStock = EmployeeProductStock::where('employee_id', $fromId)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();
                    } else {
                        return response()->json(['success' => false, 'message' => 'Invalid stock source for item.']);
                    }

                    if (!$fromStock || $fromStock->quantity < $qty) {
                        $productName = $item->product ? $item->product->name : 'Product ID: ' . $item->product_id;
                        $availableQty = $fromStock ? $fromStock->quantity : 0;
                        return response()->json([
                            'success' => false, 
                            'message' => "Insufficient stock for '{$productName}'. Required: {$qty}, Available: {$availableQty}. Please add stock before shipping."
                        ]);
                    }

                    // Transfer stock
                    $fromStock->quantity -= $qty;
                    $fromStock->save();

                    $employeeStock->quantity += $qty;
                    $employeeStock->save();

                    // Update item's current_position_type/id to employee
                    $item->current_position_type = 'employee';
                    $item->current_position_id = $employeeId;
                    $item->save();
                }
            } else {
                // E-commerce order: stock deduction from defined sources
                foreach ($order->items as $item) {
                    $productId = $item->product_id;
                    $qty = $item->quantity;

                    // Check if item has stock source defined
                    if ($item->current_position_type && $item->current_position_id) {
                        // Use defined stock source (branch, warehouse, or employee)
                        $fromType = $item->current_position_type;
                        $fromId = $item->current_position_id;

                        if ($fromType === 'branch') {
                            $fromStock = BranchProductStock::where('branch_id', $fromId)
                                ->where('product_id', $productId)
                                ->lockForUpdate()
                                ->first();
                        } elseif ($fromType === 'warehouse') {
                            $fromStock = WarehouseProductStock::where('warehouse_id', $fromId)
                                ->where('product_id', $productId)
                                ->lockForUpdate()
                                ->first();
                        } elseif ($fromType === 'employee') {
                            $fromStock = EmployeeProductStock::where('employee_id', $fromId)
                                ->where('product_id', $productId)
                                ->lockForUpdate()
                                ->first();
                        } else {
                            return response()->json(['success' => false, 'message' => 'Invalid stock source for item: ' . $item->id]);
                        }

                        if (!$fromStock || $fromStock->quantity < $qty) {
                            $productName = $item->product ? $item->product->name : 'Product ID: ' . $item->product_id;
                            $availableQty = $fromStock ? $fromStock->quantity : 0;
                            return response()->json([
                                'success' => false, 
                                'message' => "Insufficient stock for '{$productName}'. Required: {$qty}, Available: {$availableQty}. Please add stock before shipping."
                            ]);
                        }

                        // Deduct from source stock
                        $fromStock->quantity -= $qty;
                        $fromStock->save();

                        // Mark item as shipped (remove from inventory tracking)
                        $item->current_position_type = null;
                        $item->current_position_id = null;
                        $item->save();
                    } else {
                        // For e-commerce orders without specific stock source,
                        // we'll deduct from a default warehouse (ID 1) or create one
                        $defaultWarehouseId = 1; // You can change this to your preferred default warehouse
                        
                        $fromStock = WarehouseProductStock::where('warehouse_id', $defaultWarehouseId)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();

                        if (!$fromStock) {
                            // Create default warehouse stock if it doesn't exist
                            $fromStock = WarehouseProductStock::create([
                                'warehouse_id' => $defaultWarehouseId,
                                'product_id' => $productId,
                                'quantity' => 0,
                                'updated_by' => auth()->id() ?? 1
                            ]);
                        }

                        if ($fromStock->quantity < $qty) {
                            $productName = $item->product ? $item->product->name : 'Product ID: ' . $item->product_id;
                            return response()->json([
                                'success' => false, 
                                'message' => "Insufficient stock for '{$productName}'. Required: {$qty}, Available: {$fromStock->quantity}. Please add stock to warehouse before shipping."
                            ]);
                        }

                        // Deduct from default warehouse stock
                        $fromStock->quantity -= $qty;
                        $fromStock->save();

                        // Mark item as shipped
                        $item->current_position_type = null;
                        $item->current_position_id = null;
                        $item->save();
                    }
                }
            }
        }

        // Update the order status
        $order->status = $request->status;
        $order->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function updateTechnician($id, $employee_id)
    {
        $order = Order::findOrFail($id);
        $order->employee_id = $employee_id;

        $order->save();
        return response()->json(['success' => true, 'message' => 'Technician Assigned']);
    }

    public function deleteTechnician($id)
    {
        $order = Order::findOrFail($id);
        $order->employee_id = null;

        $order->save();
        return response()->json(['success' => true, 'message' => 'Technician Assigned']);
    }

    public function updateNote(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->notes = $request->notes;

        $order->save();
        return response()->json(['success' => true, 'message' => 'Notes updated.']);
    }

    public function addPayment($orderId, Request $request)
    {
        $order = Order::with('invoice')->find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
        $invoice = $order->invoice;
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
        $payment->payment_for = 'order';
        $payment->pos_id = $order->id;
        $payment->invoice_id = $invoice->id;
        $payment->payment_date = now()->toDateString();
        $payment->amount = $request->amount;
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->payment_method;
        $payment->note = $request->note;
        $payment->save();
        // Update invoice
        $invoice->paid_amount += $request->amount;
        $invoice->due_amount = max(0, $invoice->total_amount - $invoice->paid_amount);
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $invoice->status = 'paid';
            $invoice->due_amount = 0;
        } elseif ($invoice->paid_amount > 0) {
            $invoice->status = 'partial';
        } else {
            $invoice->status = 'unpaid';
        }
        $invoice->save();

        // If invoice is fully paid, mark ecommerce order as approved
        if ($invoice->status === 'paid' && $order && $order->status !== 'approved') {
            $order->status = 'approved';
            $order->save();
        }

        // Create Journal Entry for Order Payment
        $this->createOrderPaymentJournalEntry($order, $request->amount, $request->account_id);

        if($request->payment_method == 'cash' && $order->customer_id)
        {
            $balance = Balance::where('source_type', 'customer')->where('source_id', $order->customer_id)->first();
            if($balance)
            {
                $balance->balance -= $request->amount;
                $balance->save();
            }
            else
            {
                Balance::create([
                    'source_type' => 'customer',
                    'source_id' => $order->customer_id,
                    'balance' => $invoice->due_amount,
                    'description' => 'Order Sale',
                    'reference' => $order->order_number,
                ]);
            }
        }

        if($request->received_by)
        {
            $balance = Balance::where('source_type', 'employee')->where('source_id', $request->received_by)->first();
            if($balance)
            {
                $balance->balance += $request->amount;
                $balance->save();   
            }
            else
            {
                Balance::create([
                    'source_type' => 'employee',
                    'source_id' => $request->received_by,
                    'balance' => $request->amount,
                    'description' => 'Order Sale',
                    'reference' => $order->order_number,
                ]);
            }
        }

        if($request->account_id)
        {
            $account = FinancialAccount::find($request->account_id);
            $account->balance += $request->amount;
            $account->save();
        }

        return response()->json(['success' => true, 'message' => 'Payment added successfully.']);
    }

    public function addAddress(Request $request, $id)
    {
        $existingInvoiceAddress = InvoiceAddress::where('invoice_id',$id)->first();

        if($existingInvoiceAddress){
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
        }else{
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

    public function getProductStocks($productId)
    {
        // Branch stocks
        $branchStocks = BranchProductStock::with('branch')
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function($stock) {
                return [
                    'type' => 'branch',
                    'location' => $stock->branch->name ?? 'Unknown Branch',
                    'quantity' => $stock->quantity,
                    'branch_id' => $stock->branch_id,
                ];
            });

        // Warehouse stocks
        $warehouseStocks = WarehouseProductStock::with('warehouse')
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function($stock) {
                return [
                    'type' => 'warehouse',
                    'location' => $stock->warehouse->name ?? 'Unknown Warehouse',
                    'quantity' => $stock->quantity,
                    'warehouse_id' => $stock->warehouse_id,
                ];
            });

        // Employee stocks
        $employeeStocks = EmployeeProductStock::with(['employee.user'])
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function($stock) {
                return [
                    'type' => 'employee',
                    'location' => $stock->employee->user->first_name . ' ' . $stock->employee->user->last_name,
                    'quantity' => $stock->quantity,
                    'employee_id' => $stock->employee_id,
                ];
            });

        // Merge all stocks
        $allStocks = $branchStocks->concat($warehouseStocks)->concat($employeeStocks)->values();

        return response()->json([
            'success' => true,
            'stocks' => $allStocks,
        ]);
    }

    public function addStockToOrderItem(Request $request, $id)
    {
        $orderItem = OrderItem::find($id);

        $orderItem->current_position_type = $request->current_position_type;
        $orderItem->current_position_id = $request->current_position_id;
        $orderItem->save();
        return response()->json(['success' => true, 'message' => 'Stock added successfully.']);
    }

    public function transferStockToEmployee(Request $request, $orderItemId)
    {
        $orderItem = OrderItem::findOrFail($orderItemId);
        $order = $orderItem->order;
        $productId = $orderItem->product_id;
        $quantity = $orderItem->quantity;

        // 1. Check if order has an employee
        if (!$order->employee_id) {
            return response()->json(['success' => false, 'message' => 'No technician assigned to this order.']);
        }

        $employeeId = $order->employee_id;

        // 2. Find or create employee stock for this product
        $employeeStock = EmployeeProductStock::firstOrCreate(
            ['employee_id' => $employeeId, 'product_id' => $productId],
            [
                'quantity' => 0,
                'issued_by' => optional(auth()->user())->id ?? 1
            ]
        );

        // 3. Transfer from current position
        $fromType = $orderItem->current_position_type;
        $fromId = $orderItem->current_position_id;

        DB::beginTransaction();
        try {
            if ($fromType === 'branch') {
                $fromStock = BranchProductStock::where('branch_id', $fromId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
            } elseif ($fromType === 'warehouse') {
                $fromStock = WarehouseProductStock::where('warehouse_id', $fromId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
            } elseif ($fromType === 'employee') {
                $fromStock = EmployeeProductStock::where('employee_id', $fromId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid source for stock transfer.']);
            }

            if (!$fromStock || $fromStock->quantity < $quantity) {
                return response()->json(['success' => false, 'message' => 'Insufficient stock to transfer.']);
            }

            // Deduct from source
            $fromStock->quantity -= $quantity;
            $fromStock->save();

            // Add to employee stock
            $employeeStock->quantity += $quantity;
            $employeeStock->save();

            // 4. Update order item
            $orderItem->current_position_type = 'employee';
            $orderItem->current_position_id = $employeeId;
            $orderItem->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Stock transferred to employee successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Transfer failed: ' . $e->getMessage()]);
        }
    }

    public function orderSearch(Request $request)
    {
        $q = $request->input('q');
        $query = Order::with('customer');
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('order_number', 'like', "%$q%")
                    ->orWhereHas('customer', function($q2) use ($q) {
                        $q2->where('name', 'like', "%$q%")
                            ->orWhere('phone', 'like', "%$q%")
                            ->orWhere('email', 'like', "%$q%") ;
                    });
            });
        }
        $sales = $query->orderBy('order_number', 'desc')->limit(20)->get();
        $results = $sales->map(function($sale) {
            $customer = $sale->customer;
            $text = $sale->order_number;
            if ($customer) {
                $text .= ' - ' . $customer->name;
                if ($customer->phone) $text .= ' (' . $customer->phone . ')';
                if ($customer->email) $text .= ' [' . $customer->email . ']';
            }
            return [
                'id' => $sale->id,
                'text' => $text
            ];
        });
        return response()->json($results);
    }

    /**
     * Create Journal Entry for Order Payment (following InvoiceController pattern)
     */
    private function createOrderPaymentJournalEntry($order, $amount, $accountId = null)
    {
        try {
            // Find or create a journal for this date
            $journal = Journal::where('entry_date', now()->toDateString())
                ->where('type', 'Receipt')
                ->where('description', 'like', '%Order Payment%')
                ->first();

            if (!$journal) {
                $journal = new Journal();
                $journal->type = 'Receipt'; // Using 'Receipt' which is allowed in the enum
                $journal->entry_date = now()->toDateString();
                $journal->description = 'Order Payment - ' . $order->order_number;
                $journal->created_by = auth()->id();
                $journal->updated_by = auth()->id();
                $journal->save();
            }

            // Get default accounts (you may need to adjust these based on your chart of accounts)
            $cashAccount = ChartOfAccount::where('name', 'like', '%cash%')->orWhere('name', 'like', '%bank%')->first();
            $accountsReceivableAccount = ChartOfAccount::where('name', 'like', '%accounts receivable%')->orWhere('name', 'like', '%debtors%')->orWhere('name', 'like', '%receivable%')->first();

            // Log what accounts we found for debugging
            Log::info('Order Payment Journal Entry - Found accounts:', [
                'cash' => $cashAccount ? $cashAccount->name : 'NOT FOUND',
                'receivable' => $accountsReceivableAccount ? $accountsReceivableAccount->name : 'NOT FOUND'
            ]);

            // Log all available accounts for debugging
            $allAccounts = ChartOfAccount::all();
            Log::info('All available chart of accounts:', $allAccounts->pluck('name', 'id')->toArray());

            if (!$cashAccount || !$accountsReceivableAccount) {
                Log::warning('Required accounts not found for Order payment journal entry. Creating basic entry.');
                // Create basic entry without specific accounts
                $this->createBasicOrderPaymentJournalEntry($journal, $order, $amount);
                return;
            }

            // Create journal entries for Order payment
            // Debit Cash/Bank Account (money received)
            $this->createJournalEntry($journal->id, $cashAccount->id, $amount, 0, 'Cash received for Order payment ' . $order->order_number);

            // Credit Accounts Receivable (reducing the receivable)
            $this->createJournalEntry($journal->id, $accountsReceivableAccount->id, 0, $amount, 'Accounts receivable reduced for Order ' . $order->order_number);

            // Update financial account balances if they exist
            $this->updateOrderPaymentFinancialAccountBalances($cashAccount, $accountsReceivableAccount, $amount);

            Log::info('Order Payment Journal Entry created successfully for order: ' . $order->order_number);

        } catch (\Exception $e) {
            Log::error('Error creating Order payment journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Create basic journal entry when specific accounts are not found for Order payment
     */
    private function createBasicOrderPaymentJournalEntry($journal, $order, $amount)
    {
        // Try to find any available accounts to use
        $anyAccount = ChartOfAccount::first();
        
        if (!$anyAccount) {
            Log::error('No chart of accounts found. Cannot create journal entries.');
            return;
        }

        // Create a simple entry with the first available account
        $this->createJournalEntry($journal->id, $anyAccount->id, $amount, 0, 'Cash received for Order payment ' . $order->order_number);
        $this->createJournalEntry($journal->id, $anyAccount->id, 0, $amount, 'Accounts receivable reduced for Order ' . $order->order_number);
        
        Log::warning('Created basic Order payment journal entries using fallback account: ' . $anyAccount->name);
    }

    /**
     * Create individual journal entry
     */
    private function createJournalEntry($journalId, $chartOfAccountId, $debit, $credit, $memo)
    {
        $entry = new JournalEntry();
        $entry->journal_id = $journalId;
        $entry->chart_of_account_id = $chartOfAccountId;
        $entry->debit = $debit;
        $entry->credit = $credit;
        $entry->memo = $memo;
        $entry->created_by = auth()->id(); // Add missing created_by field
        $entry->updated_by = auth()->id(); // Add missing updated_by field
        $entry->save();
    }

    /**
     * Update financial account balances for Order payments
     */
    private function updateOrderPaymentFinancialAccountBalances($cashAccount, $accountsReceivableAccount, $amount)
    {
        // Update cash account balance (money received)
        $cashFinancialAccount = FinancialAccount::where('account_id', $cashAccount->id)->first();
        if ($cashFinancialAccount) {
            $cashFinancialAccount->balance += $amount;
            $cashFinancialAccount->save();
        }

        // Update accounts receivable balance (reducing the receivable)
        $arFinancialAccount = FinancialAccount::where('account_id', $accountsReceivableAccount->id)->first();
        if ($arFinancialAccount) {
            $arFinancialAccount->balance -= $amount; // Reduce receivable
            $arFinancialAccount->save();
        }
    }

    /**
     * Delete an order with proper validation and safeguards
     */
    public function destroy($id)
    {
        // Check if user has permission to delete orders
        if (!auth()->user()->hasPermissionTo('delete orders')) {
            return response()->json([
                'success' => false, 
                'message' => 'You do not have permission to delete orders.'
            ], 403);
        }

        $order = Order::with(['items', 'invoice', 'payments'])->findOrFail($id);

        // Check if order can be deleted based on status
        $deletableStatuses = ['pending', 'cancelled'];
        if (!in_array($order->status, $deletableStatuses)) {
            return response()->json([
                'success' => false, 
                'message' => 'Cannot delete order with status: ' . ucfirst($order->status) . '. Only pending or cancelled orders can be deleted.'
            ], 400);
        }

        // Check if order has been shipped or delivered
        if (in_array($order->status, ['shipping', 'shipped', 'delivered', 'received'])) {
            return response()->json([
                'success' => false, 
                'message' => 'Cannot delete order that has been shipped or delivered.'
            ], 400);
        }

        // Check if order has payments (except for cancelled orders)
        if ($order->status !== 'cancelled' && $order->payments && $order->payments->count() > 0) {
            return response()->json([
                'success' => false, 
                'message' => 'Cannot delete order with existing payments. Please process a refund first.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Log the deletion for audit purposes
            Log::info('Order deletion initiated', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'deleted_by' => auth()->id(),
                'deleted_at' => now(),
                'order_status' => $order->status,
                'customer_name' => $order->name,
                'customer_phone' => $order->phone
            ]);

            // Restore stock for each order item
            foreach ($order->items as $item) {
                $this->restoreStockForOrderItem($item);
            }

            // Delete related records
            $order->items()->delete();
            
            // Delete invoice if exists and not paid
            if ($order->invoice && $order->invoice->status !== 'paid') {
                $order->invoice->items()->delete();
                $order->invoice->addresses()->delete();
                $order->invoice->delete();
            }

            // Delete payments
            $order->payments()->delete();

            // Delete the order
            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Order ' . $order->order_number . ' has been deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order deletion failed', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'Failed to delete order. Please try again.'
            ], 500);
        }
    }

    /**
     * Restore stock for an order item
     */
    private function restoreStockForOrderItem($item)
    {
        $productId = $item->product_id;
        $quantity = $item->quantity;
        $fromType = $item->current_position_type;
        $fromId = $item->current_position_id;
        $userId = auth()->id() ?? 1;

        if (!$fromType || !$fromId) {
            // If no stock source, add to warehouse stock (default)
            $warehouseStock = WarehouseProductStock::firstOrCreate(
                ['warehouse_id' => 1, 'product_id' => $productId], // Assuming warehouse ID 1 as default
                ['quantity' => 0, 'updated_by' => $userId]
            );
            $warehouseStock->quantity += $quantity;
            $warehouseStock->updated_by = $userId;
            $warehouseStock->save();
            return;
        }

        // Restore stock to original location
        if ($fromType === 'branch') {
            $stock = BranchProductStock::firstOrCreate(
                ['branch_id' => $fromId, 'product_id' => $productId],
                ['quantity' => 0, 'updated_by' => $userId]
            );
        } elseif ($fromType === 'warehouse') {
            $stock = WarehouseProductStock::firstOrCreate(
                ['warehouse_id' => $fromId, 'product_id' => $productId],
                ['quantity' => 0, 'updated_by' => $userId]
            );
        } elseif ($fromType === 'employee') {
            $stock = EmployeeProductStock::firstOrCreate(
                ['employee_id' => $fromId, 'product_id' => $productId],
                ['quantity' => 0, 'issued_by' => $userId, 'updated_by' => $userId]
            );
        } else {
            return; // Unknown stock type
        }

        $stock->quantity += $quantity;
        $stock->updated_by = $userId;
        $stock->save();
    }
}
