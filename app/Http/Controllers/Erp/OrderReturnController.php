<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderReturnController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view order return list')) {
            abort(403, 'Unauthorized action.');
        }
        $query = OrderReturn::query();

        // Search by customer name, phone, email, or POS order_number
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($qc) use ($search) {
                    $qc->where('name', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                })
                ->orWhereHas('order', function($qp) use ($search) {
                    $qp->where('order_number', 'like', "%$search%");
                });
            });
        }

        // Filter by return_date
        if ($returnDate = $request->input('return_date')) {
            $query->whereDate('return_date', $returnDate);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $returns = $query->orderBy('created_at', 'desc')->paginate(10)->appends($request->all());
        $statuses = ['pending', 'approved', 'rejected', 'processed'];
        $filters = $request->only(['search', 'return_date', 'status']);
        return view('erp.orderReturn.orderreturnlist', compact('returns', 'statuses', 'filters'));
    }

    public function create()
    {
        $customers = Customer::all();
        $orders = Order::all();
        $invoices = Invoice::all();
        $products = \App\Models\Product::all();
        $branches = \App\Models\Branch::all();
        $warehouses = \App\Models\Warehouse::all();
        return view('erp.orderReturn.create', compact('customers', 'orders', 'invoices', 'products', 'branches', 'warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'order_id' => 'nullable|exists:orders,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'return_date' => 'required|date',
            'refund_type' => 'required|in:none,cash,bank,credit',
            'return_to_type' => 'required|in:branch,warehouse,employee',
            'return_to_id' => 'required|integer',
            'reason' => 'nullable|string',
            'processed_by' => 'nullable|exists:users,id',
            'processed_at' => 'nullable|date',
            'account_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.returned_qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string',
        ]);

        // Validate return quantities against order if order_id is provided
        if ($request->order_id) {
            $this->validateReturnQuantities($request->order_id, $request->items);
        }

        $data = $request->except(['items', 'status']);
        $data['status'] = 'pending';
        $orderReturn = OrderReturn::create($data);
        foreach ($request->items as $item) {
            \App\Models\OrderReturnItem::create([
                'order_return_id' => $orderReturn->id,
                'order_item_id' => $item['order_item_id'] ?? null,
                'product_id' => $item['product_id'],
                'variation_id' => $item['variation_id'] ?? null,
                'returned_qty' => $item['returned_qty'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['returned_qty'] * $item['unit_price'],
                'reason' => $item['reason'] ?? null,
            ]);
        }
        return redirect()->route('orderReturn.list')->with('success', 'Order return created successfully.');
    }

    public function show($id)
    {
        $orderReturn = OrderReturn::with(['items.product', 'employee.user'])->findOrFail($id);
        return view('erp.orderReturn.show', compact('orderReturn'));
    }

    public function edit($id)
    {
        $orderReturn = OrderReturn::with(['items', 'employee.user'])->findOrFail($id);
        $customers = Customer::all();
        $orders = Order::all();
        $invoices = Invoice::all();
        $products = \App\Models\Product::all();
        $branches = \App\Models\Branch::all();
        $warehouses = \App\Models\Warehouse::all();
        return view('erp.orderReturn.edit', compact('orderReturn', 'customers', 'orders', 'invoices', 'products', 'branches', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'order_id' => 'nullable|exists:orders,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'return_date' => 'required|date',
            'refund_type' => 'required|in:none,cash,bank,credit',
            'return_to_type' => 'required|in:branch,warehouse,employee',
            'return_to_id' => 'required|integer',
            'reason' => 'nullable|string',
            'processed_by' => 'nullable|exists:users,id',
            'processed_at' => 'nullable|date',
            'account_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.returned_qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string',
        ]);

        $orderReturn = OrderReturn::findOrFail($id);

        // Prevent editing if already processed
        if ($orderReturn->status === 'processed') {
            return redirect()->back()->withErrors(['error' => 'Cannot edit a processed return.']);
        }

        // Validate return quantities against order if order_id is provided
        if ($request->order_id) {
            $this->validateReturnQuantities($request->order_id, $request->items, $id);
        }

        $orderReturn->update($request->except(['items', 'status']));
        // Remove old items
        $orderReturn->items()->delete();
        // Add new items
        foreach ($request->items as $item) {
            \App\Models\OrderReturnItem::create([
                'order_return_id' => $orderReturn->id,
                'order_item_id' => $item['order_item_id'] ?? null,
                'product_id' => $item['product_id'],
                'variation_id' => $item['variation_id'] ?? null,
                'returned_qty' => $item['returned_qty'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['returned_qty'] * $item['unit_price'],
                'reason' => $item['reason'] ?? null,
            ]);
        }
        return redirect()->route('orderReturn.list')->with('success', 'Order return updated successfully.');
    }

    public function destroy($id)
    {
        $orderReturn = OrderReturn::findOrFail($id);
        $orderReturn->delete();
        return redirect()->route('orderReturn.list')->with('success', 'Order return deleted successfully.');
    }

    public function updateReturnStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,processed',
            'notes' => 'nullable|string|max:500'
        ]);

        $orderReturn = OrderReturn::with(['items'])->findOrFail($id);

        // Prevent re-processing
        if ($orderReturn->status === 'processed') {
            return response()->json([
                'success' => false,
                'message' => 'Sale return is already processed and cannot be updated.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $orderReturn->status;
            $newStatus = $request->status;
            $updateData = ['status' => $newStatus];

            // Add notes if provided
            if ($request->filled('notes')) {
                $currentNotes = $orderReturn->notes ? $orderReturn->notes . "\n" : "";
                $updateData['notes'] = $currentNotes . "[" . now()->format('Y-m-d H:i:s') . "] Status changed to " . ucfirst($newStatus) . ": " . $request->notes;
            }

            $orderReturn->update($updateData);

            // If status is being processed, adjust stock (add returned qty)
            if ($newStatus === 'processed') {
                foreach ($orderReturn->items as $item) {
                    $this->addStockForReturnItem($orderReturn, $item);
                }
                
                // Track who processed and when
                $orderReturn->processed_by = auth()->id();
                $orderReturn->processed_at = now();
                $orderReturn->save();
            }

            DB::commit();

            $statusMessage = match($newStatus) {
                'approved' => 'Order return has been approved successfully.',
                'rejected' => 'Order return has been rejected.',
                'processed' => 'Order return has been processed and stock has been updated.',
                default => 'Order return status has been updated.'
            };

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'data' => [
                    'id' => $orderReturn->id,
                    'status' => $orderReturn->status
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order return status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add returned quantity to the selected stock (branch, warehouse, or employee)
     */
    private function addStockForReturnItem($orderReturn, $item)
    {
        $qty = $item->returned_qty;
        $productId = $item->product_id;
        $variationId = $item->variation_id ?? null;
        $toType = $orderReturn->return_to_type;
        $toId = $orderReturn->return_to_id;

        switch ($toType) {
            case 'branch':
                if ($variationId) {
                    $stock = \App\Models\ProductVariationStock::where('variation_id', $variationId)
                        ->where('branch_id', $toId)
                        ->whereNull('warehouse_id')
                        ->first();
                    if ($stock) {
                        $stock->increment('quantity', $qty);
                    } else {
                        \App\Models\ProductVariationStock::create([
                            'variation_id' => $variationId,
                            'branch_id' => $toId,
                            'quantity' => $qty,
                            'updated_by' => auth()->id()
                        ]);
                    }
                } else {
                    $stock = \App\Models\BranchProductStock::where('branch_id', $toId)
                        ->where('product_id', $productId)
                        ->first();
                    if ($stock) {
                        $stock->increment('quantity', $qty);
                    } else {
                        \App\Models\BranchProductStock::create([
                            'branch_id' => $toId,
                            'product_id' => $productId,
                            'quantity' => $qty,
                            'updated_by' => auth()->id()
                        ]);
                    }
                }
                break;
            case 'warehouse':
                if ($variationId) {
                    $stock = \App\Models\ProductVariationStock::where('variation_id', $variationId)
                        ->where('warehouse_id', $toId)
                        ->whereNull('branch_id')
                        ->first();
                    if ($stock) {
                        $stock->increment('quantity', $qty);
                    } else {
                        \App\Models\ProductVariationStock::create([
                            'variation_id' => $variationId,
                            'warehouse_id' => $toId,
                            'quantity' => $qty,
                            'updated_by' => auth()->id()
                        ]);
                    }
                } else {
                    $stock = \App\Models\WarehouseProductStock::where('warehouse_id', $toId)
                        ->where('product_id', $productId)
                        ->first();
                    if ($stock) {
                        $stock->increment('quantity', $qty);
                    } else {
                        \App\Models\WarehouseProductStock::create([
                            'warehouse_id' => $toId,
                            'product_id' => $productId,
                            'quantity' => $qty,
                            'updated_by' => auth()->id()
                        ]);
                    }
                }
                break;
            case 'employee':
                $stock = \App\Models\EmployeeProductStock::where('employee_id', $toId)
                    ->where('product_id', $productId)
                    ->first();
                if ($stock) {
                    $stock->increment('quantity', $qty);
                } else {
                    \App\Models\EmployeeProductStock::create([
                        'employee_id' => $toId,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'issued_by' => auth()->id()
                    ]);
                }
                break;
            default:
                throw new \Exception("Invalid return_to_type: {$toType}");
        }
    }

    /**
     * Validate return quantities against original order
     */
    private function validateReturnQuantities($orderId, $items, $excludeReturnId = null)
    {
        $order = Order::with('items')->findOrFail($orderId);

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $variationId = $item['variation_id'] ?? null;
            $returnedQty = (float) $item['returned_qty'];

            // Find matching order item
            $orderItem = $order->items()->where('product_id', $productId)
                ->when($variationId, function($q) use ($variationId) {
                    $q->where('variation_id', $variationId);
                })
                ->first();

            if (!$orderItem) {
                throw new \Exception("Product not found in the original order.");
            }

            // Check if there are existing returns for this order
            $existingReturns = OrderReturn::where('order_id', $orderId)
                ->when($excludeReturnId, function($q) use ($excludeReturnId) {
                    $q->where('id', '!=', $excludeReturnId);
                })
                ->whereIn('status', ['pending', 'approved', 'processed'])
                ->get();

            $totalReturnedQty = 0;
            foreach ($existingReturns as $return) {
                $returnItems = $return->items()->where('product_id', $productId)
                    ->when($variationId, function($q) use ($variationId) {
                        $q->where('variation_id', $variationId);
                    })
                    ->sum('returned_qty');
                $totalReturnedQty += $returnItems;
            }

            $availableQty = (float) $orderItem->quantity - $totalReturnedQty;

            if ($returnedQty > $availableQty) {
                throw new \Exception("Cannot return quantity greater than available. Available: {$availableQty}, Requested: {$returnedQty}");
            }
        }
    }
}
