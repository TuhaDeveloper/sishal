<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\GeneralSetting;
use App\Models\Payment;
use App\Models\Journal;
use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf; // Add this at the top

class InvoiceController extends Controller
{
    public function templateList(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view invoice list template')) {
            abort(403, 'Unauthorized action.');
        }
        $query = InvoiceTemplate::query();

        // Search by name
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $templates = $query->orderBy('created_at', 'desc')->paginate(10)->appends($request->all());
        $filters = $request->only(['search']);
        return view('erp.invoiceTemplate.invoiceTemplateList', compact('templates', 'filters'));
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'footer_note' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);
        $validated['is_default'] = $request->has('is_default') ? 1 : 0;
        if ($validated['is_default'] == 1) {
            InvoiceTemplate::where('is_default', 1)->update(['is_default' => 0]);
        }
        InvoiceTemplate::create($validated);
        return redirect()->route('invoice.template.list')->with('success', 'Template created successfully.');
    }

    public function updateTemplate(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'footer_note' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);
        $validated['is_default'] = $request->has('is_default') ? 1 : 0;
        if ($validated['is_default'] == 1) {
            InvoiceTemplate::where('is_default', 1)->where('id', '!=', $id)->update(['is_default' => 0]);
        }
        $template = InvoiceTemplate::findOrFail($id);
        $template->update($validated);
        return redirect()->route('invoice.template.list')->with('success', 'Template updated successfully.');
    }

    public function deleteTemplate($id)
    {
        $template = InvoiceTemplate::findOrFail($id);
        $template->delete();
        return redirect()->route('invoice.template.list')->with('success', 'Template deleted successfully.');
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view invoice list')) {
            abort(403, 'Unauthorized action.');
        }
        $query = Invoice::query();

        // Join salesman (user) and employee for phone
        $query->leftJoin('users as salesman', 'invoices.created_by', '=', 'salesman.id')
              ->leftJoin('employees as emp', 'salesman.id', '=', 'emp.user_id')
              ->select('invoices.*');

        // Join customer for search/filter
        $query->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id');

        // Search by id, customer, salesman
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('invoices.id', 'like', "%$search%")
                ->orWhere('invoices.invoice_number', 'like', "%$search%")
                    ->orWhere('customers.name', 'like', "%$search%")
                    ->orWhere('customers.email', 'like', "%$search%")
                    ->orWhere('customers.phone', 'like', "%$search%")
                    ->orWhere('salesman.first_name', 'like', "%$search%")
                    ->orWhere('salesman.last_name', 'like', "%$search%")
                    ->orWhere('salesman.email', 'like', "%$search%")
                    ->orWhere('emp.phone', 'like', "%$search%")
                    ;
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('invoices.status', $status);
        }

        // Filter by issue_date
        if ($issueDate = $request->input('issue_date')) {
            $query->whereDate('invoices.issue_date', $issueDate);
        }

        // Filter by due_date
        if ($dueDate = $request->input('due_date')) {
            $query->whereDate('invoices.due_date', $dueDate);
        }

        // Filter by customer
        if ($customerId = $request->input('customer_id')) {
            $query->where('invoices.customer_id', $customerId);
        }

        $invoices = $query->distinct()->orderBy('invoices.created_at', 'desc')->paginate(10)->appends($request->all());
        $statuses = ['unpaid', 'partial', 'paid'];
        $filters = $request->only(['search', 'status', 'issue_date', 'due_date', 'customer_id']);
        $customers = \App\Models\Customer::orderBy('name')->get();
        return view('erp.invoices.invoicelist', compact('invoices', 'statuses', 'filters', 'customers'));
    }

    public function create()
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        $products = \App\Models\Product::orderBy('name')->get();
        $templates = \App\Models\InvoiceTemplate::orderBy('name')->get();
        return view('erp.invoices.create', compact('customers', 'products', 'templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'template_id' => 'required|exists:invoice_templates,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'send_date' => 'nullable|date',
            'note' => 'nullable|string',
            'footer_text' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'billing_address_1' => 'required|string',
            'billing_address_2' => 'nullable|string',
            'billing_city' => 'nullable|string',
            'billing_state' => 'nullable|string',
            'billing_country' => 'nullable|string',
            'billing_zip_code' => 'nullable|string',
            'shipping_address_1' => 'nullable|string',
            'shipping_address_2' => 'nullable|string',
            'shipping_city' => 'nullable|string',
            'shipping_state' => 'nullable|string',
            'shipping_country' => 'nullable|string',
            'shipping_zip_code' => 'nullable|string',
        ]);
        \DB::beginTransaction();
        try {
            $invoiceNumber = $this->generateInvoiceNumber();
            
            // Calculate subtotal from items
            $subtotal = collect($request->items)->sum(function($item) {
                return $item['total_price'];
            });
            
            // Get tax rate from general settings
            $generalSettings = GeneralSetting::first();
            $taxRate = $generalSettings ? ($generalSettings->tax_rate / 100) : 0.00;
            $tax = round($subtotal * $taxRate, 2);
            
            // Calculate total amount including tax
            $totalAmount = $subtotal + $tax - $request->input('discount_apply', 0);
            $paidAmount = $request->input('paid_amount', 0);
            $dueAmount = $totalAmount - $paidAmount;
            
            $invoice = \App\Models\Invoice::create([
                'invoice_number' => $invoiceNumber,
                'template_id' => $request->template_id,
                'customer_id' => $request->customer_id,
                'operated_by' => auth()->id(),
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'send_date' => $request->send_date,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'discount_apply' => $request->input('discount_apply', 0),
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => 'unpaid',
                'note' => $request->note,
                'footer_text' => $request->footer_text,
                'created_by' => auth()->id(),
            ]);
            foreach ($request->items as $item) {
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }
            $customer = Customer::find($request->customer_id);
            if($customer->address_1) {
                \App\Models\InvoiceAddress::create([
                    'invoice_id' => $invoice->id,
                    'billing_address_1' => $customer->address_1,
                    'billing_address_2' => $customer->address_2,
                    'billing_city' => $customer->city,
                    'billing_state' => $customer->state,
                    'billing_country' => $customer->country,
                    'billing_zip_code' => $customer->zip_code,
                    'shipping_address_1' => $customer->address_1,
                    'shipping_address_2' => $customer->address_2,
                    'shipping_city' => $customer->city,
                    'shipping_state' => $customer->state,
                    'shipping_country' => $customer->country,
                    'shipping_zip_code' => $customer->zip_code,
                ]);
            }
            
            // Create payment if paid_amount > 0
            if ($paidAmount > 0) {
                \App\Models\Payment::create([
                    'payment_for' => 'invoice',
                    'invoice_id' => $invoice->id,
                    'payment_date' => now(),
                    'amount' => $paidAmount,
                    'customer_id' => $invoice->customer_id,
                ]);
            }

            if($dueAmount == 0)
            {
                $invoice->status = 'paid';
                $invoice->save();
            }else if($dueAmount > 0){
                $invoice->status = 'partial';
                $invoice->save();
            }else if($totalAmount == $dueAmount ){
                $invoice->status = 'unpaid';
                $invoice->save();
            }

            \DB::commit();
            return redirect()->route('invoice.list')->with('success', 'Invoice created successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'template_id' => 'required|exists:invoice_templates,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'send_date' => 'nullable|date',
            'note' => 'nullable|string',
            'footer_text' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'billing_address_1' => 'required|string',
            'billing_address_2' => 'nullable|string',
            'billing_city' => 'nullable|string',
            'billing_state' => 'nullable|string',
            'billing_country' => 'nullable|string',
            'billing_zip_code' => 'nullable|string',
            'shipping_address_1' => 'nullable|string',
            'shipping_address_2' => 'nullable|string',
            'shipping_city' => 'nullable|string',
            'shipping_state' => 'nullable|string',
            'shipping_country' => 'nullable|string',
            'shipping_zip_code' => 'nullable|string',
        ]);
        \DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($id);
            
            // Calculate subtotal from items
            $subtotal = collect($request->items)->sum(function($item) {
                return $item['total_price'];
            });
            
            // Get tax rate from general settings
            $generalSettings = GeneralSetting::first();
            $taxRate = $generalSettings ? ($generalSettings->tax_rate / 100) : 0.00;
            $tax = round($subtotal * $taxRate, 2);
            
            // Calculate total amount including tax
            $discount = $request->input('discount_apply', 0);
            $totalAmount = $subtotal + $tax - $discount;
            $paidAmount = $invoice->paid_amount;
            $dueAmount = $totalAmount - $paidAmount;

            $invoice->update([
                'template_id' => $request->template_id,
                'customer_id' => $request->customer_id,
                'operated_by' => auth()->id(),
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'send_date' => $request->send_date,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'discount_apply' => $discount,
                'due_amount' => $dueAmount,
                'note' => $request->note,
                'footer_text' => $request->footer_text,
            ]);
            // Remove old items and add new
            $invoice->items()->delete();
            foreach ($request->items as $item) {
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['unit_price'] * $item['quantity'],
                ]);
            }
            // Update address
            $invoice->invoiceAddress()->delete();
            \App\Models\InvoiceAddress::create([
                'invoice_id' => $invoice->id,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2,
                'billing_city' => $request->billing_city,
                'billing_state' => $request->billing_state,
                'billing_country' => $request->billing_country,
                'billing_zip_code' => $request->billing_zip_code,
                'shipping_address_1' => $request->shipping_address_1,
                'shipping_address_2' => $request->shipping_address_2,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_country' => $request->shipping_country,
                'shipping_zip_code' => $request->shipping_zip_code,
            ]);
            // Update status
            if($dueAmount == 0)
            {
                $invoice->status = 'paid';
                $invoice->save();
            }else if($dueAmount > 0){
                $invoice->status = 'partial';
                $invoice->save();
            }else if($totalAmount == $dueAmount ){
                $invoice->status = 'unpaid';
                $invoice->save();
            }
            \DB::commit();
            return redirect()->route('invoice.list')->with('success', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $invoice = \App\Models\Invoice::with(['customer', 'invoiceAddress', 'items.product'])->findOrFail($id);
        $templates = \App\Models\InvoiceTemplate::orderBy('name')->get();
        return view('erp.invoices.edit', compact('invoice', 'templates'));
    }

    public function show($id)
    {
        $invoice = Invoice::with('pos','payments','customer','invoiceAddress','salesman','items')->find($id);
        $bankAccounts = \App\Models\FinancialAccount::all();
        return view('erp.invoices.show',compact('invoice', 'bankAccounts'));
    }

    public function addPayment($invId, Request $request)
    {
        $invoice = Invoice::find($invId);

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
        $payment->payment_for = 'invoice';
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

        // Create Journal Entry for Invoice Payment
        $this->createInvoiceJournalEntry($invoice, $request->amount, $request->account_id);

        $account = FinancialAccount::find($request->account_id);
        $account->balance += $request->amount;
        $account->save();

        return response()->json(['success' => true, 'message' => 'Payment added successfully.']);
    }

    private function createInvoiceJournalEntry($invoice, $amount, $accountId = null)
    {
        try {
            // Find or create a journal for this date
            $journal = Journal::where('entry_date', now()->toDateString())
                ->where('type', 'Receipt')
                ->where('description', 'like', '%Invoice Payment%')
                ->first();

            if (!$journal) {
                $journal = new Journal();
                $journal->type = 'Receipt'; // Using 'Receipt' which is allowed in the enum
                $journal->entry_date = now()->toDateString();
                $journal->description = 'Invoice Payment - ' . $invoice->invoice_number;
                $journal->created_by = auth()->id();
                $journal->updated_by = auth()->id();
                $journal->save();
            }

            // Get default accounts (you may need to adjust these based on your chart of accounts)
            $cashAccount = ChartOfAccount::where('name', 'like', '%cash%')->orWhere('name', 'like', '%bank%')->first();
            $accountsReceivableAccount = ChartOfAccount::where('name', 'like', '%accounts receivable%')->orWhere('name', 'like', '%debtors%')->orWhere('name', 'like', '%receivable%')->first();

            // Log what accounts we found for debugging
            Log::info('Invoice Payment Journal Entry - Found accounts:', [
                'cash' => $cashAccount ? $cashAccount->name : 'NOT FOUND',
                'receivable' => $accountsReceivableAccount ? $accountsReceivableAccount->name : 'NOT FOUND'
            ]);

            // Log all available accounts for debugging
            $allAccounts = ChartOfAccount::all();
            Log::info('All available chart of accounts:', $allAccounts->pluck('name', 'id')->toArray());

            if (!$cashAccount || !$accountsReceivableAccount) {
                Log::warning('Required accounts not found for Invoice payment journal entry. Creating basic entry.');
                // Create basic entry without specific accounts
                $this->createBasicInvoiceJournalEntry($journal, $invoice, $amount);
                return;
            }

            // Create journal entries for invoice payment
            // Debit Cash/Bank Account (money received)
            $this->createJournalEntry($journal->id, $cashAccount->id, $amount, 0, 'Cash received for invoice payment ' . $invoice->invoice_number);

            // Credit Accounts Receivable (reducing the receivable)
            $this->createJournalEntry($journal->id, $accountsReceivableAccount->id, 0, $amount, 'Accounts receivable reduced for invoice ' . $invoice->invoice_number);

            // Update financial account balances if they exist
            $this->updateInvoiceFinancialAccountBalances($cashAccount, $accountsReceivableAccount, $amount);

            Log::info('Invoice Payment Journal Entry created successfully for invoice: ' . $invoice->invoice_number);

        } catch (\Exception $e) {
            Log::error('Error creating Invoice payment journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Create basic journal entry when specific accounts are not found
     */
    private function createBasicInvoiceJournalEntry($journal, $invoice, $amount)
    {
        // Try to find any available accounts to use
        $anyAccount = ChartOfAccount::first();
        
        if (!$anyAccount) {
            Log::error('No chart of accounts found. Cannot create journal entries.');
            return;
        }

        // Create a simple entry with the first available account
        $this->createJournalEntry($journal->id, $anyAccount->id, $amount, 0, 'Cash received for invoice payment ' . $invoice->invoice_number);
        $this->createJournalEntry($journal->id, $anyAccount->id, 0, $amount, 'Accounts receivable reduced for invoice ' . $invoice->invoice_number);
        
        Log::warning('Created basic invoice journal entries using fallback account: ' . $anyAccount->name);
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
     * Update financial account balances for invoice payments
     */
    private function updateInvoiceFinancialAccountBalances($cashAccount, $accountsReceivableAccount, $amount)
    {
        // Update cash account balance (money received)
        $cashFinancialAccount = \App\Models\FinancialAccount::where('account_id', $cashAccount->id)->first();
        if ($cashFinancialAccount) {
            $cashFinancialAccount->balance += $amount;
            $cashFinancialAccount->save();
        }

        // Update accounts receivable balance (reducing the receivable)
        $arFinancialAccount = \App\Models\FinancialAccount::where('account_id', $accountsReceivableAccount->id)->first();
        if ($arFinancialAccount) {
            $arFinancialAccount->balance -= $amount; // Reduce receivable
            $arFinancialAccount->save();
        }
    }

    public function print(Request $request, $invoice_number)
    {
        $invoice = Invoice::where('invoice_number', $invoice_number)->first();
        if(!$invoice)
        {
            return redirect()->route('invoice.print', ['invoice_number' => 'notfound'])->with('error', 'Invoice not found.');
        }
        $template = InvoiceTemplate::find($invoice->template_id);
        $general_settings = GeneralSetting::first();

        $action = $request->action;

        // Calculate tax if not already calculated
        if (!$invoice->tax && $general_settings && $general_settings->tax_rate > 0) {
            $taxRate = $general_settings->tax_rate / 100;
            $invoice->tax = round($invoice->subtotal * $taxRate, 2);
        }

        // Generate QR code as SVG (no imagick required)
        $printUrl = route('invoice.print', ['invoice_number' => $invoice->invoice_number]);
        $qrCodeSvg = QrCode::format('svg')->size(60)->generate($printUrl);

        // PDF download logic
        if ($action == 'download') {
            $pdf = Pdf::loadView('erp.invoices.print', compact('invoice', 'template', 'action', 'qrCodeSvg', 'general_settings'));
            return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
        }

        return view('erp.invoices.print', compact('invoice', 'template', 'action', 'qrCodeSvg', 'general_settings'));
    }

    private function generateInvoiceNumber()
    {
        $generalSettings = GeneralSetting::first();
        $prefix = $generalSettings ? $generalSettings->invoice_prefix : 'INV';
        
        do {
            $number = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $fullNumber = $prefix . $number;
        } while (Invoice::where('invoice_number', $fullNumber)->exists());
        
        return $fullNumber;
    }
}
