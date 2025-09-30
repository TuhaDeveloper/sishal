<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\PurchaseItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['vendor', 'bill']);

        // Search by purchase id or vendor name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('vendor', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
        }
        // Filter by purchase date
        if ($request->filled('purchase_date')) {
            $query->whereDate('purchase_date', $request->purchase_date);
        }
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Filter by bill status
        if ($request->filled('bill_status')) {
            $query->whereHas('bill', function($q) use ($request) {
                $q->where('status', $request->bill_status);
            });
        }
        $purchases = $query->paginate(10)->appends($request->all());
        return view('erp.purchases.purchaseList', [
            'purchases' => $purchases,
            'filters' => $request->only(['search', 'purchase_date', 'status', 'bill_status'])
        ]);
    }

    public function create()
    {
        $suppliers = \App\Models\Supplier::all();
        $branches = \App\Models\Branch::all();
        $warehouses = \App\Models\Warehouse::all();
        $products = \App\Models\Product::all();
        return view('erp.purchases.create', compact('suppliers', 'branches', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'ship_location_type' => 'required|in:branch,warehouse',
            'location_id' => 'required|integer',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
    
        DB::beginTransaction();
    
        try {
            // Calculate total
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }
    
            // Create Purchase
            $purchase = Purchase::create([
                'supplier_id'         => $request->supplier_id,
                'ship_location_type'  => $request->ship_location_type,
                'location_id'         => $request->location_id,
                'purchase_date'       => $request->purchase_date,
                'status'              => 'pending',
                'created_by'          => auth()->id(),
                'notes'               => $request->notes,
            ]);
    
            // Add Purchase Items
            foreach ($request->items as $item) {
                PurchaseItem::create(attributes: [
                    'purchase_id'  => $purchase->id,
                    'product_id'   => $item['product_id'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'total_price'  => $item['quantity'] * $item['unit_price'],
                    'description'     => $item['description'],
                ]);
            }
    
            // Create Bill
            $bill = PurchaseBill::create([
                'supplier_id'   => $request->supplier_id,
                'purchase_id'   => $purchase->id,
                'bill_date'     => now()->toDateString(),
                'total_amount'  => $totalAmount,
                'paid_amount'   => 0,
                'due_amount'    => $totalAmount,
                'status'        => 'unpaid',
                'created_by'    => auth()->id(),
                'description'   => 'Auto-generated bill from purchase ID: ' . $purchase->id,
            ]);
    
            DB::commit();
    
            return redirect()->route('purchase.list');
    
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Something went wrong.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $purchase = Purchase::where('id',$id)->with('bill','items.product','vendor')->first();

        if($purchase->ship_location_type == 'branch')
        {
            $purchase->location_name = Branch::find($purchase->location_id)->first()->name;
        }else{
            $purchase->location_name = Warehouse::find($purchase->location_id)->first()->name;
        }

        return view('erp.purchases.show',compact('purchase'));
    }

    public function edit($id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);
        $suppliers = \App\Models\Supplier::all();
        $branches = \App\Models\Branch::all();
        $warehouses = \App\Models\Warehouse::all();
        $products = \App\Models\Product::all();
        return view('erp.purchases.edit', compact('purchase', 'suppliers', 'branches', 'warehouses', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'ship_location_type' => 'required|in:branch,warehouse',
            'location_id' => 'required|integer',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $purchase = Purchase::findOrFail($id);
            $purchase->update([
                'supplier_id'         => $request->supplier_id,
                'ship_location_type'  => $request->ship_location_type,
                'location_id'         => $request->location_id,
                'purchase_date'       => $request->purchase_date,
                'status'              => $request->status,
                'notes'               => $request->notes,
            ]);

            if ($request->status === 'received') {
                foreach ($purchase->items as $item) {
                    if ($purchase->ship_location_type === 'branch') {
                        $stock = \App\Models\BranchProductStock::firstOrNew([
                            'branch_id' => $purchase->location_id,
                            'product_id' => $item->product_id,
                        ]);
                        $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                        $stock->save();
                    } elseif ($purchase->ship_location_type === 'warehouse') {
                        $stock = \App\Models\WarehouseProductStock::firstOrNew([
                            'warehouse_id' => $purchase->location_id,
                            'product_id' => $item->product_id,
                        ]);
                        $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                        $stock->save();
                    }
                }
            }

            // Remove old items
            $purchase->items()->delete();
            // Add new items
            foreach ($request->items as $item) {
                $purchase->items()->create([
                    'product_id'   => $item['product_id'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'total_price'  => $item['quantity'] * $item['unit_price'],
                    'description'  => $item['description'] ?? null,
                ]);
            }
            // Optionally update bill if needed (not shown here)
            DB::commit();
            return redirect()->route('purchase.list')->with('success', 'Purchase updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);
        $purchase = Purchase::with('items')->findOrFail($id);
        $purchase->status = $request->status;
        $purchase->save();

        if ($request->status === 'received') {
            foreach ($purchase->items as $item) {
                if ($purchase->ship_location_type === 'branch') {
                    $stock = \App\Models\BranchProductStock::firstOrNew([
                        'branch_id' => $purchase->location_id,
                        'product_id' => $item->product_id,
                    ]);
                    $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                    $stock->save();
                } elseif ($purchase->ship_location_type === 'warehouse') {
                    $stock = \App\Models\WarehouseProductStock::firstOrNew([
                        'warehouse_id' => $purchase->location_id,
                        'product_id' => $item->product_id,
                    ]);
                    $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                    $stock->save();
                }
            }
        }
        return redirect()->back()->with('success', 'Purchase status updated successfully.');
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $purchase = Purchase::with(['items', 'bill'])->findOrFail($id);
            // Delete related items
            $purchase->items()->delete();
            // Delete related bill
            if ($purchase->bill) {
                $purchase->bill->delete();
            }
            // Delete the purchase itself
            $purchase->delete();
            DB::commit();
            return redirect()->route('purchase.list')->with('success', 'Purchase and related data deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function searchPurchase(Request $request)
    {
        $search = $request->q;
        $supplier_q = $request->supplier;
        $query = Purchase::with('vendor');
        if ($search) {
            $query->where(function($q) use ($search, $supplier_q) {
                $q->where('id', $search)
                  ->orWhereHas('vendor', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
                if ($supplier_q) {
                    $q->orWhere('supplier_id', $supplier_q);
                }
            });
        } elseif ($supplier_q) {
            $query->where('supplier_id', $supplier_q);
        }
        $purchases = $query->limit(20)->get()->filter();
        $results = $purchases->filter(function($purchase) {
            return $purchase !== null;
        })->map(function($purchase) {
            $supplier = $purchase->vendor ? $purchase->vendor->name : 'Unknown';
            $text = "#{$purchase->id} - {$supplier} ({$purchase->purchase_date})";
            return [
                'id' => $purchase->id,
                'text' => $text
            ];
        });
        return response()->json(['results' => $results]);
    }

    public function getItemByPurchase($id)
    {
        $purchaseItems = \App\Models\PurchaseItem::with('product')
            ->where('purchase_id', $id)
            ->get();

        $results = $purchaseItems->map(function($item) {
            return [
                'id' => $item->id,
                'text' => "#{$item->id} - {$item->product->name} (Qty: {$item->quantity})",
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'description' => $item->description,
            ];
        });

        return response()->json(['results' => $results]);
    }

}
