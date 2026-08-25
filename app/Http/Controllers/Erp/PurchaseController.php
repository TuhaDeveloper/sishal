<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FinancialAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\PurchaseItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = \App\Models\PurchaseItem::with([
            'purchase.bill',
            'purchase.supplier',
            'product.category',
            'product.brand',
            'product.season',
            'product.gender',
            'product.branchStock',
            'product.warehouseStock',
            'variation.attributeValues.attribute',
            'variation.stocks',
            'returnItems'
        ]);

        $query = $this->applyFilters($query, $request, $startDate, $endDate);

        // Big Data Optimization: Calculate accurate totals in SQL
        $itemTotals = \DB::table(\DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query->getQuery())
            ->selectRaw("SUM(quantity) as total_qty, SUM(total_price) as total_amount")
            ->first();

        // Return totals (Return Qty, Return Value)
        $filteredItemIds = clone $query;
        $returnTotals = \DB::table('purchase_return_items')
            ->whereIn('purchase_item_id', $filteredItemIds->select('id'))
            ->selectRaw("SUM(returned_qty) as total_ret_qty, SUM(returned_qty * unit_price) as total_ret_amt")
            ->first();

        // Sale-level totals (Discount, Paid, Due)
        $filteredPurchaseIds = clone $query;
        $purchaseTotals = \DB::table('purchase_bills')
            ->whereIn('purchase_id', $filteredPurchaseIds->select('purchase_id')->distinct())
            ->selectRaw("SUM(discount_amount) as total_discount, SUM(paid_amount) as total_paid, SUM(due_amount) as total_due")
            ->first();

        $reportTotals = [
            'pur_qty'  => $itemTotals->total_qty ?? 0,
            'pur_amt'  => $itemTotals->total_amount ?? 0,
            'ret_qty'  => $returnTotals->total_ret_qty ?? 0,
            'ret_amt'  => $returnTotals->total_ret_amt ?? 0,
            'act_qty'  => ($itemTotals->total_qty ?? 0) - ($returnTotals->total_ret_qty ?? 0),
            'act_amt'  => ($itemTotals->total_amount ?? 0) - ($purchaseTotals->total_discount ?? 0) - ($returnTotals->total_ret_amt ?? 0),
            'discount' => $purchaseTotals->total_discount ?? 0,
            'paid'     => $purchaseTotals->total_paid ?? 0,
            'due'      => $purchaseTotals->total_due ?? 0,
        ];

        $items = $query->latest()->paginate(100)->appends($request->all());
        
        $items->getCollection()->transform(function ($item) {
            $product = $item->product;
            $variation = $item->variation;
            $purchase = $item->purchase;
            $currentStock = 0;
            
            if ($product) {
                if ($product->has_variations && $variation) {
                    if ($purchase->ship_location_type === 'branch') {
                        $currentStock = $variation->stocks->where('branch_id', $purchase->location_id)->sum('quantity');
                    } else {
                        $currentStock = $variation->stocks->where('warehouse_id', $purchase->location_id)->sum('quantity');
                    }
                } else {
                    if ($purchase->ship_location_type === 'branch') {
                        $currentStock = $product->branchStock->where('branch_id', $purchase->location_id)->sum('quantity');
                    } else {
                        $currentStock = $product->warehouseStock->where('warehouse_id', $purchase->location_id)->sum('quantity');
                    }
                }
            }
            $item->current_stock = $currentStock;
            return $item;
        });
        
        // Big Data Dropdown Optimization: Limit initial load
        $suppliers = \App\Models\Supplier::orderBy('name')->limit(100)->get();
        $categories = \App\Models\ProductServiceCategory::whereNull('parent_id')->orderBy('name')->get();
        $brands = \App\Models\Brand::orderBy('name')->get();
        $seasons = \App\Models\Season::orderBy('name')->get();
        $genders = \App\Models\Gender::orderBy('name')->get();
        $products = \App\Models\Product::where('type', 'product')->orderBy('name')->limit(100)->get();
        
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branches = Branch::where('id', $restrictedBranchId)->get();
            $warehouses = collect();
        } else {
            $branches = Branch::where('status', 'active')->get();
            $warehouses = \App\Models\Warehouse::all();
        }
        $bankAccounts = \DB::table('financial_accounts')->get();

        if ($request->ajax()) {
            return view('erp.purchases.partials.table', compact('items', 'reportTotals'));
        }

        return view('erp.purchases.purchaseList', compact(
            'items', 'suppliers', 'categories', 'brands', 'seasons', 'genders', 'products', 
            'branches', 'warehouses', 'bankAccounts', 'reportType', 'startDate', 'endDate', 'reportTotals'
        ));
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = \App\Models\PurchaseItem::with([
            'purchase.bill', 
            'purchase.supplier', 
            'purchase.items.returnItems',
            'product.category', 
            'product.brand', 
            'product.season', 
            'product.gender',
            'product.branchStock',
            'product.warehouseStock',
            'variation.attributeValues.attribute',
            'variation.stocks',
            'returnItems'
        ]);
        $query = $this->applyFilters($query, $request, $startDate, $endDate);
        $items = $query->orderBy('created_at', 'desc')->get();

        $branchesMap = \Illuminate\Support\Facades\Cache::remember('branches_map', 300, function() {
            return \App\Models\Branch::pluck('name', 'id');
        });
        $warehousesMap = \Illuminate\Support\Facades\Cache::remember('warehouses_map', 300, function() {
            return \App\Models\Warehouse::pluck('name', 'id');
        });

        $headers = [
            'SL', 'Inv #', 'Date', 'Supplier', 'Warehouse', 'Category', 'Brand', 'Season', 'Gender', 
            'Product Name', 'Style #', 'Color', 'Size', 
            'Pur. Qty', 'Inv. T. Qty', 'Pur. Value', 'Inv. T. Value', 'Item Disc.',
            'Ret. Qty', 'Inv. T. Ret. Qty', 'Ret. Value', 'Inv. T. Ret. Value', 
            'Act. Qty', 'Inv. T. Act. Qty', 'Act. Value', 'Inv. T. Act. Value', 
            'Live Stock', 'Bill Disc.', 'Paid A/C', 'Due A/C', 'Status'
        ];
        $exportData[] = $headers;

        $totPurQty = 0;
        $totInvPurQty = 0;
        $totPurAmt = 0;
        $totInvPurAmt = 0;
        $totItemDisc = 0;
        $totRetQty = 0;
        $totInvRetQty = 0;
        $totRetAmt = 0;
        $totInvRetAmt = 0;
        $totActQty = 0;
        $totInvActQty = 0;
        $totActAmt = 0;
        $totInvActAmt = 0;
        $totDiscount = 0;
        $totPaid = 0;
        $totDue = 0;

        foreach ($items as $index => $item) {
            $purchase = $item->purchase;
            $bill = $purchase->bill;
            $product = $item->product;
            $variation = $item->variation;
            
            // Extract Color and Size
            $color = '-'; $size = '-';
            if ($variation && $variation->attributeValues) {
                foreach($variation->attributeValues as $val) {
                    $attrName = strtolower($val->attribute->name ?? '');
                    if (str_contains($attrName, 'color') || (isset($val->attribute) && $val->attribute->is_color)) {
                        $color = $val->value;
                    } elseif (str_contains($attrName, 'size')) {
                        $size = $val->value;
                    }
                }
            }

            // Calculations
            $retQty = (float)$item->returnItems->sum('returned_qty');
            $retAmt = (float)$item->returnItems->sum(fn($ri) => $ri->returned_qty * $ri->unit_price);
            $actQty = (float)($item->quantity - $retQty);
            $itemDiscount = (float)($item->discount ?? 0);
            $actAmt = (float)($item->total_price - $itemDiscount - $retAmt);

            $showInvoiceTotals = ($index == 0 || $items[$index-1]->purchase_id != $item->purchase_id);
            
            $invPurQty = $purchase->items->sum('quantity');
            $invPurAmt = $purchase->items->sum('total_price');
            $invRetQty = $purchase->items->sum(fn($i) => $i->returnItems->sum('returned_qty'));
            $invRetAmt = $purchase->items->sum(fn($i) => $i->returnItems->sum(fn($ri) => $ri->returned_qty * $ri->unit_price));
            $invDiscount = (float)($bill->discount_amount ?? 0);
            $invActQty = $invPurQty - $invRetQty;
            $invActAmt = $invPurAmt - $invDiscount - $invRetAmt;

            $discountVal = $showInvoiceTotals ? (float)($bill->discount_amount ?? 0) : 0.0;
            $paidVal = $showInvoiceTotals ? (float)($bill->paid_amount ?? 0) : 0.0;
            $dueVal = $showInvoiceTotals ? (float)($bill->due_amount ?? 0) : 0.0;

            // Live Stock (Current Stock)
            $currentStock = 0;
            if ($product) {
                if ($product->has_variations && $variation) {
                    if ($purchase->ship_location_type === 'branch') {
                        $currentStock = $variation->stocks->where('branch_id', $purchase->location_id)->sum('quantity');
                    } else {
                        $currentStock = $variation->stocks->where('warehouse_id', $purchase->location_id)->sum('quantity');
                    }
                } else {
                    if ($purchase->ship_location_type === 'branch') {
                        $currentStock = $product->branchStock->where('branch_id', $purchase->location_id)->sum('quantity');
                    } else {
                        $currentStock = $product->warehouseStock->where('warehouse_id', $purchase->location_id)->sum('quantity');
                    }
                }
            }

            $totPurQty += (float)$item->quantity;
            $totPurAmt += (float)$item->total_price;
            $totItemDisc += (float)($item->discount ?? 0);
            $totRetQty += $retQty;
            $totRetAmt += $retAmt;
            $totActQty += $actQty;
            $totActAmt += $actAmt;

            if ($showInvoiceTotals) {
                $totInvPurQty += (float)$invPurQty;
                $totInvPurAmt += (float)$invPurAmt;
                $totInvRetQty += (float)$invRetQty;
                $totInvRetAmt += (float)$invRetAmt;
                $totInvActQty += (float)$invActQty;
                $totInvActAmt += (float)$invActAmt;
            }

            $totDiscount += $discountVal;
            $totPaid += $paidVal;
            $totDue += $dueVal;

            $locationName = '-';
            if ($purchase->ship_location_type === 'branch') {
                $locationName = $branchesMap[$purchase->location_id] ?? '-';
            } elseif ($purchase->ship_location_type === 'warehouse') {
                $locationName = $warehousesMap[$purchase->location_id] ?? '-';
            }

            $row = [
                $index + 1,
                $bill->bill_number ?? 'P-'.$purchase->id,
                $purchase->purchase_date,
                $purchase->supplier->name ?? 'N/A',
                $locationName,
                $product->category->name ?? 'N/A',
                $product->brand->name ?? 'N/A',
                $product->season->name ?? 'N/A',
                $product->gender->name ?? 'N/A',
                $product->name ?? 'N/A',
                $product->sku ?? $product->style_number ?? 'N/A',
                $color,
                $size,
                (float)$item->quantity,
                $showInvoiceTotals ? (float)$invPurQty : '',
                (float)$item->total_price,
                $showInvoiceTotals ? (float)$invPurAmt : '',
                (float)($item->discount ?? 0),
                $retQty,
                $showInvoiceTotals ? (float)$invRetQty : '',
                $retAmt,
                $showInvoiceTotals ? (float)$invRetAmt : '',
                $actQty,
                $showInvoiceTotals ? (float)$invActQty : '',
                $actAmt,
                $showInvoiceTotals ? (float)$invActAmt : '',
                (float)$currentStock,
                $discountVal,
                $paidVal,
                $dueVal,
                ucfirst($purchase->status)
            ];
            $exportData[] = $row;
        }

        // Add Grand Total row at the end
        $totalRow = [
            'Total', '', '', '', '', '', '', '', '', '', '', '', '',
            $totPurQty,
            $totInvPurQty,
            $totPurAmt,
            $totInvPurAmt,
            $totItemDisc,
            $totRetQty,
            $totInvRetQty,
            $totRetAmt,
            $totInvRetAmt,
            $totActQty,
            $totInvActQty,
            $totActAmt,
            $totInvActAmt,
            '',
            $totDiscount,
            $totPaid,
            $totDue,
            ''
        ];
        $exportData[] = $totalRow;

        $filename = 'purchase_audit_report_' . date('Y-m-d_His') . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->fromArray($exportData, NULL, 'A1');
        
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        // Style the total row
        $totalRowIndex = count($exportData);
        $sheet->getStyle('A' . $totalRowIndex . ':' . $sheet->getHighestColumn() . $totalRowIndex)->getFont()->setBold(true);
        $sheet->getStyle('A' . $totalRowIndex . ':' . $sheet->getHighestColumn() . $totalRowIndex)
            ->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A' . $totalRowIndex . ':' . $sheet->getHighestColumn() . $totalRowIndex)
            ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $filename);
        $writer->save($filePath);
        
        return response()->download($filePath, $filename)->deleteFileAfterSend();
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $startDate = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = \App\Models\PurchaseItem::with([
            'purchase.bill', 
            'purchase.supplier', 
            'product.category', 
            'product.brand', 
            'product.season', 
            'product.gender',
            'variation.attributeValues.attribute',
            'returnItems'
        ]);
        $query = $this->applyFilters($query, $request, $startDate, $endDate);
        $items = $query->orderBy('created_at', 'desc')->get();

        $filename = 'purchase_audit_report_' . date('Y-m-d_His') . '.pdf';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.purchases.report-pdf', [
            'items' => $items,
            'filters' => $request->all()
        ]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    private function applyFilters($query, Request $request, $startDate = null, $endDate = null)
    {
        // Date Filtering
        if ($startDate && $endDate) {
            $query->whereHas('purchase', function($q) use ($startDate, $endDate) {
                $q->whereBetween('purchase_date', [$startDate, $endDate]);
            });
        } elseif ($startDate) {
            $query->whereHas('purchase', function($q) use ($startDate) {
                $q->whereDate('purchase_date', '>=', $startDate);
            });
        } elseif ($endDate) {
            $query->whereHas('purchase', function($q) use ($endDate) {
                $q->whereDate('purchase_date', '<=', $endDate);
            });
        }

        // Search by purchase id / invoice / product name / style number / SKU
        if ($request->filled('search')) {
            $search = trim($request->search);
            $cleanSearch = preg_replace('/[:\s\-]+/', '%', $search);
            $query->where(function($q) use ($search, $cleanSearch) {
                $q->whereHas('purchase', function($pq) use ($search) {
                    $pq->where('id', 'LIKE', "%$search%")
                      ->orWhereHas('bill', function($bq) use ($search) {
                          $bq->where('bill_number', 'LIKE', "%$search%");
                      });
                })->orWhereHas('product', function($prq) use ($search, $cleanSearch) {
                    $prq->where('style_number', 'LIKE', "%$search%")
                        ->orWhere('style_number', 'LIKE', "%$cleanSearch%")
                        ->orWhere('name', 'LIKE', "%$search%")
                        ->orWhere('sku', 'LIKE', "%$search%");
                })->orWhereHas('variation', function($vq) use ($search, $cleanSearch) {
                    $vq->where('sku', 'LIKE', "%$search%")
                       ->orWhere('sku', 'LIKE', "%$cleanSearch%")
                       ->orWhere('name', 'LIKE', "%$search%");
                });
            });
        }

        // Filters from dropdowns
        if ($request->filled('supplier_id')) {
            $query->whereHas('purchase', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }
        if ($request->filled('status')) {
            $query->whereHas('purchase', function($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        // Location Filters
        $restrictedBranchId = $this->getRestrictedBranchId();
        $selectedBranchId = $restrictedBranchId ?: $request->branch_id;

        if ($selectedBranchId) {
            $query->whereHas('purchase', function($q) use ($selectedBranchId) {
                $q->where('ship_location_type', 'branch')->where('location_id', $selectedBranchId);
            });
        }

        if (!$restrictedBranchId && $request->filled('warehouse_id')) {
            $query->whereHas('purchase', function($q) use ($request) {
                $q->where('ship_location_type', 'warehouse')->where('location_id', $request->warehouse_id);
            });
        }
        
        // Filter by Product/Style/Category/Brand/Season/Gender
        if ($request->filled('product_id')) $query->where('product_id', $request->product_id);

        if ($request->filled('style_number')) {
            $styleSearch = trim($request->style_number);
            $cleanStyle = preg_replace('/[:\s\-]+/', '%', $styleSearch);
            $query->where(function($q) use ($styleSearch, $cleanStyle) {
                $q->whereHas('product', function($prq) use ($styleSearch, $cleanStyle) {
                    $prq->where('style_number', 'LIKE', "%$styleSearch%")
                        ->orWhere('style_number', 'LIKE', "%$cleanStyle%")
                        ->orWhere('sku', 'LIKE', "%$styleSearch%");
                })->orWhereHas('variation', function($vq) use ($styleSearch, $cleanStyle) {
                    $vq->where('sku', 'LIKE', "%$styleSearch%")
                       ->orWhere('sku', 'LIKE', "%$cleanStyle%");
                });
            });
        }

        if ($request->filled('category_id') || $request->filled('brand_id') || 
            $request->filled('season_id') || $request->filled('gender_id')) {
            
            $query->whereHas('product', function($q) use ($request) {
                if ($request->filled('category_id')) $q->where('category_id', $request->category_id);
                if ($request->filled('brand_id')) $q->where('brand_id', $request->brand_id);
                if ($request->filled('season_id')) $q->where('season_id', $request->season_id);
                if ($request->filled('gender_id')) $q->where('gender_id', $request->gender_id);
            });
        }

        return $query;
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('manage purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branches = Branch::where('id', $restrictedBranchId)->get();
            $warehouses = collect();
        } else {
            $branches = Branch::where('status', 'active')->get();
            $warehouses = \App\Models\Warehouse::all();
        }
        $products = \App\Models\Product::all();
        $suppliers = \App\Models\Supplier::all();
        $bankAccounts = FinancialAccount::orderBy('type')->orderBy('provider_name')->get();
        return view('erp.purchases.create', compact('branches', 'warehouses', 'products', 'suppliers', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'supplier_id' => 'nullable|integer',
            'ship_location_type' => 'required|in:branch,warehouse',
            'location_id' => 'required|integer',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable', // allow string/int
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'paid_amount' => 'nullable|numeric|min:0',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:flat,percent',
            'payment_method' => $request->paid_amount > 0 ? 'required|string' : 'nullable|string',
            'account_id' => $request->paid_amount > 0 ? 'required|integer' : 'nullable|integer',
        ]);
    
        DB::beginTransaction();
    
        try {
            // Calculate subtotal
            $itemsList = array_values($request->items ?? []);
            $subTotal = 0;
            foreach ($itemsList as $item) {
                $subTotal += $item['quantity'] * $item['unit_price'];
            }
    
            // Calculate discount
            $discountValue = (float) $request->input('discount_value', 0);
            $discountType = $request->input('discount_type', 'flat');
            $discountAmount = 0;
            if ($discountType === 'percent') {
                $discountAmount = ($subTotal * $discountValue) / 100;
            } else {
                $discountAmount = $discountValue;
            }
    
            $totalAmount = max(0, $subTotal - $discountAmount);
    
            $restrictedBranchId = $this->getRestrictedBranchId();
            $locationType = $restrictedBranchId ? 'branch' : $request->ship_location_type;
            $locationId = $restrictedBranchId ?: $request->location_id;

            // Create Purchase (supplier is optional)
            $purchase = Purchase::create([
                'supplier_id'         => $request->supplier_id ?? null,
                'ship_location_type'  => $locationType,
                'location_id'         => $locationId,
                'purchase_date'       => $request->purchase_date,
                'status'              => 'pending',
                'created_by'          => auth()->id(),
                'notes'               => $request->notes,
            ]);
    
            // Add Purchase Items with proportional item-wise discount distribution
            $itemsToInsert = [];
            $totalAllocatedDiscount = 0;
            $itemCount = count($itemsList);

            foreach ($itemsList as $index => $item) {
                $lineTotal = (float)($item['quantity'] * $item['unit_price']);
                $itemDiscount = 0;

                if ($discountAmount > 0 && $subTotal > 0) {
                    if ($index === $itemCount - 1) {
                        $itemDiscount = max(0, round($discountAmount - $totalAllocatedDiscount, 2));
                    } else {
                        $itemDiscount = round(($lineTotal / $subTotal) * $discountAmount, 2);
                        $totalAllocatedDiscount += $itemDiscount;
                    }
                }

                $varId = !empty($item['variation_id']) ? $item['variation_id'] : null;

                $itemsToInsert[] = [
                    'purchase_id'  => $purchase->id,
                    'product_id'   => $item['product_id'],
                    'variation_id' => $varId,
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'discount'     => $itemDiscount,
                    'total_price'  => $lineTotal,
                    'description'  => $item['description'] ?? null,
                    'sort_order'   => $index,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
            PurchaseItem::insert($itemsToInsert);

            // NOTE: Stock is NOT increased on creation.
            // An authorized user must manually approve the purchase (set status to 'received')
            // via the Purchase show page before stock is updated.
    
            // Create Bill (only if supplier is provided)
            if ($request->supplier_id) {
                $paid_amount = $request->input('paid_amount', 0);
                $due_amount = max(0, $totalAmount - $paid_amount);
                $status = 'unpaid';
                if ($paid_amount >= $totalAmount) $status = 'paid';
                elseif ($paid_amount > 0) $status = 'partial';

                $bill = PurchaseBill::create([
                    'supplier_id'   => $request->supplier_id,
                    'purchase_id'   => $purchase->id,
                    'bill_date'     => now()->toDateString(),
                    'sub_total'      => $subTotal,
                    'discount_amount'=> $discountAmount,
                    'discount_type'  => $discountType,
                    'discount_value' => $discountValue,
                    'total_amount'  => $totalAmount,
                    'paid_amount'   => $paid_amount,
                    'due_amount'    => $due_amount,
                    'status'        => $status,
                    'created_by'    => auth()->id(),
                    'description'   => 'Auto-generated bill from purchase ID: ' . $purchase->id,
                ]);

                // Record Bill in Ledger (CREDIT the supplier)
                \App\Models\SupplierLedger::recordTransaction(
                    $request->supplier_id,
                    'credit',
                    $totalAmount,
                    'Purchase Bill: ' . ($bill->bill_number ?: 'P-'.$purchase->id),
                    $request->purchase_date,
                    $bill
                );

                // If payment made, record Payment and update Ledger
                if ($paid_amount > 0) {
                    $payment = \App\Models\SupplierPayment::create([
                        'supplier_id' => $request->supplier_id,
                        'purchase_bill_id' => $bill->id,
                        'payment_date' => $request->purchase_date,
                        'amount' => $paid_amount,
                        'payment_method' => $request->payment_method ?? 'cash',
                        'reference' => 'Initial payment at purchase',
                        'note' => $request->notes,
                        'created_by' => auth()->id(),
                    ]);

                    // Record Payment in Ledger (DEBIT the supplier)
                    \App\Models\SupplierLedger::recordTransaction(
                        $request->supplier_id,
                        'debit',
                        $paid_amount,
                        'Payment for Purchase Bill: ' . ($bill->bill_number ?: 'P-'.$purchase->id),
                        $request->purchase_date,
                        $payment
                    );
                }

                // =====================================================
                // AUTO JOURNAL ENTRY (Double-Entry Accounting)
                // =====================================================
                $financialAccount = $request->account_id
                    ? FinancialAccount::find($request->account_id)
                    : null;

                // The payment account must be linked to a Chart of Account
                $paymentChartAccountId = $financialAccount?->account_id ?? null;

                if ($paymentChartAccountId && $paid_amount > 0) {
                    // Ensure unique voucher number
                    $voucherNo = 'PUR-' . str_pad($purchase->id, 6, '0', STR_PAD_LEFT);
                    while (Journal::where('voucher_no', $voucherNo)->exists()) {
                        $voucherNo = 'PUR-' . str_pad($purchase->id, 6, '0', STR_PAD_LEFT) . '-' . rand(10, 99);
                    }

                    $journal = Journal::create([
                        'voucher_no'     => $voucherNo,
                        'entry_date'     => $request->purchase_date,
                        'type'           => 'Payment',
                        'description'    => 'Auto: Purchase #' . $purchase->id . ($request->notes ? ' - ' . $request->notes : ''),
                        'supplier_id'    => $request->supplier_id ?? null,
                        'branch_id'      => $locationId ?? null,
                        'voucher_amount' => $totalAmount,
                        'paid_amount'    => $paid_amount,
                        'reference'      => isset($bill) ? ($bill->bill_number ?? 'BILL-' . $bill->id) : 'PUR-' . $purchase->id,
                        'created_by'     => Auth::id(),
                        'updated_by'     => Auth::id(),
                    ]);

                    // Find a Purchases/Expense account to DEBIT
                    // Try by name first, then fall back to any Expense-type account
                    $purchaseChartAccount = ChartOfAccount::where('name', 'like', '%purchase%')
                        ->orWhere('name', 'like', '%stock%')
                        ->orWhere('name', 'like', '%inventory%')
                        ->first();

                    // If no purchase account found, use the payment account itself as a simple transfer
                    // and log the debit against a generic expense
                    if (!$purchaseChartAccount) {
                        // Use any expense-type account, or just use the payment COA as a self-balancing entry
                        $purchaseChartAccount = ChartOfAccount::whereHas('type', function($q) {
                            $q->where('name', 'like', '%expense%');
                        })->first();
                    }

                    if ($purchaseChartAccount) {
                        // DEBIT: Purchases/Expense account (goods/services received)
                        JournalEntry::create([
                            'journal_id'           => $journal->id,
                            'chart_of_account_id'  => $purchaseChartAccount->id,
                            'financial_account_id' => null,
                            'debit'                => $paid_amount,
                            'credit'               => 0,
                            'memo'                 => 'Purchase of goods - Purchase #' . $purchase->id,
                            'created_by'           => Auth::id(),
                            'updated_by'           => Auth::id(),
                        ]);

                        // CREDIT: Payment account (bank/mobile/cash)
                        JournalEntry::create([
                            'journal_id'           => $journal->id,
                            'chart_of_account_id'  => $paymentChartAccountId,
                            'financial_account_id' => $financialAccount->id,
                            'debit'                => 0,
                            'credit'               => $paid_amount,
                            'memo'                 => 'Payment via ' . ($financialAccount->provider_name ?? $request->payment_method),
                            'created_by'           => Auth::id(),
                            'updated_by'           => Auth::id(),
                        ]);
                    } else {
                        // Fallback: single-sided entry just recording the payment outflow
                        JournalEntry::create([
                            'journal_id'           => $journal->id,
                            'chart_of_account_id'  => $paymentChartAccountId,
                            'financial_account_id' => $financialAccount->id,
                            'debit'                => 0,
                            'credit'               => $paid_amount,
                            'memo'                 => 'Purchase payment via ' . ($financialAccount->provider_name ?? $request->payment_method),
                            'created_by'           => Auth::id(),
                            'updated_by'           => Auth::id(),
                        ]);
                    }

                    // CREDIT: Accounts Payable for any due/remaining amount
                    if (isset($due_amount) && $due_amount > 0) {
                        $payableChartAccount = ChartOfAccount::where('name', 'like', '%payable%')
                            ->orWhere('name', 'like', '%creditor%')
                            ->first();
                        if ($payableChartAccount) {
                            JournalEntry::create([
                                'journal_id'           => $journal->id,
                                'chart_of_account_id'  => $payableChartAccount->id,
                                'financial_account_id' => null,
                                'debit'                => 0,
                                'credit'               => $due_amount,
                                'memo'                 => 'Due to supplier - Purchase #' . $purchase->id,
                                'created_by'           => Auth::id(),
                                'updated_by'           => Auth::id(),
                            ]);
                        }
                    }
                }
                // =====================================================
            }

            DB::commit();

            return redirect()->route('purchase.list')->with('success', 'Purchase created successfully.');
    
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
        if (!auth()->user()->hasPermissionTo('view purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $purchase = Purchase::with(['bill', 'supplier', 'items.product', 'items.variation'])->findOrFail($id);

        // Safely resolve location name; the related branch/warehouse might not exist anymore
        if ($purchase->ship_location_type === 'branch') {
            $branch = Branch::find($purchase->location_id);
            $purchase->location_name = $branch?->name ?? 'Unknown Branch';
        } elseif ($purchase->ship_location_type === 'warehouse') {
            $warehouse = Warehouse::find($purchase->location_id);
            $purchase->location_name = $warehouse?->name ?? 'Unknown Warehouse';
        } else {
            $purchase->location_name = 'Unknown Location';
        }

        return view('erp.purchases.show', compact('purchase'));
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermissionTo('edit purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $purchase = Purchase::with('items')->findOrFail($id);
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branches = Branch::where('id', $restrictedBranchId)->get();
            $warehouses = collect();
        } else {
            $branches = Branch::where('status', 'active')->get();
            $warehouses = Warehouse::all();
        }
        $suppliers = \App\Models\Supplier::all();
        return view('erp.purchases.edit', compact('purchase', 'branches', 'warehouses', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('edit purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'supplier_id' => 'nullable|integer',
            'ship_location_type' => 'required|in:branch,warehouse',
            'location_id' => 'required|integer',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:flat,percent',
            'total_amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $purchase = Purchase::findOrFail($id);
            $previousStatus = $purchase->status;

            $purchase->update([
                'supplier_id'         => $request->supplier_id ?? null,
                'ship_location_type'  => $request->ship_location_type,
                'location_id'         => $request->location_id,
                'purchase_date'       => $request->purchase_date,
                'status'              => $request->status,
                'notes'               => $request->notes,
            ]);

            // Only add stock the first time we move into "received" status
            if ($request->status === 'received' && $previousStatus !== 'received') {
                $this->increaseStock($purchase);
            }

            // Calculate subtotal
            $itemsList = array_values($request->items ?? []);
            $subTotal = 0;
            foreach ($itemsList as $item) {
                $subTotal += $item['quantity'] * $item['unit_price'];
            }
    
            // Calculate discount
            $discountValue = (float) $request->input('discount_value', 0);
            $discountType = $request->input('discount_type', 'flat');
            $discountAmount = 0;
            if ($discountType === 'percent') {
                $discountAmount = ($subTotal * $discountValue) / 100;
            } else {
                $discountAmount = $discountValue;
            }
    
            $totalAmount = max(0, $subTotal - $discountAmount);

            // Remove old items
            $purchase->items()->delete();

            // Add new items with proportional item-wise discount distribution
            $totalAllocatedDiscount = 0;
            $itemCount = count($itemsList);

            foreach ($itemsList as $index => $item) {
                $lineTotal = (float)($item['quantity'] * $item['unit_price']);
                $itemDiscount = 0;

                if ($discountAmount > 0 && $subTotal > 0) {
                    if ($index === $itemCount - 1) {
                        $itemDiscount = max(0, round($discountAmount - $totalAllocatedDiscount, 2));
                    } else {
                        $itemDiscount = round(($lineTotal / $subTotal) * $discountAmount, 2);
                        $totalAllocatedDiscount += $itemDiscount;
                    }
                }

                $varId = !empty($item['variation_id']) ? $item['variation_id'] : null;

                $purchase->items()->create([
                    'product_id'   => $item['product_id'],
                    'variation_id' => $varId,
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'discount'     => $itemDiscount,
                    'total_price'  => $lineTotal,
                    'description'  => $item['description'] ?? null,
                    'sort_order'   => $index,
                ]);
            }

            // Update bill if exists
            if ($purchase->bill) {
                $purchase->bill->update([
                    'sub_total'       => $subTotal,
                    'discount_amount' => $discountAmount,
                    'discount_type'   => $discountType,
                    'discount_value'  => $discountValue,
                    'total_amount'    => $totalAmount,
                    'due_amount'      => max(0, $totalAmount - $purchase->bill->paid_amount),
                ]);
            }

            DB::commit();
            return redirect()->route('purchase.list')->with('success', 'Purchase updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('edit purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'status' => 'required|string',
        ]);
        $purchase = Purchase::with('items')->findOrFail($id);
        $previousStatus = $purchase->status;
        $purchase->status = $request->status;
        $purchase->save();

        // Only add stock the first time we move into "received" status
        if ($request->status === 'received' && $previousStatus !== 'received') {
            $this->increaseStock($purchase);
        }
        return redirect()->back()->with('success', 'Purchase status updated successfully.');
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermissionTo('delete purchases')) {
            abort(403, 'Unauthorized action.');
        }
        DB::beginTransaction();
        try {
            $purchase = Purchase::with(['items', 'bill'])->findOrFail($id);

            // 1. Revert stock quantity if the purchase was received
            if ($purchase->status === 'received') {
                $this->decreaseStock($purchase);
            }

            // 2. Delete related bills, payments, ledgers, journals
            if ($purchase->bill) {
                $billId = $purchase->bill->id;

                // Delete ledger entries of the bill
                \App\Models\SupplierLedger::where('transactionable_type', \App\Models\PurchaseBill::class)
                    ->where('transactionable_id', $billId)
                    ->delete();

                // Find associated payments
                $payments = \App\Models\SupplierPayment::where('purchase_bill_id', $billId)->get();
                foreach ($payments as $payment) {
                    // Delete ledger entries of the payment
                    \App\Models\SupplierLedger::where('transactionable_type', \App\Models\SupplierPayment::class)
                        ->where('transactionable_id', $payment->id)
                        ->delete();

                    // Delete associated double-entry journal records
                    $journalReference = $purchase->bill->bill_number ?? 'BILL-' . $billId;
                    $journals = Journal::whereIn('reference', [$journalReference, 'PUR-' . $purchase->id])->get();
                    foreach ($journals as $journal) {
                        $journal->entries()->delete();
                        $journal->delete();
                    }

                    // Delete payment itself
                    $payment->delete();
                }

                // Delete the bill
                $purchase->bill->delete();
            }

            // 3. Delete related items and the purchase record
            $purchase->items()->delete();
            $purchase->delete();

            // 4. Recalibrate supplier balance
            if ($purchase->supplier_id) {
                $supplier = \App\Models\Supplier::find($purchase->supplier_id);
                if ($supplier) {
                    $ledgerBalance = $supplier->ledgerEntries()->latest('id')->first()?->balance ?? 0;
                    \App\Models\Balance::updateOrCreate(
                        ['source_type' => 'supplier', 'source_id' => $supplier->id],
                        ['balance' => $ledgerBalance, 'description' => 'Auto-synced after purchase deletion']
                    );
                }
            }

            DB::commit();
            return redirect()->route('purchase.list')->with('success', 'Purchase and related data deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Something went wrong.', 'details' => $e->getMessage()]);
        }
    }

    public function searchPurchase(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view purchases')) {
            abort(403, 'Unauthorized action.');
        }
        $search = $request->q;
        $query = Purchase::query();
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('id', 'like', "%$search%");
            });
        }
        $purchases = $query->limit(20)->get()->filter();
        $results = $purchases->filter(function($purchase) {
            return $purchase !== null;
        })->map(function($purchase) {
            $text = "#{$purchase->id} - Assign ({$purchase->purchase_date})";
            return [
                'id' => $purchase->id,
                'text' => $text
            ];
        });
        return response()->json(['results' => $results]);
    }

    public function getItemByPurchase($id)
    {
        if (!auth()->user()->hasPermissionTo('view purchases')) {
            abort(403, 'Unauthorized action.');
        }
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

    private function increaseStock(Purchase $purchase)
    {
        foreach ($purchase->items as $item) {
            if ($item->variation_id) {
                // Update detailed variation stock
                if ($purchase->ship_location_type === 'branch') {
                    $stock = \App\Models\ProductVariationStock::firstOrNew([
                        'variation_id' => $item->variation_id,
                        'branch_id' => $purchase->location_id,
                    ]);
                    $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                    $stock->updated_by = auth()->id() ?? 1;
                    $stock->last_updated_at = now();
                    $stock->save();

                    // Also mirror into branch product stock so POS can see this product
                    $branchStock = \App\Models\BranchProductStock::firstOrNew([
                        'branch_id'  => $purchase->location_id,
                        'product_id' => $item->product_id,
                    ]);
                    $branchStock->quantity = ($branchStock->quantity ?? 0) + $item->quantity;
                    $branchStock->updated_by = auth()->id() ?? 1;
                    $branchStock->last_updated_at = now();
                    $branchStock->save();
                } elseif ($purchase->ship_location_type === 'warehouse') {
                    $stock = \App\Models\ProductVariationStock::firstOrNew([
                        'variation_id' => $item->variation_id,
                        'warehouse_id' => $purchase->location_id,
                    ]);
                    $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                    $stock->updated_by = auth()->id() ?? 1;
                    $stock->last_updated_at = now();
                    $stock->save();

                    // Mirror into warehouse product stock so non-variation flows can see it
                    $warehouseStock = \App\Models\WarehouseProductStock::firstOrNew([
                        'warehouse_id' => $purchase->location_id,
                        'product_id'   => $item->product_id,
                    ]);
                    $warehouseStock->quantity = ($warehouseStock->quantity ?? 0) + $item->quantity;
                    $warehouseStock->updated_by = auth()->id() ?? 1;
                    $warehouseStock->last_updated_at = now();
                    $warehouseStock->save();
                }
            } else {
                // Simple (non-variation) products: existing behavior
                if ($purchase->ship_location_type === 'branch') {
                    $stock = \App\Models\BranchProductStock::firstOrNew([
                        'branch_id' => $purchase->location_id,
                        'product_id' => $item->product_id,
                    ]);
                    $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                    $stock->updated_by = auth()->id() ?? 1;
                    $stock->last_updated_at = now();
                    $stock->save();
                } elseif ($purchase->ship_location_type === 'warehouse') {
                    $stock = \App\Models\WarehouseProductStock::firstOrNew([
                        'warehouse_id' => $purchase->location_id,
                        'product_id' => $item->product_id,
                    ]);
                    $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                    $stock->updated_by = auth()->id() ?? 1;
                    $stock->last_updated_at = now();
                    $stock->save();
                }
            }

            \App\Services\CacheService::clearProductCaches($item->product_id);
        }
    }

    private function decreaseStock(Purchase $purchase)
    {
        foreach ($purchase->items as $item) {
            if ($item->variation_id) {
                if ($purchase->ship_location_type === 'branch') {
                    $stock = \App\Models\ProductVariationStock::where([
                        'variation_id' => $item->variation_id,
                        'branch_id' => $purchase->location_id,
                    ])->first();
                    if ($stock) {
                        $stock->quantity = max(0, $stock->quantity - $item->quantity);
                        $stock->save();
                    }

                    $branchStock = \App\Models\BranchProductStock::where([
                        'branch_id'  => $purchase->location_id,
                        'product_id' => $item->product_id,
                    ])->first();
                    if ($branchStock) {
                        $branchStock->quantity = max(0, $branchStock->quantity - $item->quantity);
                        $branchStock->save();
                    }
                } elseif ($purchase->ship_location_type === 'warehouse') {
                    $stock = \App\Models\ProductVariationStock::where([
                        'variation_id' => $item->variation_id,
                        'warehouse_id' => $purchase->location_id,
                    ])->first();
                    if ($stock) {
                        $stock->quantity = max(0, $stock->quantity - $item->quantity);
                        $stock->save();
                    }

                    $warehouseStock = \App\Models\WarehouseProductStock::where([
                        'warehouse_id' => $purchase->location_id,
                        'product_id'   => $item->product_id,
                    ])->first();
                    if ($warehouseStock) {
                        $warehouseStock->quantity = max(0, $warehouseStock->quantity - $item->quantity);
                        $warehouseStock->save();
                    }
                }
            } else {
                if ($purchase->ship_location_type === 'branch') {
                    $stock = \App\Models\BranchProductStock::where([
                        'branch_id' => $purchase->location_id,
                        'product_id' => $item->product_id,
                    ])->first();
                    if ($stock) {
                        $stock->quantity = max(0, $stock->quantity - $item->quantity);
                        $stock->save();
                    }
                } elseif ($purchase->ship_location_type === 'warehouse') {
                    $stock = \App\Models\WarehouseProductStock::where([
                        'warehouse_id' => $purchase->location_id,
                        'product_id' => $item->product_id,
                    ])->first();
                    if ($stock) {
                        $stock->quantity = max(0, $stock->quantity - $item->quantity);
                        $stock->save();
                    }
                }
            }

            \App\Services\CacheService::clearProductCaches($item->product_id);
        }
    }
}
