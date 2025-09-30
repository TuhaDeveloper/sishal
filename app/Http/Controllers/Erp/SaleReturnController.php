<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SaleReturn;
use App\Models\Customer;
use App\Models\Pos;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleReturn::query();

        // Search by customer name, phone, email, or POS sale_number
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($qc) use ($search) {
                    $qc->where('name', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                })
                ->orWhereHas('posSale', function($qp) use ($search) {
                    $qp->where('sale_number', 'like', "%$search%");
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
        return view('erp.saleReturn.salereturnlist', compact('returns', 'statuses', 'filters'));
    }

    public function create()
    {
        $customers = Customer::all();
        $posSales = Pos::all();
        $invoices = Invoice::all();
        $products = \App\Models\Product::all();
        $branches = \App\Models\Branch::all();
        $warehouses = \App\Models\Warehouse::all();
        return view('erp.saleReturn.create', compact('customers', 'posSales', 'invoices', 'products', 'branches', 'warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'pos_sale_id' => 'nullable|exists:pos,id',
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
        $data = $request->except(['items', 'status']);
        $data['status'] = 'pending';
        $saleReturn = SaleReturn::create($data);
        foreach ($request->items as $item) {
            \App\Models\SaleReturnItem::create([
                'sale_return_id' => $saleReturn->id,
                'sale_item_id' => $item['sale_item_id'] ?? null,
                'product_id' => $item['product_id'],
                'returned_qty' => $item['returned_qty'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['returned_qty'] * $item['unit_price'],
                'reason' => $item['reason'] ?? null,
            ]);
        }
        return redirect()->route('saleReturn.list')->with('success', 'Sale return created successfully.');
    }

    public function show($id)
    {
        $saleReturn = SaleReturn::with(['items.product', 'employee.user'])->findOrFail($id);
        return view('erp.saleReturn.show', compact('saleReturn'));
    }

    public function edit($id)
    {
        $saleReturn = SaleReturn::with(['items', 'employee.user'])->findOrFail($id);
        $customers = Customer::all();
        $posSales = Pos::all();
        $invoices = Invoice::all();
        $products = \App\Models\Product::all();
        $branches = \App\Models\Branch::all();
        $warehouses = \App\Models\Warehouse::all();
        return view('erp.saleReturn.edit', compact('saleReturn', 'customers', 'posSales', 'invoices', 'products', 'branches', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'pos_sale_id' => 'nullable|exists:pos,id',
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
        $saleReturn = SaleReturn::findOrFail($id);
        $saleReturn->update($request->except(['items', 'status']));
        // Remove old items
        $saleReturn->items()->delete();
        // Add new items
        foreach ($request->items as $item) {
            \App\Models\SaleReturnItem::create([
                'sale_return_id' => $saleReturn->id,
                'sale_item_id' => $item['sale_item_id'] ?? null,
                'product_id' => $item['product_id'],
                'returned_qty' => $item['returned_qty'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['returned_qty'] * $item['unit_price'],
                'reason' => $item['reason'] ?? null,
            ]);
        }
        return redirect()->route('saleReturn.list')->with('success', 'Sale return updated successfully.');
    }

    public function destroy($id)
    {
        $saleReturn = SaleReturn::findOrFail($id);
        $saleReturn->delete();
        return redirect()->route('saleReturn.list')->with('success', 'Sale return deleted successfully.');
    }

    /**
     * Change the status of a sale return. If processed, add returned quantity to the selected stock.
     */
    public function updateReturnStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,processed',
            'notes' => 'nullable|string|max:500'
        ]);

        $saleReturn = SaleReturn::with(['items'])->findOrFail($id);

        // Prevent re-processing
        if ($saleReturn->status === 'processed') {
            return response()->json([
                'success' => false,
                'message' => 'Sale return is already processed and cannot be updated.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $saleReturn->status;
            $newStatus = $request->status;
            $updateData = ['status' => $newStatus];

            // Add notes if provided
            if ($request->filled('notes')) {
                $currentNotes = $saleReturn->notes ? $saleReturn->notes . "\n" : "";
                $updateData['notes'] = $currentNotes . "[" . now()->format('Y-m-d H:i:s') . "] Status changed to " . ucfirst($newStatus) . ": " . $request->notes;
            }

            $saleReturn->update($updateData);

            // If status is being processed, adjust stock (add returned qty)
            if ($newStatus === 'processed') {
                foreach ($saleReturn->items as $item) {
                    $this->addStockForReturnItem($saleReturn, $item);
                }
            }

            DB::commit();

            $statusMessage = match($newStatus) {
                'approved' => 'Sale return has been approved successfully.',
                'rejected' => 'Sale return has been rejected.',
                'processed' => 'Sale return has been processed and stock has been updated.',
                default => 'Sale return status has been updated.'
            };

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'data' => [
                    'id' => $saleReturn->id,
                    'status' => $saleReturn->status
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sale return status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add returned quantity to the selected stock (branch, warehouse, or employee)
     */
    private function addStockForReturnItem($saleReturn, $item)
    {
        $qty = $item->returned_qty;
        $productId = $item->product_id;
        $toType = $saleReturn->return_to_type;
        $toId = $saleReturn->return_to_id;

        switch ($toType) {
            case 'branch':
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
                break;
            case 'warehouse':
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
} 