<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SaleReturn;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Pos;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use App\Models\ChartOfAccountType;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountParent;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view returns')) {
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

        $query = \App\Models\SaleReturnItem::whereHas('saleReturn')->with([
            'saleReturn.customer',
            'saleReturn.posSale',
            'saleReturn.branch',
            'product.category',
            'product.brand',
            'product.season',
            'product.gender',
            'variation.attributeValues.attribute',
        ]);

        $query = $this->applyFilters($query, $request, $startDate, $endDate);

        $totalQty = $query->sum('returned_qty');
        $totalAmount = $query->sum('total_price');

        $items = $query->latest()->paginate(20)->appends($request->all());

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branches = Branch::where('id', $restrictedBranchId)->get();
        } else {
            $branches = Branch::where('status', 'active')->get();
        }
        $customersQuery = Customer::query();
        if ($restrictedBranchId) {
            $customersQuery->where('branch_id', $restrictedBranchId);
        }
        $customers = $customersQuery->orderBy('name')->get();
        $products = \App\Models\Product::orderBy('name')->get();
        $categories = \App\Models\ProductServiceCategory::whereNull('parent_id')->orderBy('name')->get();
        $brands = \App\Models\Brand::orderBy('name')->get();
        $seasons = \App\Models\Season::orderBy('name')->get();
        $genders = \App\Models\Gender::orderBy('name')->get();

        if ($request->ajax()) {
            return view('erp.saleReturn.partials.table', compact('items'))->render();
        }

        return view('erp.saleReturn.salereturnlist', compact(
            'items',
            'branches',
            'customers',
            'products',
            'categories',
            'brands',
            'seasons',
            'genders',
            'reportType',
            'startDate',
            'endDate',
            'totalQty',
            'totalAmount'
        ));
    }

    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view returns')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $startDate = \Carbon\Carbon::createFromDate($request->get('year', date('Y')), $request->get('month', date('m')), 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $startDate = \Carbon\Carbon::createFromDate($request->get('year', date('Y')), 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = \App\Models\SaleReturnItem::whereHas("saleReturn")->with([
            'saleReturn.customer',
            'saleReturn.posSale',
            'saleReturn.branch',
            'product.category',
            'product.brand',
            'product.season',
            'product.gender',
            'variation.attributeValues.attribute'
        ]);

        $query = $this->applyFilters($query, $request, $startDate, $endDate);
        $items = $query->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Serial No',
            'Date',
            'R-Inv. No.',
            'S-Inv. No.',
            'Customer',
            'Mobile',
            'Branch',
            'Category',
            'Brand',
            'Season',
            'Gender',
            'Product Name',
            'Style Number',
            'Color',
            'Size',
            'Qty',
            'Total Amount'
        ];

        $sheet->fromArray([$headers], NULL, 'A1');
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($items as $index => $item) {
            $return = $item->saleReturn;
            if (!$return)
                continue;

            $product = $item->product;
            $variation = $item->variation;

            $color = '-';
            $size = '-';
            if ($variation && $variation->attributeValues) {
                foreach ($variation->attributeValues as $val) {
                    $attrName = strtolower($val->attribute->name ?? '');
                    if (str_contains($attrName, 'color'))
                        $color = $val->value;
                    elseif (str_contains($attrName, 'size'))
                        $size = $val->value;
                }
            }

            $data = [
                $index + 1,
                $return?->return_date ? \Carbon\Carbon::parse($return->return_date)->format('d/m/Y') : '-',
                '#SR-' . str_pad($return?->id ?? 0, 5, '0', STR_PAD_LEFT),
                $return?->posSale?->sale_number ?? '-',
                $return?->customer?->name ?? 'Walk-in',
                $return?->customer?->phone ?? '-',
                $return?->branch?->name ?? '-',
                $product?->category?->name ?? '-',
                $product?->brand?->name ?? '-',
                $product?->season?->name ?? '-',
                $product?->gender?->name ?? '-',
                $product?->name ?? '-',
                $product?->style_number ?? '-',
                $color,
                $size,
                $item->returned_qty,
                $item->total_price
            ];
            $sheet->fromArray([$data], NULL, 'A' . $rowNum);
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'sale_return_report_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view returns')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'yearly');
        if ($reportType == 'monthly') {
            $startDate = \Carbon\Carbon::createFromDate($request->get('year', date('Y')), $request->get('month', date('m')), 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $startDate = \Carbon\Carbon::createFromDate($request->get('year', date('Y')), 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : \Carbon\Carbon::today()->startOfDay();
            $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : \Carbon\Carbon::today()->endOfDay();
        }

        $query = \App\Models\SaleReturnItem::whereHas("saleReturn")->with([
            'saleReturn.customer',
            'saleReturn.posSale',
            'saleReturn.branch',
            'product.category',
            'product.brand',
            'product.season',
            'product.gender',
            'variation.attributeValues.attribute'
        ]);

        $query = $this->applyFilters($query, $request, $startDate, $endDate);
        $items = $query->latest()->get();

        $pdf = Pdf::loadView('erp.saleReturn.export-pdf', compact('items', 'reportType', 'startDate', 'endDate'));
        $pdf->setPaper('A4', 'landscape');

        $filename = 'sale_return_report_' . date('Ymd_His') . '.pdf';
        if ($request->input('action') === 'print') {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }

    private function applyFilters($query, Request $request, $startDate = null, $endDate = null)
    {
        // Date Filtering
        if ($startDate && $endDate) {
            $query->whereHas('saleReturn', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('return_date', [$startDate, $endDate]);
            });
        } elseif ($startDate) {
            $query->whereHas('saleReturn', function ($q) use ($startDate) {
                $q->whereDate('return_date', '>=', $startDate);
            });
        } elseif ($endDate) {
            $query->whereHas('saleReturn', function ($q) use ($endDate) {
                $q->whereDate('return_date', '<=', $endDate);
            });
        }

        // Search by sale number / invoice / customer / product / salesperson
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('saleReturn', function ($sq) use ($search) {
                    $sq->whereHas('posSale', function ($psq) use ($search) {
                        $psq->where('sale_number', 'LIKE', "%{$search}%");
                    })
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('phone', 'LIKE', "%{$search}%");
                        });
                })
                    ->orWhereHas('product', function ($prq) use ($search) {
                        $prq->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('style_number', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Filters from dropdowns
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $query->whereHas('saleReturn', function ($q) use ($restrictedBranchId) {
                $q->where('return_to_id', $restrictedBranchId)->where('return_to_type', 'branch');
            });
        } elseif ($request->filled('branch_id')) {
            $query->whereHas('saleReturn', function ($q) use ($request) {
                $q->where('return_to_id', $request->branch_id)->where('return_to_type', 'branch');
            });
        }
        if ($request->filled('customer_id')) {
            $query->whereHas('saleReturn', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }
        if ($request->filled('status')) {
            $query->whereHas('saleReturn', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        // Product Filters
        if ($request->filled('product_id'))
            $query->where('product_id', $request->product_id);

        if (
            $request->filled('style_number') || $request->filled('category_id') ||
            $request->filled('brand_id') || $request->filled('season_id') || $request->filled('gender_id')
        ) {

            $query->whereHas('product', function ($q) use ($request) {
                if ($request->filled('style_number'))
                    $q->where('style_number', 'like', '%' . $request->style_number . '%');
                if ($request->filled('category_id'))
                    $q->where('category_id', $request->category_id);
                if ($request->filled('brand_id'))
                    $q->where('brand_id', $request->brand_id);
                if ($request->filled('season_id'))
                    $q->where('season_id', $request->season_id);
                if ($request->filled('gender_id'))
                    $q->where('gender_id', $request->gender_id);
            });
        }

        return $query;
    }

    public function create(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage returns')) {
            abort(403, 'Unauthorized action.');
        }
        $restrictedBranchId = $this->getRestrictedBranchId();
        $customersQuery = Customer::query();
        if ($restrictedBranchId) {
            $customersQuery->where('branch_id', $restrictedBranchId);
        }
        $customers = $customersQuery->orderBy('name')->take(100)->get(); // Limit initial load

        // We only load what is absolutely needed for the view to avoid memory issues
        $branches = Branch::where('status', 'active')->get();
        $warehouses = Warehouse::all();

        // PosSales, Invoices, and Products are fetched via AJAX when searching or not needed for initial load
        $posSales = collect();
        $invoices = collect();
        $products = collect();

        // Handle pre-selected POS sale from query parameter
        $selectedPosSale = null;
        if ($request->has('pos_sale_id')) {
            $selectedPosSale = Pos::with(['customer', 'items.product', 'items.variation', 'branch', 'invoice'])
                ->find($request->pos_sale_id);
        }

        return view('erp.saleReturn.create', compact('customers', 'posSales', 'invoices', 'products', 'branches', 'warehouses', 'selectedPosSale'));
    }

    public function searchInvoice(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view returns')) {
            abort(403, 'Unauthorized action.');
        }
        $invoiceNo = $request->invoice_no;
        if (!$invoiceNo) {
            return response()->json(['success' => false, 'message' => 'Invoice number is required.']);
        }

        $sale = Pos::with(['customer', 'items.product', 'items.variation.attributeValues.attribute', 'branch', 'invoice'])
            ->where('sale_number', $invoiceNo)
            ->first();

        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'customer_id' => $sale->customer_id,
                'customer_name' => $sale->customer->name ?? 'Walk-in',
                'customer_phone' => $sale->customer->phone ?? '-',
                'branch_id' => $sale->branch_id,
                'items' => $sale->items->map(function ($item) {
                    $color = '-';
                    $size = '-';
                    if ($item->variation && $item->variation->attributeValues) {
                        foreach ($item->variation->attributeValues as $val) {
                            $attrName = strtolower($val->attribute->name ?? '');
                            if (str_contains($attrName, 'color'))
                                $color = $val->value;
                            elseif (str_contains($attrName, 'size'))
                                $size = $val->value;
                        }
                    }

                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'variation_id' => $item->variation_id,
                        'variation_name' => $item->variation->name ?? 'Standard',
                        'quantity' => $item->quantity,
                        'already_returned' => \App\Models\SaleReturnItem::where('sale_item_id', $item->id)
                            ->whereHas('saleReturn', function ($q) {
                                $q->where('status', '!=', 'rejected'); })
                            ->sum('returned_qty'),
                        'unit_price' => $item->unit_price,
                        'net_unit_price' => $item->quantity > 0 ? round($item->total_price / $item->quantity, 2) : $item->unit_price,
                        'color' => $color,
                        'size' => $size,
                        'style_number' => $item->product->style_number ?? '-',
                    ];
                })
            ]
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage returns')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'pos_sale_id' => 'nullable|exists:pos,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'return_date' => 'required|date',
            'refund_type' => 'required|in:none,cash,bank,credit',
            'return_to_type' => 'required|in:branch,warehouse,employee',
            'return_to_id' => 'required|integer',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.returned_qty' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Validate return quantities against original sale
        foreach ($request->items as $item) {
            if (isset($item['sale_item_id'])) {
                $saleItem = \App\Models\PosItem::find($item['sale_item_id']);
                if ($saleItem) {
                    $alreadyReturned = \App\Models\SaleReturnItem::where('sale_item_id', $saleItem->id)
                        ->whereHas('saleReturn', function ($q) {
                            $q->where('status', '!=', 'rejected'); })
                        ->sum('returned_qty');

                    if (($alreadyReturned + $item['returned_qty']) > $saleItem->quantity) {
                        return back()->withInput()->with('error', "Invalid quantity for {$saleItem->product->name}. Sold: {$saleItem->quantity}, Returned so far: {$alreadyReturned}, Attempting to return: {$item['returned_qty']}");
                    }
                }
            }
        }

        $data = $request->except(['items', 'status']);
        $data['status'] = 'processed';

        DB::beginTransaction();
        try {
            $saleReturn = SaleReturn::create($data);

            foreach ($request->items as $item) {
                $returnedQty = $item['returned_qty'] ?? 0;
                if ($returnedQty <= 0)
                    continue;

                // Use net_unit_price to account for original item discount
                $netUnitPrice = $item['net_unit_price'] ?? $item['unit_price'];
                \App\Models\SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'variation_id' => ($item['variation_id'] === 'null' || !$item['variation_id']) ? null : $item['variation_id'],
                    'returned_qty' => $returnedQty,
                    'unit_price' => $item['unit_price'],
                    'total_price' => $returnedQty * $netUnitPrice,
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            // Load items relationship so processReturn gets them correctly
            $saleReturn->load('items');

            // Process return immediately (adjust stock, invoice, accounting journals)
            $this->processReturn($saleReturn);

            DB::commit();
            return redirect()->route('saleReturn.list')->with('success', 'Sale return created and processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create and process sale return: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        if (!auth()->user()->hasPermissionTo('view returns')) {
            abort(403, 'Unauthorized action.');
        }
        $saleReturn = SaleReturn::with([
            'customer',
            'posSale',
            'items.product',
            'items.variation',
            'employee.user',
            'branch',
            'warehouse'
        ])->findOrFail($id);
        return view('erp.saleReturn.show', compact('saleReturn'));
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermissionTo('manage returns')) {
            abort(403, 'Unauthorized action.');
        }
        $saleReturn = SaleReturn::with([
            'items.product',
            'items.variation.attributeValues.attribute',
            'employee.user',
            'customer',
            'posSale',
            'branch',
            'warehouse'
        ])->findOrFail($id);

        $restrictedBranchId = $this->getRestrictedBranchId();
        $customersQuery = Customer::query();
        if ($restrictedBranchId) {
            $customersQuery->where('branch_id', $restrictedBranchId);
        }
        $customers = $customersQuery->orderBy('name')->take(100)->get();

        $branches = Branch::where('status', 'active')->get();
        $warehouses = Warehouse::all();

        $posSales = collect();
        $invoices = collect();
        $products = \App\Models\Product::orderBy('name')->get();

        return view('erp.saleReturn.edit', compact('saleReturn', 'customers', 'posSales', 'invoices', 'products', 'branches', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('manage returns')) {
            abort(403, 'Unauthorized action.');
        }
        $saleReturn = \App\Models\SaleReturn::findOrFail($id);
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
        // Validate return quantities against original sale

        foreach ($request->items as $item) {
            if (isset($item['sale_item_id'])) {
                $saleItem = \App\Models\PosItem::find($item['sale_item_id']);
                if ($saleItem) {
                    $alreadyReturned = \App\Models\SaleReturnItem::where('sale_item_id', $saleItem->id)
                        ->where('sale_return_id', '!=', $saleReturn->id)
                        ->whereHas('saleReturn', function ($q) {
                            $q->where('status', '!=', 'rejected'); })
                        ->sum('returned_qty');

                    if (($alreadyReturned + $item['returned_qty']) > $saleItem->quantity) {
                        return back()->withInput()->with('error', "Invalid quantity for {$saleItem->product->name}. Sold: {$saleItem->quantity}, Returned so far: {$alreadyReturned}, Attempting to return: {$item['returned_qty']}");
                    }
                }
            }
        }

        $saleReturn->update($request->except(['items', 'status']));
        // Remove old items
        $saleReturn->items()->delete();
        // Add new items
        foreach ($request->items as $item) {
            // Use net_unit_price to account for original item discount
            $netUnitPrice = $item['net_unit_price'] ?? $item['unit_price'];
            \App\Models\SaleReturnItem::create([
                'sale_return_id' => $saleReturn->id,
                'sale_item_id' => $item['sale_item_id'] ?? null,
                'product_id' => $item['product_id'],
                'variation_id' => ($item['variation_id'] === 'null' || !$item['variation_id']) ? null : $item['variation_id'],
                'returned_qty' => $item['returned_qty'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['returned_qty'] * $netUnitPrice,
                'reason' => $item['reason'] ?? null,
            ]);
        }
        return redirect()->route('saleReturn.list')->with('success', 'Sale return updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('manage returns')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        DB::beginTransaction();
        try {
            $saleReturn = SaleReturn::with(['items'])->findOrFail($id);

            // Block deletion of return records created by exchanges
            if ($saleReturn->reason && str_starts_with($saleReturn->reason, 'Exchange ')) {
                $errorMsg = 'This return was created from an Exchange. Please delete the Exchange record instead to keep stock and accounting in sync.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return redirect()->route('saleReturn.list')->with('error', $errorMsg);
            }

            // Roll back stock if it was processed
            if ($saleReturn->status === 'processed') {
                foreach ($saleReturn->items as $item) {
                    $this->removeStockForReturnItem($saleReturn, $item);
                }

                // Restore Invoice amounts that were reduced during processing
                $totalReturnAmount = $saleReturn->items->sum('total_price');

                // Calculate VAT that was deducted during processing
                $totalReturnedVat = 0;
                $posSale = null;
                if ($saleReturn->pos_sale_id) {
                    $posSale = \App\Models\Pos::find($saleReturn->pos_sale_id);
                } elseif ($saleReturn->invoice_id) {
                    $posSale = \App\Models\Pos::where('invoice_id', $saleReturn->invoice_id)->first();
                }

                if ($posSale) {
                    $originalItems = \App\Models\PosItem::where('pos_sale_id', $posSale->id)->get();
                    $originalGrossTotal = $originalItems->sum(fn($i) => $i->quantity * $i->unit_price);

                    if ($originalGrossTotal > 0) {
                        foreach ($saleReturn->items as $returnItem) {
                            $originalItem = \App\Models\PosItem::find($returnItem->sale_item_id);
                            if ($originalItem) {
                                $itemGross = $originalItem->quantity * $originalItem->unit_price;
                                $itemProportion = $itemGross / $originalGrossTotal;
                                $qtyProportion = $returnItem->returned_qty / $originalItem->quantity;
                                $itemVat = round($itemProportion * $qtyProportion * ($posSale->vat_amount ?? 0), 2);
                                $totalReturnedVat += $itemVat;
                            }
                        }
                    }
                }

                $totalRestoration = $totalReturnAmount + $totalReturnedVat;

                $invoiceToRestore = null;
                if ($saleReturn->invoice_id) {
                    $invoiceToRestore = Invoice::lockForUpdate()->find($saleReturn->invoice_id);
                } elseif ($saleReturn->pos_sale_id) {
                    $pos = \App\Models\Pos::with('invoice')->find($saleReturn->pos_sale_id);
                    if ($pos && $pos->invoice_id) {
                        $invoiceToRestore = Invoice::lockForUpdate()->find($pos->invoice_id);
                    }
                }
                if ($invoiceToRestore && $totalRestoration > 0) {
                    $invoiceToRestore->total_amount += $totalRestoration;
                    if (in_array($saleReturn->refund_type, ['cash', 'bank'])) {
                        $invoiceToRestore->paid_amount += $totalRestoration;
                    }
                    $invoiceToRestore->due_amount = max(0, $invoiceToRestore->total_amount - $invoiceToRestore->paid_amount);
                    if ($invoiceToRestore->paid_amount >= $invoiceToRestore->total_amount) {
                        $invoiceToRestore->status = 'paid';
                        $invoiceToRestore->due_amount = 0;
                    } elseif ($invoiceToRestore->paid_amount > 0) {
                        $invoiceToRestore->status = 'partial';
                    } else {
                        $invoiceToRestore->status = 'unpaid';
                    }
                    $invoiceToRestore->save();
                }

                // Delete associated Journal entries and restore financial account balances
                $voucherNo = 'SRT-' . str_pad($saleReturn->id, 6, '0', STR_PAD_LEFT);
                $journal = Journal::where('voucher_no', $voucherNo)->with('entries')->first();
                if ($journal) {
                    foreach ($journal->entries as $entry) {
                        if ($entry->financial_account_id && $entry->credit > 0) {
                            $fa = FinancialAccount::find($entry->financial_account_id);
                            if ($fa) {
                                $fa->increment('balance', $entry->credit);
                            }
                        }
                    }
                    $journal->entries()->delete();
                    $journal->delete();
                }
            }

            $saleReturn->items()->delete();
            $saleReturn->delete();

            DB::commit();

            $successMsg = 'Sale return deleted successfully and stock rolled back.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $successMsg]);
            }
            return redirect()->route('saleReturn.list')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMsg = 'Failed to delete sale return: ' . $e->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }
            return redirect()->route('saleReturn.list')->with('error', $errorMsg);
        }
    }

    /**
     * Change the status of a sale return. If processed, add returned quantity to the selected stock.
     */
    public function updateReturnStatus(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('manage returns')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,processed',
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
                $this->processReturn($saleReturn);
            }

            DB::commit();

            $statusMessage = match ($newStatus) {
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
     * Process all side-effects of a finalized (processed) sale return.
     * Including stock adjustments, invoice updates, and journal entries.
     */
    private function processReturn(SaleReturn $saleReturn)
    {
        foreach ($saleReturn->items as $item) {
            $this->addStockForReturnItem($saleReturn, $item);
        }

        // =====================================================
        // UPDATE INVOICE DUE AMOUNT (deduct returned amount + proportional VAT + proportional discount)
        // =====================================================
        $totalReturnAmount = $saleReturn->items->sum('total_price');

        // Get original POS for VAT and discount calculation
        $posSale = null;
        if ($saleReturn->pos_sale_id) {
            $posSale = \App\Models\Pos::with('invoice')->find($saleReturn->pos_sale_id);
        } elseif ($saleReturn->invoice_id) {
            $posSale = \App\Models\Pos::where('invoice_id', $saleReturn->invoice_id)->with('invoice')->first();
        }

        // Calculate proportional VAT and discount per returned item
        $totalReturnedVat = 0;
        $totalReturnedDiscount = 0;

        if ($posSale) {
            // Calculate original invoice gross amount (sum of all items at unit price)
            $originalItems = \App\Models\PosItem::where('pos_sale_id', $posSale->id)->get();
            $originalGrossTotal = $originalItems->sum(fn($i) => $i->quantity * $i->unit_price);

            if ($originalGrossTotal > 0) {
                foreach ($saleReturn->items as $returnItem) {
                    $originalItem = \App\Models\PosItem::find($returnItem->sale_item_id);
                    if ($originalItem) {
                        // Calculate this item's gross amount (original full quantity)
                        $itemGross = $originalItem->quantity * $originalItem->unit_price;
                        // Calculate proportion of this item in original invoice
                        $itemProportion = $itemGross / $originalGrossTotal;
                        // Calculate proportion of returned quantity vs original quantity
                        $qtyProportion = $returnItem->returned_qty / $originalItem->quantity;
                        // Calculate proportional VAT for this returned item (accounting for partial quantity)
                        $itemVat = round($itemProportion * $qtyProportion * ($posSale->vat_amount ?? 0), 2);
                        // Calculate proportional discount for this returned item (accounting for partial quantity)
                        $itemDiscount = round($itemProportion * $qtyProportion * ($posSale->discount ?? 0), 2);

                        $totalReturnedVat += $itemVat;
                        $totalReturnedDiscount += $itemDiscount;
                    }
                }
            }
        }

        $totalDeduction = $totalReturnAmount + $totalReturnedVat;
        // Note: totalReturnedDiscount is already included in totalReturnAmount (net_unit_price), so don't add it again

        // Find the linked invoice via invoice_id or pos_sale_id
        $invoiceToUpdate = null;
        if ($saleReturn->invoice_id) {
            $invoiceToUpdate = Invoice::lockForUpdate()->find($saleReturn->invoice_id);
        } elseif ($posSale && $posSale->invoice_id) {
            $invoiceToUpdate = Invoice::lockForUpdate()->find($posSale->invoice_id);
        }

        if ($invoiceToUpdate && $totalDeduction > 0) {
            $invoiceToUpdate->total_amount = max(0, $invoiceToUpdate->total_amount - $totalDeduction);
            if (in_array($saleReturn->refund_type, ['cash', 'bank'])) {
                $invoiceToUpdate->paid_amount = max(0, $invoiceToUpdate->paid_amount - $totalDeduction);
            }
            $invoiceToUpdate->due_amount = max(0, $invoiceToUpdate->total_amount - $invoiceToUpdate->paid_amount);
            if ($invoiceToUpdate->paid_amount >= $invoiceToUpdate->total_amount) {
                $invoiceToUpdate->status = 'paid';
                $invoiceToUpdate->due_amount = 0;
            } elseif ($invoiceToUpdate->paid_amount > 0) {
                $invoiceToUpdate->status = 'partial';
            } else {
                $invoiceToUpdate->status = 'unpaid';
            }
            $invoiceToUpdate->save();
        }

        // =====================================================
        // AUTO JOURNAL ENTRY (Double-Entry Accounting)
        // =====================================================
        if ($totalReturnAmount > 0) {
            $salesReturnAccount = ChartOfAccount::where('name', 'Sales Returns')
                ->orWhere('name', 'Sales Return')
                ->first();
            if (!$salesReturnAccount) {
                $revenueType = \App\Models\ChartOfAccountType::where('name', 'Revenue')->first() ?? \App\Models\ChartOfAccountType::find(4);
                $revenueSubType = \App\Models\ChartOfAccountSubType::where('type_id', $revenueType->id)->first();
                if (!$revenueSubType) {
                    $revenueSubType = \App\Models\ChartOfAccountSubType::create(['name' => 'Sales Revenue', 'type_id' => $revenueType->id]);
                }
                $revenueParent = \App\Models\ChartOfAccountParent::where('type_id', $revenueType->id)->first();
                if (!$revenueParent) {
                    $revenueParent = \App\Models\ChartOfAccountParent::create([
                        'name' => 'Operating Revenue',
                        'type_id' => $revenueType->id,
                        'sub_type_id' => $revenueSubType->id,
                        'code' => '4000',
                        'created_by' => auth()->id()
                    ]);
                }

                $salesReturnAccount = ChartOfAccount::create([
                    'name' => 'Sales Returns',
                    'type_id' => $revenueType->id,
                    'sub_type_id' => $revenueSubType->id,
                    'parent_id' => $revenueParent->id,
                    'code' => '40002',
                    'status' => 'active',
                    'created_by' => auth()->id()
                ]);
            }

            $voucherNo = 'SRT-' . str_pad($saleReturn->id, 6, '0', STR_PAD_LEFT);
            while (Journal::where('voucher_no', $voucherNo)->exists()) {
                $voucherNo = 'SRT-' . str_pad($saleReturn->id, 6, '0', STR_PAD_LEFT) . '-' . rand(10, 99);
            }

            // Determine branch for journal & financial account lookup
            $journalBranchId = null;
            if ($saleReturn->return_to_type == 'branch' && $saleReturn->return_to_id) {
                $journalBranchId = $saleReturn->return_to_id;
            } elseif ($posSale && $posSale->branch_id) {
                $journalBranchId = $posSale->branch_id;
            } elseif ($saleReturn->branch_id) {
                $journalBranchId = $saleReturn->branch_id;
            }

            $journal = Journal::create([
                'voucher_no' => $voucherNo,
                'entry_date' => $saleReturn->return_date,
                'type' => 'Payment',
                'description' => 'Sale Return #' . $saleReturn->id . ($saleReturn->reason ? ' - ' . $saleReturn->reason : ''),
                'customer_id' => $saleReturn->customer_id,
                'branch_id' => $journalBranchId,
                'voucher_amount' => $totalDeduction,
                'paid_amount' => in_array($saleReturn->refund_type, ['cash', 'bank']) ? $totalDeduction : 0,
                'reference' => 'SR-' . $saleReturn->id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // DEBIT Sales Return account (Revenue decreases by item amount)
            JournalEntry::create([
                'journal_id' => $journal->id,
                'chart_of_account_id' => $salesReturnAccount->id,
                'debit' => $totalReturnAmount,
                'credit' => 0,
                'memo' => 'Sale Return processed (excl. VAT)',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // DEBIT VAT Payable (reverse collected VAT on returned items)
            if ($totalReturnedVat > 0) {
                $vatAccount = ChartOfAccount::where('name', 'like', '%VAT%')
                    ->orWhere('name', 'like', '%Tax Payable%')
                    ->first();
                if ($vatAccount) {
                    JournalEntry::create([
                        'journal_id' => $journal->id,
                        'chart_of_account_id' => $vatAccount->id,
                        'debit' => $totalReturnedVat,
                        'credit' => 0,
                        'memo' => 'VAT reversal on returned items',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            if (in_array($saleReturn->refund_type, ['cash', 'bank'])) {
                // CREDIT Cash/Bank (Asset decreases) — full refund including VAT
                $financialAccount = null;
                if ($saleReturn->account_id) {
                    $financialAccount = FinancialAccount::find($saleReturn->account_id);
                }

                if (!$financialAccount) {
                    $faQuery = FinancialAccount::where('type', $saleReturn->refund_type);
                    if ($journalBranchId) {
                        $faBranchQuery = clone $faQuery;
                        $financialAccount = $faBranchQuery->where('branch_id', $journalBranchId)->first();
                    }
                    if (!$financialAccount) {
                        $financialAccount = $faQuery->first();
                    }
                }

                $chartAccountId = $financialAccount?->account_id;
                if (!$chartAccountId) {
                    if ($saleReturn->refund_type === 'cash') {
                        $cashCoA = ChartOfAccount::where('name', 'like', '%Cash%')->first();
                        $chartAccountId = $cashCoA?->id;
                    } else {
                        $bankCoA = ChartOfAccount::where('name', 'like', '%Bank%')->first();
                        $chartAccountId = $bankCoA?->id;
                    }
                }

                if ($financialAccount) {
                    $financialAccount->decrement('balance', $totalDeduction);
                }

                if ($chartAccountId) {
                    JournalEntry::create([
                        'journal_id' => $journal->id,
                        'chart_of_account_id' => $chartAccountId,
                        'financial_account_id' => $financialAccount ? $financialAccount->id : null,
                        'debit' => 0,
                        'credit' => $totalDeduction,
                        'memo' => 'Refund via ' . ($financialAccount ? $financialAccount->provider_name : ucfirst($saleReturn->refund_type)) . ' (incl. VAT)',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            } else {
                // CREDIT Accounts Receivable (Asset decreases)
                $arAccount = ChartOfAccount::where('name', 'like', '%Receivable%')->first();
                if (!$arAccount) {
                    $assetType = \App\Models\ChartOfAccountType::where('name', 'Asset')->first() ?? \App\Models\ChartOfAccountType::find(1);
                    $assetSubType = \App\Models\ChartOfAccountSubType::where('type_id', $assetType->id)->first();
                    if (!$assetSubType) {
                        $assetSubType = \App\Models\ChartOfAccountSubType::create(['name' => 'Current Assets', 'type_id' => $assetType->id]);
                    }
                    $assetParent = \App\Models\ChartOfAccountParent::where('type_id', $assetType->id)->first();
                    if (!$assetParent) {
                        $assetParent = \App\Models\ChartOfAccountParent::create([
                            'name' => 'Accounts Receivable Parent',
                            'type_id' => $assetType->id,
                            'sub_type_id' => $assetSubType->id,
                            'code' => '1000',
                            'created_by' => auth()->id()
                        ]);
                    }

                    $arAccount = ChartOfAccount::create([
                        'name' => 'Accounts Receivable',
                        'type_id' => $assetType->id,
                        'sub_type_id' => $assetSubType->id,
                        'parent_id' => $assetParent->id,
                        'code' => '10002',
                        'status' => 'active',
                        'created_by' => auth()->id()
                    ]);
                }
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $arAccount->id,
                    'debit' => 0,
                    'credit' => $totalDeduction,
                    'memo' => 'Return credit to customer balance (incl. VAT)',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Add returned quantity to the selected stock (branch, warehouse, or employee)
     */
    private function addStockForReturnItem($saleReturn, $item)
    {
        $qty = $item->returned_qty;
        $productId = $item->product_id;
        $variationId = $item->variation_id ?? null;

        // Handle Combo Products recursively (restore component stock)
        $product = \App\Models\Product::find($productId);
        if ($product && $product->type === 'combo') {
            foreach ($product->comboItems as $comboItem) {
                // Mock an item structure for recursion
                $compItem = (object) [
                    'product_id' => $comboItem->product_id,
                    'variation_id' => $comboItem->variation_id,
                    'returned_qty' => $comboItem->quantity * $qty
                ];
                $this->addStockForReturnItem($saleReturn, $compItem);
            }
            return; // Stock is added to components, not the combo header itself
        }

        $toType = $saleReturn->return_to_type;
        $toId = $saleReturn->return_to_id;

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

    private function removeStockForReturnItem($saleReturn, $item)
    {
        $qty = $item->returned_qty;
        $productId = $item->product_id;
        $variationId = $item->variation_id ?? null;

        // Handle Combo Products recursively
        $product = \App\Models\Product::find($productId);
        if ($product && $product->type === 'combo') {
            foreach ($product->comboItems as $comboItem) {
                $compItem = (object) [
                    'product_id' => $comboItem->product_id,
                    'variation_id' => $comboItem->variation_id,
                    'returned_qty' => $comboItem->quantity * $qty
                ];
                $this->removeStockForReturnItem($saleReturn, $compItem);
            }
            return;
        }

        $toType = $saleReturn->return_to_type;
        $toId = $saleReturn->return_to_id;

        switch ($toType) {
            case 'branch':
                if ($variationId) {
                    $stock = \App\Models\ProductVariationStock::where('variation_id', $variationId)
                        ->where('branch_id', $toId)
                        ->whereNull('warehouse_id')
                        ->first();
                    if ($stock) {
                        $stock->decrement('quantity', $qty);
                    }
                } else {
                    $stock = \App\Models\BranchProductStock::where('branch_id', $toId)
                        ->where('product_id', $productId)
                        ->first();
                    if ($stock) {
                        $stock->decrement('quantity', $qty);
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
                        $stock->decrement('quantity', $qty);
                    }
                } else {
                    $stock = \App\Models\WarehouseProductStock::where('warehouse_id', $toId)
                        ->where('product_id', $productId)
                        ->first();
                    if ($stock) {
                        $stock->decrement('quantity', $qty);
                    }
                }
                break;
            case 'employee':
                $stock = \App\Models\EmployeeProductStock::where('employee_id', $toId)
                    ->where('product_id', $productId)
                    ->first();
                if ($stock) {
                    $stock->decrement('quantity', $qty);
                }
                break;
        }
    }
}
