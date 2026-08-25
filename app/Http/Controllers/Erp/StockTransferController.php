<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\ProductVariationStock;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\FinancialAccount;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view transfers')) {
            abort(403, 'Unauthorized action.');
        }

        // Grab view_mode before it can affect anything
        $viewMode = $request->input('view_mode', 'all');

        $startDate = null;
        $endDate = null;

        // Build the base query with all OTHER filters applied (no type/view_mode filter yet)
        $query = $this->applyFilters($request, $startDate, $endDate);

        // Count tabs on base query BEFORE adding the type filter
        $transferCount = (clone $query)->where('type', '!=', 'return')->count();
        $returnCount   = (clone $query)->where('type', 'return')->count();

        // NOW apply the view_mode type filter on top
        if ($viewMode === 'returns') {
            $query->where('type', 'return');
        } elseif ($viewMode === 'transfers') {
            $query->where('type', '!=', 'return');
        }

        $totalQuantity = (clone $query)->sum('quantity');
        $totalValue    = (clone $query)->sum('total_price');

        // Itemized listing (ungrouped) to show product details
        $transfers = $query->orderBy('requested_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(100);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('erp.stockTransfer.partials.table', compact('transfers'))->render(),
                'transferCount' => $transferCount,
                'returnCount' => $returnCount,
                'totalQuantity' => number_format($totalQuantity, 0),
                'totalValue' => number_format($totalValue, 2) . '৳'
            ]);
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branches   = Branch::all();
        $warehouses = Warehouse::all();

        $statuses     = ['pending', 'approved', 'rejected', 'shipped', 'delivered'];
        $categories   = \App\Models\ProductServiceCategory::all();
        $brands       = \App\Models\Brand::all();
        $seasons      = \App\Models\Season::all();
        $genders      = \App\Models\Gender::all();
        $styleNumbers = \App\Models\Product::whereNotNull('style_number')->distinct()->pluck('style_number');
        $products     = \App\Models\Product::orderBy('name')->get();
        $filters      = $request->only([
            'search', 'from_branch_id', 'from_warehouse_id', 'to_branch_id',
            'to_warehouse_id', 'status', 'date_from', 'date_to',
            'variation_id', 'product_id', 'quick_filter', 'view_mode',
        ]);

        $reportType = $request->get('report_type_active', 'yearly');

        return view('erp.stockTransfer.stockTransfer', compact(
            'transfers', 'branches', 'warehouses', 'statuses', 'filters',
            'categories', 'brands', 'seasons', 'genders', 'styleNumbers', 'products',
            'totalQuantity', 'totalValue', 'transferCount', 'returnCount',
            'startDate', 'endDate', 'reportType'
        ));
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('create transfers') && !auth()->user()->hasPermissionTo('manage transfers')) {
            abort(403, 'Unauthorized action.');
        }
        
        $restrictedBranchId = $this->getRestrictedBranchId();
        
        // Fetch all locations to allow transfers BETWEEN branches
        $branches = Branch::all();
        $warehouses = Warehouse::all();
        
        return view('erp.stockTransfer.create', compact('branches', 'warehouses', 'restrictedBranchId'));
    }

    private function applyFilters(Request $request, &$startDate = null, &$endDate = null)
    {
        $query = StockTransfer::with([
            'product.category', 
            'product.brand', 
            'product.season', 
            'product.gender',
            'variation.combinations.attribute', 
            'variation.combinations.attributeValue',
            'fromBranch', 
            'fromWarehouse', 
            'toBranch', 
            'toWarehouse', 
            'requestedPerson', 
            'approvedPerson'
        ]);

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->where(function($q) use ($restrictedBranchId) {
                $q->where(function($q2) use ($restrictedBranchId) {
                    $q2->where('from_type', 'branch')->where('from_id', $restrictedBranchId);
                })->orWhere(function($q2) use ($restrictedBranchId) {
                    $q2->where('to_type', 'branch')->where('to_id', $restrictedBranchId);
                });
            });
        }

        if ($request->filled('from_branch_id')) {
            $fromValue = $request->from_branch_id;
            if (str_starts_with($fromValue, 'branch_')) {
                $branchId = str_replace('branch_', '', $fromValue);
                $query->where('from_type', 'branch')->where('from_id', $branchId);
            } elseif (str_starts_with($fromValue, 'warehouse_')) {
                $warehouseId = str_replace('warehouse_', '', $fromValue);
                $query->where('from_type', 'warehouse')->where('from_id', $warehouseId);
            } else {
                $query->where('from_type', 'branch')->where('from_id', $fromValue);
            }
        }
        if ($request->filled('from_warehouse_id')) {
            $query->where('from_type', 'warehouse')->where('from_id', $request->from_warehouse_id);
        }
        
        if ($request->filled('to_branch_id')) {
            $toValue = $request->to_branch_id;
            if (str_starts_with($toValue, 'branch_')) {
                $branchId = str_replace('branch_', '', $toValue);
                $query->where('to_type', 'branch')->where('to_id', $branchId);
            } elseif (str_starts_with($toValue, 'warehouse_')) {
                $warehouseId = str_replace('warehouse_', '', $toValue);
                $query->where('to_type', 'warehouse')->where('to_id', $warehouseId);
            } else {
                $query->where('to_type', 'branch')->where('to_id', $toValue);
            }
        }
        if ($request->filled('to_warehouse_id')) {
            $query->where('to_type', 'warehouse')->where('to_id', $request->to_warehouse_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $cleanSearch = preg_replace('/[:\s\-]+/', '%', $search);
            $query->where(function($q) use ($search, $cleanSearch) {
                $q->whereHas('product', function($pq) use ($search, $cleanSearch) {
                    $pq->where('name', 'like', "%$search%")
                      ->orWhere('style_number', 'like', "%$search%")
                      ->orWhere('style_number', 'like', "%$cleanSearch%")
                      ->orWhere('sku', 'like', "%$search%");
                })->orWhereHas('variation', function($vq) use ($search, $cleanSearch) {
                    $vq->where('name', 'like', "%$search%")
                      ->orWhere('sku', 'like', "%$search%")
                      ->orWhere('sku', 'like', "%$cleanSearch%");
                })->orWhere('invoice_number', 'like', "%$search%")
                  ->orWhere('id', 'like', "%$search%");
            });
        }

        if ($request->filled('variation_id')) {
            $query->where('variation_id', $request->variation_id);
        }
        
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }
        
        if ($request->filled('style_number')) {
            $styleSearch = trim($request->style_number);
            $cleanStyle = preg_replace('/[:\s\-]+/', '%', $styleSearch);
            $query->where(function($q) use ($styleSearch, $cleanStyle) {
                $q->whereHas('product', function($pq) use ($styleSearch, $cleanStyle) {
                    $pq->where('style_number', 'like', "%$styleSearch%")
                      ->orWhere('style_number', 'like', "%$cleanStyle%")
                      ->orWhere('sku', 'like', "%$styleSearch%");
                })->orWhereHas('variation', function($vq) use ($styleSearch, $cleanStyle) {
                    $vq->where('sku', 'like', "%$styleSearch%")
                      ->orWhere('sku', 'like', "%$cleanStyle%");
                });
            });
        }
        
        if ($request->filled('category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }
        
        if ($request->filled('brand_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }
        
        if ($request->filled('season_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('season_id', $request->season_id);
            });
        }
        
        if ($request->filled('gender_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('gender_id', $request->gender_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reportType = $request->get('report_type_active', 'yearly');

        if ($reportType === 'daily') {
            $startDate = $request->filled('date_from') ? \Carbon\Carbon::parse($request->date_from) : \Carbon\Carbon::today();
            $endDate = $request->filled('date_to') ? \Carbon\Carbon::parse($request->date_to) : \Carbon\Carbon::today();
            $query->whereBetween('requested_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
        } elseif ($reportType === 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $query->whereMonth('requested_at', $month)->whereYear('requested_at', $year);
        } elseif ($reportType === 'yearly') {
            $year = $request->get('year', date('Y'));
            $query->whereYear('requested_at', $year);
        }

        if ($request->filled('quick_filter')) {
            if ($request->quick_filter == 'today') {
                $query->whereDate('requested_at', now()->toDateString());
            } elseif ($request->quick_filter == 'monthly') {
                $query->whereMonth('requested_at', now()->month)
                      ->whereYear('requested_at', now()->year);
            }
        }

        // Filter by transfer type (transfer vs return)
        if ($request->filled('view_mode')) {
            if ($request->view_mode === 'returns') {
                $query->where('type', 'return');
            } elseif ($request->view_mode === 'transfers') {
                $query->where('type', '!=', 'return');
            }
        } else {
            // Default: show both but separate view handled in blade
        }

        return $query;
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view transfers')) {
            abort(403, 'Unauthorized action.');
        }

        $viewMode = $request->input('view_mode', 'all');
        $query = $this->applyFilters($request);

        if ($viewMode === 'returns') {
            $query->where('type', 'return');
        } elseif ($viewMode === 'transfers') {
            $query->where('type', '!=', 'return');
        }

        $transfers = $query->orderBy('requested_at', 'desc')->get();
        $headers = ['Invoice No', 'Date', 'Source', 'Destination', 'Category', 'Brand', 'Season', 'Gender', 'Product Name', 'Style #', 'Color', 'Size', 'Qty', 'Requested By', 'Status'];
        
        $exportData = [];
        foreach ($transfers as $transfer) {
            $product = $transfer->product;
            $variation = $transfer->variation;
            
            $color = '-'; $size = '-';
            if ($variation && $variation->attributeValues) {
                foreach($variation->attributeValues as $val) {
                    $attrName = strtolower($val->attribute->name ?? '');
                    if (str_contains($attrName, 'color') || (isset($val->attribute) && $val->attribute->is_color)) $color = $val->value;
                    elseif (str_contains($attrName, 'size')) $size = $val->value;
                }
            }

            $exportData[] = [
                $transfer->invoice_number ?? 'N/A',
                $transfer->requested_at ? \Carbon\Carbon::parse($transfer->requested_at)->format('d-m-Y') : '-',
                $transfer->from_type == 'branch' ? ($transfer->fromBranch->name ?? '-') : ($transfer->fromWarehouse->name ?? '-'),
                $transfer->to_type == 'branch' ? ($transfer->toBranch->name ?? '-') : ($transfer->toWarehouse->name ?? '-'),
                $product->category->name ?? '-',
                $product->brand->name ?? '-',
                $product->season->name ?? '-',
                $product->gender->name ?? '-',
                $product->name ?? '-',
                $product->style_number ?? $product->sku ?? '-',
                $color,
                $size,
                $transfer->quantity,
                $transfer->requestedPerson->name ?? '-',
                ucfirst($transfer->status)
            ];
        }

        $filename = 'stock_transfer_summary_' . date('Y-m-d_H-i-s') . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Stock Transfer Summary Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . '3', $header);
            $sheet->getStyle(chr(65 + $index) . '3')->getFont()->setBold(true);
        }
        
        $dataRow = 4;
        foreach ($exportData as $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue(chr(65 + $colIndex) . $dataRow, $value);
            }
            $dataRow++;
        }
        
        foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $filename);
        $writer->save($filePath);
        
        return response()->download($filePath, $filename)->deleteFileAfterSend();
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view transfers')) {
            abort(403, 'Unauthorized action.');
        }

        $viewMode = $request->input('view_mode', 'all');
        $query = $this->applyFilters($request);

        if ($viewMode === 'returns') {
            $query->where('type', 'return');
        } elseif ($viewMode === 'transfers') {
            $query->where('type', '!=', 'return');
        }

        $transfers = $query->orderBy('requested_at', 'desc')->get();
        $filename = 'stock_transfer_detailed_' . date('Y-m-d_H-i-s') . '.pdf';
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.stockTransfer.report-pdf', [
            'transfers' => $transfers,
            'filters' => $request->all()
        ]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    public function destroy(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('delete transfers') && !auth()->user()->hasPermissionTo('manage transfers')) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $transfer = StockTransfer::findOrFail($id);

        // Collect all transfers in the same invoice batch (or just this one)
        $transfers = $transfer->invoice_number
            ? StockTransfer::where('invoice_number', $transfer->invoice_number)->get()
            : collect([$transfer]);

        // Non-super-admins cannot delete delivered transfers
        if (!$this->isSuperAdmin()) {
            $hasDelivered = $transfers->contains(fn($t) => $t->status === 'delivered');
            if ($hasDelivered) {
                $msg = 'Delivered transfers cannot be deleted. Use "Return" to reverse stock instead.';
                if ($request->ajax()) return response()->json(['success' => false, 'message' => $msg], 422);
                return redirect()->back()->with('error', $msg);
            }
            $hasInvalid = $transfers->contains(fn($t) => !in_array($t->status, ['pending', 'rejected']));
            if ($hasInvalid) {
                $msg = 'Only pending or rejected transfers can be deleted by non-admins.';
                if ($request->ajax()) return response()->json(['success' => false, 'message' => $msg], 422);
                return redirect()->back()->with('error', $msg);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($transfers as $t) {
                // Reverse stock based on what was actually moved
                if ($t->status === 'delivered') {
                    // Full reversal: restore FROM source, deduct FROM destination
                    $this->adjustOutletStock($t->from_type, $t->from_id, $t->product_id, $t->variation_id, +$t->quantity);
                    $this->adjustOutletStock($t->to_type,   $t->to_id,   $t->product_id, $t->variation_id, -$t->quantity);
                } elseif ($t->status === 'approved') {
                    // Stock was only deducted from source (not yet added to destination)
                    $this->adjustOutletStock($t->from_type, $t->from_id, $t->product_id, $t->variation_id, +$t->quantity);
                }
                // pending / rejected: no stock was moved, just delete

                // Clear product cache
                \App\Services\CacheService::clearProductCaches($t->product_id);
            }

            // Delete all records in the batch
            if ($transfer->invoice_number) {
                StockTransfer::where('invoice_number', $transfer->invoice_number)->delete();
            } else {
                $transfer->delete();
            }

            DB::commit();

            $msg = 'Transfer deleted and stock reversed successfully.';
            if ($request->ajax()) return response()->json(['success' => true, 'message' => $msg]);
            return redirect()->route('stocktransfer.list')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Stock Transfer Delete Failed: ' . $e->getMessage());
            $msg = 'Failed to delete transfer: ' . $e->getMessage();
            if ($request->ajax()) return response()->json(['success' => false, 'message' => $msg], 500);
            return redirect()->back()->with('error', $msg);
        }
    }

    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('delete transfers') && !auth()->user()->hasPermissionTo('manage transfers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.']);
        }

        $selected = $request->selected;
        if (empty($selected)) {
            return response()->json(['success' => false, 'message' => 'No items selected.']);
        }

        $deletedCount = 0;
        foreach ($selected as $item) {
            $val = $item['val'];
            $type = $item['type'];

            if ($type === 'invoice') {
                $query = StockTransfer::where('invoice_number', $val);
                
                // Block deletion if ANY item is delivered AND user is NOT superadmin
                if (!$this->isSuperAdmin() && StockTransfer::where('invoice_number', $val)->where('status', 'delivered')->exists()) {
                    continue; 
                }

                if (!$this->isSuperAdmin()) {
                    $query->whereIn('status', ['pending', 'rejected']);
                }
                $deletedCount += $query->delete();
            } else {
                $transfer = StockTransfer::find($val);
                if ($transfer) {
                    if ($this->isSuperAdmin() || in_array($transfer->status, ['pending', 'rejected'])) {
                        $transfer->delete();
                        $deletedCount++;
                    }
                }
            }
        }

        return response()->json([
            'success' => true, 
            'message' => "Successfully deleted {$deletedCount} record(s)."
        ]);
    }

    private function isSuperAdmin()
    {
        return auth()->user()->hasRole('Super Admin') || auth()->user()->id == 18;
    }

    public function show($id)
    {
        if (!auth()->user()->hasPermissionTo('view transfers')) {
            abort(403, 'Unauthorized action.');
        }
        $transfer = StockTransfer::with(['product.category', 'variation'])->findOrFail($id);
        
        if ($transfer->invoice_number) {
            $transfers = StockTransfer::with(['product.category', 'variation'])
                ->where('invoice_number', $transfer->invoice_number)
                ->get();
        } else {
            $transfers = collect([$transfer]);
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        
        return view('erp.stockTransfer.show', compact('transfer', 'transfers', 'restrictedBranchId'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage transfers')) {
            abort(403, 'Unauthorized action.');
        }
        // Validate basic transfer information
    $request->validate([
        'transfer_date' => 'required|date',
        'from_outlet' => 'required|string',
        'to_outlet' => 'required|string',
        'items' => 'required|array|min:1',
        'is_direct' => 'nullable|boolean',
    ]);

    // Parse to_outlet
    $toOutlet = $request->to_outlet;
    if (str_starts_with($toOutlet, 'branch_')) {
        $toType = 'branch';
        $toId = str_replace('branch_', '', $toOutlet);
    } elseif (str_starts_with($toOutlet, 'warehouse_')) {
        $toType = 'warehouse';
        $toId = str_replace('warehouse_', '', $toOutlet);
    } else {
        return redirect()->back()->with('error', 'Invalid receiver outlet selected.');
    }

    // Parse from_outlet
    $fromOutlet = $request->from_outlet;
    if (str_starts_with($fromOutlet, 'branch_')) {
        $fromType = 'branch';
        $fromId = str_replace('branch_', '', $fromOutlet);
    } elseif (str_starts_with($fromOutlet, 'warehouse_')) {
        $fromType = 'warehouse';
        $fromId = str_replace('warehouse_', '', $fromOutlet);
    } else {
        return redirect()->back()->with('error', 'Invalid sender outlet selected.');
    }    

    // Restrict to Warehouse or Warehouse-Branch -> Branch only (for new transfers)
    if (!$request->return_of_id) {
        $fromBranch = ($fromType === 'branch') ? \App\Models\Branch::find($fromId) : null;
        $isFromWarehouse = ($fromType === 'warehouse') || ($fromBranch && $fromBranch->is_warehouse);
        
        if (!$isFromWarehouse || $toType !== 'branch') {
            return redirect()->back()->with('error', 'Transfers are only allowed from Warehouse (or Warehouse Branch) to Branch.');
        }
    }

        // Process each item and validate stock
        $transfersCreated = 0;
        $errors = [];

        // Generate sequential invoice number for this batch
        $today = date('Ymd');
        $prefix = $request->return_of_id ? 'RET' : 'TRF';
        $lastInvoice = StockTransfer::where('invoice_number', 'like', "{$prefix}-{$today}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice && preg_match('/' . $prefix . '-\d{8}-(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }
        
        $invoiceNumber = $prefix . '-' . $today . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Calculate total dispatch value first
        $totalDispatchValue = 0;
        foreach ($request->items as $i) {
            $totalDispatchValue += floatval($i['quantity'] ?? 0) * floatval($i['unit_price'] ?? 0);
        }

        foreach ($request->items as $key => $item) {
            // Skip if no quantity
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                continue;
            }

            $productId = $item['product_id'];
            $variationId = (isset($item['variation_id']) && $item['variation_id'] !== '' && $item['variation_id'] !== 'null') ? $item['variation_id'] : null;
            $quantity = floatval($item['quantity']);
            $unitPrice = floatval($item['unit_price'] ?? 0);
            $totalPrice = $quantity * $unitPrice;
            
            $itemPaid = 0;
            $itemDue = $totalPrice;

            // Validate stock availability based on Source Location (existing logic)
            if ($variationId) {
                $query = \App\Models\ProductVariationStock::where('variation_id', $variationId);
                if ($fromType === 'branch') {
                    $query->where('branch_id', $fromId)->whereNull('warehouse_id');
                } else {
                    $query->where('warehouse_id', $fromId)->whereNull('branch_id');
                }
                $totalStock = $query->sum('quantity');
            } else {
                if ($fromType === 'branch') {
                    $totalStock = \App\Models\BranchProductStock::where('product_id', $productId)->where('branch_id', $fromId)->sum('quantity');
                } else {
                    $totalStock = \App\Models\WarehouseProductStock::where('product_id', $productId)->where('warehouse_id', $fromId)->sum('quantity');
                }
            }    

            if ($quantity > $totalStock) {
                $errors[] = "Product/Variation ID {$productId}/{$variationId}: Requested {$quantity}, but only {$totalStock} available.";
                continue;
            }

            // Create transfer record
            try {
                $status = $request->is_direct ? 'delivered' : 'pending';
                $transfer = StockTransfer::create([
                    'from_type' => $fromType,
                    'from_id' => $fromId,
                    'to_type' => $toType,
                    'to_id' => $toId,
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'paid_amount' => 0,
                    'due_amount' => $totalPrice,
                    'sender_account_id' => null,
                    'receiver_account_id' => null,
                    'sender_account_type' => null,
                    'sender_account_number' => null,
                    'receiver_account_type' => null,
                    'receiver_account_number' => null,
                    'type' => $request->return_of_id ? 'return' : 'transfer',
                    'status' => $status,
                    'requested_by' => auth()->id(),
                    'requested_at' => $request->transfer_date,
                    'notes' => $request->note ?? null,
                    'invoice_number' => $invoiceNumber,
                    'return_of_id' => $request->return_of_id ?? null,
                ]);

                if ($request->is_direct) {
                    $transfer->approved_by = auth()->id();
                    $transfer->approved_at = now();
                    $transfer->delivered_by = auth()->id();
                    $transfer->delivered_at = now();
                    $transfer->save();

                    // Movement
                    $this->deductStock($transfer);
                    $this->addStock($transfer);
                }

                $transfersCreated++;
            } catch (\Exception $e) {
                $errors[] = "Error creating transfer for product {$productId}: " . $e->getMessage();
            }
        }

        if ($transfersCreated > 0) {
                // Financial accounting skipped for stock transfers as per request
            $message = "Successfully created {$transfersCreated} transfer(s).";
            if (count($errors) > 0) {
                $message .= " Errors: " . implode(', ', $errors);
            }
            return redirect()->route('stocktransfer.list')->with('success', $message);
        } else {
            return redirect()->back()->with('error', 'No transfers created. ' . implode(', ', $errors));
        }
    }

    public function updateStatus(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('approve transfers') && !auth()->user()->hasPermissionTo('manage transfers')) {
            abort(403, 'Unauthorized action.');
        }
        $primaryTransfer = StockTransfer::findOrFail($id);
        
        // Identify the batch: if invoice_number exists, get all; otherwise just this one
        if ($primaryTransfer->invoice_number) {
            $transfers = StockTransfer::where('invoice_number', $primaryTransfer->invoice_number)->get();
        } else {
            $transfers = collect([$primaryTransfer]);
        }

        // Phase 1: Pre-validation (Crucial for atomic invoice approval/delivery)
        if (in_array($request->status, ['approved', 'delivered'])) {
            foreach ($transfers as $transfer) {
                // If already approved/delivered, stock was already validated and deducted
                if (in_array($transfer->status, ['approved', 'delivered'])) continue;

                if ($transfer->variation_id) {
                     // Check Variation Stock
                     if($transfer->from_type == 'branch'){
                         $vStock = ProductVariationStock::where('variation_id', $transfer->variation_id)
                             ->where('branch_id', $transfer->from_id)
                             ->whereNull('warehouse_id')
                             ->first();
                         $availableQty = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                         if (!$vStock || $availableQty < $transfer->quantity) {
                             return redirect()->back()->with('error', "Insufficient stock for product '{$transfer->product->name}' (Var: {$transfer->variation->name}) at source branch. Available: {$availableQty}, Requested: {$transfer->quantity}");
                         }
                     } else {
                         // Warehouse Variation
                         $vStock = ProductVariationStock::where('variation_id', $transfer->variation_id)
                             ->where('warehouse_id', $transfer->from_id)
                             ->whereNull('branch_id')
                             ->first();
                         $availableQty = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                         if (!$vStock || $availableQty < $transfer->quantity) {
                             return redirect()->back()->with('error', "Insufficient stock for product '{$transfer->product->name}' (Var: {$transfer->variation->name}) at source warehouse. Available: {$availableQty}, Requested: {$transfer->quantity}");
                         }
                     }
                } else {
                    // Check Regular Stock
                    if($transfer->from_type == 'branch'){
                         $branchStock = BranchProductStock::where('product_id', $transfer->product_id)->where('branch_id', $transfer->from_id)->first();
                         if (!$branchStock || $branchStock->quantity < $transfer->quantity) {
                             return redirect()->back()->with('error', "Insufficient stock for product '{$transfer->product->name}' at source branch.");
                         }
                    } else {
                         $warehouseStock = WarehouseProductStock::where('product_id', $transfer->product_id)->where('warehouse_id', $transfer->from_id)->first();
                         if (!$warehouseStock || $warehouseStock->quantity < $transfer->quantity) {
                             return redirect()->back()->with('error', "Insufficient stock for product '{$transfer->product->name}' at source warehouse.");
                         }
                    }
                }
            }
        }

        // Phase 2: Apply Status Updates and Stock Changes
        DB::beginTransaction();
        try {
            foreach ($transfers as $transfer) {
                // Skip if status matches (idempotency, though some transitions might be valid re-entries, simplified here)
                if ($transfer->status == $request->status) continue;

                if($request->status == 'approved')
                {
                    $transfer->status = $request->status;
                    $transfer->approved_by = auth()->id();
                    $transfer->approved_at = now();
                    $transfer->save(); // Save status first

                    // Deduct Stock
                    $this->deductStock($transfer);
                }elseif($request->status == 'delivered'){
                    $prevStatus = $transfer->status;
                    $transfer->status = $request->status;
                    $transfer->delivered_by = auth()->id();
                    $transfer->delivered_at = now();
                    $transfer->save();

                    // If stock wasn't deducted yet, deduct it from source now
                    if ($prevStatus !== 'approved') {
                        $this->deductStock($transfer);
                    }
 
                    // Add Stock to Destination
                    $this->addStock($transfer);
                }elseif($request->status == 'rejected' && $transfer->status != 'delivered'){
                    $oldStatus = $transfer->status;
                    $transfer->status = $request->status;
                    $transfer->approved_by = null; 
                    $transfer->approved_at = null;
                    $transfer->delivered_by = null; 
                    $transfer->delivered_at = null;
                    $transfer->save();
        
                    // Restore stock IF it was previously deducted (i.e. if it was approved)
                    if ($oldStatus == 'approved') {
                        if ($transfer->variation_id) {
                            if($transfer->from_type == 'branch'){
                                $vStock = ProductVariationStock::where('variation_id', $transfer->variation_id)
                                    ->where('branch_id', $transfer->from_id)
                                    ->whereNull('warehouse_id')
                                    ->first();
                                if ($vStock) {
                                    $vStock->quantity += $transfer->quantity;
                                    $vStock->save();
                                }

                                // Mirror restoration to branch product stock
                                $branchStock = BranchProductStock::where('branch_id', $transfer->from_id)
                                    ->where('product_id', $transfer->product_id)
                                    ->first();
                                if ($branchStock) {
                                    $branchStock->quantity += $transfer->quantity;
                                    $branchStock->save();
                                }
                            } else {
                                $vStock = ProductVariationStock::where('variation_id', $transfer->variation_id)
                                    ->where('warehouse_id', $transfer->from_id)
                                    ->whereNull('branch_id')
                                    ->first();
                                if ($vStock) {
                                    $vStock->quantity += $transfer->quantity;
                                    $vStock->save();
                                }

                                // Mirror restoration to warehouse product stock
                                $warehouseStock = WarehouseProductStock::where('warehouse_id', $transfer->from_id)
                                    ->where('product_id', $transfer->product_id)
                                    ->first();
                                if ($warehouseStock) {
                                    $warehouseStock->quantity += $transfer->quantity;
                                    $warehouseStock->save();
                                }
                            }
                        } else {
                            if($transfer->from_type == 'branch'){
                                $branchStock = BranchProductStock::where('product_id', $transfer->product_id)->where('branch_id', $transfer->from_id)->first();
                                if ($branchStock) {
                                    $branchStock->quantity += $transfer->quantity;
                                    $branchStock->save();
                                }
                            } else {
                                $warehouseStock = WarehouseProductStock::where('product_id', $transfer->product_id)->where('warehouse_id', $transfer->from_id)->first();
                                if ($warehouseStock) {
                                    $warehouseStock->quantity += $transfer->quantity;
                                    $warehouseStock->save();
                                }
                            }
                        }
                    }
                }
            }

            // =====================================================
            // ACCOUNTING LOGIC: Move money from Receiver to Sender
            // =====================================================
            if ($request->status == 'delivered') {
                $this->processAccounting($transfers);
            }
            // =====================================================

            DB::commit();
            return redirect()->back()->with('success', 'Status updated for Invoice ' . ($primaryTransfer->invoice_number ?? $primaryTransfer->id));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating status: ' . $e->getMessage());
        }
    }

    public function reconcile(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('reconcile transfers') && !$this->isSuperAdmin()) {
            abort(403, 'Unauthorized action. You need permission to reconcile stock transfers.');
        }

        $request->validate([
            'quantities' => 'required|array',
            'quantities.*' => 'required|numeric|min:0'
        ]);

        $primaryTransfer = StockTransfer::findOrFail($id);

        DB::beginTransaction();
        try {
            foreach ($request->quantities as $itemId => $newQty) {
                $transfer = StockTransfer::findOrFail($itemId);
                $oldQty = $transfer->quantity;
                $delta = $newQty - $oldQty;

                if ($delta == 0) {
                    continue;
                }

                // If delta is positive and status is approved/delivered, verify source location has enough stock before deducting
                if ($delta > 0 && in_array($transfer->status, ['approved', 'delivered'])) {
                    $availableStock = 0;
                    if ($transfer->variation_id) {
                        $vQuery = ProductVariationStock::where('variation_id', $transfer->variation_id);
                        if ($transfer->from_type === 'branch') {
                            $vQuery->where('branch_id', $transfer->from_id)->whereNull('warehouse_id');
                        } else {
                            $vQuery->where('warehouse_id', $transfer->from_id)->whereNull('branch_id');
                        }
                        $vStock = $vQuery->first();
                        $availableStock = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                    } else {
                        if ($transfer->from_type === 'branch') {
                            $bStock = BranchProductStock::where('product_id', $transfer->product_id)->where('branch_id', $transfer->from_id)->first();
                            $availableStock = $bStock ? $bStock->quantity : 0;
                        } else {
                            $wStock = WarehouseProductStock::where('product_id', $transfer->product_id)->where('warehouse_id', $transfer->from_id)->first();
                            $availableStock = $wStock ? $wStock->quantity : 0;
                        }
                    }

                    if ($availableStock < $delta) {
                        DB::rollBack();
                        $pName = $transfer->product ? $transfer->product->name : "ID {$transfer->product_id}";
                        return redirect()->back()->with('error', "That amount is not available in stock for '{$pName}'! Current Transfer Qty: {$oldQty}, Available Stock at Source: {$availableStock}. You need to purchase stock first.");
                    }
                }

                // If transfer was delivered, adjust the stocks at source and destination locations
                if ($transfer->status === 'delivered') {
                    // Source Location: Restore/Deduct stock based on -$delta
                    $this->adjustOutletStock(
                        $transfer->from_type,
                        $transfer->from_id,
                        $transfer->product_id,
                        $transfer->variation_id,
                        -$delta
                    );

                    // Destination Location: Add/Deduct stock based on +$delta
                    $this->adjustOutletStock(
                        $transfer->to_type,
                        $transfer->to_id,
                        $transfer->product_id,
                        $transfer->variation_id,
                        $delta
                    );
                } elseif ($transfer->status === 'approved') {
                    // Source Location only: Restore/Deduct stock based on -$delta (as destination hasn't confirmed receipt yet)
                    $this->adjustOutletStock(
                        $transfer->from_type,
                        $transfer->from_id,
                        $transfer->product_id,
                        $transfer->variation_id,
                        -$delta
                    );
                }

                // Update the Stock Transfer record
                $transfer->quantity = $newQty;
                $transfer->total_price = $newQty * $transfer->unit_price;
                $transfer->due_amount = $transfer->total_price - $transfer->paid_amount;
                $transfer->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Stock Transfer quantities reconciled successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error reconciling stock: ' . $e->getMessage());
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

    private function deductStock(StockTransfer $transfer)
    {
        if ($transfer->variation_id) {
            if ($transfer->from_type == 'branch') {
                $vStock = ProductVariationStock::where('variation_id', $transfer->variation_id)
                    ->where('branch_id', $transfer->from_id)
                    ->whereNull('warehouse_id')
                    ->first();
                if ($vStock) {
                    $vStock->quantity -= $transfer->quantity;
                    if ($vStock->quantity < 0) $vStock->quantity = 0;
                    $vStock->save();
                }

                $branchStock = BranchProductStock::where('branch_id', $transfer->from_id)
                    ->where('product_id', $transfer->product_id)
                    ->first();
                if ($branchStock) {
                    $branchStock->quantity -= $transfer->quantity;
                    if ($branchStock->quantity < 0) $branchStock->quantity = 0;
                    $branchStock->save();
                }
            } else {
                $vStock = ProductVariationStock::where('variation_id', $transfer->variation_id)
                    ->where('warehouse_id', $transfer->from_id)
                    ->whereNull('branch_id')
                    ->first();
                if ($vStock) {
                    $vStock->quantity -= $transfer->quantity;
                    if ($vStock->quantity < 0) $vStock->quantity = 0;
                    $vStock->save();
                }

                $warehouseStock = WarehouseProductStock::where('warehouse_id', $transfer->from_id)
                    ->where('product_id', $transfer->product_id)
                    ->first();
                if ($warehouseStock) {
                    $warehouseStock->quantity -= $transfer->quantity;
                    if ($warehouseStock->quantity < 0) $warehouseStock->quantity = 0;
                    $warehouseStock->save();
                }
            }
        } else {
            if ($transfer->from_type == 'branch') {
                $branchStock = BranchProductStock::where('product_id', $transfer->product_id)->where('branch_id', $transfer->from_id)->first();
                if ($branchStock) {
                    $branchStock->quantity -= $transfer->quantity;
                    if ($branchStock->quantity < 0) $branchStock->quantity = 0;
                    $branchStock->save();
                }
            } else {
                $warehouseStock = WarehouseProductStock::where('product_id', $transfer->product_id)->where('warehouse_id', $transfer->from_id)->first();
                if ($warehouseStock) {
                    $warehouseStock->quantity -= $transfer->quantity;
                    if ($warehouseStock->quantity < 0) $warehouseStock->quantity = 0;
                    $warehouseStock->save();
                }
            }
        }
    }

    private function addStock(StockTransfer $transfer)
    {
        if ($transfer->variation_id) {
            if ($transfer->to_type == 'branch') {
                $vStock = ProductVariationStock::firstOrNew([
                    'variation_id' => $transfer->variation_id,
                    'branch_id' => $transfer->to_id,
                    'warehouse_id' => null
                ]);
                $vStock->quantity = ($vStock->quantity ?? 0) + $transfer->quantity;
                $vStock->updated_by = auth()->id();
                $vStock->last_updated_at = now();
                $vStock->save();

                $branchStock = BranchProductStock::firstOrNew([
                    'branch_id'  => $transfer->to_id,
                    'product_id' => $transfer->product_id,
                ]);
                $branchStock->quantity = ($branchStock->quantity ?? 0) + $transfer->quantity;
                $branchStock->updated_by = auth()->id();
                $branchStock->last_updated_at = now();
                $branchStock->save();
            } else {
                $vStock = ProductVariationStock::firstOrNew([
                    'variation_id' => $transfer->variation_id,
                    'warehouse_id' => $transfer->to_id,
                    'branch_id' => null
                ]);
                $vStock->quantity = ($vStock->quantity ?? 0) + $transfer->quantity;
                $vStock->updated_by = auth()->id();
                $vStock->last_updated_at = now();
                $vStock->save();

                $warehouseStock = WarehouseProductStock::firstOrNew([
                    'warehouse_id' => $transfer->to_id,
                    'product_id'   => $transfer->product_id,
                ]);
                $warehouseStock->quantity = ($warehouseStock->quantity ?? 0) + $transfer->quantity;
                $warehouseStock->updated_by = auth()->id();
                $warehouseStock->last_updated_at = now();
                $warehouseStock->save();
            }
        } else {
            if ($transfer->to_type == 'branch') {
                $branchStock = BranchProductStock::firstOrNew([
                    'product_id' => $transfer->product_id,
                    'branch_id' => $transfer->to_id
                ]);
                $branchStock->quantity = ($branchStock->quantity ?? 0) + $transfer->quantity;
                $branchStock->updated_by = auth()->id();
                $branchStock->last_updated_at = now();
                $branchStock->save();
            } else {
                $warehouseStock = WarehouseProductStock::firstOrNew([
                    'product_id' => $transfer->product_id,
                    'warehouse_id' => $transfer->to_id
                ]);
                $warehouseStock->quantity = ($warehouseStock->quantity ?? 0) + $transfer->quantity;
                $warehouseStock->updated_by = auth()->id();
                $warehouseStock->last_updated_at = now();
                $warehouseStock->save();
            }
        }
    }

    public function processAccounting($transfers)
    {
        $firstTransfer  = $transfers->first();
        if (!$firstTransfer) return;

        // Skip accounting if the transfer involves a warehouse
        if ($firstTransfer->from_type === 'warehouse' || $firstTransfer->to_type === 'warehouse') {
            return;
        }

        $totalBatchPaid = $transfers->sum('paid_amount');
        $totalBatchDue  = $transfers->sum('due_amount');

        // 1. Process Paid Amount (Cash/Bank Movement)
        if ($totalBatchPaid > 0) {
            $senderAccId = $firstTransfer->sender_account_id;
            $receiverAccId = $firstTransfer->receiver_account_id;

            if ($senderAccId && $receiverAccId) {
                $senderAcc = FinancialAccount::find($senderAccId);
                $receiverAcc = FinancialAccount::find($receiverAccId);

                if ($senderAcc && $receiverAcc) {
                    $senderAcc->balance += $totalBatchPaid;
                    $senderAcc->save();

                    $receiverAcc->balance -= $totalBatchPaid;
                    $receiverAcc->save();

                    $voucherNo = 'STP-' . str_pad($firstTransfer->id, 6, '0', STR_PAD_LEFT);
                    if (!Journal::where('voucher_no', $voucherNo)->exists()) {
                        $journal = Journal::create([
                            'voucher_no'     => $voucherNo,
                            'entry_date'     => now(),
                            'type'           => 'Journal',
                            'description'    => 'Payment for Stock Transfer #' . ($firstTransfer->invoice_number ?? $firstTransfer->id),
                            'branch_id'      => $firstTransfer->from_type == 'branch' ? $firstTransfer->from_id : null,
                            'voucher_amount' => $totalBatchPaid,
                            'paid_amount'    => $totalBatchPaid,
                            'reference'      => $firstTransfer->invoice_number,
                            'created_by'     => auth()->id() ?? 1,
                            'updated_by'     => auth()->id() ?? 1,
                        ]);

                        JournalEntry::create([
                            'journal_id'           => $journal->id,
                            'chart_of_account_id'  => $senderAcc->account_id,
                            'financial_account_id' => $senderAcc->id,
                            'debit'                => $totalBatchPaid,
                            'credit'               => 0,
                            'memo'                 => 'Received payment for stock transfer',
                            'created_by'           => auth()->id() ?? 1,
                            'updated_by'           => auth()->id() ?? 1,
                        ]);

                        JournalEntry::create([
                            'journal_id'           => $journal->id,
                            'chart_of_account_id'  => $receiverAcc->account_id,
                            'financial_account_id' => $receiverAcc->id,
                            'debit'                => 0,
                            'credit'               => $totalBatchPaid,
                            'memo'                 => 'Paid for stock transfer receipt',
                            'created_by'           => auth()->id() ?? 1,
                            'updated_by'           => auth()->id() ?? 1,
                        ]);
                    }
                }
            }
        }

        // 2. Process Due Amount (Inter-branch Debt - Option B)
        if ($totalBatchDue > 0) {
            $receivableAcc = \App\Models\ChartOfAccount::where('name', 'Inter-branch Receivable')->first();
            $payableAcc    = \App\Models\ChartOfAccount::where('name', 'Inter-branch Payable')->first();

            if ($receivableAcc && $payableAcc) {
                $voucherNo = 'STD-' . str_pad($firstTransfer->id, 6, '0', STR_PAD_LEFT);
                if (!Journal::where('voucher_no', $voucherNo)->exists()) {
                    $journal = Journal::create([
                        'voucher_no'     => $voucherNo,
                        'entry_date'     => now(),
                        'type'           => 'Journal',
                        'description'    => 'Debt Recording for Stock Transfer #' . ($firstTransfer->invoice_number ?? $firstTransfer->id),
                        'branch_id'      => $firstTransfer->from_type == 'branch' ? $firstTransfer->from_id : null,
                        'voucher_amount' => $totalBatchDue,
                        'paid_amount'    => 0,
                        'reference'      => $firstTransfer->invoice_number,
                        'created_by'     => auth()->id() ?? 1,
                        'updated_by'     => auth()->id() ?? 1,
                    ]);

                    // Sender Branch: Increase Receivable
                    JournalEntry::create([
                        'journal_id'           => $journal->id,
                        'chart_of_account_id'  => $receivableAcc->id,
                        'debit'                => $totalBatchDue,
                        'credit'               => 0,
                        'memo'                 => 'Inter-branch receivable recorded',
                        'created_by'           => auth()->id() ?? 1,
                        'updated_by'           => auth()->id() ?? 1,
                    ]);

                    // Receiver Branch: Increase Payable
                    JournalEntry::create([
                        'journal_id'           => $journal->id,
                        'chart_of_account_id'  => $payableAcc->id,
                        'debit'                => 0,
                        'credit'               => $totalBatchDue,
                        'memo'                 => 'Inter-branch payable recorded',
                        'created_by'           => auth()->id() ?? 1,
                        'updated_by'           => auth()->id() ?? 1,
                    ]);
                }
            }
        }
    }
    public function return($id)
    {
        if (!auth()->user()->hasPermissionTo('manage transfers')) {
            abort(403, 'Unauthorized action.');
        }

        $originalTransfer = StockTransfer::with([
            'fromBranch', 'fromWarehouse', 'toBranch', 'toWarehouse'
        ])->findOrFail($id);

        // Guard: only allow returns on delivered transfers
        if ($originalTransfer->status !== 'delivered') {
            return redirect()->route('stocktransfer.show', $id)
                ->with('error', 'Only delivered transfers can be returned.');
        }

        // Guard: don't allow returning a return
        if ($originalTransfer->type === 'return') {
            return redirect()->route('stocktransfer.show', $id)
                ->with('error', 'A return transfer cannot be returned again.');
        }

        // Find all items in this batch (invoice), with full relations for the view
        $relations = [
            'product.category',
            'product.brand',
            'product.season',
            'variation.combinations.attribute',
            'variation.combinations.attributeValue',
        ];

        if ($originalTransfer->invoice_number) {
            $items = StockTransfer::with($relations)
                ->where('invoice_number', $originalTransfer->invoice_number)
                ->get();
        } else {
            $items = collect([$originalTransfer->load($relations)]);
        }

        // Swap outlets: return FROM the destination, TO the original source
        $fromOutlet = ($originalTransfer->to_type === 'branch' ? 'branch_' : 'warehouse_') . $originalTransfer->to_id;
        $toOutlet   = ($originalTransfer->from_type === 'branch' ? 'branch_' : 'warehouse_') . $originalTransfer->from_id;

        $branches         = Branch::all();
        $warehouses       = Warehouse::all();
        $restrictedBranchId = $this->getRestrictedBranchId();
        $financialAccounts = \App\Models\FinancialAccount::orderBy('provider_name')->get();

        return view('erp.stockTransfer.create', compact(
            'branches', 'warehouses', 'financialAccounts',
            'items', 'fromOutlet', 'toOutlet', 'originalTransfer', 'restrictedBranchId'
        ));
    }
}

