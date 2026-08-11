<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Warehouse;
use App\Models\StockTransfer;
use App\Models\BranchProductStock;
use App\Models\WarehouseProductStock;
use App\Models\ProductVariationStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequisitionController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view requisitions')) {
            abort(403, 'Unauthorized action.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $query = Requisition::with(['branch', 'warehouse', 'creator']);

        if ($restrictedBranchId) {
            $query->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                  ->orWhere('warehouse_id', $restrictedBranchId);
            });
        } elseif ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('warehouse_id', $branchId);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $startDate = null;
        $endDate = null;
        $this->applyDateFilters($query, $request, $startDate, $endDate);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('requisition_number', 'LIKE', "%$search%");
        }

        $requisitions = $query->latest()->paginate(20)->appends($request->all());

        if ($request->ajax()) {
            return view('erp.requisition.partials.table', compact('requisitions'));
        }

        $reportType = $request->get('report_type_active', 'yearly');
        $branches = Branch::where('status', 'active')->get();
        return view('erp.requisition.index', compact('requisitions', 'restrictedBranchId', 'branches', 'startDate', 'endDate', 'reportType'));
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view requisitions')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Requisition::with(['branch', 'warehouse', 'creator']);

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                  ->orWhere('warehouse_id', $restrictedBranchId);
            });
        } elseif ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('warehouse_id', $branchId);
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        $this->applyDateFilters($query, $request);
        if ($request->filled('search')) $query->where('requisition_number', 'LIKE', "%{$request->search}%");

        $requisitions = $query->latest()->get();

        $headers = ['Req #', 'Branch', 'Warehouse', 'Date', 'Status', 'Requested By'];
        $data[] = $headers;

        foreach ($requisitions as $req) {
            $data[] = [
                $req->requisition_number,
                $req->branch->name ?? '—',
                $req->warehouse->name ?? '—',
                $req->requisition_date,
                strtoupper(str_replace('_', ' ', $req->status)),
                $req->creator->name ?? '—'
            ];
        }

        $filename = 'requisitions_' . date('Y-m-d_His') . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $filename);
        $writer->save($filePath);

        return response()->download($filePath, $filename)->deleteFileAfterSend();
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view requisitions')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Requisition::with(['branch', 'warehouse', 'creator']);

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                  ->orWhere('warehouse_id', $restrictedBranchId);
            });
        } elseif ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('warehouse_id', $branchId);
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        $this->applyDateFilters($query, $request);
        if ($request->filled('search')) $query->where('requisition_number', 'LIKE', "%{$request->search}%");

        $requisitions = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.requisition.report-pdf', compact('requisitions'));
        return $pdf->download('requisitions_' . date('Y-m-d_His') . '.pdf');
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('manage requisitions')) {
            abort(403, 'Unauthorized action.');
        }
        $restrictedBranchId = $this->getRestrictedBranchId();
        $branches = Branch::where('status', 'active')->get();
        $warehouses = Branch::withoutGlobalScope('active')->where('is_warehouse', true)->where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        return view('erp.requisition.create', compact('branches', 'warehouses', 'products', 'restrictedBranchId'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage requisitions')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'warehouse_id' => 'required|exists:branches,id',
            'requisition_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $restrictedBranchId = $this->getRestrictedBranchId();
            $branchId = $restrictedBranchId ?: $request->branch_id;

            if (!$branchId) {
                return back()->with('error', 'Branch is required.');
            }

            // Generate requisition number
            $today = date('Ymd');
            $lastRequisition = Requisition::where('requisition_number', 'like', "REQ-{$today}-%")
                ->orderBy('requisition_number', 'desc')
                ->first();
            
            if ($lastRequisition && preg_match('/REQ-\d{8}-(\d+)/', $lastRequisition->requisition_number, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            } else {
                $nextNumber = 1;
            }
            $requisitionNumber = 'REQ-' . $today . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $requisition = Requisition::create([
                'requisition_number' => $requisitionNumber,
                'branch_id' => $branchId,
                'warehouse_id' => $request->warehouse_id,
                'requisition_date' => $request->requisition_date,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                RequisitionItem::create([
                    'requisition_id' => $requisition->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
            return redirect()->route('requisition.index')->with('success', 'Requisition created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        if (!auth()->user()->hasPermissionTo('view requisitions')) {
            abort(403, 'Unauthorized action.');
        }
        $requisition = Requisition::with([
            'items.product.category',
            'items.variation.combinations.attribute',
            'items.variation.combinations.attributeValue',
            'branch',
            'warehouse',
            'creator',
        ])->findOrFail($id);
        return view('erp.requisition.show', compact('requisition'));
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermissionTo('manage requisitions')) {
            abort(403, 'Unauthorized action.');
        }
        $requisition = Requisition::with([
            'items.product',
            'items.variation.combinations.attribute',
            'items.variation.combinations.attributeValue',
        ])->findOrFail($id);

        if ($requisition->status !== 'pending') {
            return redirect()->route('requisition.show', $id)
                ->with('error', 'Only pending requisitions can be edited.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branches   = Branch::where('status', 'active')->get();
        $warehouses = Branch::withoutGlobalScope('active')->where('is_warehouse', true)->where('status', 'active')->get();

        return view('erp.requisition.edit', compact('requisition', 'branches', 'warehouses', 'restrictedBranchId'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('manage requisitions')) {
            abort(403, 'Unauthorized action.');
        }
        $requisition = Requisition::findOrFail($id);

        if ($requisition->status !== 'pending') {
            return redirect()->route('requisition.show', $id)
                ->with('error', 'Only pending requisitions can be edited.');
        }

        $request->validate([
            'warehouse_id'      => 'required|exists:branches,id',
            'requisition_date'  => 'required|date',
            'items'             => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $requisition->update([
                'warehouse_id'     => $request->warehouse_id,
                'requisition_date' => $request->requisition_date,
                'notes'            => $request->notes,
            ]);

            // Replace all items fresh
            $requisition->items()->delete();

            foreach ($request->items as $item) {
                RequisitionItem::create([
                    'requisition_id' => $requisition->id,
                    'product_id'     => $item['product_id'],
                    'variation_id'   => $item['variation_id'] ?: null,
                    'quantity'       => (int) $item['quantity'],
                ]);
            }

            DB::commit();
            return redirect()->route('requisition.show', $id)
                ->with('success', 'Requisition updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasPermissionTo('manage requisitions')) {
            abort(403, 'Unauthorized action.');
        }
        $requisition = Requisition::with('items')->findOrFail($id);
        
        DB::beginTransaction();
        try {
            // Find all related stock transfers linked to the items of this requisition
            $itemIds = $requisition->items->pluck('id');
            $relatedTransfers = StockTransfer::whereIn('requisition_item_id', $itemIds)->get();

            foreach ($relatedTransfers as $transfer) {
                // Reverse stock adjustments if they were approved/delivered
                if ($transfer->status === 'delivered') {
                    // Restore to source (Warehouse), deduct from destination (Branch)
                    $this->adjustOutletStock($transfer->from_type, $transfer->from_id, $transfer->product_id, $transfer->variation_id, +$transfer->quantity);
                    $this->adjustOutletStock($transfer->to_type,   $transfer->to_id,   $transfer->product_id, $transfer->variation_id, -$transfer->quantity);
                } elseif ($transfer->status === 'approved') {
                    // Restore to source (Warehouse)
                    $this->adjustOutletStock($transfer->from_type, $transfer->from_id, $transfer->product_id, $transfer->variation_id, +$transfer->quantity);
                }

                // Clear cache for product
                \App\Services\CacheService::clearProductCaches($transfer->product_id);

                // Delete the Stock Transfer record
                $transfer->delete();
            }

            // Delete child items first to prevent orphan rows
            $requisition->items()->delete();

            // Delete the main requisition
            $requisition->delete();

            DB::commit();
            return redirect()->route('requisition.index')->with('success', 'Requisition and related stock transfers deleted/reversed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete requisition: ' . $e->getMessage());
        }
    }

    private function adjustOutletStock($type, $id, $productId, $variationId, $qtyChange)
    {
        if ($variationId) {
            // Check if variation still exists in database to prevent foreign key issues
            if (!\App\Models\ProductVariation::where('id', $variationId)->exists()) {
                // Variation deleted, but we should still adjust base product stock if product exists
                if (\App\Models\Product::where('id', $productId)->exists()) {
                    if ($type === 'branch') {
                        $branchStock = BranchProductStock::firstOrNew([
                            'branch_id' => $id,
                            'product_id' => $productId
                        ]);
                        $branchStock->quantity = ($branchStock->quantity ?? 0) + $qtyChange;
                        if ($branchStock->quantity < 0) $branchStock->quantity = 0;
                        $branchStock->updated_by = auth()->id();
                        $branchStock->last_updated_at = now();
                        $branchStock->save();
                    } else {
                        $warehouseStock = WarehouseProductStock::firstOrNew([
                            'warehouse_id' => $id,
                            'product_id' => $productId
                        ]);
                        $warehouseStock->quantity = ($warehouseStock->quantity ?? 0) + $qtyChange;
                        if ($warehouseStock->quantity < 0) $warehouseStock->quantity = 0;
                        $warehouseStock->updated_by = auth()->id();
                        $warehouseStock->last_updated_at = now();
                        $warehouseStock->save();
                    }
                }
                return;
            }

            if ($type === 'branch') {
                $vStock = ProductVariationStock::firstOrNew([
                    'variation_id' => $variationId,
                    'branch_id' => $id,
                    'warehouse_id' => null
                ]);
                $vStock->quantity = ($vStock->quantity ?? 0) + $qtyChange;
                if ($vStock->quantity < 0) $vStock->quantity = 0;
                $vStock->updated_by = auth()->id();
                $vStock->last_updated_at = now();
                $vStock->save();

                $branchStock = BranchProductStock::firstOrNew([
                    'branch_id' => $id,
                    'product_id' => $productId
                ]);
                $branchStock->quantity = ($branchStock->quantity ?? 0) + $qtyChange;
                if ($branchStock->quantity < 0) $branchStock->quantity = 0;
                $branchStock->updated_by = auth()->id();
                $branchStock->last_updated_at = now();
                $branchStock->save();
            } else {
                $vStock = ProductVariationStock::firstOrNew([
                    'variation_id' => $variationId,
                    'warehouse_id' => $id,
                    'branch_id' => null
                ]);
                $vStock->quantity = ($vStock->quantity ?? 0) + $qtyChange;
                if ($vStock->quantity < 0) $vStock->quantity = 0;
                $vStock->updated_by = auth()->id();
                $vStock->last_updated_at = now();
                $vStock->save();

                $warehouseStock = WarehouseProductStock::firstOrNew([
                    'warehouse_id' => $id,
                    'product_id' => $productId
                ]);
                $warehouseStock->quantity = ($warehouseStock->quantity ?? 0) + $qtyChange;
                if ($warehouseStock->quantity < 0) $warehouseStock->quantity = 0;
                $warehouseStock->updated_by = auth()->id();
                $warehouseStock->last_updated_at = now();
                $warehouseStock->save();
            }
        } else {
            if ($type === 'branch') {
                $branchStock = BranchProductStock::firstOrNew([
                    'product_id' => $productId,
                    'branch_id' => $id
                ]);
                $branchStock->quantity = ($branchStock->quantity ?? 0) + $qtyChange;
                if ($branchStock->quantity < 0) $branchStock->quantity = 0;
                $branchStock->updated_by = auth()->id();
                $branchStock->last_updated_at = now();
                $branchStock->save();
            } else {
                $warehouseStock = WarehouseProductStock::firstOrNew([
                    'product_id' => $productId,
                    'warehouse_id' => $id
                ]);
                $warehouseStock->quantity = ($warehouseStock->quantity ?? 0) + $qtyChange;
                if ($warehouseStock->quantity < 0) $warehouseStock->quantity = 0;
                $warehouseStock->updated_by = auth()->id();
                $warehouseStock->last_updated_at = now();
                $warehouseStock->save();
            }
        }
    }

    /**
     * Handle fulfillment of requisition items (Stock Transfer only).
     * Purchases are done manually via the Purchases module.
     */
    public function fulfill(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('process requisitions') && !auth()->user()->hasPermissionTo('manage requisitions')) {
            abort(403, 'Unauthorized action.');
        }
        $requisition = Requisition::with('items.product', 'items.variation')->findOrFail($id);

        $request->validate([
            'items'        => 'required|array',
            'items.*.type' => 'required|in:transfer,skip',
            'items.*.qty'  => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $transferItems = [];

            foreach ($request->items as $itemId => $data) {
                if (($data['type'] ?? '') === 'skip') continue;
                $qty = isset($data['qty']) ? (int)$data['qty'] : 0;
                if ($qty <= 0) continue;

                $reqItem = RequisitionItem::find($itemId);
                if (!$reqItem || $reqItem->requisition_id != $requisition->id) continue;

                $pending      = $reqItem->quantity - $reqItem->fulfilled_quantity;
                $qtyToFulfill = min($qty, $pending);

                if ($qtyToFulfill <= 0) continue;

                if ($data['type'] === 'transfer') {
                    $transferItems[] = ['req_item' => $reqItem, 'qty' => $qtyToFulfill];
                }
            }

            if (!empty($transferItems)) {
                $today       = date('Ymd');
                $lastInvoice = StockTransfer::where('invoice_number', 'like', "TRF-{$today}-%")
                    ->orderBy('invoice_number', 'desc')->first();

                $nextNumber    = ($lastInvoice && preg_match('/TRF-\d{8}-(\d+)/', $lastInvoice->invoice_number, $m))
                    ? intval($m[1]) + 1 : 1;
                $invoiceNumber = 'TRF-' . $today . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                foreach ($transferItems as $itemData) {
                    $reqItem   = $itemData['req_item'];
                    $qty       = $itemData['qty'];
                    $sourceId  = $requisition->warehouse_id;

                    $availableStock = 0;
                    if ($reqItem->variation_id) {
                        $vStock = ProductVariationStock::where('variation_id', $reqItem->variation_id)
                            ->where(function($q) use ($sourceId) {
                                $q->where('branch_id', $sourceId)->orWhere('warehouse_id', $sourceId);
                            })->first();
                        $availableStock = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                    } else {
                        $bStock = BranchProductStock::where('product_id', $reqItem->product_id)->where('branch_id', $sourceId)->first();
                        $wStock = WarehouseProductStock::where('product_id', $reqItem->product_id)->where('warehouse_id', $sourceId)->first();
                        $availableStock = ($bStock ? $bStock->quantity : 0) + ($wStock ? $wStock->quantity : 0);
                    }

                    if ($availableStock < $qty) {
                        DB::rollBack();
                        $pName = $reqItem->product ? $reqItem->product->name : "Item #{$reqItem->product_id}";
                        return redirect()->back()->with('error', "Cannot fulfill requisition for '{$pName}'. Requested transfer quantity: {$qty}, but only {$availableStock} available at source location.");
                    }

                    $unitPrice = $reqItem->variation
                        ? ($reqItem->variation->cost ?: $reqItem->product->cost)
                        : $reqItem->product->cost;

                    StockTransfer::create([
                        'from_type'            => 'branch',
                        'from_id'              => $requisition->warehouse_id,
                        'to_type'              => 'branch',
                        'to_id'                => $requisition->branch_id,
                        'product_id'           => $reqItem->product_id,
                        'variation_id'         => $reqItem->variation_id,
                        'quantity'             => $qty,
                        'unit_price'           => $unitPrice,
                        'total_price'          => $qty * $unitPrice,
                        'due_amount'           => $qty * $unitPrice,
                        'status'               => 'pending',
                        'requested_at'         => now(),
                        'requested_by'         => auth()->id(),
                        'invoice_number'       => $invoiceNumber,
                        'requisition_item_id'  => $reqItem->id,
                    ]);

                    $reqItem->increment('fulfilled_quantity', $qty);
                }
            }

            // Reload fresh relation to update status accurately based on DB values
            $requisition = $requisition->fresh(['items']);
            $allFulfilled = $requisition->items->every(fn($i) => $i->fulfilled_quantity >= $i->quantity);
            $anyFulfilled = $requisition->items->some(fn($i)  => $i->fulfilled_quantity > 0);

            if ($allFulfilled) {
                $requisition->update(['status' => 'fulfilled']);
            } elseif ($anyFulfilled) {
                $requisition->update(['status' => 'partially_fulfilled']);
            }

            DB::commit();

            $msg = !empty($transferItems)
                ? 'Stock transfer initiated successfully.'
                : 'Requisition saved (no items were transferred).';

            return redirect()->route('requisition.show', $id)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Fulfillment failed: ' . $e->getMessage());
        }
    }

    private function applyDateFilters($query, Request $request, &$startDate = null, &$endDate = null)
    {
        $reportType = $request->get('report_type_active', 'yearly');

        if ($reportType === 'daily') {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date) : \Carbon\Carbon::today();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date) : \Carbon\Carbon::today();
            $query->whereBetween('requisition_date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
        } elseif ($reportType === 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $query->whereMonth('requisition_date', $month)->whereYear('requisition_date', $year);
        } elseif ($reportType === 'yearly') {
            $year = $request->get('year', date('Y'));
            $query->whereYear('requisition_date', $year);
        }
    }
}
