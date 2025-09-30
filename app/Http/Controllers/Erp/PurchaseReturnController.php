<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\BranchProductStock;
use App\Models\WarehouseProductStock;
use App\Models\EmployeeProductStock;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseReturn::with(['purchase', 'supplier', 'createdBy', 'items.product']);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('purchase', function($purchaseQuery) use ($searchTerm) {
                    $purchaseQuery->where('id', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('supplier', function($supplierQuery) use ($searchTerm) {
                    $supplierQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhere('id', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by purchase
        if ($request->filled('purchase_id')) {
            $query->where('purchase_id', $request->purchase_id);
        }

        // Filter by return date range
        if ($request->filled('return_date_from')) {
            $query->where('return_date', '>=', $request->return_date_from);
        }
        if ($request->filled('return_date_to')) {
            $query->where('return_date', '<=', $request->return_date_to);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by created by
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $returns = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get data for filter dropdowns
        $statuses = ['pending', 'approved', 'rejected', 'processed'];

        return view('erp.purchaseReturn.purchasereturnlist', compact('returns', 'statuses'));
    }

    public function create()
    {
        $branches = Branch::all();
        $warehouses = Warehouse::all();

        return view('erp.purchaseReturn.create', compact('branches', 'warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'return_date' => 'required|date',
            'return_type' => 'required|in:refund,adjust_to_due,none',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.returned_qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string',
        ]);

        // Sum returned quantities per product in this request
        $productReturnSums = [];
        foreach ($request->items as $item) {
            $productId = $item['product_id'];
            $productReturnSums[$productId] = ($productReturnSums[$productId] ?? 0) + $item['returned_qty'];
        }

        // For each product, check if total returned (including previous returns) exceeds purchased quantity
        foreach ($productReturnSums as $productId => $returnQty) {
            // Get purchased quantity for this product in this purchase
            $purchaseItem = PurchaseItem::where('purchase_id', $request->purchase_id)
                ->where('product_id', $productId)
                ->first();
            if (!$purchaseItem) {
                return back()->withErrors(["error" => "Product not found in purchase."])->withInput();
            }
            $purchasedQty = $purchaseItem->quantity;
            // Get previous returned quantity for this product in this purchase
            $previousReturnedQty = PurchaseReturnItem::where('product_id', $productId)
                ->whereHas('purchaseReturn', function($q) use ($request) {
                    $q->where('purchase_id', $request->purchase_id);
                })->sum('returned_qty');
            // Check
            if (($previousReturnedQty + $returnQty) > $purchasedQty) {
                return back()->withErrors(["error" => "Total returned quantity for product ID $productId exceeds purchased quantity."])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $request->purchase_id,
                'supplier_id' => $request->supplier_id,
                'return_date' => $request->return_date,
                'return_type' => $request->return_type,
                'status' => 'pending',
                'reason' => $request->reason,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $item['purchase_item_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'returned_qty' => $item['returned_qty'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['returned_qty'] * $item['unit_price'],
                    'reason' => $item['reason'] ?? null,
                    'return_from_type' => $item['return_from'] ?? null,
                    'return_from_id' => $item['from_id'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('purchaseReturn.list')->with('success', 'Purchase return created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $purchaseReturn = PurchaseReturn::with([
            'purchase', 
            'supplier', 
            'createdBy', 
            'approvedBy', 
            'items.product', 
            'items.purchaseItem',
            'items.branch',
            'items.warehouse',
            'items.employee'
        ])->findOrFail($id);

        return view('erp.purchaseReturn.show', compact('purchaseReturn'));
    }

    public function edit($id)
    {
        $purchaseReturn = PurchaseReturn::with([
            'purchase', 
            'supplier', 
            'items.product', 
            'items.purchaseItem',
            'items.branch',
            'items.warehouse',
            'items.employee'
        ])->findOrFail($id);

        // Check if return can be edited (only pending returns can be edited)
        if ($purchaseReturn->status !== 'pending') {
            return redirect()->route('purchaseReturn.show', $id)
                ->with('error', 'Only pending purchase returns can be edited.');
        }

        $branches = Branch::all();
        $warehouses = Warehouse::all();

        return view('erp.purchaseReturn.edit', compact('purchaseReturn', 'branches', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        $purchaseReturn = PurchaseReturn::findOrFail($id);

        // Check if return can be updated (only pending returns can be updated)
        if ($purchaseReturn->status !== 'pending') {
            return redirect()->route('purchaseReturn.show', $id)
                ->with('error', 'Only pending purchase returns can be updated.');
        }

        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'return_date' => 'required|date',
            'return_type' => 'required|in:refund,adjust_to_due,none',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.returned_qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string',
        ]);

        // Sum returned quantities per product in this request
        $productReturnSums = [];
        foreach ($request->items as $item) {
            $productId = $item['product_id'];
            $productReturnSums[$productId] = ($productReturnSums[$productId] ?? 0) + $item['returned_qty'];
        }

        // For each product, check if total returned (including previous returns) exceeds purchased quantity
        foreach ($productReturnSums as $productId => $returnQty) {
            // Get purchased quantity for this product in this purchase
            $purchaseItem = PurchaseItem::where('purchase_id', $request->purchase_id)
                ->where('product_id', $productId)
                ->first();
            if (!$purchaseItem) {
                return back()->withErrors(["error" => "Product not found in purchase."])->withInput();
            }
            $purchasedQty = $purchaseItem->quantity;
            // Get previous returned quantity for this product in this purchase (excluding current return)
            $previousReturnedQty = PurchaseReturnItem::where('product_id', $productId)
                ->whereHas('purchaseReturn', function($q) use ($request, $id) {
                    $q->where('purchase_id', $request->purchase_id)
                      ->where('id', '!=', $id);
                })->sum('returned_qty');
            // Check
            if (($previousReturnedQty + $returnQty) > $purchasedQty) {
                return back()->withErrors(["error" => "Total returned quantity for product ID $productId exceeds purchased quantity."])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Update purchase return
            $purchaseReturn->update([
                'purchase_id' => $request->purchase_id,
                'supplier_id' => $request->supplier_id,
                'return_date' => $request->return_date,
                'return_type' => $request->return_type,
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            // Delete existing items
            $purchaseReturn->items()->delete();

            // Create new items
            foreach ($request->items as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $item['purchase_item_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'returned_qty' => $item['returned_qty'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['returned_qty'] * $item['unit_price'],
                    'reason' => $item['reason'] ?? null,
                    'return_from_type' => $item['return_from'] ?? null,
                    'return_from_id' => $item['from_id'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('purchaseReturn.show', $id)->with('success', 'Purchase return updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function updateReturnStatus(Request $request, $returnId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,processed',
            'notes' => 'nullable|string|max:500'
        ]);

        $purchaseReturn = PurchaseReturn::with(['items'])->findOrFail($returnId);
        
        // Check if status can be updated
        if ($purchaseReturn->status === 'processed') {
            return response()->json([
                'success' => false,
                'message' => 'Purchase return is already processed and cannot be updated.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $purchaseReturn->status;
            $newStatus = $request->status;

            // Update the purchase return status
            $updateData = ['status' => $newStatus];

            // If status is being approved, set approved_by and approved_at
            if ($newStatus === 'approved') {
                $updateData['approved_by'] = Auth::id();
                $updateData['approved_at'] = now();
            }

            // Add notes if provided
            if ($request->filled('notes')) {
                $currentNotes = $purchaseReturn->notes ? $purchaseReturn->notes . "\n" : "";
                $updateData['notes'] = $currentNotes . "[" . now()->format('Y-m-d H:i:s') . "] Status changed to " . ucfirst($newStatus) . ": " . $request->notes;
            }

            $purchaseReturn->update($updateData);

            // If status is being processed, adjust stock
            if ($newStatus === 'processed') {
                foreach ($purchaseReturn->items as $item) {
                    $this->adjustStockForReturnItem($item);
                }
            }

            DB::commit();

            $statusMessage = match($newStatus) {
                'approved' => 'Purchase return has been approved successfully.',
                'rejected' => 'Purchase return has been rejected.',
                'processed' => 'Purchase return has been processed and stock has been adjusted.',
                default => 'Purchase return status has been updated.'
            };

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'data' => [
                    'id' => $purchaseReturn->id,
                    'status' => $purchaseReturn->status,
                    'approved_by' => $purchaseReturn->approved_by,
                    'approved_at' => $purchaseReturn->approved_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase return status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adjust stock for a return item based on return_from_type and return_from_id
     */
    private function adjustStockForReturnItem($item)
    {
        $returnedQty = $item->returned_qty;
        $productId = $item->product_id;
        $returnFromType = $item->return_from_type;
        $returnFromId = $item->return_from_id;

        switch ($returnFromType) {
            case 'branch':
                $stock = BranchProductStock::where('branch_id', $returnFromId)
                    ->where('product_id', $productId)
                    ->first();
                
                if ($stock) {
                    $stock->decrement('quantity', $returnedQty);
                } else {
                    // Create negative stock record if doesn't exist
                    BranchProductStock::create([
                        'branch_id' => $returnFromId,
                        'product_id' => $productId,
                        'quantity' => -$returnedQty
                    ]);
                }
                break;

            case 'warehouse':
                $stock = WarehouseProductStock::where('warehouse_id', $returnFromId)
                    ->where('product_id', $productId)
                    ->first();
                
                if ($stock) {
                    $stock->decrement('quantity', $returnedQty);
                } else {
                    // Create negative stock record if doesn't exist
                    WarehouseProductStock::create([
                        'warehouse_id' => $returnFromId,
                        'product_id' => $productId,
                        'quantity' => -$returnedQty
                    ]);
                }
                break;

            case 'employee':
                $stock = EmployeeProductStock::where('employee_id', $returnFromId)
                    ->where('product_id', $productId)
                    ->first();
                
                if ($stock) {
                    $stock->decrement('quantity', $returnedQty);
                } else {
                    // Create negative stock record if doesn't exist
                    EmployeeProductStock::create([
                        'employee_id' => $returnFromId,
                        'product_id' => $productId,
                        'quantity' => -$returnedQty
                    ]);
                }
                break;

            default:
                throw new \Exception("Invalid return_from_type: {$returnFromType}");
        }
    }

    public function getStockByType(Request $request, $productId, $fromId)
    {
        $stock = [];
        if($request->return_from == 'branch')
        {
            $stock = BranchProductStock::where('branch_id', $fromId)->where('product_id', $productId)->first();
        } else if($request->return_from == 'warehouse')
        {
            $stock = WarehouseProductStock::where('warehouse_id', $fromId)->where('product_id', $productId)->first();
        }

        return response()->json($stock);
    }
}
