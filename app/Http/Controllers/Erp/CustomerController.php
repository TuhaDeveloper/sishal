<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Pos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('addedBy');

        // Search by Customer ID
        if ($request->filled('customer_id')) {
            $query->where('id', $request->customer_id);
        }

        // Search by Name
        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        // Search by Email
        if ($request->filled('email')) {
            $query->where('email', 'LIKE', '%' . $request->email . '%');
        }

        // Search by Phone
        if ($request->filled('phone')) {
            $query->where('phone', 'LIKE', '%' . $request->phone . '%');
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        // Filter by Premium Status
        if ($request->filled('premium')) {
            $query->where('is_premium', $request->premium);
        }

        // General Search (searches across multiple fields)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('email', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('phone', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Get paginated results
        $customers = $query->orderBy('created_at', 'desc')->paginate(10);

        // Append search parameters to pagination links
        $customers->appends($request->all());

        return view('erp.customers.customerlist', compact('customers'));
    }

    public function store(Request $request)
    {
        $rules = [
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'address_1' => 'nullable|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ];
        if ($request->register_as_user) {
            $rules['email'] = 'required|email|max:255';
            $rules['user_password'] = 'required|string|min:6|confirmed';
        }
        $validated = $request->validate($rules);
        $validated['created_by'] = Auth::id() ?? 1;
        $validated['is_active'] = $request->has('is_active') ? $request->is_active : 1;

        // Handle user registration if requested
        if ($request->register_as_user) {
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'errors' => ['email' => ['A user with this email already exists.']]
                ], 422);
            }
            // Split name into first and last name if possible
            $nameParts = explode(' ', trim($request->name), 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $request->email,
                'password' => Hash::make($request->user_password),
            ]);
            $validated['user_id'] = $user->id;
        }

        $customer = Customer::create($validated);

        // If AJAX, return JSON
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Customer created successfully.']);
        }
        return redirect()->route('customers.list')->with('success', 'Customer created successfully.');
    }

    public function show($id)
    {
        $customer = Customer::with(['addedBy'])->findOrFail($id);
        
        // Get customer's orders
        $orders = Order::where('created_by', $customer->user_id)
            ->with(['invoice', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get customer's invoices
        $invoices = Invoice::where('customer_id', $customer->id)
            ->with(['payments'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get customer's POS sales
        $posSales = Pos::where('customer_id', $customer->id)
            ->with(['invoice', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Calculate financial summary
        $totalRevenue = $invoices->where('status', 'paid')->sum('total_amount');
        $outstandingAmount = $invoices->where('status', 'unpaid')->sum('due_amount');
        $paidAmount = $invoices->where('status', 'paid')->sum('paid_amount');
        $overdueAmount = $invoices->where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->sum('due_amount');
        
        // Get recent activity
        $recentActivity = collect();
        
        // Add orders to activity
        foreach ($orders->take(5) as $order) {
            $recentActivity->push([
                'type' => 'order',
                'title' => 'Order #' . $order->order_number . ' ' . ucfirst($order->status),
                'date' => $order->created_at,
                'amount' => $order->total,
                'status' => $order->status
            ]);
        }
        
        // Add invoices to activity
        foreach ($invoices->take(5) as $invoice) {
            $recentActivity->push([
                'type' => 'invoice',
                'title' => 'Invoice #' . $invoice->invoice_number . ' ' . ucfirst($invoice->status),
                'date' => $invoice->created_at,
                'amount' => $invoice->total_amount,
                'status' => $invoice->status
            ]);
        }
        
        // Sort by date and take latest 10
        $recentActivity = $recentActivity->sortByDesc('date')->take(10);
        
        if (request()->ajax()) {
            return response()->json([
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address_1,
                    'city' => $customer->city,
                    'state' => $customer->state,
                    'zip_code' => $customer->zip_code,
                    'country' => $customer->country,
                ]
            ]);
        }
        
        return view('erp.customers.show', compact(
            'customer', 
            'orders', 
            'invoices', 
            'posSales',
            'totalRevenue',
            'outstandingAmount',
            'paidAmount',
            'overdueAmount',
            'recentActivity'
        ));
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('erp.customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'address_1' => 'nullable|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        $customer = Customer::findOrFail($id);
        
        $data = $request->only([
            'name', 'email', 'phone', 'tax_number', 'address_1', 'address_2',
            'city', 'state', 'country', 'zip_code', 'notes'
        ]);
        
        // Handle boolean fields
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['is_premium'] = $request->has('is_premium') ? 1 : 0;

        $customer->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'redirect' => route('customer.show', $customer->id)
            ]);
        }

        return redirect()->route('customer.show', $customer->id)->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customerName = $customer->name;
        
        $customer->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully.',
                'redirect' => route('customers.list')
            ]);
        }

        return redirect()->route('customers.list')->with('success', "Customer '{$customerName}' deleted successfully.");
    }

    public function makePremium($id)
    {
        $customer = Customer::find($id);

        $customer->is_premium = 1;

        $customer->save();

        return redirect()->back();
    }

    public function removePremium($id)
    {
        $customer = Customer::find($id);

        $customer->is_premium = 0;

        $customer->save();

        return redirect()->back();
    }

    public function editNotes(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update([
            'notes' => $request->notes,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer notes updated successfully.',
                'notes' => $request->notes
            ]);
        }

        return redirect()->back()->with('success', 'Customer notes updated successfully.');
    }

    public function customerSearch(Request $request)
    {
        $q = $request->input('q');
        $query = Customer::query();
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%")
                    ->orWhere('phone', 'like', "%$q%")
                    ->orWhere('id', $q);
            });
        }
        $customers = $query->orderBy('name')->limit(20)->get(['id', 'name', 'email', 'phone']);
        return response()->json($customers);
    }

    public function address($id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json([
            'address_1' => $customer->address_1,
            'address_2' => $customer->address_2,
            'city' => $customer->city,
            'state' => $customer->state,
            'country' => $customer->country,
            'zip_code' => $customer->zip_code,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
        ]);
    }
}
