<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\BranchProductStock;
use App\Models\EmployeeProductStock;
use App\Models\InvoiceAddress;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceProvidedPart;
use App\Models\WarehouseProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerServiceController extends Controller
{

    public function search(Request $request)
    {
        $services = Product::where('type','service')->where('name', 'like', '%' . $request->search . '%')->get();
        return response()->json($services);
    }

    public function index()
    {
        $services = Service::paginate(10);
        return view('erp.customerSupport.customersupportlist',compact('services'));
    }

    public function create()
    {
        return view('erp.customerSupport.create');
    }

    public function store(Request $request)
    {
        try {
            // Filter out empty or incomplete provided_parts rows before validation
            $providedParts = collect($request->input('provided_parts', []))
                ->filter(function ($part) {
                    return !empty($part['product_id']) && !empty($part['product_type']);
                })->values()->toArray();
            $request->merge(['provided_parts' => $providedParts]);

            $validated = $request->validate([
                'customer_id' => 'required|exists:users,id',
                'product_service_id' => 'nullable|integer',
                'service_type' => 'required|in:installation,maintenance,repair,filter_change,other',
                'requested_date' => 'required|date',
                'preferred_time' => 'nullable|string',
                'status' => 'nullable|in:pending,assigned,in_progress,completed,cancelled',
                'technician_id' => 'required|exists:employees,id',
                'service_notes' => 'nullable|string',
                'admin_notes' => 'nullable|string',
                'service_fee' => 'required|numeric',
                'travel_fee' => 'required|numeric',
                'discount' => 'required|numeric',
                'provided_parts' => 'nullable|array',
                'provided_parts.*.product_type' => 'required_with:provided_parts.*.product_id|in:product,material',
                'provided_parts.*.product_id' => 'required_with:provided_parts.*.product_type|integer',
                'provided_parts.*.qty' => 'required_with:provided_parts.*.product_id|numeric|min:1',
                'provided_parts.*.price' => 'required_with:provided_parts.*.product_id|numeric|min:0',
            ]);

            $validated['user_id'] = $validated['customer_id'];
            unset($validated['customer_id']);

            // Auto-generate service_number
            $validated['service_number'] = 'SRV-' . date('YmdHis') . rand(1000, 9999);

            // Calculate total for provided parts
            $partsTotal = 0;
            if (!empty($validated['provided_parts'])) {
                foreach ($validated['provided_parts'] as $part) {
                    $qty = isset($part['qty']) ? (float)$part['qty'] : 1;
                    $price = isset($part['price']) ? (float)$part['price'] : 0;
                    $partsTotal += $qty * $price;
                }
            }

            $serviceFee = isset($validated['service_fee']) ? (float)$validated['service_fee'] : 0;
            $travelFee = isset($validated['travel_fee']) ? (float)$validated['travel_fee'] : 0;
            $discount = isset($validated['discount']) ? (float)$validated['discount'] : 0;

            $validated['total'] = $partsTotal + $serviceFee + $travelFee - $discount;

            $service = Service::create($validated);

            // Handle provided parts
            if (!empty($validated['provided_parts'])) {
                foreach ($validated['provided_parts'] as $part) {
                    $service->serviceProvidedParts()->create([
                        'product_type' => $part['product_type'],
                        'product_id' => $part['product_id'],
                        'qty' => $part['qty'] ?? 1,
                        'price' => $part['price'] ?? 0,
                    ]);
                }
            }

            // --- Create Invoice for Service ---
            $invTemplate = \App\Models\InvoiceTemplate::where('is_default', 1)->first();
            // Generate unique invoice number (same logic as PosController)
            do {
                $invoiceNumber = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (\App\Models\Invoice::where('invoice_number', $invoiceNumber)->exists());

            // Find customer_id for invoice (from user_id)

            $invoice = \App\Models\Invoice::create([
                'customer_id' => $service->user_id,
                'template_id' => $invTemplate ? $invTemplate->id : null,
                'operated_by' => $service->technician_id,
                'issue_date' => $service->requested_date,
                'due_date' => $service->requested_date,
                'send_date' => $service->requested_date,
                'subtotal' => $partsTotal + $serviceFee + $travelFee,
                'total_amount' => $validated['total'],
                'discount_apply' => $discount,
                'paid_amount' => 0,
                'due_amount' => $validated['total'],
                'status' => 'unpaid',
                'note' => $service->service_notes,
                'footer_text' => null,
                'created_by' => auth()->id(),
                'invoice_number' => $invoiceNumber,
            ]);

            if (!empty($validated['provided_parts'])) {
                foreach ($validated['provided_parts'] as $part) {
                    \App\Models\InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $part['product_id'],
                        'quantity' => $part['qty'] ?? 1,
                        'unit_price' => $part['price'] ?? 0,
                        'total_price' => ($part['qty'] ?? 1) * ($part['price'] ?? 0),
                    ]);
                }
            }
            
            $service->invoice_id = $invoice->id;
            $service->save();
            

            return redirect()->route('customerService.list');
        } catch (\Exception $e) {
            Log::error('Service store error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->withInput()->withErrors(['error' => 'Failed to create service. Please check the logs for details.']);
        }
    }

    public function show($id)
    {
        $service = Service::find($id);

        return view('erp.customerSupport.show',compact('service'));
    }

    public function updateStatus(Request $request, $id)
    {
        $service = Service::with('serviceProvidedParts')->findOrFail($id);

        if ($request->status === 'assigned') {
            if (!$service->technician_id) {
                return response()->json(['success' => false, 'message' => 'Assign a technician before shipping.']);
            }
            
            foreach ($service->serviceProvidedParts as $item) {
                if (!$item->current_position_type || !$item->current_position_id) {
                    return response()->json(['success' => false, 'message' => 'All items must have a stock source before shipping.']);
                }
            }

            foreach ($service->serviceProvidedParts as $item) {
                $productId = $item->product_id;
                $qty = $item->quantity;
                $fromType = $item->current_position_type;
                $fromId = $item->current_position_id;
                $employeeId = $service->technician_id;

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
                    return response()->json(['success' => false, 'message' => 'Insufficient stock for item: ' . $item->id]);
                }

                // Transfer stock
                $fromStock->quantity -= $qty;
                $fromStock->save();

                $employeeStock->quantity += $qty;
                $employeeStock->save();

                // Optionally, update item's current_position_type/id to employee
                $item->current_position_type = 'employee';
                $item->current_position_id = $employeeId;
                $item->save();
            }
        }

        // If all checks pass, update status
        $service->status = $request->status;
        $service->save();

        return response()->json(['success' => true, 'message' => 'Status updated.']);
    }

    public function updateTechnician($id, $employee_id)
    {
        $service = Service::findOrFail($id);
        $service->technician_id = $employee_id;

        $service->save();
        return response()->json(['success' => true, 'message' => 'Technician Assigned']);
    }

    public function deleteTechnician($id)
    {
        $service = Service::findOrFail($id);
        $service->technician_id = null;

        $service->save();
        return response()->json(['success' => true, 'message' => 'Technician Assigned']);
    }

    public function updateNote(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $service->service_notes = $request->notes;

        $service->save();
        return response()->json(['success' => true, 'message' => 'Notes updated.']);
    }

    public function addAddress(Request $request, $id)
    {
        $existingInvoiceAddress = InvoiceAddress::where('invoice_id',$id)->first();

        if($existingInvoiceAddress){
            $existingInvoiceAddress->billing_address_1 = $request->address_1;
            $existingInvoiceAddress->billing_address_2 = $request->address_2;
            $existingInvoiceAddress->billing_city = $request->city;
            $existingInvoiceAddress->billing_state = $request->state;
            $existingInvoiceAddress->billing_country = $request->country;
            $existingInvoiceAddress->billing_zip_code = $request->zip_code;

            $existingInvoiceAddress->save();
        }else{
            $invoiceAddress = new InvoiceAddress();
            $invoiceAddress->invoice_id = $id;
            $invoiceAddress->billing_address_1 = $request->address_1;
            $invoiceAddress->billing_address_2 = $request->address_2;
            $invoiceAddress->billing_city = $request->city;
            $invoiceAddress->billing_state = $request->state;
            $invoiceAddress->billing_country = $request->country;
            $invoiceAddress->billing_zip_code = $request->zip_code;

            $invoiceAddress->save();
        }
    }

    public function addStockToServiceItem(Request $request, $id)
    {
        $serviceItem = ServiceProvidedPart::find($id);

        $serviceItem->current_position_type = $request->current_position_type;
        $serviceItem->current_position_id = $request->current_position_id;
        $serviceItem->save();
        return response()->json(['success' => true, 'message' => 'Stock added successfully.']);
    }

    public function transferStockToEmployee(Request $request, $serviceItemId)
    {
        $serviceItem = ServiceProvidedPart::findOrFail($serviceItemId);
        $service = $serviceItem->service;
        $productId = $serviceItem->product_id;
        $quantity = $serviceItem->qty;

        // 1. Check if order has an employee
        if (!$service->technician_id) {
            return response()->json(['success' => false, 'message' => 'No technician assigned to this order.']);
        }

        $employeeId = $service->technician_id;

        // 2. Find or create employee stock for this product
        $employeeStock = EmployeeProductStock::firstOrCreate(
            ['employee_id' => $employeeId, 'product_id' => $productId],
            [
                'quantity' => 0,
                'issued_by' => optional(auth()->user())->id ?? 1
            ]
        );

        // 3. Transfer from current position
        $fromType = $serviceItem->current_position_type;
        $fromId = $serviceItem->current_position_id;

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
            $serviceItem->current_position_type = 'employee';
            $serviceItem->current_position_id = $employeeId;
            $serviceItem->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Stock transferred to employee successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Transfer failed: ' . $e->getMessage()]);
        }
    }

    public function addPayment($orderId, Request $request)
    {
        $service = Service::with('invoice')->find($orderId);
        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Service not found.'], 404);
        }
        $invoice = $service->invoice;
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
        $payment->payment_for = 'service';
        $payment->pos_id = $service->id;
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
        return response()->json(['success' => true, 'message' => 'Payment added successfully.']);
    }

    public function addExtraPart(Request $request)
    {
        $validated = $request->validate([
            'service_id'   => 'required|exists:services,id',
            'product_type' => 'required|in:product,material',
            'product_id'   => 'required|exists:products,id',
            'qty'          => 'required|numeric|min:1',
            'price'        => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::with('invoice')->findOrFail($validated['service_id']);
            $invoice = $service->invoice;

            // 1. Add to ServiceProvidedPart
            $part = $service->serviceProvidedParts()->create([
                'product_type' => $validated['product_type'],
                'product_id'   => $validated['product_id'],
                'qty'          => $validated['qty'],
                'price'        => $validated['price'],
            ]);

            // 2. Add to InvoiceItem
            if ($invoice) {
                \App\Models\InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'product_id'  => $validated['product_id'],
                    'quantity'    => $validated['qty'],
                    'unit_price'  => $validated['price'],
                    'total_price' => $validated['qty'] * $validated['price'],
                ]);

                // 3. Update invoice totals
                $invoice->subtotal    += $validated['qty'] * $validated['price'];
                $invoice->total_amount = $invoice->subtotal;
                $invoice->due_amount   = $invoice->total_amount - ($invoice->paid_amount ?? 0);
                if ($invoice->paid_amount >= $invoice->total_amount) {
                    $invoice->status = 'paid';
                    $invoice->due_amount = 0;
                } elseif ($invoice->paid_amount > 0) {
                    $invoice->status = 'partial';
                } else {
                    $invoice->status = 'unpaid';
                }
                $invoice->save();
            }

            // 4. Optionally update service total
            $service->total += $validated['qty'] * $validated['price'];
            $service->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Part added successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteExtraPart(Request $request)
    {
        $validated = $request->validate([
            'part_id' => 'required|exists:service_provided_parts,id',
        ]);

        DB::beginTransaction();
        try {
            $part = \App\Models\ServiceProvidedPart::findOrFail($validated['part_id']);
            $service = $part->service()->with('invoice')->first();
            $invoice = $service->invoice;
            $amountToRemove = $part->qty * $part->price;

            // Remove InvoiceItem if exists
            if ($invoice) {
                $invoiceItem = \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('product_id', $part->product_id)
                    ->where('unit_price', $part->price)
                    ->orderByDesc('id')
                    ->first();
                if ($invoiceItem) {
                    $invoiceItem->delete();
                }
                // Update invoice totals
                $invoice->subtotal = max(0, $invoice->subtotal - $amountToRemove);
                $invoice->total_amount = $invoice->subtotal;
                $invoice->due_amount = $invoice->total_amount - ($invoice->paid_amount ?? 0);
                if ($invoice->paid_amount >= $invoice->total_amount) {
                    $invoice->status = 'paid';
                    $invoice->due_amount = 0;
                } elseif ($invoice->paid_amount > 0) {
                    $invoice->status = 'partial';
                } else {
                    $invoice->status = 'unpaid';
                }
                $invoice->save();
            }

            // Update service total
            $service->total = max(0, $service->total - $amountToRemove);
            $service->save();

            // Delete the part
            $part->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Part deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateServiceFees(Request $request)
    {
        $validated = $request->validate([
            'service_id'   => 'required|exists:services,id',
            'service_fee'  => 'required|numeric|min:0',
            'travel_fee'   => 'required|numeric|min:0',
            'discount'     => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::with('invoice', 'serviceProvidedParts')->findOrFail($validated['service_id']);
            $invoice = $service->invoice;

            // Calculate new total
            $partsTotal = $service->serviceProvidedParts->sum(function($part) {
                return $part->qty * $part->price;
            });
            $total = $partsTotal + $validated['service_fee'] + $validated['travel_fee'] - $validated['discount'];

            // Update service
            $service->service_fee = $validated['service_fee'];
            $service->travel_fee = $validated['travel_fee'];
            $service->discount = $validated['discount'];
            $service->total = $total;
            $service->save();

            // Update invoice if exists
            if ($invoice) {
                $invoice->subtotal = $partsTotal + $validated['service_fee'] + $validated['travel_fee'];
                $invoice->discount_apply = $validated['discount'];
                $invoice->total_amount = $invoice->subtotal - $validated['discount'];
                $invoice->due_amount = $invoice->total_amount - ($invoice->paid_amount ?? 0);
                if ($invoice->paid_amount >= $invoice->total_amount) {
                    $invoice->status = 'paid';
                    $invoice->due_amount = 0;
                } elseif ($invoice->paid_amount > 0) {
                    $invoice->status = 'partial';
                } else {
                    $invoice->status = 'unpaid';
                }
                $invoice->save();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Service fees updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
