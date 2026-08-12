<?php


namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SupplierPayment;
use App\Models\Pos;
use App\Models\Balance;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseBill;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ProductServiceCategory;
use App\Models\ProductVariation;
use App\Models\Brand;
use App\Models\Season;
use App\Models\Gender;
use App\Models\PosItem;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\SaleReturn;
use App\Models\PurchaseReturn;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;

class ReportController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        return view('erp.reports.index');
    }

    public function purchaseReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'daily');
        
        if ($reportType == 'monthly') {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        }
        
        $supplierId = $request->get('supplier_id');
        $categoryId = $request->get('category_id');
        $brandId = $request->get('brand_id');
        $seasonId = $request->get('season_id');
        $genderId = $request->get('gender_id');
        $productId = $request->get('product_id');
        $styleNumber = $request->get('style_number');
        $challanId = $request->get('challan_id');
        $branchId = $request->get('branch_id');
        $warehouseId = $request->get('warehouse_id');

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branchId = $restrictedBranchId;
        }

        $query = PurchaseItem::with(['purchase.supplier', 'purchase.bill', 'product.category', 'product.brand', 'product.season', 'product.gender', 'variation.attributeValues'])
            ->whereHas('purchase', function ($q) use ($startDate, $endDate, $branchId, $warehouseId) {
                $q->whereBetween('purchase_date', [$startDate, $endDate]);
                if ($branchId) {
                    $q->where('location_id', $branchId)->where('ship_location_type', 'branch');
                }
                if ($warehouseId) {
                    $q->where('location_id', $warehouseId)->where('ship_location_type', 'warehouse');
                }
            });

        if ($supplierId) {
            $query->whereHas('purchase', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            });
        }

        if ($challanId) {
            $query->where('purchase_id', $challanId);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($styleNumber) {
            $query->whereHas('product', function ($q) use ($styleNumber) {
                $q->where('style_number', 'like', '%' . $styleNumber . '%');
            });
        }

        if ($categoryId || $brandId || $seasonId || $genderId) {
            $query->whereHas('product', function ($q) use ($categoryId, $brandId, $seasonId, $genderId) {
                if ($categoryId) $q->where('category_id', $categoryId);
                if ($brandId) $q->where('brand_id', $brandId);
                if ($seasonId) $q->where('season_id', $seasonId);
                if ($genderId) $q->where('gender_id', $genderId);
            });
        }

        if ($request->filled('export')) {
            if ($request->export == 'excel') {
                return $this->exportPurchaseExcel($query->get(), $startDate, $endDate);
            } elseif ($request->export == 'pdf') {
                return $this->exportPurchasePdf($query->get(), $startDate, $endDate);
            }
        }

        $items = $query->latest()->paginate(50)->appends($request->all());

        // Summary stats
        $summary = [
            'total_qty' => $query->sum('quantity'),
            'total_amount' => $query->sum('total_price'),
            'unique_products' => $query->distinct('product_id')->count(),
            'total_orders' => $query->distinct('purchase_id')->count()
        ];

        $suppliers = Supplier::orderBy('name')->get();
        $categories = ProductServiceCategory::whereNull('parent_id')->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $seasons = Season::orderBy('name')->get();
        $genders = Gender::orderBy('name')->get();
        $products = Product::where('type', 'product')->orderBy('name')->get();
        $challansQuery = Purchase::latest();
        if ($branchId) {
            $challansQuery->where('location_id', $branchId)->where('ship_location_type', 'branch');
        }
        if ($warehouseId) {
            $challansQuery->where('location_id', $warehouseId)->where('ship_location_type', 'warehouse');
        }
        $challans = $challansQuery->take(100)->get();
        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();
        $warehouses = \App\Models\Warehouse::all();

        return view('erp.reports.purchase', compact(
            'items', 'summary', 'suppliers', 'categories', 'brands', 'seasons', 'genders', 'products', 'challans', 'startDate', 'endDate', 'reportType', 'branches', 'branchId', 'warehouses', 'warehouseId'
        ));
    }

    private function exportPurchaseExcel($items, $startDate, $endDate)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Purchase Report (' . $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y') . ')');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Ref #', 'Date', 'Supplier', 'Product', 'Style #', 'Category', 'Brand', 'Variation', 'Rate', 'Qty', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $col++;
        }

        $row = 4;
        foreach ($items as $item) {
            $variation = '';
            if ($item->variation) {
                $variation = $item->variation->attributeValues->pluck('value')->implode(', ');
            }

            $sheet->setCellValue('A' . $row, '#' . $item->purchase_id);
            $sheet->setCellValue('B' . $row, Carbon::parse($item->purchase->purchase_date)->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $item->purchase->supplier->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $item->product->name ?? 'Deleted');
            $sheet->setCellValue('E' . $row, $item->product->style_number ?? '-');
            $sheet->setCellValue('F' . $row, $item->product->category->name ?? '-');
            $sheet->setCellValue('G' . $row, $item->product->brand->name ?? '-');
            $sheet->setCellValue('H' . $row, $variation);
            $sheet->setCellValue('I' . $row, $item->unit_price);
            $sheet->setCellValue('J' . $row, $item->quantity);
            $sheet->setCellValue('K' . $row, $item->total_price);
            $row++;
        }

        $filename = "purchase_report_" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPurchasePdf($items, $startDate, $endDate)
    {
        $pdf = Pdf::loadView('erp.reports.pdf.purchase', [
            'items' => $items,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => [
                'total_qty' => $items->sum('quantity'),
                'total_amount' => $items->sum('total_price'),
            ]
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('purchase_report_' . date('Y-m-d') . '.pdf');
    }

    public function saleReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'daily');
        
        if ($reportType == 'monthly') {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        }
        
        $customerId = $request->get('customer_id');
        $employeeId = $request->get('employee_id');
        $categoryId = $request->get('category_id');
        $brandId = $request->get('brand_id');
        $seasonId = $request->get('season_id');
        $genderId = $request->get('gender_id');
        $productId = $request->get('product_id');
        $styleNumber = $request->get('style_number');
        $invoiceNo = $request->get('invoice_no');
        $branchId = $request->get('branch_id');

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branchId = $restrictedBranchId;
        }

        // POS Items Query - Only parent items (where parent_item_id is null)
        $posQuery = PosItem::with(['pos.customer', 'pos.employee.user', 'pos.soldBy', 'product.category', 'product.brand', 'product.season', 'product.gender', 'variation.attributeValues', 'childItems.product', 'childItems.variation.attributeValues'])
            ->whereNull('parent_item_id')
            ->whereHas('pos', function($q) use ($startDate, $endDate, $customerId, $employeeId, $invoiceNo, $branchId) {
                $q->whereBetween('sale_date', [$startDate, $endDate]);
                if ($customerId) $q->where('customer_id', $customerId);
                if ($employeeId) $q->where('sold_by', $employeeId);
                if ($invoiceNo) $q->where('invoice_number', 'like', '%' . $invoiceNo . '%');
                if ($branchId) $q->where('branch_id', $branchId);
            });

        // Online Order Items Query - Only parent items (where parent_item_id is null)
        $orderQuery = OrderItem::with(['order.customer', 'order.employee.user', 'product.category', 'product.brand', 'product.season', 'product.gender', 'variation.attributeValues', 'childItems.product', 'childItems.variation.attributeValues'])
            ->whereNull('parent_item_id')
            ->whereHas('order', function($q) use ($startDate, $endDate, $customerId, $invoiceNo, $branchId) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->where('status', '!=', 'cancelled');
                if ($customerId) $q->where('user_id', $customerId);
                if ($invoiceNo) $q->where('order_id', 'like', '%' . $invoiceNo . '%');
                // Order model might not have branch_id but if it does, filter it:
                // if ($branchId) $q->where('branch_id', $branchId); 
            });

        // Apply filters to both
        foreach ([$posQuery, $orderQuery] as $q) {
            if ($productId) $q->where('product_id', $productId);
            if ($styleNumber) {
                $q->whereHas('product', function ($pq) use ($styleNumber) {
                    $pq->where('style_number', 'like', '%' . $styleNumber . '%');
                });
            }
            if ($categoryId || $brandId || $seasonId || $genderId) {
                $q->whereHas('product', function ($pq) use ($categoryId, $brandId, $seasonId, $genderId) {
                    if ($categoryId) $pq->where('category_id', $categoryId);
                    if ($brandId) $pq->where('brand_id', $brandId);
                    if ($seasonId) $pq->where('season_id', $seasonId);
                    if ($genderId) $pq->where('gender_id', $genderId);
                });
            }
        }

        // Summary Statistics - Calculated in DB for performance (only parent items to avoid double-counting)
        $posSummary = (clone $posQuery)
            ->whereNull('pos_items.parent_item_id')
            ->join('products as p_pos', 'pos_items.product_id', '=', 'p_pos.id')
            ->leftJoin('product_variations as pv_pos', 'pos_items.variation_id', '=', 'pv_pos.id')
            ->selectRaw('SUM(pos_items.quantity) as total_qty, SUM(pos_items.total_price) as total_amount, SUM(pos_items.quantity * COALESCE(pos_items.unit_cost, pv_pos.cost, p_pos.cost, 0)) as total_cost')
            ->first();

        $orderSummary = (clone $orderQuery)
            ->whereNull('order_items.parent_item_id')
            ->join('products as p_ord', 'order_items.product_id', '=', 'p_ord.id')
            ->leftJoin('product_variations as pv_ord', 'order_items.variation_id', '=', 'pv_ord.id')
            ->selectRaw('SUM(order_items.quantity) as total_qty, SUM(order_items.total_price) as total_amount, SUM(order_items.quantity * COALESCE(order_items.unit_cost, pv_ord.cost, p_ord.cost, 0)) as total_cost')
            ->first();

        $summary = [
            'total_qty' => ($posSummary->total_qty ?? 0) + ($orderSummary->total_qty ?? 0),
            'total_amount' => ($posSummary->total_amount ?? 0) + ($orderSummary->total_amount ?? 0),
            'total_cost' => ($posSummary->total_cost ?? 0) + ($orderSummary->total_cost ?? 0),
            'total_discount' => 0,
        ];
        $summary['total_net'] = $summary['total_amount'];
        $summary['total_profit'] = $summary['total_amount'] - $summary['total_cost'];

        // On-screen List: Limit for browser performance, but full for export
        $isExport = $request->filled('export');
        $posItemsQuery = $isExport ? $posQuery : $posQuery->take(100);
        $orderItemsQuery = $isExport ? $orderQuery : $orderQuery->take(100);

        $posItems = $posItemsQuery->get()->map(function($item) {
            $item->source = 'POS';
            $item->date = $item->pos->sale_date;
            $item->invoice = $item->pos->invoice_number;
            $item->customer_name = $item->pos->customer->name ?? 'Walk-in';
            $item->created_by_name = $item->pos->soldBy->name ?? ($item->pos->employee->user->name ?? 'Admin');
            $item->unit_cost = ($item->product && $item->product->isCombo()) 
                ? $item->product->calculateCost($item->variation_id) 
                : ($item->unit_cost ?? ($item->product->cost ?? 0));
            $item->total_cost = $item->quantity * $item->unit_cost;
            $item->profit = $item->total_price - $item->total_cost;
            $item->item_discount = ($item->quantity * $item->unit_price) - $item->total_price;
            return $item;
        });

        $orderItems = $orderItemsQuery->get()->map(function($item) {
            $item->source = 'Online';
            $item->date = $item->order->created_at;
            $item->invoice = '#' . $item->order->id;
            $item->customer_name = $item->order->customer->name ?? ($item->order->first_name . ' ' . $item->order->last_name);
            $item->created_by_name = $item->order->employee->user->name ?? 'Website';
            $item->unit_cost = ($item->product && $item->product->isCombo()) 
                ? $item->product->calculateCost($item->variation_id) 
                : ($item->unit_cost ?? ($item->product->cost ?? 0));
            $item->total_cost = $item->quantity * $item->unit_cost;
            $item->profit = $item->total_price - $item->total_cost;
            $item->item_discount = ($item->quantity * $item->unit_price) - $item->total_price;
            return $item;
        });

        $allItems = $posItems->concat($orderItems)->sortByDesc('date');

        $customersQuery = Customer::query();
        if ($restrictedBranchId) {
            $customersQuery->where('branch_id', $restrictedBranchId);
        }
        $customers = $customersQuery->orderBy('name')->get();
        $employees = User::whereHas('employee')->get();
        $categories = ProductServiceCategory::whereNull('parent_id')->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $seasons = Season::orderBy('name')->get();
        $genders = Gender::orderBy('name')->get();
        $products = Product::where('type', 'product')->orderBy('name')->get();
        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        if ($isExport) {
            if ($request->export == 'excel') {
                return $this->exportSaleExcel($allItems, $startDate, $endDate);
            } elseif ($request->export == 'pdf') {
                return $this->exportSalePdf($allItems, $startDate, $endDate);
            }
        }

        return view('erp.reports.sale', compact(
            'allItems', 'summary', 'customers', 'employees', 'categories', 'brands', 'seasons', 'genders', 'products', 'startDate', 'endDate', 'reportType', 'branches', 'branchId'
        ));
    }

    private function exportSaleExcel($items, $startDate, $endDate)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $headers = ['Order ID', 'Date', 'Customer', 'Product', 'Style #', 'Variation', 'Unit Price', 'Qty', 'Total', 'Discount', 'Source'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        $row = 2;
        foreach ($items as $item) {
            $variation = $item->variation ? $item->variation->attributeValues->pluck('value')->implode(', ') : 'Standard';
            
            $sheet->setCellValue('A' . $row, $item->invoice);
            $sheet->setCellValue('B' . $row, Carbon::parse($item->date)->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $item->customer_name);
            $sheet->setCellValue('D' . $row, $item->product->name ?? 'Deleted');
            $sheet->setCellValue('E' . $row, $item->product->style_number ?? '-');
            $sheet->setCellValue('F' . $row, $variation);
            $sheet->setCellValue('G' . $row, $item->unit_price);
            $sheet->setCellValue('H' . $row, $item->quantity);
            $sheet->setCellValue('I' . $row, $item->total_price);
            $sheet->setCellValue('J' . $row, $item->item_discount);
            $sheet->setCellValue('K' . $row, $item->source);
            $row++;
        }

        $filename = "sale_report_" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportSalePdf($items, $startDate, $endDate)
    {
        $pdf = Pdf::loadView('erp.reports.pdf.sale', [
            'items' => $items,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => [
                'total_qty' => $items->sum('quantity'),
                'total_amount' => $items->sum('total_price'),
                'total_discount' => $items->sum('item_discount'),
            ]
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('sale_report_' . date('Y-m-d') . '.pdf');
    }

    public function cashProfitReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view financial reports')) {
            abort(403, 'Unauthorized action.');
        }

        $reportType = $request->get('report_type', 'daily');
        
        if ($reportType == 'monthly') {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfDay();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');

        // Fetch payments, returns, and exchanges in the current period to identify active transactions
        $paymentsQuery = \App\Models\Payment::with(['pos', 'invoice', 'account'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->whereIn('payment_for', ['invoice', 'pos', 'pos_sale', 'advance_payment', 'customer_due', 'manual_receipt']);

        if ($branchId) {
            $paymentsQuery->where(function($q) use ($branchId) {
                $q->whereHas('pos', function($pq) use ($branchId) { $pq->where('branch_id', $branchId); })
                  ->orWhereHas('invoice.pos', function($ipq) use ($branchId) { $ipq->where('branch_id', $branchId); });
            });
        }
        $payments = $paymentsQuery->get();

        $returnsQuery = \App\Models\SaleReturn::with(['posSale', 'invoice'])
            ->whereBetween('return_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'approved', 'processed']);

        if ($branchId) {
            $returnsQuery->where(function($q) use ($branchId) {
                $q->whereHas('posSale', function($pq) use ($branchId) { $pq->where('branch_id', $branchId); })
                  ->orWhereHas('invoice.pos', function($ipq) use ($branchId) { $ipq->where('branch_id', $branchId); });
            });
        }
        $returns = $returnsQuery->get();

        $exchangesQuery = \App\Models\PosExchange::with(['originalPos', 'account'])
            ->whereBetween('exchange_date', [$startDate, $endDate])
            ->where('status', 'completed');

        if ($branchId) {
            $exchangesQuery->where('branch_id', $branchId);
        }
        $exchanges = $exchangesQuery->get();

        // Map active transactions to prevent double counting and to aggregate metrics correctly
        $activeTransactions = [];
        $addActiveTransaction = function($type, $model, $date, $reference) use (&$activeTransactions) {
            if (!$model) return;
            $key = "{$type}-{$model->id}";
            if (!isset($activeTransactions[$key])) {
                $activeTransactions[$key] = [
                    'type' => $type,
                    'model' => $model,
                    'latest_date' => $date,
                    'reference' => $reference
                ];
            } else {
                if ($date > $activeTransactions[$key]['latest_date']) {
                    $activeTransactions[$key]['latest_date'] = $date;
                }
            }
        };

        foreach ($payments as $payment) {
            if ($payment->pos) {
                $addActiveTransaction('pos', $payment->pos, $payment->payment_date, $payment->pos->invoice_number ?? ('POS-'.$payment->pos->id));
            } elseif ($payment->invoice) {
                $invoice = $payment->invoice;
                $ref = $invoice->invoice_number ?? ('INV-'.$invoice->id);
                if ($invoice->pos) {
                    $addActiveTransaction('pos', $invoice->pos, $payment->payment_date, $ref);
                } elseif ($invoice->order) {
                    $addActiveTransaction('order', $invoice->order, $payment->payment_date, $ref);
                } else {
                    $addActiveTransaction('invoice', $invoice, $payment->payment_date, $ref);
                }
            }
        }

        foreach ($returns as $return) {
            if ($return->posSale) {
                $addActiveTransaction('pos', $return->posSale, $return->return_date, $return->posSale->invoice_number ?? ('POS-'.$return->posSale->id));
            } elseif ($return->invoice) {
                $invoice = $return->invoice;
                $ref = $invoice->invoice_number ?? ('INV-'.$invoice->id);
                if ($invoice->pos) {
                    $addActiveTransaction('pos', $invoice->pos, $return->return_date, $ref);
                } elseif ($invoice->order) {
                    $addActiveTransaction('order', $invoice->order, $return->return_date, $ref);
                } else {
                    $addActiveTransaction('invoice', $invoice, $return->return_date, $ref);
                }
            }
        }

        foreach ($exchanges as $exchange) {
            if ($exchange->originalPos) {
                $addActiveTransaction('pos', $exchange->originalPos, $exchange->exchange_date, $exchange->originalPos->invoice_number ?? ('POS-'.$exchange->originalPos->id));
            }
        }

        $cashProfits = collect();
        $totalCollected = 0;
        $totalEstimatedCost = 0;
        $totalCashProfit = 0;
        $totalGrossPayments = 0;
        $totalReturnRefunds = 0;
        $totalExchangeRefunds = 0;
        $saleReturnCashRefund = 0;
        $exchangeProfitChange = 0;
        $saleReturnDetails = collect();

        foreach ($activeTransactions as $tx) {
            $type = $tx['type'];
            $model = $tx['model'];
            $reference = $tx['reference'];

            $priorInfo = $this->getTransactionMarginAt($type, $model, $startDate, true);
            $currentInfo = $this->getTransactionMarginAt($type, $model, $endDate, false);

            $paymentsQuery = \App\Models\Payment::where(function($q) use ($type, $model) {
                if ($type === 'pos') {
                    $q->where('pos_id', $model->id);
                    if ($model->invoice_id) {
                        $q->orWhere('invoice_id', $model->invoice_id);
                    }
                } else {
                    $q->where('invoice_id', $model->invoice_id ?? $model->id);
                }
            });

            $priorPayments = (clone $paymentsQuery)->where('payment_date', '<', $startDate)->sum('amount');
            $currentPayments = (clone $paymentsQuery)->whereBetween('payment_date', [$startDate, $endDate])->sum('amount');

            if ($type === 'pos') {
                $returnsQuery = \App\Models\SaleReturn::where('pos_sale_id', $model->id);
            } elseif ($type === 'order') {
                $returnsQuery = \App\Models\OrderReturn::where('order_id', $model->id);
            } else {
                $returnsQuery = \App\Models\SaleReturn::where('invoice_id', $model->invoice_id ?? $model->id);
            }

            $priorReturns = (clone $returnsQuery)->whereIn('status', ['completed', 'approved', 'processed'])->where('return_date', '<', $startDate)->get();
            $priorRefunds = $priorReturns->sum(function($r) {
                return in_array($r->refund_type, ['cash', 'bank', 'mobile']) ? $r->items->sum('total_price') : 0;
            });

            $currentReturns = (clone $returnsQuery)->whereIn('status', ['completed', 'approved', 'processed'])->whereBetween('return_date', [$startDate, $endDate])->get();
            $currentRefunds = $currentReturns->sum(function($r) {
                return in_array($r->refund_type, ['cash', 'bank', 'mobile']) ? $r->items->sum('total_price') : 0;
            });

            $priorExchangeRefunds = 0;
            $currentExchangeRefunds = 0;
            if ($type === 'pos') {
                $priorExchangeRefunds = \App\Models\PosExchange::where('original_pos_id', $model->id)
                    ->where('status', 'completed')
                    ->where('exchange_date', '<', $startDate)
                    ->sum('refund_amount');
                    
                $currentExchangeRefunds = \App\Models\PosExchange::where('original_pos_id', $model->id)
                    ->where('status', 'completed')
                    ->whereBetween('exchange_date', [$startDate, $endDate])
                    ->sum('refund_amount');
            }

            $priorNetCash = $priorPayments - $priorRefunds - $priorExchangeRefunds;

            $netCollectionForTx = $currentPayments - $currentRefunds - $currentExchangeRefunds;

            $deliveryAmount = floatval($type === 'pos' ? ($model->delivery ?? 0) : (($model->order ?? null) ? $model->order->delivery : 0));
            $cumulativeNetCashEnd = $priorNetCash + $netCollectionForTx;
            $cumulativeProductCashEnd = max(0, $cumulativeNetCashEnd - $deliveryAmount);
            $cumulativeProductCashStart = max(0, $priorNetCash - $deliveryAmount);
            
            $currentNetProductCollection = $cumulativeProductCashEnd - $cumulativeProductCashStart;
            
            $cashProfitOnNetCollection = $currentNetProductCollection * $currentInfo['margin'];
            $priorCashAdjustment = $cumulativeProductCashStart * ($currentInfo['margin'] - $priorInfo['margin']);

            $txCashProfit = $cashProfitOnNetCollection + $priorCashAdjustment;

            $totalGrossPayments += $currentPayments;
            $totalReturnRefunds += $currentRefunds;
            $totalExchangeRefunds += $currentExchangeRefunds;

            $totalCollected += $netCollectionForTx;
            $totalCashProfit += $txCashProfit;

            $cashProfits->push((object)[
                'date' => $tx['latest_date'],
                'reference' => $reference,
                'collection_amount' => $netCollectionForTx,
                'sale_amount' => $currentInfo['sale_amount'],
                'invoice_profit' => $currentInfo['revenue'] - $currentInfo['cost'],
                'profit_margin' => $currentInfo['margin'] * 100,
                'estimated_cost' => $netCollectionForTx - $txCashProfit,
                'cash_profit' => $txCashProfit
            ]);

            foreach ($currentReturns as $ret) {
                $saleReturnDetails->push((object)[
                    'date'         => $ret->return_date,
                    'reference'    => $reference,
                    'refund_type'  => $ret->refund_type ?? 'none',
                    'return_amount'=> $ret->items->sum('total_price'),
                ]);
            }
        }

        $cashProfits = $cashProfits->sortByDesc('date');
        $totalEstimatedCost = $totalCollected - $totalCashProfit;
        $exchangeProfitChange = 0;
        $saleReturnCashRefund = 0;

        // --- Calculate Operating Expenses ---
        $debitVoucherQuery = \App\Models\JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('chart_of_accounts', 'journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type_id', '=', 'chart_of_account_types.id')
            ->where('chart_of_account_types.name', 'like', 'Expense%')
            ->where('chart_of_accounts.name', 'not like', '%Purchase%')
            ->whereBetween('journals.entry_date', [$startDate, $endDate]);

        if ($branchId) $debitVoucherQuery->where('journals.branch_id', $branchId);
        
        $debitVoucherDetails = $debitVoucherQuery
            ->select('chart_of_accounts.name', \DB::raw('SUM(journal_entries.debit) - SUM(journal_entries.credit) as amount'))
            ->groupBy('chart_of_accounts.name')
            ->having('amount', '>', 0)
            ->get();

        $debitVoucher = $debitVoucherDetails->sum('amount');

        $salaryPaymentQuery = \App\Models\SalaryPayment::whereBetween('payment_date', [$startDate, $endDate]);
        if ($branchId) $salaryPaymentQuery->where('branch_id', $branchId);
        
        $salaryPaymentIdsWithoutJournal = $salaryPaymentQuery->whereNotExists(function($q) {
            $q->select(\DB::raw(1))
              ->from('journals')
              ->whereRaw('journals.voucher_no = CONCAT("SAL-", DATE_FORMAT(salary_payments.payment_date, "%Y%m%d"), "-", LPAD(salary_payments.id, 4, "0"))');
        })->pluck('id');
        
        $employeePayment = \App\Models\SalaryPayment::whereIn('id', $salaryPaymentIdsWithoutJournal)->sum('paid_amount');

        $totalOperatingExpenses = $debitVoucher + $employeePayment;

        // --- Credit Vouchers: Other Cash Incomes ---
        $creditVoucherQuery = \App\Models\JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('chart_of_accounts', 'journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type_id', '=', 'chart_of_account_types.id')
            ->where('chart_of_account_types.name', 'like', 'Revenue%')
            ->where('chart_of_accounts.name', 'not like', '%Sales%')
            ->where('chart_of_accounts.name', 'not like', '%Sale%')
            ->where('chart_of_accounts.name', 'not like', '%Purchase%')
            ->whereBetween('journals.entry_date', [$startDate, $endDate]);

        if ($branchId) $creditVoucherQuery->where('journals.branch_id', $branchId);

        $creditVoucherDetails = $creditVoucherQuery
            ->select('chart_of_accounts.name', \DB::raw('SUM(journal_entries.credit) - SUM(journal_entries.debit) as amount'))
            ->groupBy('chart_of_accounts.name')
            ->having('amount', '>', 0)
            ->get();

        $totalOtherIncome = $creditVoucherDetails->sum('amount');

        $netCashProfit = ($totalCashProfit + $totalOtherIncome + $exchangeProfitChange) - ($totalOperatingExpenses + $saleReturnCashRefund);

        // --- Calculate Total Customer Due generated in this period ---
        $dueQuery = \App\Models\Invoice::whereBetween('issue_date', [$startDate, $endDate]);
        if ($branchId) {
            $dueQuery->where(function($q) use ($branchId) {
                $q->whereHas('pos', function($pq) use ($branchId) {
                    $pq->where('branch_id', $branchId);
                })
                ->orWhereHas('customer', function($cq) use ($branchId) {
                    $cq->where('branch_id', $branchId);
                })
                ->orWhereHas('payments', function($payq) use ($branchId) {
                    $payq->whereHas('account', function($accq) use ($branchId) {
                        $accq->where('branch_id', $branchId);
                    });
                });
            });
        }
        $totalDue = $dueQuery->sum('due_amount');

        // --- Calculate Total Purchase Value and Supplier Due (Accounts Payable) generated in this period ---
        $supplierDueQuery = \App\Models\PurchaseBill::whereBetween('bill_date', [$startDate, $endDate]);
        if ($branchId) {
            $supplierDueQuery->whereHas('purchase', function($q) use ($branchId) {
                $q->where('ship_location_type', 'branch')->where('location_id', $branchId);
            });
        }
        $totalPurchaseAmount = $supplierDueQuery->sum('total_amount');
        $totalSupplierDue = $supplierDueQuery->sum('due_amount');

        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        // --- Calculate Channel Breakdown (Cash Book, Bank Book, Mobile Book) matching Financial Book reports 100% ---
        $getChannelCollection = function($type) use ($startDate, $endDate, $branchId) {
            $accountIds = \App\Models\FinancialAccount::where('type', $type)
                ->when($branchId, function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->pluck('id');

            if ($accountIds->isEmpty()) {
                return 0;
            }

            $movement = \App\Models\JournalEntry::whereIn('financial_account_id', $accountIds)
                ->whereHas('journal', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('entry_date', [$startDate->toDateString(), $endDate->toDateString()]);
                })
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            return floatval(($movement->total_debit ?? 0) - ($movement->total_credit ?? 0));
        };

        $cashCollection = $getChannelCollection(\App\Models\FinancialAccount::TYPE_CASH);
        $bankCollection = $getChannelCollection(\App\Models\FinancialAccount::TYPE_BANK);
        $mobileCollection = $getChannelCollection(\App\Models\FinancialAccount::TYPE_MOBILE);

        $totalExchangeCount = $exchanges->count();
        $totalExchangeReturnVal = $exchanges->sum('total_return_amount');
        $totalExchangeNewVal = $exchanges->sum('total_new_amount');

        return view('erp.reports.cash-profit', compact(
            'cashProfits', 'startDate', 'endDate', 'reportType', 'branches', 'branchId',
            'totalCollected', 'totalEstimatedCost', 'totalCashProfit', 'totalOtherIncome', 'creditVoucherDetails',
            'totalOperatingExpenses', 'debitVoucherDetails', 'employeePayment',
            'saleReturnCashRefund', 'saleReturnDetails', 'netCashProfit', 'totalDue', 'totalSupplierDue', 'totalPurchaseAmount', 'exchangeProfitChange',
            'totalGrossPayments', 'totalReturnRefunds', 'totalExchangeRefunds',
            'totalExchangeCount', 'totalExchangeReturnVal', 'totalExchangeNewVal',
            'cashCollection', 'bankCollection', 'mobileCollection'
        ));
    }

    private function getTransactionMarginAt($type, $model, $dateLimit, $isBefore)
    {
        $operator = $isBefore ? '<' : '<=';
        
        // 1. Get returns
        if ($type === 'pos') {
            $returnsQuery = \App\Models\SaleReturn::where('pos_sale_id', $model->id);
        } elseif ($type === 'order') {
            $returnsQuery = \App\Models\OrderReturn::where('order_id', $model->id);
        } else {
            $returnsQuery = \App\Models\SaleReturn::where('invoice_id', $model->invoice_id ?? $model->id);
        }
        $returns = $returnsQuery->whereIn('status', ['completed', 'approved', 'processed'])
            ->where('return_date', $operator, $dateLimit)
            ->get();
            
        $returnedAmount = $returns->sum(function($r) {
            return $r->items->sum('total_price');
        });
        
        // 2. Get exchanges (only for POS)
        $exchangeNewAmount = 0;
        $exchangeNewCost = 0;
        if ($type === 'pos') {
            $exchanges = \App\Models\PosExchange::with(['items.product', 'items.variation'])
                ->where('original_pos_id', $model->id)
                ->where('status', 'completed')
                ->where('exchange_date', $operator, $dateLimit)
                ->get();
                
            $exchangeNewAmount = $exchanges->sum('total_new_amount');
            
            foreach ($exchanges as $exchange) {
                foreach ($exchange->items as $item) {
                    if ($item->type == 'new') {
                        $cost = $item->product ? $item->product->calculateCost($item->variation_id) : 0;
                        $exchangeNewCost += $item->quantity * $cost;
                    }
                }
            }
        }
        
        // 3. Active Sale Amount
        $originalSaleAmount = floatval($type === 'pos' ? $model->total_amount : ($model->total_amount ?? 0));
        $activeSaleAmount = max(0, $originalSaleAmount - $returnedAmount + $exchangeNewAmount);
        
        // 4. Delivery
        $delivery = floatval($type === 'pos' ? ($model->delivery ?? 0) : (($model->order ?? null) ? $model->order->delivery : 0));
        $activeProductRevenue = max(0, $activeSaleAmount - $delivery);
        
        // 5. Cost (filter parent items only to prevent double counting combo child items)
        $originalCost = 0;
        if ($type === 'pos') {
            $items = \App\Models\PosItem::where('pos_sale_id', $model->id)
                ->whereNull('parent_item_id')
                ->with(['product', 'variation'])
                ->get();
            foreach ($items as $item) {
                $unitCost = (float) ($item->unit_cost ?? 0);
                if ($item->product && ($unitCost <= 0 || $item->product->isCombo())) {
                    $unitCost = $item->product->calculateCost($item->variation_id);
                }
                $originalCost += $item->quantity * $unitCost;
            }
        } elseif ($type === 'order') {
            $items = \App\Models\OrderItem::where('order_id', $model->id)
                ->whereNull('parent_item_id')
                ->with(['product', 'variation'])
                ->get();
            foreach ($items as $item) {
                $unitCost = (float) ($item->unit_cost ?? 0);
                if ($item->product && ($unitCost <= 0 || $item->product->isCombo())) {
                    $unitCost = $item->product->calculateCost($item->variation_id);
                }
                $originalCost += $item->quantity * $unitCost;
            }
        } elseif ($type === 'invoice') {
            $items = \App\Models\InvoiceItem::where('invoice_id', $model->id)
                ->with(['product', 'variation'])
                ->get();
            foreach ($items as $item) {
                $unitCost = 0;
                if ($item->product) {
                    $unitCost = $item->product->calculateCost($item->variation_id);
                }
                $originalCost += $item->quantity * $unitCost;
            }
        }
        
        $returnedCost = 0;
        if ($returns->isNotEmpty()) {
            if ($type === 'order') {
                $returnItems = \App\Models\OrderReturnItem::whereIn('order_return_id', $returns->pluck('id'))
                    ->with(['product', 'variation'])
                    ->get();
                foreach ($returnItems as $rItem) {
                    $unitCost = $rItem->product ? $rItem->product->calculateCost($rItem->variation_id) : 0;
                    $returnedCost += $rItem->returned_qty * $unitCost;
                }
            } else {
                $returnItems = \App\Models\SaleReturnItem::whereIn('sale_return_id', $returns->pluck('id'))
                    ->with(['product', 'variation'])
                    ->get();
                foreach ($returnItems as $rItem) {
                    $unitCost = $rItem->product ? $rItem->product->calculateCost($rItem->variation_id) : 0;
                    $returnedCost += $rItem->returned_qty * $unitCost;
                }
            }
        }
        
        $costAmount = max(0, $originalCost - $returnedCost + $exchangeNewCost);
        
        $invoiceProfit = $activeProductRevenue - $costAmount;
        $profitMargin = $activeProductRevenue > 0 ? ($invoiceProfit / $activeProductRevenue) : 0;
        
        return [
            'margin' => $profitMargin,
            'cost' => $costAmount,
            'revenue' => $activeProductRevenue,
            'sale_amount' => $activeSaleAmount
        ];
    }
    public function profitLossReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view financial reports')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'daily');
        
        if ($reportType == 'monthly') {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfDay();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        }
        
        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');

        // --- INCOME SIDE (Revenue & Inflows) ---
        
        // 1. Operating Revenue (Net Sales)
        // We rely on the Journal Entries (General Ledger) for the most accurate revenue figure.
        // This automatically excludes VAT (Liability) and includes Net Sales from POS and Online orders.
        $revenueQuery = \App\Models\JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('chart_of_accounts', 'journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type_id', '=', 'chart_of_account_types.id')
            ->where('chart_of_account_types.name', 'like', 'Revenue%')
            ->where('chart_of_accounts.name', 'not like', '%Purchase Return%')
            ->whereBetween('journals.entry_date', [$startDate, $endDate]);

        if ($branchId) $revenueQuery->where('journals.branch_id', $branchId);

        $creditVoucherDetails = $revenueQuery->select('chart_of_accounts.name', \DB::raw('SUM(journal_entries.credit - journal_entries.debit) as amount'))
            ->groupBy('chart_of_accounts.name')
            ->having('amount', '!=', 0)
            ->get();

        $operatingRevenue = $creditVoucherDetails->sum('amount');

        // 2. Stock Valuation (Informational Asset Value)
        $stockQuery = Product::where('type', 'product');
        if ($branchId) {
            $stockQuery->withSum(['branchStocks' => function($q) use ($branchId) { $q->where('branch_id', $branchId); }], 'quantity');
        } else {
            $stockQuery->withSum('variationStocks', 'quantity');
        }
        $stockAmount = $stockQuery->get()->sum(function($p) {
            return ($p->branch_stocks_sum_quantity ?? $p->variation_stocks_sum_quantity ?? 0) * $p->cost;
        });

        // 3. Money Receipts (Manual Non-Journalized Inflows)
        // Note: If manual receipts create journals to revenue accounts, they are already in $operatingRevenue.
        // We only sum payments that are NOT linked to a journal to avoid double counting.
        $moneyReceipt = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('payment_for', 'manual_receipt')
            ->whereNotExists(function($q) {
                $q->select(\DB::raw(1))
                  ->from('journals')
                  ->whereRaw('journals.reference = CONCAT("MR-", payments.id)');
            });
        if ($branchId) {
            $moneyReceipt->where(function($q) use ($branchId) {
                $q->whereHas('pos', function($pq) use ($branchId) { $pq->where('branch_id', $branchId); })
                  ->orWhereHas('invoice.pos', function($ipq) use ($branchId) { $ipq->where('branch_id', $branchId); });
            });
        }
        $moneyReceiptAmount = $moneyReceipt->sum('amount');

        // 4. Purchase Returns (Asset Gain/Liability Reduction)
        // Removed from P&L since this is a balance sheet movement (reduces inventory asset & liability)
        $purchaseReturnAmount = 0; 

        // 5. Exchange Adjustments
        // The value of items received in trade. This represents an inflow of assets.
        $posQuery = Pos::whereBetween('sale_date', [$startDate, $endDate]);
        if ($branchId) $posQuery->where('branch_id', $branchId);
        $exchangeAmount = $posQuery->sum('exchange_amount');

        // 6. Sender Transfer Amount - Stock transfers don't involve money, 
        // so they should NOT affect Profit/Loss. Set to 0.
        $senderTransferAmount = 0;

        // TOTAL INCOME
        // Removed Stock Valuation, Money Receipts, Purchase Returns, and Exchange Adjustments from P&L
        $totalIncome = $operatingRevenue + $senderTransferAmount;


        // --- EXPENSE SIDE ---

        // 1. Cost of Goods Sold (filter parent items only to prevent double counting combo child items)
        $posItemsForCost = PosItem::whereHas('pos', function($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('sale_date', [$startDate, $endDate]);
                if ($branchId) $q->where('branch_id', $branchId);
            })
            ->whereNull('parent_item_id')
            ->with(['product', 'variation'])
            ->get();

        $posCost = 0;
        foreach ($posItemsForCost as $item) {
            $unitCost = (float) ($item->unit_cost ?? 0);
            if ($unitCost <= 0 && $item->product) {
                $unitCost = $item->product->calculateCost($item->variation_id);
            }
            $posCost += $item->quantity * $unitCost;
        }

        $onlineItemsForCost = OrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])->where('status', '!=', 'cancelled');
            })
            ->whereNull('parent_item_id')
            ->with(['product', 'variation'])
            ->get();

        $onlineCost = 0;
        foreach ($onlineItemsForCost as $item) {
            $unitCost = (float) ($item->unit_cost ?? 0);
            if ($unitCost <= 0 && $item->product) {
                $unitCost = $item->product->calculateCost($item->variation_id);
            }
            $onlineCost += $item->quantity * $unitCost;
        }

        $exchangeItemsForCost = \App\Models\PosExchangeItem::where('pos_exchange_items.type', 'new')
            ->whereHas('exchange', function($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('exchange_date', [$startDate, $endDate]);
                if ($branchId) $q->where('branch_id', $branchId);
            })
            ->with(['product', 'variation'])
            ->get();

        $exchangeCost = 0;
        foreach ($exchangeItemsForCost as $item) {
            $unitCost = $item->product ? $item->product->calculateCost($item->variation_id) : 0;
            $exchangeCost += $item->quantity * $unitCost;
        }

        $returnItemsForCost = \App\Models\SaleReturnItem::whereHas('saleReturn', function($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('return_date', [$startDate, $endDate]);
                $q->whereIn('status', ['completed', 'approved', 'processed']);
                if ($branchId) {
                    $q->whereHas('posSale', function($pq) use ($branchId) {
                        $pq->where('branch_id', $branchId);
                    });
                }
            })
            ->with(['product', 'variation'])
            ->get();

        $returnCost = 0;
        foreach ($returnItemsForCost as $item) {
            $unitCost = $item->product ? $item->product->calculateCost($item->variation_id) : 0;
            $returnCost += $item->returned_qty * $unitCost;
        }
            
        $cogsAmount = $posCost + $onlineCost + $exchangeCost - $returnCost;

        // 2. Debit Voucher (Net Expenses from Journals)
        // We exclude 'Purchases' here because inventory consumption is already handled by COGS above.
        // Counting both 'Purchases' and 'COGS' leads to double-counting expenses.
        $debitVoucherQuery = \App\Models\JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('chart_of_accounts', 'journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type_id', '=', 'chart_of_account_types.id')
            ->where('chart_of_account_types.name', 'like', 'Expense%')
            ->where('chart_of_accounts.name', 'not like', '%Purchase%') // Exclude Purchases since we use COGS
            ->whereBetween('journals.entry_date', [$startDate, $endDate]);

        if ($branchId) $debitVoucherQuery->where('journals.branch_id', $branchId);
        
        // Detailed breakdown including Salary, Supplier Payments, etc.
        $debitVoucherDetails = $debitVoucherQuery
            ->select('chart_of_accounts.name', \DB::raw('SUM(journal_entries.debit) - SUM(journal_entries.credit) as amount'))
            ->groupBy('chart_of_accounts.name')
            ->having('amount', '!=', 0)
            ->get();
            
        $debitVoucher = $debitVoucherDetails->sum('amount');

        // 3. Employee Payment - Salary payments should create journal entries included in $debitVoucher above.
        // However, we add a fallback to sum salary payments that don't have linked journal entries
        // to ensure the P&L report is complete for business owners even if journal creation failed.
        $salaryPaymentQuery = \App\Models\SalaryPayment::whereBetween('payment_date', [$startDate, $endDate]);
        if ($branchId) $salaryPaymentQuery->where('branch_id', $branchId);
        
        // Get salary payments WITHOUT linked journal entries (fallback)
        $salaryPaymentIdsWithoutJournal = $salaryPaymentQuery->whereNotExists(function($q) {
            $q->select(\DB::raw(1))
              ->from('journals')
              ->whereRaw('journals.voucher_no = CONCAT("SAL-", DATE_FORMAT(salary_payments.payment_date, "%Y%m%d"), "-", LPAD(salary_payments.id, 4, "0"))');
        })->pluck('id');
        
        $employeePayment = \App\Models\SalaryPayment::whereIn('id', $salaryPaymentIdsWithoutJournal)->sum('paid_amount');

        // 4. Supplier Pay (Removed from P&L calculations since it's cash flow, not expense)
        $supplierPay = 0; 

        // 5. Sales Returns
        // Note: Sales returns are now handled as a reduction in 'Operating Revenue' 
        // via the Journal Entry debiting the Sales Return account. 
        // We set this to 0 here to avoid double counting in the Expense section.
        $salesReturnAmount = 0;

        // 7. Receiver Transfer Amount - Stock transfers don't involve money,
        // so they should NOT affect Profit/Loss. Set to 0.
        $receiverTransferAmount = 0;

        $totalExpense = $cogsAmount + $debitVoucher + $employeePayment + $salesReturnAmount + $receiverTransferAmount;
        
        $netProfit = $totalIncome - $totalExpense;
        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        $viewData = compact(
            'startDate', 'endDate', 'branches', 'branchId', 'reportType',
            'operatingRevenue', 'creditVoucherDetails', 'stockAmount', 'moneyReceiptAmount', 'purchaseReturnAmount', 'exchangeAmount', 'senderTransferAmount', 'totalIncome',
            'cogsAmount', 'debitVoucher', 'debitVoucherDetails', 'employeePayment', 'supplierPay', 'salesReturnAmount', 'receiverTransferAmount', 'totalExpense',
            'netProfit'
        );

        if ($request->filled('export')) {
            if ($request->export == 'excel') {
                return $this->exportProfitLossExcel($viewData);
            } elseif ($request->export == 'pdf') {
                return $this->exportProfitLossPdf($viewData);
            }
        }

        return view('erp.reports.profit-loss', $viewData);
    }

    public function cashBookReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view financial reports')) {
            abort(403, 'Unauthorized action.');
        }
        return $this->financialBookReport($request, FinancialAccount::TYPE_CASH, 'Cash Book');
    }

    public function bankBookReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view financial reports')) {
            abort(403, 'Unauthorized action.');
        }
        return $this->financialBookReport($request, FinancialAccount::TYPE_BANK, 'Bank Book');
    }

    public function mobileBookReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view financial reports')) {
            abort(403, 'Unauthorized action.');
        }
        return $this->financialBookReport($request, FinancialAccount::TYPE_MOBILE, 'Mobile Book');
    }

    private function financialBookReport(Request $request, $type, $title)
    {
        $reportType = $request->get('report_type', 'daily');
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } elseif ($reportType == 'monthly') {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', Carbon::now()->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');

        $query = FinancialAccount::where('type', $type);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $accounts = $query->get()->map(function($account) use ($startDate, $endDate, $branchId) {
            // 1. Calculate opening balance before the start date
            $openingMovement = JournalEntry::where('financial_account_id', $account->id)
                ->whereHas('journal', function($q) use ($startDate, $branchId) {
                    $q->where('entry_date', '<', $startDate->toDateString());
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                })
                ->selectRaw('SUM(debit) as d, SUM(credit) as c')
                ->first();
            
            $opening = ($openingMovement->d ?? 0) - ($openingMovement->c ?? 0);

            // 2. Calculate balance during the period (Movements)
            $periodMovement = JournalEntry::where('financial_account_id', $account->id)
                ->whereHas('journal', function($q) use ($startDate, $endDate, $branchId) {
                    $q->whereBetween('entry_date', [$startDate->toDateString(), $endDate->toDateString()]);
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                })
                ->selectRaw('SUM(debit) as d, SUM(credit) as c')
                ->first();

            $account->opening = $opening;
            $account->debit = $periodMovement->d ?? 0;
            $account->credit = $periodMovement->c ?? 0;
            $account->closing = $opening + $account->debit - $account->credit;

            return $account;
        });

        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        if ($request->filled('export')) {
            $data = compact('accounts', 'startDate', 'endDate', 'title', 'branchId', 'branches');
            if ($request->export == 'excel') {
                return $this->exportFinancialBookExcel($data);
            } elseif ($request->export == 'pdf') {
                return $this->exportFinancialBookPdf($data);
            }
        }

        return view('erp.reports.financial-book', compact(
            'accounts', 'startDate', 'endDate', 'reportType', 'title', 'branches', 'branchId'
        ));
    }

    private function exportFinancialBookExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', $data['title']);
        $sheet->setCellValue('A2', 'Period: ' . $data['startDate']->format('d M Y') . ' to ' . $data['endDate']->format('d M Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Account Name', 'Branch', 'Opening', 'Debit', 'Credit', 'Closing'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $col++;
        }

        $row = 5;
        foreach ($data['accounts'] as $account) {
            $sheet->setCellValue('A' . $row, $account->account_holder_name ?? ($account->provider_name . ' - ' . $account->account_number));
            $sheet->setCellValue('B' . $row, $account->branch_name ?? '-');
            $sheet->setCellValue('C' . $row, $account->opening);
            $sheet->setCellValue('D' . $row, $account->debit);
            $sheet->setCellValue('E' . $row, $account->credit);
            $sheet->setCellValue('F' . $row, $account->closing);
            $row++;
        }

        $filename = str_replace(' ', '_', $data['title']) . "_" . date('Ymd') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportFinancialBookPdf($data)
    {
        $pdf = Pdf::loadView('erp.reports.pdf.financial-book', $data)->setPaper('a4', 'portrait');
        return $pdf->download(str_replace(' ', '_', $data['title']) . "_" . date('Y-m-d') . ".pdf");
    }

    private function exportProfitLossExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Profit & Loss Report');
        $sheet->setCellValue('A2', 'Period: ' . $data['startDate']->format('d M Y') . ' to ' . $data['endDate']->format('d M Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // INCOME SECTION
        $row = 4;
        $sheet->setCellValue('A'.$row, 'INCOME'); $sheet->setCellValue('B'.$row, 'AMOUNT');
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A'.$row, 'Sales Revenue'); $sheet->setCellValue('B'.$row, $data['operatingRevenue']); $row++;
        
        // Loop credit vouchers
        if($data['creditVoucherDetails']->isNotEmpty()){
            foreach($data['creditVoucherDetails'] as $detail){
                $sheet->setCellValue('A'.$row, $detail->name); 
                $sheet->setCellValue('B'.$row, $detail->amount); 
                $row++;
            }
        } else {
             $sheet->setCellValue('A'.$row, 'Credit Vouchers'); $sheet->setCellValue('B'.$row, $data['operatingRevenue']); $row++;
        }

        $sheet->setCellValue('A'.$row, 'Money Receipts'); $sheet->setCellValue('B'.$row, $data['moneyReceiptAmount']); $row++;
        $sheet->setCellValue('A'.$row, 'Purchase Returns'); $sheet->setCellValue('B'.$row, $data['purchaseReturnAmount']); $row++;
        $sheet->setCellValue('A'.$row, 'Exchange Adjustments'); $sheet->setCellValue('B'.$row, $data['exchangeAmount']); $row++;
        $sheet->setCellValue('A'.$row, 'Transfers In'); $sheet->setCellValue('B'.$row, $data['senderTransferAmount']); $row++;
        
        $sheet->setCellValue('A'.$row, 'TOTAL INCOME'); $sheet->setCellValue('B'.$row, $data['totalIncome']);
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $row += 2; // Spacer

        // EXPENSE SECTION
        $sheet->setCellValue('A'.$row, 'EXPENSES'); $sheet->setCellValue('B'.$row, 'AMOUNT');
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A'.$row, 'Cost of Goods Sold'); $sheet->setCellValue('B'.$row, $data['cogsAmount']); $row++;
        

        // Loop debit vouchers
        if($data['debitVoucherDetails']->isNotEmpty()){
             foreach($data['debitVoucherDetails'] as $detail){
                $sheet->setCellValue('A'.$row, $detail->name); 
                $sheet->setCellValue('B'.$row, $detail->amount); 
                $row++;
            }
        } else {
             $sheet->setCellValue('A'.$row, 'Debit Vouchers'); $sheet->setCellValue('B'.$row, $data['debitVoucher']); $row++;
        }

        $sheet->setCellValue('A'.$row, 'Employee Salaries'); $sheet->setCellValue('B'.$row, $data['employeePayment']); $row++;
        $sheet->setCellValue('A'.$row, 'Supplier Payments'); $sheet->setCellValue('B'.$row, $data['supplierPay']); $row++;
        $sheet->setCellValue('A'.$row, 'Sales Returns'); $sheet->setCellValue('B'.$row, $data['salesReturnAmount']); $row++;
        $sheet->setCellValue('A'.$row, 'Transfers Out'); $sheet->setCellValue('B'.$row, $data['receiverTransferAmount']); $row++;

        $sheet->setCellValue('A'.$row, 'TOTAL EXPENSE'); $sheet->setCellValue('B'.$row, $data['totalExpense']);
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
        $row += 2;

        $sheet->setCellValue('A'.$row, 'NET PROFIT / LOSS'); $sheet->setCellValue('B'.$row, $data['netProfit']);
        $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true)->setSize(12);

        $row += 2;
        $sheet->setCellValue('A'.$row, '* Current Stock Value (Asset Info)'); $sheet->setCellValue('B'.$row, $data['stockAmount']);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="ProfitLoss_Report_'.date('Ymd').'.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportProfitLossPdf($data)
    {
        $pdf = Pdf::loadView('erp.reports.pdf.profit-loss', $data)->setPaper('a4', 'portrait');
        return $pdf->download('ProfitLoss_Report_' . date('Y-m-d') . '.pdf');
    }

    public function customerReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }

        $reportType = $request->get('report_type', 'yearly');
        $now = Carbon::now();
        
        if ($reportType == 'daily') {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : $now->copy()->startOfDay();
            $endDate = $startDate->copy()->endOfDay();
        } elseif ($reportType == 'monthly') {
            $month = $request->get('month', $now->month);
            $year = $request->get('year', $now->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', $now->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else { // Custom
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : $now->copy()->startOfMonth();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : $now->copy()->endOfDay();
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');

        // Initial Customer Query with Filters
        $customerQuery = Customer::query();
        if ($request->filled('name')) {
            $customerQuery->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('phone')) {
            $customerQuery->where('mobile', 'like', '%' . $request->phone . '%');
        }

        // Handle Export
        $isExport = $request->get('export') == 'excel';
        
        if ($isExport) {
            $customers = $customerQuery->get();
        } else {
            $customers = $customerQuery->paginate(50)->appends($request->all());
        }

        $customerIds = $customers->pluck('id')->toArray();

        // 1. Sales & Exchange (from Pos table)
        $salesQuery = Pos::selectRaw('customer_id, 
            SUM(CASE WHEN sale_date < ? THEN total_amount ELSE 0 END) as opening_sales,
            SUM(CASE WHEN sale_date < ? THEN discount ELSE 0 END) as opening_discount,
            SUM(CASE WHEN sale_date < ? THEN exchange_amount ELSE 0 END) as opening_exchange,
            SUM(CASE WHEN sale_date BETWEEN ? AND ? THEN total_amount ELSE 0 END) as period_sales,
            SUM(CASE WHEN sale_date BETWEEN ? AND ? THEN discount ELSE 0 END) as period_discount,
            SUM(CASE WHEN sale_date BETWEEN ? AND ? THEN exchange_amount ELSE 0 END) as period_exchange
        ', [$startDate, $startDate, $startDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate])
        ->whereIn('customer_id', $customerIds)
        ->groupBy('customer_id');
        
        if ($branchId) $salesQuery->where('branch_id', $branchId);
        $salesStats = $salesQuery->get()->keyBy('customer_id');

        // 2. Payments (Mixed POS and Manual)
        $paymentQuery = Payment::query();
        if ($branchId) {
             $paymentQuery->where(function($q) use ($branchId) {
                $q->whereHas('pos', fn($pq) => $pq->where('branch_id', $branchId))
                  ->orWhereHas('invoice.pos', fn($ipq) => $ipq->where('branch_id', $branchId))
                  ->orWhere('payment_for', 'manual_receipt'); 
             });
        }
        
        $paymentStats = $paymentQuery->selectRaw('customer_id,
            SUM(CASE WHEN payment_date < ? THEN amount ELSE 0 END) as opening_paid,
            SUM(CASE WHEN payment_date BETWEEN ? AND ? AND payment_for = "manual_receipt" THEN amount ELSE 0 END) as period_manual,
            SUM(CASE WHEN payment_date BETWEEN ? AND ? AND (payment_for IS NULL OR payment_for != "manual_receipt") THEN amount ELSE 0 END) as period_paid
        ', [$startDate, $startDate, $endDate, $startDate, $endDate])
        ->whereIn('customer_id', $customerIds)
        ->whereNotNull('customer_id')
        ->groupBy('customer_id')
        ->get()
        ->keyBy('customer_id');

        // 3. Returns (SaleReturn)
        $returnQuery = SaleReturn::query()
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id');
        
        if ($branchId) $returnQuery->where('return_to_id', $branchId);

        $returnStats = $returnQuery->selectRaw('customer_id,
            SUM(CASE WHEN return_date < ? THEN sale_return_items.total_price ELSE 0 END) as opening_return,
            SUM(CASE WHEN return_date BETWEEN ? AND ? THEN sale_return_items.total_price ELSE 0 END) as period_return
        ', [$startDate, $startDate, $endDate])
        ->whereIn('customer_id', $customerIds)
        ->groupBy('customer_id')
        ->get()
        ->keyBy('customer_id');

        // 4. Initial Balances (from Balance table)
        $initialBalances = Balance::where('source_type', 'customer')
            ->whereIn('source_id', $customerIds)
            ->selectRaw('source_id as customer_id, 
                SUM(CASE WHEN created_at < ? THEN balance ELSE 0 END) as opening_bal
            ', [$startDate])
            ->groupBy('source_id')
            ->get()
            ->keyBy('customer_id');

        // Merge Data
        $customerCollection = $isExport ? $customers : $customers->getCollection();
        
        $customerCollection->transform(function($customer) use ($salesStats, $paymentStats, $returnStats, $initialBalances, $branchId) {
            $sid = $customer->id;
            
            $s = $salesStats[$sid] ?? null;
            $p = $paymentStats[$sid] ?? null;
            $r = $returnStats[$sid] ?? null;
            $b = $initialBalances[$sid] ?? null;

            $op_sales = $s->opening_sales ?? 0;
            $op_paid = $p->opening_paid ?? 0; 
            $op_return = $r->opening_return ?? 0;
            $op_exchange = $s->opening_exchange ?? 0;
            $op_init = $b->opening_bal ?? 0;

            $opening = $op_init + $op_sales - ($op_paid + $op_return + $op_exchange);

            $period_sales = $s->period_sales ?? 0;
            $period_manual = $p->period_manual ?? 0;
            $period_paid = $p->period_paid ?? 0; 
            $period_return = $r->period_return ?? 0;
            $period_exchange = $s->period_exchange ?? 0;
            $period_discount = $s->period_discount ?? 0;

            $due = $opening + $period_sales - ($period_paid + $period_manual + $period_return + $period_exchange);
            
            $customer->outlet = $branchId ? (\App\Models\Branch::find($branchId)->name ?? 'Main') : 'All';
            $customer->opening = $opening;
            $customer->sales = $period_sales;
            $customer->paid = $period_paid;
            $customer->payment = $period_manual;
            $customer->discount = $period_discount;
            $customer->return = $period_return;
            $customer->exchange = $period_exchange;
            $customer->due = $due;

            // Mark for removal if all values are zero
            $customer->is_empty = ($opening == 0 && $period_sales == 0 && $period_paid == 0 && $period_manual == 0 && $period_return == 0 && $period_exchange == 0 && $due == 0);

            return $customer;
        });

        if ($isExport) {
            return $this->exportCustomerReportExcelFile($customers, $startDate, $endDate);
        }

        // Filter out empty customers if desired (to avoid 2027 showing data for everyone)
        if ($request->filled('hide_empty') || $reportType != 'custom') {
             // Optional: You could filter here, but pagination might get weird. 
             // However, for "2027", we definitely want to see only those with standing balances or new activity.
        }

        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        if ($request->ajax()) {
            return view('erp.reports.partials.customer-report-table', compact('customers'))->render();
        }

        return view('erp.reports.customer-report', compact('customers', 'startDate', 'endDate', 'reportType', 'branches', 'branchId'));
    }

    public function customerLedger(Request $request, $id = null)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }

        $customerId = $id ?: $request->get('customer_id');
        $customers = Customer::orderBy('name')->get();
        $reportType = $request->get('report_type', 'all');
        $viewType = $request->get('view_type', 'debit_credit'); // 'debit_credit' or 'info_wise'
        $startDate = null;
        $endDate = null;
        
        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');

        if (!$customerId) {
            $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();
            return view('erp.reports.customer-ledger', compact('customers', 'branches', 'branchId', 'reportType', 'startDate', 'endDate', 'viewType'));
        }

        $customer = Customer::findOrFail($customerId);
        $now = Carbon::now();

        if ($reportType == 'daily') {
            $startDate = $now->copy()->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($reportType == 'monthly') {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        }

        // Opening Balance calculation (Consolidated)
        $openingBalance = 0;
        if ($startDate) {
            // 1. Initial Balance (No branch filtering usually, but we check if it exists before startDate)
            $op_init = Balance::where('source_type', 'customer')
                ->where('source_id', $customerId)
                ->where('created_at', '<', $startDate)
                ->sum('balance');

            // 2. Previous Sales
            $salesQ = Pos::where('customer_id', $customerId)->where('sale_date', '<', $startDate->toDateString());
            if ($branchId) $salesQ->where('branch_id', $branchId);
            $op_sales = $salesQ->sum('total_amount') + $salesQ->sum('exchange_amount');

            // 3. Previous Payments
            $payQ = Payment::where('customer_id', $customerId)->where('payment_date', '<', $startDate->toDateString());
            if ($branchId) {
                $payQ->where(function($q) use ($branchId) {
                    $q->whereHas('pos', fn($pq) => $pq->where('branch_id', $branchId))
                      ->orWhereHas('invoice.pos', fn($ipq) => $ipq->where('branch_id', $branchId))
                      ->orWhere('payment_for', 'manual_receipt');
                });
            }
            $op_paid = $payQ->sum('amount');

            // 4. Previous Returns
            $retQ = SaleReturn::where('customer_id', $customerId)->where('return_date', '<', $startDate->toDateString());
            if ($branchId) $retQ->where('return_to_id', $branchId)->where('return_to_type', 'branch');
            $op_return = $retQ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                               ->sum('sale_return_items.total_price');

            $openingBalance = $op_init + $op_sales - ($op_paid + $op_return);
        }

        // Transactions Fetching
        // 1. POS Sales (Debit)
        $posQuery = Pos::where('customer_id', $customerId);
        if ($branchId) $posQuery->where('branch_id', $branchId);
        if ($startDate) $posQuery->where('sale_date', '>=', $startDate->toDateString());
        if ($endDate) $posQuery->where('sale_date', '<=', $endDate->toDateString());
        $posSales = $posQuery->with('branch')->get()->map(fn($p) => [
            'date' => $p->sale_date,
            'type' => 'POS Sale',
            'reference' => $p->invoice_number,
            'debit' => $p->total_amount + $p->exchange_amount,
            'credit' => 0,
            'note' => $p->exchange_amount > 0 ? "Sale with Exchange (Net Debit)" : "POS Transaction",
            // Info wise details
            'invoice' => $p->invoice_number,
            'challan' => $p->challan_number ?? '-',
            'branch' => $p->branch->name ?? '-',
            'total' => $p->total_amount,
            'discount' => $p->discount_amount ?? 0,
            'paid' => 0,
            'due' => $p->total_amount - $p->discount_amount,
            'particulars' => 'POS Sale - ' . ($p->exchange_amount > 0 ? 'With Exchange' : 'Regular')
        ]);

        // 2. Payments (Credit)
        $payQuery = Payment::where('customer_id', $customerId);
        if ($branchId) {
            $payQuery->where(function($q) use ($branchId) {
                $q->whereHas('pos', fn($pq) => $pq->where('branch_id', $branchId))
                  ->orWhereHas('invoice.pos', fn($ipq) => $ipq->where('branch_id', $branchId))
                  ->orWhere('payment_for', 'manual_receipt');
            });
        }
        if ($startDate) $payQuery->where('payment_date', '>=', $startDate->toDateString());
        if ($endDate) $payQuery->where('payment_date', '<=', $endDate->toDateString());
        $payments = $payQuery->with(['pos.branch', 'invoice.pos.branch'])->get()->map(fn($p) => [
            'date' => $p->payment_date,
            'type' => 'Payment (' . str_replace('_', ' ', $p->payment_for) . ')',
            'reference' => $p->payment_reference ?: ($p->transaction_id ?: 'PAY-'.$p->id),
            'debit' => 0,
            'credit' => $p->amount,
            'note' => $p->note,
            // Info wise details
            'invoice' => $p->pos->invoice_number ?? ($p->invoice->invoice_number ?? '-'),
            'challan' => '-',
            'branch' => $p->pos->branch->name ?? ($p->invoice->pos->branch->name ?? ($branchId ? Branch::find($branchId)->name : '-')),
            'total' => 0,
            'discount' => 0,
            'paid' => $p->amount,
            'due' => 0,
            'particulars' => 'Payment - ' . str_replace('_', ' ', $p->payment_for)
        ]);

        // 3. Returns (Credit)
        $retQuery = SaleReturn::where('customer_id', $customerId);
        if ($branchId) {
            $retQuery->where('return_to_id', $branchId)->where('return_to_type', 'branch');
        }
        if ($startDate) $retQuery->where('return_date', '>=', $startDate->toDateString());
        if ($endDate) $retQuery->where('return_date', '<=', $endDate->toDateString());
        $returns = $retQuery->with(['items', 'posSale.branch'])->get()->map(fn($r) => [
            'date' => $r->return_date,
            'type' => 'Sale Return',
            'reference' => 'RET-'.$r->id,
            'debit' => 0,
            'credit' => $r->items->sum('total_price'),
            'note' => $r->reason,
            // Info wise details
            'invoice' => $r->posSale->invoice_number ?? '-',
            'challan' => '-',
            'branch' => $r->posSale->branch->name ?? ($branchId ? Branch::find($branchId)->name : '-'),
            'total' => 0,
            'discount' => 0,
            'paid' => 0,
            'due' => $r->items->sum('total_price'),
            'particulars' => 'Return - ' . $r->reason
        ]);

        $transactions = $posSales->concat($payments)->concat($returns)->sortBy('date');
        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        // Calculate summary data for info wise view
        $totalSales = $posSales->sum('total');
        $totalDiscount = $posSales->sum('discount');
        $totalPaid = $payments->sum('paid');
        $totalReturn = $returns->sum('due');
        $totalExchange = $posSales->sum('exchange_amount');
        $totalDueAmount = ($openingBalance ?? 0) + $totalSales - $totalPaid - $totalReturn;

        if ($request->get('export') == 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.reports.pdf.customer-ledger', compact(
                'customer', 'transactions', 'openingBalance', 'startDate', 'endDate', 'branches', 'branchId', 'viewType',
                'totalSales', 'totalDiscount', 'totalPaid', 'totalReturn', 'totalExchange', 'totalDueAmount'
            ))->setPaper('a4', 'portrait');
            
            return $pdf->download("Customer_Ledger_{$customer->name}.pdf");
        }

        if ($request->get('export') == 'excel') {
            $filename = "Customer_Ledger_{$customer->name}_" . date('Ymd_His') . ".xlsx";
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header
            $sheet->setCellValue('A1', "Statement of Customer Ledger: " . $customer->name);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            
            $sheet->setCellValue('A2', "Period: " . ($startDate ? $startDate->format('d M, Y') : 'Life-to-date') . " - " . ($endDate ? $endDate->format('d M, Y') : date('d M, Y')));
            
            if ($viewType == 'info_wise') {
                // Info Wise Excel Export
                $headers = ['SN', 'Date', 'Invoice', 'Challan', 'Branch', 'Particulars', 'Total', 'Discount', 'Paid', 'Due'];
                foreach ($headers as $index => $header) {
                    $sheet->setCellValue(chr(65 + $index) . '4', $header);
                    $sheet->getStyle(chr(65 + $index) . '4')->getFont()->setBold(true);
                    $sheet->getStyle(chr(65 + $index) . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF166534');
                    $sheet->getStyle(chr(65 + $index) . '4')->getFont()->getColor()->setARGB('FFFFFFFF');
                }
                
                $row = 5;
                $sn = 1;
                
                foreach ($transactions as $txn) {
                    $sheet->setCellValue('A' . $row, $sn++);
                    $sheet->setCellValue('B' . $row, \Carbon\Carbon::parse($txn['date'])->format('d M, Y'));
                    $sheet->setCellValue('C' . $row, $txn['invoice'] ?? '-');
                    $sheet->setCellValue('D' . $row, $txn['challan'] ?? '-');
                    $sheet->setCellValue('E' . $row, $txn['branch'] ?? '-');
                    $sheet->setCellValue('F' . $row, $txn['particulars'] ?? $txn['type']);
                    $sheet->setCellValue('G' . $row, $txn['total'] > 0 ? number_format($txn['total'], 2) : '-');
                    $sheet->setCellValue('H' . $row, $txn['discount'] > 0 ? number_format($txn['discount'], 2) : '-');
                    $sheet->setCellValue('I' . $row, $txn['paid'] > 0 ? number_format($txn['paid'], 2) : '-');
                    
                    // Due column with advance/due distinction
                    if ($txn['due'] > 0) {
                        $sheet->setCellValue('J' . $row, 'Due: ' . number_format($txn['due'], 2));
                        $sheet->getStyle('J' . $row)->getFont()->getColor()->setARGB('FFFF0000'); // Red text
                    } elseif ($txn['due'] < 0) {
                        $sheet->setCellValue('J' . $row, 'Advance: ' . number_format(abs($txn['due']), 2));
                        $sheet->getStyle('J' . $row)->getFont()->getColor()->setARGB('FF0000FF'); // Blue text
                    } else {
                        $sheet->setCellValue('J' . $row, '-');
                    }
                    $row++;
                }
                
                // Summary Footer
                $sheet->setCellValue('F' . $row, 'TOTAL');
                $sheet->setCellValue('G' . $row, number_format($totalSales ?? 0, 2));
                $sheet->setCellValue('H' . $row, number_format($totalDiscount ?? 0, 2));
                $sheet->setCellValue('I' . $row, number_format($totalPaid ?? 0, 2));
                
                // Total due with advance/due distinction
                if ($totalDueAmount > 0) {
                    $sheet->setCellValue('J' . $row, 'Due: ' . number_format($totalDueAmount, 2));
                    $sheet->getStyle('J' . $row)->getFont()->getColor()->setARGB('FFFF0000'); // Red text
                } elseif ($totalDueAmount < 0) {
                    $sheet->setCellValue('J' . $row, 'Advance: ' . number_format(abs($totalDueAmount), 2));
                    $sheet->getStyle('J' . $row)->getFont()->getColor()->setARGB('FF0000FF'); // Blue text
                } else {
                    $sheet->setCellValue('J' . $row, '0.00');
                }
                
                $sheet->getStyle('F'.$row.':J'.$row)->getFont()->setBold(true);
                $sheet->getStyle('F'.$row.':J'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF166534');
                $sheet->getStyle('F'.$row.':J'.$row)->getFont()->getColor()->setARGB('FFFFFFFF');
                
                // Set column widths and alignment
                $sheet->getColumnDimension('A')->setWidth(8);  // SN
                $sheet->getColumnDimension('B')->setWidth(15); // Date
                $sheet->getColumnDimension('C')->setWidth(15); // Invoice
                $sheet->getColumnDimension('D')->setWidth(15); // Challan
                $sheet->getColumnDimension('E')->setWidth(12); // Branch
                $sheet->getColumnDimension('F')->setWidth(25); // Particulars
                $sheet->getColumnDimension('G')->setWidth(12); // Total
                $sheet->getColumnDimension('H')->setWidth(12); // Discount
                $sheet->getColumnDimension('I')->setWidth(12); // Paid
                $sheet->getColumnDimension('J')->setWidth(18); // Due
                
                // Set alignment for numeric columns
                foreach (['G', 'H', 'I', 'J'] as $col) {
                    $sheet->getStyle($col . '5:' . $col . ($row-1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                
                // Set text wrapping for particulars column
                $sheet->getStyle('F5:F' . ($row-1))->getAlignment()->setWrapText(true);
            } else {
                // Debit/Credit Wise Excel Export (Original)
                $headers = ['Date', 'Transaction Detail', 'Reference', 'Debit', 'Credit', 'Balance'];
                foreach ($headers as $index => $header) {
                    $sheet->setCellValue(chr(65 + $index) . '4', $header);
                    $sheet->getStyle(chr(65 + $index) . '4')->getFont()->setBold(true);
                    $sheet->getStyle(chr(65 + $index) . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCACACA');
                }
                
                $row = 5;
                $runningBalance = $openingBalance ?? 0;
                
                // Opening Balance
                $sheet->setCellValue('A' . $row, '-');
                $sheet->setCellValue('B' . $row, 'PREVIOUS OPENING BALANCE');
                $sheet->setCellValue('C' . $row, '-');
                $sheet->setCellValue('D' . $row, '-');
                $sheet->setCellValue('E' . $row, '-');
                $sheet->setCellValue('F' . $row, number_format($runningBalance, 2) . ($runningBalance > 0 ? ' DR' : ' CR'));
                $sheet->getStyle('B' . $row)->getFont()->setItalic(true);
                $row++;
                
                foreach ($transactions as $txn) {
                    $runningBalance += ($txn['debit'] - $txn['credit']);
                    $sheet->setCellValue('A' . $row, \Carbon\Carbon::parse($txn['date'])->format('d M, Y'));
                    $sheet->setCellValue('B' . $row, $txn['type'] . ($txn['note'] ? " ({$txn['note']})" : ""));
                    $sheet->setCellValue('C' . $row, $txn['reference']);
                    $sheet->setCellValue('D' . $row, $txn['debit']);
                    $sheet->setCellValue('E' . $row, $txn['credit']);
                    $sheet->setCellValue('F' . $row, number_format(abs($runningBalance), 2) . ($runningBalance > 0 ? ' DR' : ' CR'));
                    $row++;
                }
                
                // Summary Footer
                $sheet->setCellValue('C' . $row, 'TOTAL');
                $sheet->setCellValue('D' . $row, $transactions->sum('debit'));
                $sheet->setCellValue('E' . $row, $transactions->sum('credit'));
                $sheet->setCellValue('F' . $row, number_format(abs($runningBalance), 2) . ($runningBalance > 0 ? ' FINAL DUE' : ' FINAL ADVANCE'));
                $sheet->getStyle('C'.$row.':F'.$row)->getFont()->setBold(true);
                
                foreach (range('A', 'F') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filePath = storage_path('app/public/' . $filename);
            $writer->save($filePath);
            
            return response()->download($filePath, $filename)->deleteFileAfterSend();
        }

        return view('erp.reports.customer-ledger', compact('customer', 'customers', 'transactions', 'openingBalance', 'startDate', 'endDate', 'reportType', 'branches', 'branchId', 'viewType', 'totalSales', 'totalDiscount', 'totalPaid', 'totalReturn', 'totalExchange', 'totalDueAmount'));
    }

    public function supplierReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }

        $reportType = $request->get('report_type', 'daily');
        $now = Carbon::now();

        if ($reportType == 'monthly') {
            $month = $request->get('month', $now->month);
            $year = $request->get('year', $now->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', $now->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } elseif ($reportType == 'custom') {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : $now->copy()->startOfMonth();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : $now->copy()->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');

        $suppliers = Supplier::orderBy('name')->get()->map(function($s) use ($startDate, $endDate, $branchId) {
            $sid = $s->id;

            // 1. Purchase Bills
            $billQuery = PurchaseBill::where('supplier_id', $sid);
            if ($branchId) {
                $billQuery->whereHas('purchase', fn($q) => $q->where('location_id', $branchId)->where('ship_location_type', 'branch'));
            }
            
            $op_purchase = (clone $billQuery)->where('bill_date', '<', $startDate->toDateString())->sum('total_amount');
            $period_purchase = (clone $billQuery)->whereBetween('bill_date', [$startDate->toDateString(), $endDate->toDateString()])->sum('total_amount');

            // 2. Payments (Granular)
            $payQuery = SupplierPayment::where('supplier_id', $sid);
            if ($branchId) {
                $payQuery->whereHas('bill.purchase', fn($q) => $q->where('location_id', $branchId)->where('ship_location_type', 'branch'));
            }

            $op_paid = (clone $payQuery)->where('payment_date', '<', $startDate->toDateString())->sum('amount');
            
            // Period Paid (Regular bill payments)
            $period_paid = (clone $payQuery)->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->whereNotNull('purchase_bill_id')
                ->sum('amount');
            
            // Period Payment (Advance or manual)
            $period_manual = (clone $payQuery)->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->whereNull('purchase_bill_id')
                ->sum('amount');

            // 3. Returns
            $returnQuery = PurchaseReturn::where('supplier_id', $sid);
            if ($branchId) {
                $returnQuery->whereHas('items', fn($q) => $q->where('return_from_type', 'branch')->where('return_from_id', $branchId));
            }

            $op_return = (clone $returnQuery)->where('return_date', '<', $startDate->toDateString())->get()->sum('total_amount');
            $period_return = (clone $returnQuery)->whereBetween('return_date', [$startDate->toDateString(), $endDate->toDateString()])->get()->sum('total_amount');

            $opening = $op_purchase - ($op_paid + $op_return);
            $due = $opening + $period_purchase - ($period_paid + $period_manual + $period_return);

            return (object)[
                'id' => $sid,
                'name' => $s->name,
                'mobile' => $s->phone,
                'supplier_id' => $s->supplier_id ?? 'SUP-'.$sid,
                'opening' => $opening,
                'purchase' => $period_purchase,
                'paid' => $period_paid,
                'payment' => $period_manual,
                'return' => $period_return,
                'due' => $due,
                'outlet' => $branchId ? (\App\Models\Branch::find($branchId)->name ?? 'Branch') : 'All Outlets',
                'is_empty' => ($opening == 0 && $period_purchase == 0 && $period_paid == 0 && $period_manual == 0 && $period_return == 0 && $due == 0)
            ];
        });

        $totals = [
            'opening' => $suppliers->sum('opening'),
            'purchase' => $suppliers->sum('purchase'),
            'paid' => $suppliers->sum('paid'),
            'payment' => $suppliers->sum('payment'),
            'return' => $suppliers->sum('return'),
            'due' => $suppliers->sum('due')
        ];

        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        if ($request->get('export') == 'excel') {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            $sheet->setCellValue('A1', 'Supplier Summary Report');
            $sheet->mergeCells('A1:K1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->setCellValue('A2', "Period: " . $startDate->format('d M, Y') . " - " . $endDate->format('d M, Y'));
            $sheet->setCellValue('A3', "Outlet: " . ($branchId ? (\App\Models\Branch::find($branchId)->name ?? 'All') : 'All Outlets'));

            $headers = ['#SN', 'Supplier Name', 'Supplier ID', 'Mobile', 'Outlet', 'Opening', 'Total', 'Paid', 'Payment', 'Return', 'Due'];
            foreach ($headers as $i => $h) {
                $col = chr(65 + $i);
                $sheet->setCellValue($col . '5', $h);
                $sheet->getStyle($col . '5')->getFont()->setBold(true);
                $sheet->getStyle($col . '5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF1B4D3E');
                $sheet->getStyle($col . '5')->getFont()->getColor()->setARGB('FFFFFFFF');
            }

            $row = 6;
            $sl = 1;
            foreach ($suppliers as $s) {
                if ($s->is_empty) continue;
                $sheet->setCellValue('A' . $row, $sl++);
                $sheet->setCellValue('B' . $row, $s->name);
                $sheet->setCellValue('C' . $row, $s->supplier_id);
                $sheet->setCellValue('D' . $row, $s->mobile);
                $sheet->setCellValue('E' . $row, $s->outlet);
                $sheet->setCellValue('F' . $row, $s->opening);
                $sheet->setCellValue('G' . $row, $s->purchase);
                $sheet->setCellValue('H' . $row, $s->paid);
                $sheet->setCellValue('I' . $row, $s->payment);
                $sheet->setCellValue('J' . $row, $s->return);
                $sheet->setCellValue('K' . $row, $s->due);
                $sheet->getStyle('F' . $row . ':K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;
            }

            // Total Row
            $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('F' . $row, $totals['opening']);
            $sheet->setCellValue('G' . $row, $totals['purchase']);
            $sheet->setCellValue('H' . $row, $totals['paid']);
            $sheet->setCellValue('I' . $row, $totals['payment']);
            $sheet->setCellValue('J' . $row, $totals['return']);
            $sheet->setCellValue('K' . $row, $totals['due']);
            $sheet->getStyle('A' . $row . ':K' . $row)->getFont()->setBold(true);
            $sheet->getStyle('F' . $row . ':K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');

            foreach (range('A', 'K') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

            $filename = "Supplier_Summary_" . date('Ymd_His') . ".xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }

        if ($request->ajax()) {
            return view('erp.reports.partials.supplier-report-table', compact('suppliers', 'totals'))->render();
        }

        return view('erp.reports.supplier-report', compact('suppliers', 'branches', 'branchId', 'reportType', 'startDate', 'endDate', 'totals'));
    }

    public function supplierLedger(Request $request, $id = null)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $id = $id ?: $request->get('supplier_id');
        $suppliers = Supplier::orderBy('name')->get();
        if (!$id) {
            $startDate = null; $endDate = null; $reportType = 'all'; $branchId = null;
            $branches = \App\Models\Branch::all();
            return view('erp.reports.supplier-ledger', compact('suppliers', 'branches', 'branchId', 'startDate', 'endDate', 'reportType'));
        }

        $supplier = Supplier::findOrFail($id);
        $reportType = $request->get('report_type', 'all');
        $now = Carbon::now();

        if ($reportType == 'daily') {
            $startDate = $now->copy()->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($reportType == 'monthly') {
            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', date('Y'));
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : $now->copy()->endOfDay();
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');

        // Helper for Stats
        $getStats = function($from = null, $to = null) use ($id, $branchId) {
            $purchaseQuery = PurchaseBill::where('supplier_id', $id);
            if ($branchId) {
                $purchaseQuery->whereHas('purchase', function($q) use ($branchId) {
                    $q->where('location_id', $branchId)->where('ship_location_type', 'branch');
                });
            }
            if ($from) $purchaseQuery->where('bill_date', '>=', $from->toDateString());
            if ($to) $purchaseQuery->where('bill_date', '<=', $to->toDateString());
            $purchases = $purchaseQuery->get()->map(fn($p) => [
                'date' => $p->bill_date,
                'type' => 'Purchase Bill',
                'reference' => $p->bill_number,
                'debit' => 0,
                'credit' => $p->total_amount,
                'note' => $p->description
            ]);

            $payQuery = SupplierPayment::where('supplier_id', $id);
            if ($branchId) {
                $payQuery->whereHas('bill.purchase', function($q) use ($branchId) {
                    $q->where('location_id', $branchId)->where('ship_location_type', 'branch');
                });
            }
            if ($from) $payQuery->where('payment_date', '>=', $from->toDateString());
            if ($to) $payQuery->where('payment_date', '<=', $to->toDateString());
            $payments = $payQuery->get()->map(fn($p) => [
                'date' => $p->payment_date,
                'type' => 'Payment',
                'reference' => $p->reference ?: 'PAY-'.$p->id,
                'debit' => $p->amount,
                'credit' => 0,
                'note' => $p->note
            ]);

            $retQuery = PurchaseReturn::where('supplier_id', $id);
            if ($branchId) {
                $retQuery->whereHas('items', function($q) use ($branchId) {
                    $q->where('return_from_type', 'branch')->where('return_from_id', $branchId);
                });
            }
            if ($from) $retQuery->where('return_date', '>=', $from->toDateString());
            if ($to) $retQuery->where('return_date', '<=', $to->toDateString());
            $returns = $retQuery->with('items')->get()->map(fn($r) => [
                'date' => $r->return_date,
                'type' => 'Purchase Return',
                'reference' => 'RET-'.$r->id,
                'debit' => $r->total_amount,
                'credit' => 0,
                'note' => $r->notes
            ]);

            return $purchases->concat($payments)->concat($returns);
        };

        $openingBalance = 0;
        if ($startDate) {
            $openingStats = $getStats(null, $startDate->copy()->subDay());
            $openingBalance = $openingStats->sum('credit') - $openingStats->sum('debit');
        }

        $transactions = $getStats($startDate, $endDate)->sortBy('date');
        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        if ($request->get('export') == 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.reports.pdf.supplier-ledger', compact(
                'supplier', 'transactions', 'openingBalance', 'startDate', 'endDate', 'branches', 'branchId'
            ))->setPaper('a4', 'portrait');
            return $pdf->download("Supplier_Ledger_{$supplier->name}.pdf");
        }

        if ($request->get('export') == 'excel') {
            $filename = "Supplier_Ledger_{$supplier->name}_" . date('Ymd_His') . ".xlsx";
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header matching Customer Ledger
            $sheet->setCellValue('A1', "Supplier Ledger Account: " . $supplier->name);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->setCellValue('A2', "Period: " . ($startDate ? $startDate->format('d M, Y') : 'Life-to-date') . " - " . ($endDate ? $endDate->format('d M, Y') : date('d M, Y')));
            
            // Info Wise Headers matching Customer Ledger
            $headers = ['SN', 'Date', 'Bill/Challan', 'Particulars', 'Total', 'Paid', 'Due'];
            foreach ($headers as $index => $header) {
                $cell = chr(65 + $index) . '4';
                $sheet->setCellValue($cell, $header);
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF166534');
                $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
            }
            
            $row = 5; 
            $sn = 1;
            $runningBalance = $openingBalance;
            
            // Opening Balance Row
            if ($runningBalance != 0) {
                $sheet->setCellValue('A'.$row, '-');
                $sheet->setCellValue('B'.$row, '-');
                $sheet->setCellValue('C'.$row, '-');
                $sheet->setCellValue('D'.$row, 'Opening Balance');
                $sheet->setCellValue('E'.$row, '-');
                $sheet->setCellValue('F'.$row, '-');
                $sheet->setCellValue('G'.$row, (float) abs($runningBalance));
                if ($runningBalance > 0) {
                    $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('"Due: "#,##0.00');
                    $sheet->getStyle('G'.$row)->getFont()->getColor()->setARGB('FFFF0000'); // Red
                } else {
                    $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('"Advance: "#,##0.00');
                    $sheet->getStyle('G'.$row)->getFont()->getColor()->setARGB('FF0000FF'); // Blue
                }
                $row++;
            }
            
            foreach ($transactions as $txn) {
                // Calculate values
                if($txn['type'] == 'Purchase Bill') {
                    $txnPaid = 0;
                    $runningBalance += $txn['credit'];
                } elseif($txn['type'] == 'Payment') {
                    $txnPaid = $txn['debit'];
                    $runningBalance -= $txn['debit'];
                } elseif($txn['type'] == 'Purchase Return') {
                    $txnPaid = $txn['debit'];
                    $runningBalance -= $txn['debit'];
                } else {
                    $txnPaid = $txn['debit'];
                    $runningBalance += ($txn['credit'] - $txn['debit']);
                }
                
                $sheet->setCellValue('A'.$row, $sn++);
                $sheet->setCellValue('B'.$row, Carbon::parse($txn['date'])->format('d M, Y'));
                $sheet->setCellValue('C'.$row, $txn['reference'] ?? '-');
                $sheet->setCellValue('D'.$row, $txn['type'] . ($txn['note'] ? ' - ' . $txn['note'] : ''));
                
                if ($txn['credit'] > 0) {
                    $sheet->setCellValue('E'.$row, (float) $txn['credit']);
                    $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                } else {
                    $sheet->setCellValue('E'.$row, '-');
                }
                
                if ($txnPaid > 0) {
                    $sheet->setCellValue('F'.$row, (float) $txnPaid);
                    $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                } else {
                    $sheet->setCellValue('F'.$row, '-');
                }
                
                // Due column with advance/due distinction
                if ($runningBalance != 0) {
                    $sheet->setCellValue('G'.$row, (float) abs($runningBalance));
                    if ($runningBalance > 0) {
                        $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('"Due: "#,##0.00');
                        $sheet->getStyle('G'.$row)->getFont()->getColor()->setARGB('FFFF0000'); // Red text
                    } else {
                        $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('"Advance: "#,##0.00');
                        $sheet->getStyle('G'.$row)->getFont()->getColor()->setARGB('FF0000FF'); // Blue text
                    }
                } else {
                    $sheet->setCellValue('G'.$row, '-');
                }
                
                $row++;
            }
            
            // Total Footer
            $finalBal = ($openingBalance ?? 0) + $transactions->sum('credit') - $transactions->sum('debit');
            $sheet->setCellValue('D'.$row, 'TOTAL');
            $sheet->getStyle('D'.$row)->getFont()->setBold(true);
            
            $sheet->setCellValue('E'.$row, (float) $transactions->sum('credit'));
            $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E'.$row)->getFont()->setBold(true);
            
            $sheet->setCellValue('F'.$row, (float) $transactions->sum('debit'));
            $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F'.$row)->getFont()->setBold(true);
            
            $sheet->getStyle('G'.$row)->getFont()->setBold(true);
            if ($finalBal != 0) {
                $sheet->setCellValue('G'.$row, (float) abs($finalBal));
                if ($finalBal > 0) {
                    $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('"Due: "#,##0.00');
                    $sheet->getStyle('G'.$row)->getFont()->getColor()->setARGB('FFFF0000');
                } else {
                    $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('"Advance: "#,##0.00');
                    $sheet->getStyle('G'.$row)->getFont()->getColor()->setARGB('FF0000FF');
                }
            } else {
                $sheet->setCellValue('G'.$row, 0.00);
                $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $sheet->getStyle('G'.$row)->getFont()->setBold(true);
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(30);
            $sheet->getColumnDimension('E')->setWidth(12);
            $sheet->getColumnDimension('F')->setWidth(12);
            $sheet->getColumnDimension('G')->setWidth(20);
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $path = storage_path('app/public/'.$filename);
            $writer->save($path);
            return response()->download($path, $filename)->deleteFileAfterSend();
        }

        return view('erp.reports.supplier-ledger', compact('supplier', 'suppliers', 'transactions', 'openingBalance', 'startDate', 'endDate', 'reportType', 'branches', 'branchId'));
    }


    public function stockReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');
        $warehouseId = $request->get('warehouse_id');

        $query = Product::with(['category', 'brand', 'branchStocks.branch', 'warehouseStocks.warehouse', 'variationStocks'])
            ->where('type', 'product');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get()->map(function($p) use ($branchId, $warehouseId) {
            if ($p->has_variations) {
                if ($branchId) {
                    $p->total_stock = $p->variationStocks->where('branch_id', $branchId)->sum('quantity');
                } elseif ($warehouseId) {
                    $p->total_stock = $p->variationStocks->where('warehouse_id', $warehouseId)->sum('quantity');
                } else {
                    $p->total_stock = $p->variationStocks->sum('quantity');
                }
            } else {
                if ($branchId) {
                    $p->total_stock = $p->branchStocks->where('branch_id', $branchId)->sum('quantity');
                } elseif ($warehouseId) {
                    $p->total_stock = $p->warehouseStocks->where('warehouse_id', $warehouseId)->sum('quantity');
                } else {
                    $p->total_stock = $p->branchStocks->sum('quantity') + $p->warehouseStocks->sum('quantity');
                }
            }
            
            $p->stock_value = $p->total_stock * $p->cost;
            $p->potential_revenue = $p->total_stock * $p->price;
            return $p;
        });

        $categories = ProductServiceCategory::whereNull('parent_id')->get();
        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();
        $warehouses = \App\Models\Warehouse::all();

        return view('erp.reports.stock-report', compact('products', 'categories', 'branches', 'branchId', 'warehouses', 'warehouseId'));
    }

    public function executiveReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view executive reports')) {
            abort(403, 'Unauthorized action.');
        }
        $reportType = $request->get('report_type', 'daily');
        $branchId = $request->get('branch_id');
        $restrictedBranchId = $this->getRestrictedBranchId();
        
        if ($restrictedBranchId) {
            $branchId = $restrictedBranchId;
        }

        $now = Carbon::now();
        if ($reportType == 'monthly') {
            $month = $request->get('month', $now->month);
            $year = $request->get('year', $now->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } elseif ($reportType == 'yearly') {
            $year = $request->get('year', $now->year);
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        } else {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : $now->copy()->startOfDay();
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : $now->copy()->endOfDay();
        }

        // --- ACCOUNT IDS (Dynamic Mapping from Financial Accounts) ---
        $cashAccIds = \App\Models\FinancialAccount::where('type', 'cash')->pluck('account_id')->unique()->toArray();
        $bankAccIds = \App\Models\FinancialAccount::where('type', 'bank')->pluck('account_id')->unique()->toArray();
        $walletAccIds = \App\Models\FinancialAccount::where('type', 'mobile')->pluck('account_id')->unique()->toArray();

        // Fallback to legacy defaults if no accounts are mapped (for backward compatibility)
        if (empty($cashAccIds)) $cashAccIds = [14];
        if (empty($bankAccIds)) $bankAccIds = [15];
        if (empty($walletAccIds)) $walletAccIds = [16];

        // Helper function for calculation
        $getBalance = function($accIds, $to) use ($branchId) {
            $query = \App\Models\JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                ->leftJoin('financial_accounts', 'journal_entries.financial_account_id', '=', 'financial_accounts.id')
                ->whereIn('journal_entries.chart_of_account_id', (array)$accIds)
                ->where('journals.entry_date', '<=', $to->toDateString());
            
            if ($branchId) {
                $query->where(function($q) use ($branchId) {
                    $q->where('financial_accounts.branch_id', $branchId)
                      ->orWhere(function($sq) use ($branchId) {
                          $sq->whereNull('journal_entries.financial_account_id')
                             ->where('journals.branch_id', $branchId);
                      });
                });
            }

            $stats = $query->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();
            return ($stats->total_debit ?? 0) - ($stats->total_credit ?? 0);
        };

        // Current Balances (at the end of selected period)
        $cashBalance = $getBalance($cashAccIds, $endDate);
        $bankBalance = $getBalance($bankAccIds, $endDate);
        $walletBalance = $getBalance($walletAccIds, $endDate);
        $totalLiquidity = $cashBalance + $bankBalance + $walletBalance;

        // Opening Balances (at the start of selected period)
        $openingDate = $startDate->copy()->subDay();
        $openingCash = $getBalance($cashAccIds, $openingDate);
        $openingBank = $getBalance($bankAccIds, $openingDate);
        $openingWallet = $getBalance($walletAccIds, $openingDate);

        // Transactions in Period
        $getInflowOutflow = function($accIds, $from, $to) use ($branchId) {
            $query = \App\Models\JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                ->leftJoin('financial_accounts', 'journal_entries.financial_account_id', '=', 'financial_accounts.id')
                ->whereIn('journal_entries.chart_of_account_id', (array)$accIds)
                ->whereBetween('journals.entry_date', [$from->toDateString(), $to->toDateString()]);
            
            if ($branchId) {
                $query->where(function($q) use ($branchId) {
                    $q->where('financial_accounts.branch_id', $branchId)
                      ->orWhere(function($sq) use ($branchId) {
                          $sq->whereNull('journal_entries.financial_account_id')
                             ->where('journals.branch_id', $branchId);
                      });
                });
            }

            return $query->selectRaw('SUM(journal_entries.debit) as inflow, SUM(journal_entries.credit) as outflow')->first();
        };

        $cashStats = $getInflowOutflow($cashAccIds, $startDate, $endDate);
        $bankStats = $getInflowOutflow($bankAccIds, $startDate, $endDate);
        $walletStats = $getInflowOutflow($walletAccIds, $startDate, $endDate);

        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        $data = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportType' => $reportType,
            'branchId' => $branchId,
            'branches' => $branches,
            'cashBalance' => $cashBalance,
            'bankBalance' => $bankBalance,
            'walletBalance' => $walletBalance,
            'totalLiquidity' => $totalLiquidity,
            'openingCash' => $openingCash,
            'openingBank' => $openingBank,
            'openingWallet' => $openingWallet,
            'cashIn' => $cashStats->inflow ?? 0,
            'cashOut' => $cashStats->outflow ?? 0,
            'bankIn' => $bankStats->inflow ?? 0,
            'bankOut' => $bankStats->outflow ?? 0,
            'walletIn' => $walletStats->inflow ?? 0,
            'walletOut' => $walletStats->outflow ?? 0,
            'month' => $request->get('month', $now->month),
            'year' => $request->get('year', $now->year)
        ];

        if ($request->filled('export')) {
            if ($request->export == 'excel') {
                return $this->exportExecutiveExcel($data);
            } elseif ($request->export == 'pdf') {
                return $this->exportExecutivePdf($data);
            }
        }

        if ($request->ajax()) {
            return view('erp.reports.executive-report-partial', $data);
        }

        return view('erp.reports.executive-report', $data);
    }

    private function exportExecutiveExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Financial Liquidity Report - Sisal Fashion');
        $sheet->setCellValue('A2', 'Period: ' . $data['startDate']->format('d M Y') . ' to ' . $data['endDate']->format('d M Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $headers = ['Account Name', 'Opening Balance', 'Total Inflow (+)', 'Total Outflow (-)', 'Closing Balance'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $col++;
        }

        $row = 5;
        // Cash
        $sheet->setCellValue('A' . $row, 'Main Cash');
        $sheet->setCellValue('B' . $row, $data['openingCash']);
        $sheet->setCellValue('C' . $row, $data['cashIn']);
        $sheet->setCellValue('D' . $row, $data['cashOut']);
        $sheet->setCellValue('E' . $row, $data['cashBalance']);
        $row++;

        // Bank
        $sheet->setCellValue('A' . $row, 'Bank Account');
        $sheet->setCellValue('B' . $row, $data['openingBank']);
        $sheet->setCellValue('C' . $row, $data['bankIn']);
        $sheet->setCellValue('D' . $row, $data['bankOut']);
        $sheet->setCellValue('E' . $row, $data['bankBalance']);
        $row++;

        // Wallet
        $sheet->setCellValue('A' . $row, 'Mobile Wallets');
        $sheet->setCellValue('B' . $row, $data['openingWallet']);
        $sheet->setCellValue('C' . $row, $data['walletIn']);
        $sheet->setCellValue('D' . $row, $data['walletOut']);
        $sheet->setCellValue('E' . $row, $data['walletBalance']);
        $row++;

        // Total
        $sheet->setCellValue('A' . $row, 'TOTAL LIQUIDITY');
        $sheet->setCellValue('B' . $row, $data['openingCash'] + $data['openingBank'] + $data['openingWallet']);
        $sheet->setCellValue('C' . $row, $data['cashIn'] + $data['bankIn'] + $data['walletIn']);
        $sheet->setCellValue('D' . $row, $data['cashOut'] + $data['bankOut'] + $data['walletOut']);
        $sheet->setCellValue('E' . $row, $data['totalLiquidity']);
        $sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true);

        foreach (range('A', 'E') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $filename = "Liquidity_Report_".date('Ymd').".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportExecutivePdf($data)
    {
        // For simplicity, we can use the same partial view or a dedicated PDF view
        // Reusing a simplified version for PDF
        $pdf = Pdf::loadView('erp.reports.pdf.liquidity', $data)->setPaper('a4', 'portrait');
        return $pdf->download('Liquidity_Report_' . date('Y-m-d') . '.pdf');
    }

    public function expenseReport(Request $request)
    {
        $reportType = $request->get('report_type', 'daily'); // Just for UI state
        
        // Date Logic: Always respect the input dates if provided, otherwise default to today.
        // The frontend toggles will handle setting these inputs.
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } else {
            $endDate = Carbon::now()->endOfDay();
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $branchId = $restrictedBranchId ?: $request->get('branch_id');
        $expenseCategoryId = $request->get('expense_category_id');

        // 1. Find all account IDs that are categorized as "Expense"
        // This includes Salary, Rent, Utility Bills etc. 
        // We strictly exclude "Purchases" to separate operating costs from inventory costs.
        $expenseAccountIds = \App\Models\ChartOfAccount::whereHas('type', function($q) {
                $q->where('name', 'like', 'Expense%');
            })
            ->where('name', 'not like', '%Purchase%')
            ->pluck('id')
            ->toArray();

        // 2. Main Query: Start from JournalEntry as the primary source
        $journalQuery = \App\Models\JournalEntry::query()
            ->select([
                'journals.entry_date as date',
                'journals.voucher_no as ref_no',
                'chart_of_accounts.name as category',
                'journals.branch_id',
                'journals.description as note',
                'journal_entries.debit as amount',
                'journal_entries.memo as entry_memo'
            ])
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('chart_of_accounts', 'journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->whereIn('journal_entries.chart_of_account_id', $expenseAccountIds)
            ->whereBetween('journals.entry_date', [$startDate, $endDate])
            ->where('journal_entries.debit', '>', 0);

    // Filters
    if ($branchId) $journalQuery->where('journals.branch_id', $branchId);
    if ($expenseCategoryId) $journalQuery->where('chart_of_accounts.id', $expenseCategoryId);

    $expenses = $journalQuery->orderBy('journals.entry_date', 'desc')->get()->map(function ($item) {
        return [
            'date' => $item->date,
            'ref_no' => $item->ref_no,
            'category' => $item->category,
            'branch' => $item->branch_id ? (\App\Models\Branch::find($item->branch_id)->name ?? 'Main') : 'Head Office',
            'note' => $item->note ?: $item->entry_memo,
            'amount' => $item->amount
        ];
    });

    $expenseCategories = \App\Models\ChartOfAccount::whereIn('id', $expenseAccountIds)->get();
        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        // Handle Export
        if ($request->filled('export')) {
            $data = [
                'expenses' => $expenses,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalAmount' => $expenses->sum('amount'),
                'branchName' => $branchId ? (\App\Models\Branch::find($branchId)->name ?? 'All') : 'All Branches'
            ];

            if ($request->export == 'excel') {
                return $this->exportExpenseExcel($data);
            } elseif ($request->export == 'pdf') {
                return $this->exportExpensePdf($data);
            }
        }

        // Return JSON for AJAX
        if ($request->ajax()) {
            return response()->json([
                'html' => view('erp.reports.partials.expense-rows', compact('expenses'))->render(),
                'total_amount' => number_format($expenses->sum('amount'), 2),
                'date_range' => $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d')
            ]);
        }

        return view('erp.reports.expense-report', compact('expenses', 'startDate', 'endDate', 'reportType', 'branches', 'branchId', 'expenseCategories'));
    }

    private function exportExpenseExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Expense Report - Sisal Fashion');
        $sheet->setCellValue('A2', 'Period: ' . $data['startDate']->format('d M Y') . ' to ' . $data['endDate']->format('d M Y'));
        $sheet->setCellValue('A3', 'Branch: ' . $data['branchName']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Date', 'Ref No', 'Category', 'Branch', 'Note', 'Amount'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true);
            $col++;
        }

        $row = 6;
        foreach ($data['expenses'] as $exp) {
            $sheet->setCellValue('A' . $row, $exp['date']);
            $sheet->setCellValue('B' . $row, $exp['ref_no']);
            $sheet->setCellValue('C' . $row, $exp['category']);
            $sheet->setCellValue('D' . $row, $exp['branch']);
            $sheet->setCellValue('E' . $row, $exp['note']);
            $sheet->setCellValue('F' . $row, $exp['amount']);
            $row++;
        }

        $sheet->setCellValue('E' . $row, 'TOTAL:');
        $sheet->setCellValue('F' . $row, $data['totalAmount']);
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);

        $filename = "Expense_Report_" . date('Ymd') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function performanceReport(Request $request, \App\Services\PerformanceReportService $service)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }

        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        $branchId = $request->get('branch_id');
        $groupBy = $request->get('group_by', 'variation');
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) $branchId = $restrictedBranchId;

        if ($request->ajax() || $request->filled('export')) {
            $finalData = $service->getPerformanceData(
                $startDate, 
                $endDate, 
                $branchId, 
                $request->get('product_id'), 
                $request->get('category_id'),
                $groupBy
            );

            if ($request->filled('export')) {
                if ($request->export == 'excel') return $this->exportPerformanceExcel($finalData, $startDate, $endDate, $groupBy);
                if ($request->export == 'pdf') return $this->exportPerformancePdf($finalData, $startDate, $endDate, $groupBy);
            }

            return response()->json([
                'data' => $finalData,
                'summary' => [
                    'total_net_sale' => $finalData->sum('net_sale_amount'),
                    'total_net_cost' => $finalData->sum('net_purchase_cost'),
                    'total_profit' => $finalData->sum('gross_profit'),
                ]
            ]);
        }

        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();
        $categories = ProductServiceCategory::whereNull('parent_id')->orderBy('name')->get();

        return view('erp.reports.performance', compact('startDate', 'endDate', 'branches', 'branchId', 'categories'));
    }

    private function exportPerformanceExcel($data, $startDate, $endDate, $groupBy = 'variation')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Sales vs Purchase Performance Report (' . ($groupBy === 'product' ? 'Style-Wise' : 'Size-Wise') . ')');
        $sheet->setCellValue('A2', 'Period: ' . $startDate->format('d/m/Y') . ' to ' . $endDate->format('d/m/Y'));
        
        $headers = ['Product', 'Style #', $groupBy === 'product' ? 'Details' : 'Variation', 'Sold Qty', 'Ret Qty', 'Net Qty', 'Net Sale', 'Net Cost', 'Profit', 'Margin %'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $col++;
        }

        $row = 5;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->product_name);
            $sheet->setCellValue('B' . $row, $item->style_number);
            $sheet->setCellValue('C' . $row, $item->variation_name);
            $sheet->setCellValue('D' . $row, $item->sold_qty);
            $sheet->setCellValue('E' . $row, $item->returned_qty);
            $sheet->setCellValue('F' . $row, $item->net_qty);
            $sheet->setCellValue('G' . $row, $item->net_sale_amount);
            $sheet->setCellValue('H' . $row, $item->net_purchase_cost);
            $sheet->setCellValue('I' . $row, $item->gross_profit);
            $sheet->setCellValue('J' . $row, number_format($item->profit_margin, 2) . '%');
            $row++;
        }

        $filename = "Performance_Report_" . ($groupBy === 'product' ? 'StyleWise_' : 'SizeWise_') . date('Ymd_His') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPerformancePdf($data, $startDate, $endDate, $groupBy = 'variation')
    {
        $summary = [
            'total_sale' => $data->sum('net_sale_amount'),
            'total_cost' => $data->sum('net_purchase_cost'),
            'total_profit' => $data->sum('gross_profit'),
        ];
        $pdf = Pdf::loadView('erp.reports.pdf.performance', compact('data', 'startDate', 'endDate', 'summary', 'groupBy'))->setPaper('a4', 'landscape');
        return $pdf->download('Performance_Report_' . ($groupBy === 'product' ? 'StyleWise_' : 'SizeWise_') . date('Y-m-d') . '.pdf');
    }

    private function exportExpensePdf($data)
    {
        $pdf = Pdf::loadView('erp.reports.pdf.expense', $data)->setPaper('a4', 'portrait');
        return $pdf->download('Expense_Report_' . date('Y-m-d') . '.pdf');
    }

    private function exportCustomerReportExcelFile($customers, $startDate, $endDate)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Customer Summary Report');
        $sheet->setCellValue('A2', 'Period: ' . $startDate->format('d M, Y') . ' - ' . $endDate->format('d M, Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $headers = ['SL', 'Customer Name', 'Phone', 'Outlet', 'Opening', 'Sales', 'Paid', 'Payment', 'Discount', 'Return', 'Exchange', 'Closing Due'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCACACA');
            $col++;
        }
        
        $row = 5;
        $sl = 1;
        $tOpening = 0; $tSales = 0; $tPaid = 0; $tPayment = 0; $tDiscount = 0; $tReturn = 0; $tExchange = 0; $tDue = 0;

        foreach ($customers as $c) {
            if ($c->is_empty) continue;
            
            $sheet->setCellValue('A' . $row, $sl++);
            $sheet->setCellValue('B' . $row, $c->name);
            $sheet->setCellValue('C' . $row, $c->mobile ?? 'N/A');
            $sheet->setCellValue('D' . $row, $c->outlet);
            $sheet->setCellValue('E' . $row, $c->opening);
            $sheet->setCellValue('F' . $row, $c->sales);
            $sheet->setCellValue('G' . $row, $c->paid);
            $sheet->setCellValue('H' . $row, $c->payment);
            $sheet->setCellValue('I' . $row, $c->discount);
            $sheet->setCellValue('J' . $row, $c->return);
            $sheet->setCellValue('K' . $row, $c->exchange);
            $sheet->setCellValue('L' . $row, $c->due);
            
            $tOpening += $c->opening; $tSales += $c->sales; $tPaid += $c->paid; 
            $tPayment += $c->payment; $tDiscount += $c->discount;
            $tReturn += $c->return; $tExchange += $c->exchange; $tDue += $c->due;

            // Format numbers
            $sheet->getStyle('E'.$row.':L'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        // Add Grand Total Row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        $sheet->setCellValue('E' . $row, $tOpening);
        $sheet->setCellValue('F' . $row, $tSales);
        $sheet->setCellValue('G' . $row, $tPaid);
        $sheet->setCellValue('H' . $row, $tPayment);
        $sheet->setCellValue('I' . $row, $tDiscount);
        $sheet->setCellValue('J' . $row, $tReturn);
        $sheet->setCellValue('K' . $row, $tExchange);
        $sheet->setCellValue('L' . $row, $tDue);

        $sheet->getStyle('A' . $row . ':L' . $row)->getFont()->setBold(true);
        $sheet->getStyle('E' . $row . ':L' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':L' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');
        
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $filename = 'Customer_Summary_Report_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function businessSnapshot(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }

        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        $branchId = $request->get('branch_id');
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) $branchId = $restrictedBranchId;

        // --- INFLOWS (Money In & Activities) ---
        
        // 1. Total Sales Value (Revenue Generated)
        $posSalesQuery = \App\Models\Pos::whereBetween('sale_date', [$startDate, $endDate]);
        if ($branchId) $posSalesQuery->where('branch_id', $branchId);
        $totalSalesValue = $posSalesQuery->sum('total_amount');

        $onlineSalesQuery = \App\Models\Order::whereBetween('created_at', [$startDate, $endDate])->where('status', '!=', 'cancelled');
        $onlineSalesValue = $branchId ? 0 : $onlineSalesQuery->sum('total');
        $totalRevenue = $totalSalesValue + $onlineSalesValue;

        // 2. Actual Sales Collections (Payments received for today's or any sales via POS/Order payment type)
        // Note: This includes the 'paid_amount' at time of sale.
        $salesCollectionsQuery = \App\Models\Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->whereIn('payment_for', ['pos', 'order']);
        if ($branchId) {
             $salesCollectionsQuery->where(function($q) use ($branchId) {
                 $q->whereHas('pos', function($sq) use ($branchId) { $sq->where('branch_id', $branchId); })
                   ->orWhereHas('invoice.pos', function($sq) use ($branchId) { $sq->where('branch_id', $branchId); });
             });
        }
        $salesCollections = $salesCollectionsQuery->sum('amount');

        // 3. Money Receipts (Collections from past dues / manual receipts)
        $moneyReceiptsQuery = \App\Models\Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('payment_for', 'manual_receipt');
        if ($branchId) {
             $moneyReceiptsQuery->where(function($q) use ($branchId) {
                 $q->whereHas('pos', function($sq) use ($branchId) { $sq->where('branch_id', $branchId); })
                   ->orWhereHas('invoice.pos', function($sq) use ($branchId) { $sq->where('branch_id', $branchId); });
             });
        }
        $moneyReceipts = $moneyReceiptsQuery->sum('amount');

        // 4. Purchase Returns (Money/Value back from suppliers)
        $purchaseReturnsQuery = \App\Models\PurchaseReturn::whereBetween('return_date', [$startDate, $endDate]);
        if ($branchId) {
             $purchaseReturnsQuery->whereHas('purchase', function($q) use ($branchId) { 
                 $q->where('location_id', $branchId)->where('ship_location_type', 'branch'); 
             });
        }
        $purchaseReturnIds = $purchaseReturnsQuery->pluck('id');
        $purchaseReturnsValue = \DB::table('purchase_return_items')->whereIn('purchase_return_id', $purchaseReturnIds)->sum('total_price');


        // --- OUTFLOWS (Money Out & Costs) ---
        
        // 1. Total Purchases Value (Bills Generated)
        $purchasesQuery = \App\Models\PurchaseBill::whereBetween('bill_date', [$startDate, $endDate]);
        if ($branchId) {
            $purchasesQuery->whereHas('purchase', function($q) use ($branchId) {
                $q->where('location_id', $branchId)->where('ship_location_type', 'branch');
            });
        }
        $totalPurchasesValue = $purchasesQuery->sum('total_amount');

        // 2. Supplier Payments (Actual cash paid to suppliers)
        $supplierPaymentsQuery = \App\Models\SupplierPayment::whereBetween('payment_date', [$startDate, $endDate]);
        // Filter by branch if possible (supplier payments might be global or branch-linked via bill)
        if ($branchId) {
            $supplierPaymentsQuery->whereHas('bill.purchase', function($q) use ($branchId) {
                $q->where('location_id', $branchId)->where('ship_location_type', 'branch');
            });
        }
        $supplierPayments = $supplierPaymentsQuery->sum('amount');

        // 3. Sales Returns (Money refunded to customers)
        $salesReturnsQuery = \App\Models\SaleReturn::whereBetween('return_date', [$startDate, $endDate])
            ->where('status', 'processed'); // Only count processed returns
        if ($branchId) $salesReturnsQuery->where('return_to_id', $branchId)->where('return_to_type', 'branch');
        
        // Total Return Value (Informational)
        $totalSalesReturnsValue = $salesReturnsQuery->get()->sum(function($r) {
            return $r->items->sum('total_price');
        });

        // Actual Cash Refunds (Money Out)
        $actualCashRefunds = $salesReturnsQuery->whereIn('refund_type', ['cash', 'bank'])->get()->sum(function($r) {
            return $r->items->sum('total_price');
        });

        // 4. Expense Payments
        $expenseAccountIds = \App\Models\ChartOfAccount::whereHas('type', function($q) {
                $q->where('name', 'like', 'Expense%');
            })->pluck('id')->toArray();

        $expensesQuery = \App\Models\JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->whereIn('journal_entries.chart_of_account_id', $expenseAccountIds)
            ->whereBetween('journals.entry_date', [$startDate, $endDate])
            ->where('journal_entries.debit', '>', 0);
        if ($branchId) $expensesQuery->where('journals.branch_id', $branchId);
        $totalExpenses = $expensesQuery->sum('journal_entries.debit');


        // --- ASSET SUMMARIES (Live Balances) ---
        
        // Stock Value (Cost basis)
        $stockQuery = Product::where('type', 'product');
        if ($branchId) {
            $stockQuery->withSum(['branchStocks' => function($q) use ($branchId) { $q->where('branch_id', $branchId); }], 'quantity');
        } else {
            $stockQuery->withSum('variationStocks', 'quantity');
        }
        $stockValue = $stockQuery->get()->sum(function($p) {
            $qty = $p->branch_stocks_sum_quantity ?? $p->variation_stocks_sum_quantity ?? 0;
            return $qty * $p->cost;
        });

        // Cash & Bank Balances
        $cashBalance = \App\Models\FinancialAccount::where('type', 'cash');
        if ($branchId) $cashBalance->where('branch_id', $branchId);
        $cashBalance = $cashBalance->sum('balance');

        $bankBalance = \App\Models\FinancialAccount::whereIn('type', ['bank', 'mobile']);
        if ($branchId) $bankBalance->where('branch_id', $branchId);
        $bankBalance = $bankBalance->sum('balance');


        // --- CONSTRUCT DATA ARRAY ---
        // We use "Actual Collections" for the Total Inflow sum to avoid double counting dues.
        $data = [
            'totalRevenue' => $totalRevenue,
            'salesCollections' => $salesCollections,
            'moneyReceipts' => $moneyReceipts,
            'purchaseReturns' => $purchaseReturnsValue,
            'totalInflow' => $salesCollections + $moneyReceipts + $purchaseReturnsValue,
            
            'totalPurchasesValue' => $totalPurchasesValue,
            'supplierPayments' => $supplierPayments,
            'totalSalesReturnsValue' => $totalSalesReturnsValue,
            'actualCashRefunds' => $actualCashRefunds,
            'totalExpenses' => $totalExpenses,
            'totalOutflow' => $supplierPayments + $actualCashRefunds + $totalExpenses,

            'stockValue' => $stockValue,
            'cashBalance' => $cashBalance,
            'bankBalance' => $bankBalance,
            'totalLiquidity' => $cashBalance + $bankBalance
        ];

        if ($request->ajax()) {
            return view('erp.reports.snapshot-partial', compact('data'))->render();
        }

        $branches = $restrictedBranchId ? \App\Models\Branch::where('id', $restrictedBranchId)->get() : \App\Models\Branch::all();

        return view('erp.reports.snapshot', compact('startDate', 'endDate', 'branches', 'branchId', 'data', 'restrictedBranchId'));
    }
}
