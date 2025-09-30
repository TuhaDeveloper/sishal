<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseProductStock;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransfer::with(['product.category', 'fromBranch', 'fromWarehouse', 'toBranch', 'toWarehouse','requestedPerson','approvedPerson']);

        // Filter by from_branch_id
        if ($request->filled('from_branch_id')) {
            $query->where('from_type', 'branch')->where('from_id', $request->from_branch_id);
        }
        // Filter by from_warehouse_id
        if ($request->filled('from_warehouse_id')) {
            $query->where('from_type', 'warehouse')->where('from_id', $request->from_warehouse_id);
        }
        // Filter by to_branch_id
        if ($request->filled('to_branch_id')) {
            $query->where('to_type', 'branch')->where('to_id', $request->to_branch_id);
        }
        // Filter by to_warehouse_id
        if ($request->filled('to_warehouse_id')) {
            $query->where('to_type', 'warehouse')->where('to_id', $request->to_warehouse_id);
        }
        // Filter by product name search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%$search%") ;
            });
        }

        $transfers = $query->orderBy('requested_at','desc')->paginate(15)->appends($request->except('page'));
        $branches = Branch::all();
        $warehouses = Warehouse::all();
        return view('erp.stockTransfer.stockTransfer', compact('transfers', 'branches', 'warehouses'));
    }

    public function show($id)
    {
        $transfer = StockTransfer::find($id);
        return view('erp.stockTransfer.show', compact('transfer'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_type' => 'required|in:branch,warehouse,external',
            'to_type' => 'required|in:branch,warehouse,employee',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'nullable|in:request,transfer',
            'status' => 'nullable|in:pending,approved,rejected,shipped,delivered',
            'notes' => 'nullable|string',
        ]);
        // Set from_id based on from_type
        if ($request->from_type === 'branch') {
            $validated['from_id'] = $request->from_branch_id;
        } elseif ($request->from_type === 'warehouse') {
            $validated['from_id'] = $request->from_warehouse_id;
        } else {
            $validated['from_id'] = null;
        }
        // Set to_id based on to_type
        if ($request->to_type === 'branch') {
            $validated['to_id'] = $request->to_branch_id;
        } elseif ($request->to_type === 'warehouse') {
            $validated['to_id'] = $request->to_warehouse_id;
        } else {
            $validated['to_id'] = null;
        }
        $validated['requested_by'] = auth()->id();
        $validated['requested_at'] = now();
        if (!isset($validated['type'])) $validated['type'] = 'transfer';
        if (!isset($validated['status'])) $validated['status'] = 'pending';
        $transfer = StockTransfer::create($validated);
        return redirect()->back();
    }

    public function updateStatus(Request $request, $id)
    {
        $transfer = StockTransfer::find($id);

        if($request->status == 'approved')
        {
            $transfer->status = $request->status;
            $transfer->approved_by = auth()->id();
            $transfer->approved_at = now();

            if($transfer->from_type == 'branch'){
                $branchStock = BranchProductStock::where('product_id', $transfer->product_id)->where('branch_id', $transfer->from_id)->first();
                if ($branchStock && $branchStock->quantity >= $transfer->quantity) {
                    $branchStock->quantity -= $transfer->quantity;
                    $branchStock->save();
                } else {
                    return response()->json(['error' => 'Invalid Requesting Stock'], 400);
                }
            }else{
                $warehouseStock = WarehouseProductStock::where('product_id', $transfer->product_id)->where('warehouse_id', $transfer->from_id)->first();
                if ($warehouseStock && $warehouseStock->quantity >= $transfer->quantity) {
                    $warehouseStock->quantity -= $transfer->quantity;
                    $warehouseStock->save();
                } else {
                    return response()->json(['error' => 'Invalid Requesting Stock'], 400);
                }
            }

        }elseif($request->status == 'shipped' && $transfer->status == 'approved'){
            $transfer->status = $request->status;
            $transfer->shipped_by = auth()->id();
            $transfer->shipped_at = now();
        }elseif($request->status == 'delivered' && $transfer->status == 'shipped'){
            $transfer->status = $request->status;
            $transfer->delivered_by = auth()->id();
            $transfer->delivered_at = now();

            if ($transfer->to_type == 'branch') {
                $branchStock = BranchProductStock::firstOrNew([
                    'product_id' => $transfer->product_id,
                    'branch_id' => $transfer->to_id
                ]);
                $branchStock->quantity = ($branchStock->quantity ?? 0) + $transfer->quantity;
                $branchStock->save();
            } else {
                $warehouseStock = WarehouseProductStock::firstOrNew([
                    'product_id' => $transfer->product_id,
                    'warehouse_id' => $transfer->to_id,
                    'updated_by' => auth()->id()
                ]);
                $warehouseStock->quantity = ($warehouseStock->quantity ?? 0) + $transfer->quantity;
                $warehouseStock->save();
            }
        }elseif($request->status == 'rejected' && $transfer->status != 'delivered'){
            $transfer->status = $request->status;
            $transfer->approved_by = null;
            $transfer->approved_at = null;
            $transfer->shipped_by = null;
            $transfer->shipped_at = null;
            $transfer->delivered_by = null;
            $transfer->delivered_at = null;

            if($transfer->to_type == 'branch'){
                $branchStock = BranchProductStock::where('product_id', $transfer->product_id)->where('branch_id', $transfer->from_id)->first();

                if($transfer->quantity < $branchStock->quantity){
                    $branchStock->quantity += $transfer->quantity;

                    $branchStock->save();
                }else{
                    return response()->json(['error' => 'Invalid Requesting Stock'], 400);
                }

            }else{
                $warehouseStock = WarehouseProductStock::where('product_id', $transfer->product_id)->where('warehouse_id', $transfer->from_id)->first();

                if($transfer->quantity < $warehouseStock->quantity){
                    $warehouseStock->quantity += $transfer->quantity;

                    $warehouseStock->save();
                }else{
                    return response()->json(['error' => 'Invalid Requesting Stock'], 400);
                }
            }
        }else{
            $transfer->status = $request->status;
        }

        $transfer->save();

        return redirect()->back();
    }
}
