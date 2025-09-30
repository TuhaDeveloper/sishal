<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\EmployeeProductStock;
use App\Models\InvoiceAddress;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\Payment;
use App\Models\Pos;
use App\Models\PosItem;
use App\Models\ProductServiceCategory;
use App\Models\JournalEntry;
use App\Models\Journal;
use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use App\Models\TechnicianStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SaleConfirmation;
use App\Models\Balance;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    public function addPos()
    {
        if(Auth::user()->hasPermissionTo('make sale')){
        $categories = ProductServiceCategory::all();
        if(Auth::user()->hasPermissionTo('manage global branches')){
            $branches = Branch::all();
        }else{
            $branches = Branch::where('id', Auth::user()->employee->branch_id)->get();
        }
        $bankAccounts = FinancialAccount::all();
        return view('erp.pos.addPos', compact('categories', 'branches', 'bankAccounts'));
        }else{
            return redirect()->route('erp.dashboard');
        }
    }

    public function makeSale(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'employee_id' => 'nullable|exists:employees,id',
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
        ]);

        DB::beginTransaction();
        try {
            // Generate unique sale number
            $saleNumber = $this->generateSaleNumber();

            $pos = new Pos();
            $pos->sale_number = $saleNumber;
            $pos->customer_id = $request->customer_id;
            $pos->employee_id = $request->employee_id;
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
            $pos->save();

            if($request->customer_type == 'new-customer') {
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
                ]);

                $pos->customer_id = $customer->id;
                $pos->save();
            }

            // --- Create Invoice ---

            $invTemplate = InvoiceTemplate::where('is_default', 1)->first();
            $invoiceNumber = $this->generateInvoiceNumber();
            $invoice = \App\Models\Invoice::create([
                'customer_id' => $pos->customer_id,
                'template_id' => $invTemplate->id,
                'operated_by' => $pos->sold_by,
                'issue_date' => $pos->sale_date,
                'due_date' => $pos->sale_date,
                'send_date' => $pos->sale_date,
                'subtotal' => $pos->subtotal,
                'total_amount' => $pos->total_amount,
                'discount_apply' => $pos->discount,
                'paid_amount' => $request->paid_amount,
                'due_amount' => $pos->total_amount - $request->paid_amount,
                'status' => $request->paid_amount == $pos->total_amount ? 'paid' : ($request->paid_amount > 0 ? 'partial' : 'unpaid'),
                'note' => $pos->notes,
                'footer_text' => null,
                'created_by' => $pos->sold_by,
                'invoice_number' => $invoiceNumber,
            ]);
            $pos->invoice_id = $invoice->id;
            $pos->save();

            // --- End Invoice ---

            // Save POS items
            foreach ($request->items as $item) {
                $createdItem = PosItem::create([
                    'pos_sale_id' => $pos->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'current_position_id' => $request->branch_id
                ]);

                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            if($request->customer_address) {
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
                $payment = Payment::create([
                    'payment_for' => 'pos',
                    'pos_id' => $pos->id,
                    'invoice_id' => $invoice->id,
                    'payment_date' => now()->toDateString(),
                    'amount' => $request->paid_amount,
                    'account_id' => $request->account_id,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'reference' => null,
                    'note' => $request->notes,
                ]);

                // Create Journal Entry for POS Payment
                $this->createPosPaymentJournalEntry($pos, $request->paid_amount, $request->account_id);

                $account = FinancialAccount::find($request->account_id);
                $account->balance += $request->paid_amount;
                $account->save();

                Balance::create([
                    'source_type' => 'customer',
                    'source_id' => $pos->customer_id,
                    'balance' => $pos->total_amount - $request->paid_amount,
                    'description' => 'POS Sale',
                    'reference' => $pos->sale_number,
                ]);
            }else{
                Balance::create([
                    'source_type' => 'customer',
                    'source_id' => $pos->customer_id,
                    'balance' => $pos->total_amount,
                    'description' => 'POS Sale',
                    'reference' => $pos->sale_number,
                ]);
            }

            DB::commit();
            
            // Send Sale Confirmation Email
            try {
                if ($pos->customer && $pos->customer->email) {
                    Mail::to($pos->customer->email)->send(new SaleConfirmation($pos));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send sale confirmation email: ' . $e->getMessage());
                // Don't fail the sale creation if email fails
            }
            
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('POS Sale Error: ' . $e->getMessage());
            DB::rollBack();
            return redirect()->back();
        }
    }

    public function index(Request $request)
    {
        if(Auth::user()->hasPermissionTo('view sales')){
        $query = Pos::with(['customer', 'invoice', 'branch']);

        // Search by sale_number, customer name, phone, email
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

        // Filter by status
        if ($request->filled('status')) {
            $query->where('pos.status', $request->input('status'));
        }

        // Filter by invoice status
        if ($request->filled('bill_status')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('status', $request->input('bill_status'));
            });
        }

        // Filter by estimated delivery date
        if ($request->filled('estimated_delivery_date')) {
            $query->whereDate('estimated_delivery_date', $request->input('estimated_delivery_date'));
        }

        // Order by latest created
        $query->orderBy('created_at', 'desc');

        $sales = $query->paginate(10)->withQueryString();
        return view('erp.pos.index', compact('sales'));
        }else{
            return redirect()->route('erp.dashboard');
        }
    }

    public function show($id)
    {
        if(Auth::user()->hasPermissionTo('view sale details')){
            $pos = Pos::where('id', $id)
                ->with(['customer', 'invoice', 'branch', 'invoice.invoiceAddress'])
                ->first();

            if (!$pos) {
                return redirect()->route('pos.list')->with('error', 'Sale not found.');
            }

            $bankAccounts = FinancialAccount::all();
            return view('erp.pos.show', compact('pos', 'bankAccounts'));
        }else{
            return redirect()->route('erp.dashboard');
        }
    }

    public function assignTechnician($saleId, $techId)
    {
        $pos = Pos::find($saleId);
        if (!$pos) {
            return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
        }
        $employee = \App\Models\Employee::find($techId);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Technician not found.'], 404);
        }
        $pos->employee_id = $techId;
        $pos->save();
        return response()->json(['success' => true, 'message' => 'Technician assigned successfully.']);
    }

    public function updateNote($saleId, Request $request)
    {
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

        // Create Journal Entry for POS Payment
        $this->createPosPaymentJournalEntry($pos, $request->amount, $request->account_id);

        if($request->payment_method == 'cash' && $pos->customer_id)
        {
            $balance = Balance::where('source_type', 'customer')->where('source_id', $pos->customer_id)->first();
            if($balance)
            {
                $balance->balance -= $request->amount;
                $balance->save();
            }
            else
            {
                Balance::create([
                    'source_type' => 'customer',
                    'source_id' => $pos->customer_id,
                    'balance' => $invoice->due_amount,
                    'description' => 'POS Sale',
                    'reference' => $pos->sale_number,
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
                    'description' => 'POS Sale',
                    'reference' => $pos->sale_number,
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

    public function updateStatus($saleId, Request $request)
    {
        $pos = Pos::find($saleId);
        if (!$pos) {
            return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
        }
        $request->validate([
            'status' => 'required|string',
        ]);

        if ($request->status == 'pending') {
            $pos->status = $request->input('status');
        } else if ($request->status == 'approved') {
            $pos->status = $request->input('status');
            foreach ($pos->items as $item) {
                $item->current_position_type = 'technician';
                $item->current_position_id = $pos->employee_id;
                $item->save();

                $branchStock = BranchProductStock::where('branch_id', $pos->branch_id)->where('product_id', $item->product_id)->first();
                if ($branchStock) {
                    $branchStock->quantity -= $item->quantity;
                    if ($branchStock->quantity < 0)
                        $branchStock->quantity = 0;
                    $branchStock->save();
                }

                $existTechStock = EmployeeProductStock::where('employee_id', $pos->employee_id)->where('product_id', $item->product_id)->first();
                if ($existTechStock) {
                    $existTechStock->quantity += $item->quantity;
                    $existTechStock->save();
                } else {
                    $techStock = new EmployeeProductStock();
                    $techStock->employee_id = $pos->employee_id;
                    $techStock->product_id = $item->product_id;
                    $techStock->quantity = $item->quantity;
                    $techStock->issued_by = auth()->id();
                    $techStock->save();
                }
            }
        } else if ($request->status == 'delivered') {
            $pos->status = $request->input('status');
            foreach ($pos->items as $item) {
                $item->current_position_id = null;
                $item->save();
                // Reduce from technician stock
                $techStock = EmployeeProductStock::where('employee_id', $pos->employee_id)->where('product_id', $item->product_id)->first();
                if ($techStock) {
                    $techStock->quantity -= $item->quantity;
                    if ($techStock->quantity < 0)
                        $techStock->quantity = 0;
                    $techStock->save();
                }
            }
        } else if ($request->status == 'shipping') {
            $pos->status = $request->input('status');
        } else if ($request->status == 'cancelled') {
            $pos->status = $request->input('status');
            foreach ($pos->items as $item) {
                // Reverse approved logic
                // Move back to branch
                $item->current_position_type = 'branch';
                $item->current_position_id = $pos->branch_id;
                $item->save();

                $branchStock = BranchProductStock::where('branch_id', $pos->branch_id)->where('product_id', $item->product_id)->first();
                if ($branchStock) {
                    $branchStock->quantity += $item->quantity;
                    $branchStock->save();
                } else {
                    // Optionally create new branch stock
                    $newBranchStock = new BranchProductStock();
                    $newBranchStock->branch_id = $pos->branch_id;
                    $newBranchStock->product_id = $item->product_id;
                    $newBranchStock->quantity = $item->quantity;
                    $newBranchStock->save();
                }

                $existTechStock = EmployeeProductStock::where('employee_id', $pos->employee_id)->where('product_id', $item->product_id)->first();
                if ($existTechStock) {
                    $existTechStock->quantity -= $item->quantity;
                    if ($existTechStock->quantity < 0)
                        $existTechStock->quantity = 0;
                    $existTechStock->save();
                }
            }
        }

        $pos->save();
        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function addAddress(Request $request, $id)
    {
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
        $q = $request->input('q');
        $query = \App\Models\Pos::with('customer');
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
        $results = $sales->map(function ($sale) {
            $customer = $sale->customer;
            $text = $sale->sale_number;
            if ($customer) {
                $text .= ' - ' . $customer->name;
                if ($customer->phone)
                    $text .= ' (' . $customer->phone . ')';
                if ($customer->email)
                    $text .= ' [' . $customer->email . ']';
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
        do {
            $number = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (\App\Models\Invoice::where('invoice_number', $number)->exists());
        return $number;
    }



    /**
     * Create Journal Entry for POS Sale
     */
    private function createPosJournalEntry($pos, $totalAmount, $paidAmount, $accountId = null)
    {
        try {
            // Find or create a journal for this date
            $journal = Journal::where('entry_date', $pos->sale_date)
                ->where('type', 'Receipt')
                ->where('description', 'like', '%POS Sale%')
                ->first();

            if (!$journal) {
                $journal = new Journal();
                $journal->type = 'Receipt'; // Using 'Receipt' which is allowed in the enum
                $journal->entry_date = $pos->sale_date;
                $journal->description = 'POS Sale - ' . $pos->sale_number;
                $journal->created_by = auth()->id();
                $journal->updated_by = auth()->id(); // Add the missing updated_by field
                $journal->save();
            }

            // Get default accounts (you may need to adjust these based on your chart of accounts)
            $cashAccount = ChartOfAccount::where('name', 'like', '%cash%')->orWhere('name', 'like', '%bank%')->first();
            $salesAccount = ChartOfAccount::where('name', 'like', '%sales%')->orWhere('name', 'like', '%revenue%')->orWhere('name', 'like', '%income%')->first();
            $accountsReceivableAccount = ChartOfAccount::where('name', 'like', '%accounts receivable%')->orWhere('name', 'like', '%debtors%')->orWhere('name', 'like', '%receivable%')->first();

            // Log what accounts we found for debugging
            Log::info('POS Journal Entry - Found accounts:', [
                'cash' => $cashAccount ? $cashAccount->name : 'NOT FOUND',
                'sales' => $salesAccount ? $salesAccount->name : 'NOT FOUND',
                'receivable' => $accountsReceivableAccount ? $accountsReceivableAccount->name : 'NOT FOUND'
            ]);

            // Log all available accounts for debugging
            $allAccounts = ChartOfAccount::all();
            Log::info('All available chart of accounts:', $allAccounts->pluck('name', 'id')->toArray());

            if (!$cashAccount || !$salesAccount || !$accountsReceivableAccount) {
                Log::warning('Required accounts not found for POS journal entry. Creating basic entry.');
                // Create basic entry without specific accounts
                $this->createBasicPosJournalEntry($journal, $pos, $totalAmount, $paidAmount);
                return;
            }

            // Create journal entries
            if ($paidAmount > 0) {
                // Debit Cash/Bank Account
                $this->createJournalEntry($journal->id, $cashAccount->id, $paidAmount, 0, 'Cash received for POS sale ' . $pos->sale_number);
            }

            if ($totalAmount > $paidAmount) {
                // Debit Accounts Receivable for unpaid amount
                $this->createJournalEntry($journal->id, $accountsReceivableAccount->id, $totalAmount - $paidAmount, 0, 'Accounts receivable for POS sale ' . $pos->sale_number);
            }

            // Credit Sales Account
            $this->createJournalEntry($journal->id, $salesAccount->id, 0, $totalAmount, 'Sales revenue for POS sale ' . $pos->sale_number);

            // Update financial account balances if they exist
            $this->updateFinancialAccountBalances($cashAccount, $salesAccount, $accountsReceivableAccount, $paidAmount, $totalAmount);

            Log::info('POS Journal Entry created successfully for sale: ' . $pos->sale_number);

        } catch (\Exception $e) {
            Log::error('Error creating POS journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Create basic journal entry when specific accounts are not found
     */
    private function createBasicPosJournalEntry($journal, $pos, $totalAmount, $paidAmount)
    {
        // Try to find any available accounts to use
        $anyAccount = ChartOfAccount::first();

        if (!$anyAccount) {
            Log::error('No chart of accounts found. Cannot create journal entries.');
            return;
        }

        // Create a simple entry with the first available account
        if ($paidAmount > 0) {
            $this->createJournalEntry($journal->id, $anyAccount->id, $paidAmount, 0, 'Cash received for POS sale ' . $pos->sale_number);
        }

        $this->createJournalEntry($journal->id, $anyAccount->id, 0, $totalAmount, 'Sales revenue for POS sale ' . $pos->sale_number);

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
     * Update financial account balances
     */
    private function updateFinancialAccountBalances($cashAccount, $salesAccount, $accountsReceivableAccount, $paidAmount, $totalAmount)
    {
        // Update cash account balance
        if ($paidAmount > 0) {
            $cashFinancialAccount = FinancialAccount::where('account_id', $cashAccount->id)->first();
            if ($cashFinancialAccount) {
                $cashFinancialAccount->balance += $paidAmount;
                $cashFinancialAccount->save();
            }
        }

        // Update accounts receivable balance
        if ($totalAmount > $paidAmount) {
            $arFinancialAccount = FinancialAccount::where('account_id', $accountsReceivableAccount->id)->first();
            if ($arFinancialAccount) {
                $arFinancialAccount->balance += ($totalAmount - $paidAmount);
                $arFinancialAccount->save();
            }
        }

        // Note: Sales account balance is typically not updated as it's an income account
        // and its balance is calculated from journal entries
    }

    /**
     * Create Journal Entry for POS Payment (following InvoiceController pattern)
     */
    private function createPosPaymentJournalEntry($pos, $amount, $accountId = null)
    {
        try {
            // Find or create a journal for this date
            $journal = Journal::where('entry_date', now()->toDateString())
                ->where('type', 'Receipt')
                ->where('description', 'like', '%POS Payment%')
                ->first();

            if (!$journal) {
                $journal = new Journal();
                $journal->type = 'Receipt'; // Using 'Receipt' which is allowed in the enum
                $journal->entry_date = now()->toDateString();
                $journal->description = 'POS Payment - ' . $pos->sale_number;
                $journal->created_by = auth()->id();
                $journal->updated_by = auth()->id();
                $journal->save();
            }

            // Get default accounts (you may need to adjust these based on your chart of accounts)
            $cashAccount = ChartOfAccount::where('name', 'like', '%cash%')->orWhere('name', 'like', '%bank%')->first();
            $accountsReceivableAccount = ChartOfAccount::where('name', 'like', '%accounts receivable%')->orWhere('name', 'like', '%debtors%')->orWhere('name', 'like', '%receivable%')->first();

            // Log what accounts we found for debugging
            Log::info('POS Payment Journal Entry - Found accounts:', [
                'cash' => $cashAccount ? $cashAccount->name : 'NOT FOUND',
                'receivable' => $accountsReceivableAccount ? $accountsReceivableAccount->name : 'NOT FOUND'
            ]);

            // Log all available accounts for debugging
            $allAccounts = ChartOfAccount::all();
            Log::info('All available chart of accounts:', $allAccounts->pluck('name', 'id')->toArray());

            if (!$cashAccount || !$accountsReceivableAccount) {
                Log::warning('Required accounts not found for POS payment journal entry. Creating basic entry.');
                // Create basic entry without specific accounts
                $this->createBasicPosPaymentJournalEntry($journal, $pos, $amount);
                return;
            }

            // Create journal entries for POS payment
            // Debit Cash/Bank Account (money received)
            $this->createJournalEntry($journal->id, $cashAccount->id, $amount, 0, 'Cash received for POS payment ' . $pos->sale_number);

            // Credit Accounts Receivable (reducing the receivable)
            $this->createJournalEntry($journal->id, $accountsReceivableAccount->id, 0, $amount, 'Accounts receivable reduced for POS ' . $pos->sale_number);

            // Update financial account balances if they exist
            $this->updatePosPaymentFinancialAccountBalances($cashAccount, $accountsReceivableAccount, $amount);

            Log::info('POS Payment Journal Entry created successfully for sale: ' . $pos->sale_number);

        } catch (\Exception $e) {
            Log::error('Error creating POS payment journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Create basic journal entry when specific accounts are not found for POS payment
     */
    private function createBasicPosPaymentJournalEntry($journal, $pos, $amount)
    {
        // Try to find any available accounts to use
        $anyAccount = ChartOfAccount::first();

        if (!$anyAccount) {
            Log::error('No chart of accounts found. Cannot create journal entries.');
            return;
        }

        // Create a simple entry with the first available account
        $this->createJournalEntry($journal->id, $anyAccount->id, $amount, 0, 'Cash received for POS payment ' . $pos->sale_number);
        $this->createJournalEntry($journal->id, $anyAccount->id, 0, $amount, 'Accounts receivable reduced for POS ' . $pos->sale_number);


    }

    /**
     * Update financial account balances for POS payments
     */
    private function updatePosPaymentFinancialAccountBalances($cashAccount, $accountsReceivableAccount, $amount)
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
     * Get report data for the modal
     */
    public function getReportData(Request $request)
    {
        if(!Auth::user()->hasPermissionTo('view sales')){
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Pos::with(['customer', 'invoice', 'branch']);

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Payment status filter
        if ($request->filled('payment_status')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('status', $request->payment_status);
            });
        }

        // Branch filter (if user doesn't have global access)
        if (!Auth::user()->hasPermissionTo('manage global branches')) {
            $query->where('branch_id', Auth::user()->employee->branch_id);
        }

        $sales = $query->get();

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
            'paid_sales' => $sales->filter(function($sale) {
                return $sale->invoice && $sale->invoice->status === 'paid';
            })->count(),
            'unpaid_sales' => $sales->filter(function($sale) {
                return $sale->invoice && $sale->invoice->status === 'unpaid';
            })->count(),
        ];

        return response()->json([
            'sales' => $transformedSales,
            'summary' => $summary
        ]);
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        if(!Auth::user()->hasPermissionTo('view sales')){
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Pos::with(['customer', 'invoice', 'branch']);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('status', $request->payment_status);
            });
        }

        // Branch filter
        if (!Auth::user()->hasPermissionTo('manage global branches')) {
            $query->where('branch_id', Auth::user()->employee->branch_id);
        }

        $sales = $query->get();
        $selectedColumns = $request->filled('columns') ? explode(',', $request->columns) : [];

        // Validate that at least one column is selected
        if (empty($selectedColumns)) {
            return response()->json(['error' => 'Please select at least one column to export.'], 400);
        }

        // Prepare data for export
        $exportData = [];
        
        // Add headers
        $headers = [];
        $columnMap = [
            'pos_id' => 'POS ID',
            'sale_date' => 'Sale Date',
            'customer' => 'Customer',
            'phone' => 'Phone',
            'branch' => 'Branch',
            'status' => 'Status',
            'payment_status' => 'Payment Status',
            'subtotal' => 'Subtotal',
            'discount' => 'Discount',
            'total' => 'Total',
            'paid_amount' => 'Paid Amount',
            'due_amount' => 'Due Amount'
        ];

        foreach ($selectedColumns as $column) {
            if (isset($columnMap[$column])) {
                $headers[] = $columnMap[$column];
            }
        }
        $exportData[] = $headers;

        // Add data rows
        foreach ($sales as $sale) {
            $row = [];
            foreach ($selectedColumns as $column) {
                switch ($column) {
                    case 'pos_id':
                        $row[] = $sale->sale_number ?? '-';
                        break;
                    case 'sale_date':
                        $row[] = $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d-m-Y') : '-';
                        break;
                    case 'customer':
                        $row[] = $sale->customer ? $sale->customer->name : 'Walk-in Customer';
                        break;
                    case 'phone':
                        $row[] = $sale->customer ? $sale->customer->phone : '-';
                        break;
                    case 'branch':
                        $row[] = $sale->branch ? $sale->branch->name : '-';
                        break;
                    case 'status':
                        $row[] = ucfirst($sale->status ?? '-');
                        break;
                    case 'payment_status':
                        $row[] = $sale->invoice ? ucfirst($sale->invoice->status) : '-';
                        break;
                    case 'subtotal':
                        $row[] = number_format($sale->sub_total, 2);
                        break;
                    case 'discount':
                        $row[] = number_format($sale->discount, 2);
                        break;
                    case 'total':
                        $row[] = number_format($sale->total_amount, 2);
                        break;
                    case 'paid_amount':
                        $row[] = $sale->invoice ? number_format($sale->invoice->paid_amount, 2) : '0.00';
                        break;
                    case 'due_amount':
                        $row[] = $sale->invoice ? number_format($sale->invoice->due_amount, 2) : '0.00';
                        break;
                }
            }
            $exportData[] = $row;
        }

        // Generate filename
        $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Create Excel file using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add title
        $sheet->setCellValue('A1', 'Sales Report');
        if (count($headers) > 0) {
            $sheet->mergeCells('A1:' . chr(65 + count($headers) - 1) . '1');
        }
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Add summary info
        $totalSales = $sales->count();
        $totalAmount = $sales->sum('total_amount');
        $paidSales = $sales->filter(function($sale) {
            return $sale->invoice && $sale->invoice->status === 'paid';
        })->count();
        $unpaidSales = $sales->filter(function($sale) {
            return $sale->invoice && $sale->invoice->status === 'unpaid';
        })->count();
        
        if (count($headers) > 0) {
            $sheet->setCellValue('A2', 'Summary: Total Sales: ' . $totalSales . ' | Total Amount: ৳' . number_format($totalAmount, 2) . ' | Paid: ' . $paidSales . ' | Unpaid: ' . $unpaidSales);
            $sheet->mergeCells('A2:' . chr(65 + count($headers) - 1) . '2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F0F8FF');
        }
        
        // Add filters info
        $filterInfo = [];
        if ($request->filled('date_from')) $filterInfo[] = 'From: ' . $request->date_from;
        if ($request->filled('date_to')) $filterInfo[] = 'To: ' . $request->date_to;
        if ($request->filled('status')) $filterInfo[] = 'Status: ' . ucfirst($request->status);
        if ($request->filled('payment_status')) $filterInfo[] = 'Payment Status: ' . ucfirst($request->payment_status);
        
        if (!empty($filterInfo) && count($headers) > 0) {
            $sheet->setCellValue('A3', 'Filters: ' . implode(', ', $filterInfo));
            $sheet->mergeCells('A3:' . chr(65 + count($headers) - 1) . '3');
            $sheet->getStyle('A3')->getFont()->setItalic(true);
        }
        
        // Add headers
        $headerRow = 4;
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . $headerRow, $header);
            $sheet->getStyle(chr(65 + $index) . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle(chr(65 + $index) . $headerRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        }
        
        // Add data
        $dataRow = 5;
        $totalRow = $dataRow;
        foreach ($exportData as $rowIndex => $row) {
            if ($rowIndex === 0) continue; // Skip headers as we already added them
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue(chr(65 + $colIndex) . $dataRow, $value);
            }
            $dataRow++;
        }
        $totalRow = $dataRow; // This will be the row after the last data row
        
        // Add totals row
        if ($sales->count() > 0) {
            $sheet->setCellValue('A' . $totalRow, 'TOTAL');
            $sheet->getStyle('A' . $totalRow)->getFont()->setBold(true);
            
            // Calculate and add totals for specific columns
            $totalAmount = 0;
            $totalPaidAmount = 0;
            $totalDueAmount = 0;
            
            foreach ($sales as $sale) {
                $totalAmount += $sale->total_amount ?? 0;
                if ($sale->invoice) {
                    $totalPaidAmount += $sale->invoice->paid_amount ?? 0;
                    $totalDueAmount += $sale->invoice->due_amount ?? 0;
                }
            }
            
            // Add totals to the appropriate columns
            foreach ($selectedColumns as $colIndex => $column) {
                $cellAddress = chr(65 + $colIndex) . $totalRow;
                
                switch ($column) {
                    case 'total':
                        $sheet->setCellValue($cellAddress, number_format($totalAmount, 2));
                        $sheet->getStyle($cellAddress)->getFont()->setBold(true);
                        break;
                    case 'paid_amount':
                        $sheet->setCellValue($cellAddress, number_format($totalPaidAmount, 2));
                        $sheet->getStyle($cellAddress)->getFont()->setBold(true);
                        break;
                    case 'due_amount':
                        $sheet->setCellValue($cellAddress, number_format($totalDueAmount, 2));
                        $sheet->getStyle($cellAddress)->getFont()->setBold(true);
                        break;
                    default:
                        // For other columns, leave empty or add count if it's the first column
                        if ($colIndex === 0) {
                            $sheet->setCellValue($cellAddress, $sales->count() . ' Sales');
                            $sheet->getStyle($cellAddress)->getFont()->setBold(true);
                        }
                        break;
                }
            }
            
            // Style the totals row
            $sheet->getStyle('A' . $totalRow . ':' . chr(65 + count($headers) - 1) . $totalRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E8F4FD');
        }
        
        // Auto-size columns
        foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create writer and output
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $filename);
        $writer->save($filePath);
        
        return response()->download($filePath, $filename)->deleteFileAfterSend();
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        if(!Auth::user()->hasPermissionTo('view sales')){
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Pos::with(['customer', 'invoice', 'branch']);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('status', $request->payment_status);
            });
        }

        // Branch filter
        if (!Auth::user()->hasPermissionTo('manage global branches')) {
            $query->where('branch_id', Auth::user()->employee->branch_id);
        }

        $sales = $query->get();
        $selectedColumns = $request->filled('columns') ? explode(',', $request->columns) : [];

        // Validate that at least one column is selected
        if (empty($selectedColumns)) {
            return response()->json(['error' => 'Please select at least one column to export.'], 400);
        }

        // Prepare data for export
        $columnMap = [
            'pos_id' => 'POS ID',
            'sale_date' => 'Sale Date',
            'customer' => 'Customer',
            'phone' => 'Phone',
            'branch' => 'Branch',
            'status' => 'Status',
            'payment_status' => 'Payment Status',
            'subtotal' => 'Subtotal',
            'discount' => 'Discount',
            'total' => 'Total',
            'paid_amount' => 'Paid Amount',
            'due_amount' => 'Due Amount'
        ];

        $headers = [];
        foreach ($selectedColumns as $column) {
            if (isset($columnMap[$column])) {
                $headers[] = $columnMap[$column];
            }
        }

        // Calculate summary
        $summary = [
            'total_sales' => $sales->count(),
            'total_amount' => number_format($sales->sum('total_amount'), 2),
            'paid_sales' => $sales->filter(function($sale) {
                return $sale->invoice && $sale->invoice->status === 'paid';
            })->count(),
            'unpaid_sales' => $sales->filter(function($sale) {
                return $sale->invoice && $sale->invoice->status === 'unpaid';
            })->count(),
        ];

        // Generate filename
        $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.pdf';

        // Create PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.pos.report-pdf', [
            'sales' => $sales,
            'headers' => $headers,
            'selectedColumns' => $selectedColumns,
            'summary' => $summary,
            'filters' => [
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'status' => $request->status,
                'payment_status' => $request->payment_status,
            ]
        ]);

        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download($filename);
    }

    private function generateSaleNumber()
    {
        $today = now();
        $dateString = $today->format('dmy');
        
        $lastSale = Pos::latest()->first();
        if (!$lastSale) {
            return "skp-{$dateString}01";
        }
        $serialNumber = str_pad($lastSale->id + 1, 2, '0', STR_PAD_LEFT);
        
        return "skp-{$dateString}{$serialNumber}";
    }
}
