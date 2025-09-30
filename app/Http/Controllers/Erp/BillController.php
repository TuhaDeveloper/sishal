<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillPayment;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseBill::query()->with('vendor');

        // Search by bill_number
        if ($request->filled('bill_number')) {
            $query->where('bill_number', 'like', '%' . $request->bill_number . '%');
        }
        // Filter by bill_date
        if ($request->filled('bill_date')) {
            $query->whereDate('bill_date', $request->bill_date);
        }
        // Filter by due_date
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }
        // Filter by supplier_id
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bills = $query->paginate(10)->appends($request->except('page'));
        $suppliers = \App\Models\Supplier::all();
        return view('erp.bill.billlist', compact('bills', 'suppliers'));
    }

    public function create()
    {
        return view('erp.bill.create');
    }

    public function store(Request $request)
    {

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            // add more item fields as needed
        ]);

        DB::beginTransaction();
        try {
            $bill = PurchaseBill::create([
                'bill_number' => $this->generateBillNumber(),
                'supplier_id' => $request->supplier_id,
                'bill_date' => $request->bill_date,
                'due_date' => $request->due_date,
                'total_amount' => $request->total_amount,
                'paid_amount' => $request->paid_amount,
                'due_amount' => $request->due_amount,
                'created_by' => $request->user() ? $request->user()->id : null,
                // add other fields as needed
            ]);

            if ($request->paid_amount == 0) {
                $bill->status = 'unpaid';
                $bill->save();
            } elseif ($request->total_amount > $request->paid_amount) {
                $bill->status = 'partial';
                $bill->save();
            } else {
                $bill->status = 'paid';
                $bill->save();
            }

            if ($request->paid_amount > 0) {
                $billpayment = new PurchaseBillPayment();

                $billpayment->bill_id = $bill->id;
                $billpayment->amount = $request->paid_amount;
                $billpayment->payment_date = now();
                $billpayment->method = 'cash';

                $billpayment->save();
            }

            foreach ($request->items as $item) {
                $bill->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price']
                ]);
            }

            // Create Journal Entry for Bill Payment
            $this->createBillPaymentJournalEntry($bill, $request->paid_amount, $request->account_id);

            DB::commit();
            return redirect()->route('bill.list')->with('success', 'Bill created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $bill = PurchaseBill::where('id', $id)->with(['items', 'payments', 'vendor'])->first();
        return view('erp.bill.show', compact('bill'));
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $bill = PurchaseBill::with(['items', 'payments'])->findOrFail($id);
            // Delete related bill items
            $bill->items()->delete();
            // Delete related payments
            if (method_exists($bill, 'payments')) {
                $bill->payments()->delete();
            }
            // Delete the bill itself
            $bill->delete();
            DB::commit();
            return redirect()->route('bill.list')->with('success', 'Bill, items, and payments deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    // Bill Payment

    public function addPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $bill = PurchaseBill::findOrFail($id);
            if ($request->amount > $bill->due_amount) {
                return back()->withErrors(['amount' => 'Payment exceeds due amount.']);
            }
            $newPaidAmount = $bill->paid_amount + $request->amount;
            $dueAmount = $bill->due_amount - $request->amount;

            // Create payment
            $payment = new PurchaseBillPayment();
            $payment->bill_id = $bill->id;
            $payment->amount = $request->amount;
            $payment->method = 'cash';
            $payment->payment_date = $request->payment_date;
            $payment->save();

            // Update bill
            $bill->paid_amount = $newPaidAmount;
            $bill->due_amount = $dueAmount;
            if ($newPaidAmount == 0) {
                $bill->status = 'unpaid';
            } elseif ($dueAmount == 0) {
                $bill->status = 'paid';
            } else {
                $bill->status = 'partial';
            }
            $bill->save();

            // Create Journal Entry for Bill Payment
            $this->createBillPaymentJournalEntry($bill, $request->amount, $request->account_id);

            DB::commit();
            return redirect()->route('bill.show', $bill->id)->with('success', 'Payment added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $bill = PurchaseBill::with(['items', 'vendor'])->findOrFail($id);
        $suppliers = \App\Models\Supplier::all();
        return view('erp.bill.edit', compact('bill', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $bill = PurchaseBill::findOrFail($id);
            $bill->supplier_id = $request->supplier_id;
            $bill->bill_date = $request->bill_date;
            $bill->due_date = $request->due_date;
            $bill->total_amount = $request->total_amount;
            $bill->paid_amount = $request->paid_amount;
            $bill->due_amount = $request->due_amount;
            $bill->description = $request->description;
            $bill->save();

            // Update status
            if ($request->paid_amount == 0) {
                $bill->status = 'unpaid';
            } elseif ($request->total_amount > $request->paid_amount) {
                $bill->status = 'partial';
            } else {
                $bill->status = 'paid';
            }
            $bill->save();

            // Update items: delete old, add new
            $bill->items()->delete();
            foreach ($request->items as $item) {
                $bill->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'description' => $item['description'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('bill.show', $bill->id)->with('success', 'Bill updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    /**
     * Create Journal Entry for Bill Payment (following InvoiceController pattern)
     */
    private function createBillPaymentJournalEntry($bill, $amount, $accountId = null)
    {
        try {
            // Find or create a journal for this date
            $journal = Journal::where('entry_date', $bill->bill_date)
                ->where('type', 'Payment')
                ->where('description', 'like', '%Bill Payment%')
                ->first();

            if (!$journal) {
                $journal = new Journal();
                $journal->type = 'Payment'; // Using 'Payment' which is allowed in the enum
                $journal->entry_date = $bill->bill_date;
                $journal->description = 'Bill Payment - ' . $bill->bill_number;
                $journal->created_by = auth()->id();
                $journal->updated_by = auth()->id();
                $journal->save();
            }

            // Get default accounts (you may need to adjust these based on your chart of accounts)
            $cashAccount = ChartOfAccount::where('name', 'like', '%cash%')->orWhere('name', 'like', '%bank%')->first();
            $accountsPayableAccount = ChartOfAccount::where('name', 'like', '%accounts payable%')->orWhere('name', 'like', '%creditors%')->orWhere('name', 'like', '%payable%')->first();

            Log::info('Bill Payment Journal Entry - Found accounts:', [
                'cash' => $cashAccount ? $cashAccount->name : 'NOT FOUND',
                'payable' => $accountsPayableAccount ? $accountsPayableAccount->name : 'NOT FOUND'
            ]);

            $allAccounts = ChartOfAccount::all();
            Log::info('All available chart of accounts:', $allAccounts->pluck('name', 'id')->toArray());

            if (!$cashAccount || !$accountsPayableAccount) {
                Log::warning('Required accounts not found for Bill payment journal entry. Creating basic entry.');
                $this->createBasicBillPaymentJournalEntry($journal, $bill, $amount);
                return;
            }

            // Create journal entries for Bill payment
            // Debit Accounts Payable (reducing the payable)
            $this->createJournalEntry($journal->id, $accountsPayableAccount->id, $amount, 0, 'Accounts payable reduced for Bill ' . $bill->bill_number);

            // Credit Cash/Bank Account (money paid out)
            $this->createJournalEntry($journal->id, $cashAccount->id, 0, $amount, 'Cash paid for Bill ' . $bill->bill_number);

            // Update financial account balances if they exist
            $this->updateBillPaymentFinancialAccountBalances($cashAccount, $accountsPayableAccount, $amount);

            Log::info('Bill Payment Journal Entry created successfully for bill: ' . $bill->bill_number);

        } catch (\Exception $e) {
            Log::error('Error creating Bill payment journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Create basic journal entry when specific accounts are not found for Bill payment
     */
    private function createBasicBillPaymentJournalEntry($journal, $bill, $amount)
    {
        $anyAccount = ChartOfAccount::first();
        if (!$anyAccount) {
            Log::error('No chart of accounts found. Cannot create journal entries.');
            return;
        }
        $this->createJournalEntry($journal->id, $anyAccount->id, $amount, 0, 'Accounts payable reduced for Bill ' . $bill->bill_number);
        $this->createJournalEntry($journal->id, $anyAccount->id, 0, $amount, 'Cash paid for Bill ' . $bill->bill_number);
        Log::warning('Created basic Bill payment journal entries using fallback account: ' . $anyAccount->name);
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
        $entry->created_by = auth()->id();
        $entry->updated_by = auth()->id();
        $entry->save();
    }

    /**
     * Update financial account balances for Bill payments
     */
    private function updateBillPaymentFinancialAccountBalances($cashAccount, $accountsPayableAccount, $amount)
    {
        $cashFinancialAccount = FinancialAccount::where('account_id', $cashAccount->id)->first();
        if ($cashFinancialAccount) {
            $cashFinancialAccount->balance -= $amount; // Reduce cash (credit)
            $cashFinancialAccount->save();
        }
        $apFinancialAccount = FinancialAccount::where('account_id', $accountsPayableAccount->id)->first();
        if ($apFinancialAccount) {
            $apFinancialAccount->balance -= $amount; // Reduce payable (debit)
            $apFinancialAccount->save();
        }
    }

    private function generateBillNumber()
    {
        $today = now();
        $dateString = $today->format('dmy');
        $lastBill = PurchaseBill::latest()->first();
        if (!$lastBill) {
            return "skp-{$dateString}01";
        }
        $serialNumber = str_pad($lastBill->id + 1, 2, '0', STR_PAD_LEFT);
        return "skp-{$dateString}{$serialNumber}";
    }

}
