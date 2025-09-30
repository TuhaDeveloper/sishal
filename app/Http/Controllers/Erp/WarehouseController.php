<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $warehouse = Warehouse::with([
            'manager',
            'branch.employees.user.roles',
            'warehouseProductStocks.product.category'
        ])->findOrFail($id);

        // Dynamic counts
        $products_count = $warehouse->warehouseProductStocks->count();
        $employees_count = $warehouse->branch ? $warehouse->branch->employees->count() : 0;
        
        // Calculate total stock value
        $total_stock_value = $warehouse->warehouseProductStocks->sum(function($stock) {
            return $stock->quantity * ($stock->product->cost ?? 0);
        });

        // Get recent products (last 10)
        $recent_products = $warehouse->warehouseProductStocks()
            ->with(['product.category'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get employees with their roles through branch
        $employees = collect();
        if ($warehouse->branch) {
            $employees = $warehouse->branch->employees()
                ->with(['user.roles'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        return view('erp.warehouses.show', compact(
            'warehouse',
            'products_count',
            'employees_count',
            'total_stock_value',
            'recent_products',
            'employees'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $warehouse = Warehouse::find($id);

        $warehouse->name = $request->name;
        $warehouse->location = $request->location;
        $warehouse->manager_id = $request->manager_id;
        $warehouse->branch_id = $request->branch_id;

        $warehouse->save();

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $warehouse = Warehouse::find($id);

        $warehouse->delete();

        return redirect()->back();
    }

    public function storeWarehousePerBranch(Request $request, $branchId)
    {
        $warehouse = new Warehouse();

        $warehouse->name = $request->name;
        $warehouse->location = $request->location;
        $warehouse->manager_id = $request->manager_id;
        $warehouse->branch_id = $branchId;

        $warehouse->save();

        return redirect()->back();
    }
}
