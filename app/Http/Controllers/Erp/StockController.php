<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductVariationStock;
use App\Models\WarehouseProductStock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function stocklist(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view product stock list')) {
            abort(403, 'Unauthorized action.');
        }
        $branches = Branch::all();
        $warehouses = Warehouse::all();
        $query = Product::with(['branchStock', 'warehouseStock', 'category', 'variations.stocks']);

        // Filter by product name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by branch stock (has stock in a specific branch with min quantity)
        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->whereHas('branchStock', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        // Filter by warehouse stock (has stock in a specific warehouse with min quantity)
        if ($request->filled('warehouse_id')) {
            $warehouseId = $request->warehouse_id;
            $query->whereHas('warehouseStock', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        if($request->filled('category_id')){
            $categoryId = $request->category_id;
            $query->where('category_id', $categoryId);
        }

        $productStocks = $query->paginate(10)->appends($request->except('page'));
        return view('erp.productStock.productStockList', compact('productStocks', 'branches', 'warehouses'));
    }

    public function addStockToBranches(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branches' => 'required|array',
            'branches.*' => 'exists:branches,id',
            'quantities' => 'required|array',
            'quantities.*' => 'numeric|min:1',
        ]);

        $productId = $request->product_id;
        $branches = $request->branches;
        $quantities = $request->quantities;

        \Log::alert($request->all());

        foreach ($branches as $i => $branchId) {
            $quantity = $quantities[$i];
            $stock = \App\Models\BranchProductStock::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->first();
            if ($stock) {
                $newQuantity = $stock->quantity + $quantity;
                $stock->update([
                    'quantity' => $newQuantity,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            } else {
                \App\Models\BranchProductStock::create([
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'quantity' => $quantity,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Stock added to branches successfully.']);
    }

    public function addStockToWarehouses(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouses' => 'required|array',
            'warehouses.*' => 'exists:warehouses,id',
            'quantities' => 'required|array',
            'quantities.*' => 'numeric|min:1',
        ]);

        $productId = $request->product_id;
        $warehouses = $request->warehouses;
        $quantities = $request->quantities;

        foreach ($warehouses as $i => $warehouseId) {
            $quantity = $quantities[$i];
            $stock = \App\Models\WarehouseProductStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();
            if ($stock) {
                $newQuantity = $stock->quantity + $quantity;
                $stock->update([
                    'quantity' => $newQuantity,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            } else {
                \App\Models\WarehouseProductStock::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'updated_by' => auth()->id() ?? 1,
                    'last_updated_at' => now(),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Stock added to warehouses successfully.']);
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'location_type' => 'required|in:branch,warehouse',
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:stock_in,stock_out',
            'quantity' => 'required|numeric|min:1',
        ]);

        // Validate location ID based on location type
        if ($request->location_type == 'branch') {
            $request->validate(['branch_id' => 'required|exists:branches,id']);
        } else {
            $request->validate(['warehouse_id' => 'required|exists:warehouses,id']);
        }

        // If a variation_id is provided, adjust variation stock; otherwise fall back to product-level stock
        $isVariation = $request->filled('variation_id');

        if($request->location_type == 'branch')
        {
            if ($isVariation) {
                $stock = ProductVariationStock::where('variation_id', $request->variation_id)
                    ->where('branch_id', $request->branch_id)
                    ->whereNull('warehouse_id')
                    ->first();

                if ($stock) {
                    if($request->type == 'stock_in') {
                        $stock->quantity += $request->quantity;
                    } else {
                        if($stock->quantity >= $request->quantity){
                            $stock->quantity -= $request->quantity;
                        } else {
                            return response()->json(['success' => false, 'message' => 'Insufficient variation stock'], 400);
                        }
                    }
                    $stock->updated_by = auth()->id() ?? 1;
                    $stock->last_updated_at = now();
                    $stock->save();
                } else {
                    if($request->type == 'stock_in') {
                        ProductVariationStock::create([
                            'variation_id' => $request->variation_id,
                            'branch_id' => $request->branch_id,
                            'quantity' => $request->quantity,
                            'updated_by' => auth()->id() ?? 1,
                            'last_updated_at' => now(),
                        ]);
                    } else {
                        return response()->json(['success' => false, 'message' => 'No variation stock to decrement for this branch.'], 400);
                    }
                }
            } else {
                $branchStock = BranchProductStock::where('branch_id', $request->branch_id)->where('product_id', $request->product_id)->first();
                if ($branchStock) {
                    if($request->type == 'stock_in')
                    {
                        $branchStock->quantity += $request->quantity;
                    }else{
                        if($branchStock->quantity > 0){
                            $branchStock->quantity -= $request->quantity;
                        }else{
                            return response()->json(['success' => false, 'message' => 'Stock is already empty'], 400);
                        }
                    }
                    $branchStock->save();
                } else {
                    if($request->type == 'stock_in') {
                        BranchProductStock::create([
                            'branch_id' => $request->branch_id,
                            'product_id' => $request->product_id,
                            'quantity' => $request->quantity,
                            'updated_by' => auth()->id() ?? 1,
                            'last_updated_at' => now(),
                        ]);
                    } else {
                        return response()->json(['success' => false, 'message' => 'No stock found for this branch and product. Cannot stock out.'], 400);
                    }
                }
            }
        } else {
            if ($isVariation) {
                $stock = ProductVariationStock::where('variation_id', $request->variation_id)
                    ->where('warehouse_id', $request->warehouse_id)
                    ->whereNull('branch_id')
                    ->first();

                if ($stock) {
                    if($request->type == 'stock_in') {
                        $stock->quantity += $request->quantity;
                    } else {
                        if($stock->quantity >= $request->quantity){
                            $stock->quantity -= $request->quantity;
                        } else {
                            return response()->json(['success' => false, 'message' => 'Insufficient variation stock'], 400);
                        }
                    }
                    $stock->updated_by = auth()->id() ?? 1;
                    $stock->last_updated_at = now();
                    $stock->save();
                } else {
                    if($request->type == 'stock_in') {
                        ProductVariationStock::create([
                            'variation_id' => $request->variation_id,
                            'warehouse_id' => $request->warehouse_id,
                            'quantity' => $request->quantity,
                            'updated_by' => auth()->id() ?? 1,
                            'last_updated_at' => now(),
                        ]);
                    } else {
                        return response()->json(['success' => false, 'message' => 'No variation stock to decrement for this warehouse.'], 400);
                    }
                }
            } else {
                $warehouseStock = WarehouseProductStock::where('warehouse_id', $request->warehouse_id)->where('product_id', $request->product_id)->first();
                if ($warehouseStock) {
                    if($request->type == 'stock_in')
                    {
                        $warehouseStock->quantity += $request->quantity;
                    } else{
                        if($warehouseStock->quantity > 0)
                        {
                            $warehouseStock->quantity -= $request->quantity;
                        }else{
                            return response()->json(['success' => false, 'message' => 'Stock is already empty'], 400);
                        }
                    }
                    $warehouseStock->save();
                } else {
                    if($request->type == 'stock_in') {
                        WarehouseProductStock::create([
                            'warehouse_id' => $request->warehouse_id,
                            'product_id' => $request->product_id,
                            'quantity' => $request->quantity,
                            'updated_by' => auth()->id() ?? 1,
                            'last_updated_at' => now(),
                        ]);
                    } else {
                        return response()->json(['success' => false, 'message' => 'No stock found for this warehouse and product. Cannot stock out.'], 400);
                    }
                }
            }
        }
        
        return redirect()->back()->with('success', 'Stock adjusted successfully.');
    }
}
