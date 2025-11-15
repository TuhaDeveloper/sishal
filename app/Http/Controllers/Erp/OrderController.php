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
        // Load order with variation relationship to ensure variation_id is available
        $order = Order::with(['invoice.payments', 'items.product', 'items.variation', 'employee.user', 'customer'])->find($id);
        
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
                        'variation_id' => $item->variation_id, // CRITICAL: This must be included
                        'variation_name' => $item->variation ? $item->variation->name : null,
                        'variation_sku' => $item->variation ? $item->variation->sku : null,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price
                    ];
                })
            ]);
        }
        
        $bankAccounts = collect(); // Empty collection since FinancialAccount model was removed
        return view('erp.order.orderdetails', compact('order', 'bankAccounts'));
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
        // Load order with items, products, and variations to ensure variation_id is available
        $order = Order::with(['items.product', 'items.product.variations', 'items.variation'])->findOrFail($id);

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
                // E-commerce order: stock deduction from warehouses ONLY (not branches)
                foreach ($order->items as $item) {
                    $productId = $item->product_id;
                    $qty = $item->quantity;
                    $variationId = $item->variation_id;

                    // Log order item details for debugging
                    \Log::info('Processing order item for shipping', [
                        'order_item_id' => $item->id,
                        'product_id' => $productId,
                        'variation_id' => $variationId,
                        'quantity' => $qty,
                        'has_current_position' => !!(($item->current_position_type && $item->current_position_id))
                    ]);

                    // Check if item has stock source defined
                    if ($item->current_position_type && $item->current_position_id) {
                        // Use defined stock source (warehouse only for ecommerce orders)
                        $fromType = $item->current_position_type;
                        $fromId = $item->current_position_id;
                        $product = $item->product;

                        // For ecommerce orders, only allow warehouse as stock source
                        if ($fromType !== 'warehouse') {
                            return response()->json([
                                'success' => false,
                                'message' => "Ecommerce orders can only deduct stock from warehouses. Please select a warehouse as the stock source for item ID {$item->id}."
                            ], 400);
                        }

                        $fromStock = null;
                        
                        // CRITICAL: For products with variations, we MUST use variation-level stock
                        // Do not fall back to product-level stock if variation_id is set
                        if ($variationId && $variationId > 0) {
                            $fromStock = \App\Models\ProductVariationStock::where('variation_id', $variationId)
                                ->where('warehouse_id', $fromId)
                                ->whereNull('branch_id')
                                ->lockForUpdate()
                                ->first();
                            
                            // If variation stock not found but variation_id is set, this is an error
                            if (!$fromStock && $product && $product->has_variations) {
                                $variation = \App\Models\ProductVariation::find($variationId);
                                $variationName = $variation ? ($variation->name ?? $variation->sku) : 'Variation ID: ' . $variationId;
                                return response()->json([
                                    'success' => false,
                                    'message' => "No stock available for variation '{$variationName}' at warehouse ID {$fromId}. Please add stock for this specific variation."
                                ], 400);
                            }
                        }
                        
                        // Only check product-level stock if no variation_id is set (product without variations)
                        if (!$fromStock && (!$variationId || !$product || !$product->has_variations)) {
                            $fromStock = WarehouseProductStock::where('warehouse_id', $fromId)
                                ->where('product_id', $productId)
                                ->lockForUpdate()
                                ->first();
                        }

                        if (!$fromStock || $fromStock->quantity < $qty) {
                            // Build error message with variation info if available
                            $itemName = 'Product ID: ' . $productId;
                            if ($variationId && $variationId > 0) {
                                $variation = \App\Models\ProductVariation::find($variationId);
                                if ($variation) {
                                    $itemName = $variation->sku . ' (' . ($variation->name ?? ($product ? $product->name : 'Unknown') ?? 'Unknown') . ')';
                                } else {
                                    $itemName = $product ? $product->name : 'Product ID: ' . $productId;
                                }
                            } elseif ($product) {
                                $itemName = $product->name ?? 'Product ID: ' . $productId;
                            }
                            
                            $availableQty = $fromStock ? $fromStock->quantity : 0;
                            return response()->json([
                                'success' => false, 
                                'message' => "Insufficient stock for '{$itemName}' at warehouse. Required: {$qty}, Available: {$availableQty}. Please add stock before shipping."
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
                        // For e-commerce orders without a specific stock source,
                        // try to deduct from warehouses ONLY. Priority:
                        // 1) Variation-level warehouse stocks (if variation_id present)
                        // 2) Product-level warehouse stocks (any warehouse)

                        $deducted = false;
                        $product = $item->product;
                        $variation = $item->variation; // Use loaded relationship
                        
                        // Load product with variations if not already loaded
                        if (!$product) {
                            $product = \App\Models\Product::with('variations')->find($productId);
                        }
                        
                        // CRITICAL: Use the variation_id from the order item - this is the EXACT variation that was ordered
                        // DO NOT fallback to default variation or first variation - this causes wrong stock deduction
                        if (!$variationId && $product && $product->has_variations) {
                            \Log::error('Order item missing variation_id for product with variations - CANNOT SHIP', [
                                'order_item_id' => $item->id,
                                'product_id' => $productId,
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'product_name' => $product->name,
                                'product_sku' => $product->sku,
                                'item_variation_id' => $item->variation_id,
                                'item_data' => $item->toArray()
                            ]);
                            
                            // DO NOT auto-assign variation - this causes wrong stock deduction
                            // The variation_id MUST be set when the order is created
                            // If it's missing, the order cannot be shipped until it's manually corrected
                            return response()->json([
                                'success' => false,
                                'message' => "Cannot ship order item: Product '{$product->name}' (SKU: {$product->sku}) has variations but no variation was specified in the order. The variation_id is missing from order item ID {$item->id}. Please edit the order to specify the correct variation before shipping."
                            ], 400);
                        }

                        // 1) Variation-level warehouse stock ONLY (check if product has variations or variation_id is set)
                        // IMPORTANT: Only deduct from the EXACT variation_id, never fallback to other variations
                        if ($variationId && $variationId > 0) {
                            // Verify the variation exists and belongs to this product
                            $variation = \App\Models\ProductVariation::where('id', $variationId)
                                ->where('product_id', $productId)
                                ->first();
                            
                            if (!$variation) {
                                \Log::warning('Variation ID mismatch', [
                                    'variation_id' => $variationId,
                                    'product_id' => $productId,
                                    'order_item_id' => $item->id
                                ]);
                            }
                            
                            // Try warehouses ONLY - for this specific variation
                            $variationWarehouseStock = \App\Models\ProductVariationStock::where('variation_id', $variationId)
                                ->whereNotNull('warehouse_id')
                                ->whereNull('branch_id')
                                ->where('quantity', '>=', $qty)
                                ->lockForUpdate()
                                ->orderByDesc('quantity')
                                ->first();

                            if ($variationWarehouseStock) {
                                $variationWarehouseStock->quantity -= $qty;
                                $variationWarehouseStock->save();
                                $deducted = true;
                                \Log::info('Stock deducted from variation warehouse', [
                                    'variation_id' => $variationId,
                                    'warehouse_id' => $variationWarehouseStock->warehouse_id,
                                    'quantity_deducted' => $qty,
                                    'remaining_quantity' => $variationWarehouseStock->quantity
                                ]);
                            }
                        }

                        // 2) Product-level warehouse stock (any warehouse) - only if product doesn't have variations
                        if (!$deducted && (!$product || !$product->has_variations)) {
                            $anyWarehouseStock = WarehouseProductStock::where('product_id', $productId)
                                ->where('quantity', '>=', $qty)
                                ->lockForUpdate()
                                ->orderByDesc('quantity')
                                ->first();

                            if ($anyWarehouseStock) {
                                $anyWarehouseStock->quantity -= $qty;
                                $anyWarehouseStock->save();
                                $deducted = true;
                            }
                        }

                        if (!$deducted) {
                            // Build error message with variation info if available
                            $itemName = 'Product ID: ' . $productId;
                            if ($variation) {
                                $itemName = $variation->sku . ' (' . ($variation->name ?? $product->name ?? 'Unknown') . ')';
                            } elseif ($product) {
                                $itemName = $product->name ?? 'Product ID: ' . $productId;
                            }
                            
                            return response()->json([
                                'success' => false,
                                'message' => "Insufficient stock for '{$itemName}'. Required: {$qty}. No available stock found in warehouses. Ecommerce orders can only deduct from warehouses, not branches."
                            ]);
                        }

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

        // Get customer ID from order or invoice
        $customerId = $order->customer_id ?? $invoice->customer_id ?? null;
        
        if($request->payment_method == 'cash' && $customerId)
        {
            // For COD orders, calculate COD discount and adjust payment amount
            $codDiscount = 0;
            if ($order->payment_method === 'cash') {
                $generalSetting = \App\Models\GeneralSetting::first();
                $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;
                if ($codPercentage > 0) {
                    // Calculate COD discount on invoice total
                    $codDiscount = round($invoice->total_amount * $codPercentage, 2);
                }
            }
            
            // For COD payments: balance was created with (invoice_total - cod_discount)
            // When payment is received, customer pays full invoice_total, but we only expected (invoice_total - cod_discount)
            // So we should subtract the net amount we expected to receive (payment - COD discount)
            $netPaymentAmount = $request->amount - $codDiscount;
            
            $balance = Balance::where('source_type', 'customer')->where('source_id', $customerId)->first();
            if($balance)
            {
                // Subtract the net amount (after COD discount) from balance
                $balanceBefore = $balance->balance;
                $balance->balance -= $netPaymentAmount;
                $balance->save();
                
                \Log::info('COD Payment Processed', [
                    'order_number' => $order->order_number,
                    'customer_id' => $customerId,
                    'payment_amount' => $request->amount,
                    'cod_discount' => $codDiscount,
                    'net_payment_amount' => $netPaymentAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balance->balance,
                ]);
            }
            else
            {
                // If balance doesn't exist, create it with remaining due amount (after COD discount if applicable)
                $remainingDue = $invoice->due_amount;
                if ($codDiscount > 0) {
                    $remainingDue = $remainingDue - $codDiscount;
                }
                Balance::create([
                    'source_type' => 'customer',
                    'source_id' => $customerId,
                    'balance' => max(0, $remainingDue),
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

    public function getProductStocks($productId, Request $request)
    {
        // Check if this is for an ecommerce order (no employee_id means ecommerce order)
        $isEcommerceOrder = false;
        $orderItemId = $request->get('order_item_id');
        
        if ($orderItemId) {
            $orderItem = OrderItem::with('order')->find($orderItemId);
            if ($orderItem && $orderItem->order) {
                // Ecommerce orders typically don't have employee_id assigned
                // Service orders have employee_id and use branches
                $isEcommerceOrder = !$orderItem->order->employee_id;
            }
        }

        $allStocks = collect();

        // For ecommerce orders, only show warehouse stocks
        if ($isEcommerceOrder) {
            // Warehouse stocks only
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
            $allStocks = $warehouseStocks;
        } else {
            // For service orders, show all stock types
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
            $allStocks = $branchStocks->concat($warehouseStocks)->concat($employeeStocks);
        }

        return response()->json([
            'success' => true,
            'stocks' => $allStocks->values(),
        ]);
    }

    public function addStockToOrderItem(Request $request, $id)
    {
        $orderItem = OrderItem::with('order')->find($id);
        
        if (!$orderItem) {
            return response()->json(['success' => false, 'message' => 'Order item not found.'], 404);
        }

        // For ecommerce orders, only allow warehouse as stock source
        $isEcommerceOrder = !$orderItem->order->employee_id;
        if ($isEcommerceOrder && $request->current_position_type !== 'warehouse') {
            return response()->json([
                'success' => false,
                'message' => 'Ecommerce orders can only use warehouse stock. Please select a warehouse.'
            ], 400);
        }

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
