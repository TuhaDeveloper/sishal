<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pos;
use App\Models\PosItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\WarehouseProductStock;
use App\Models\ProductVariationStock;
use App\Models\ProductServiceCategory;
use App\Models\Brand;
use App\Models\Season;
use App\Models\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SimpleAccountingController extends Controller
{
    /**
     * Sales Summary Report
     */
    public function salesSummary(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $dateRange = $request->get('range', 'week');
        $source = $request->get('source', 'all');
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branchId = $restrictedBranchId;
            $source = 'pos';
        }

        if ($dateRange === 'custom') {
            $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subWeek()->startOfDay();
            $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        } else {
            $startDate = $this->getStartDate($dateRange);
            $endDate = Carbon::now();
        }

        // Get sales data with filters
        $salesData = $this->getSalesData($startDate, $endDate, $source, $categoryId, $branchId);
        
        // Get cost data with filters
        $costData = $this->getCostData($startDate, $endDate, $source, $categoryId, $branchId);
        
        // Calculate profit
        $profitData = $this->calculateProfitData($salesData, $costData);

        // Get Top Variations for variation details
        $variationProfits = $this->getVariationProfits($startDate, $endDate, $source, $categoryId, $branchId);

        $categories = $this->getFormattedCategories();
        $branches = $restrictedBranchId ? Branch::where('id', $restrictedBranchId)->get() : Branch::all();

        return view('erp.simple-accounting.sales-summary', compact(
            'salesData',
            'costData', 
            'profitData',
            'dateRange',
            'startDate',
            'endDate',
            'source',
            'categoryId',
            'branchId',
            'categories',
            'branches',
            'variationProfits'
        ));
    }

    /**
     * Sales Report (Combined Product and Category)
     */
    public function salesReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $dateRange = $request->get('range', 'month');
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $source = $request->get('source', 'all');

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branchId = $restrictedBranchId;
            $source = 'pos';
        }

        if ($dateRange === 'custom') {
            $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
            $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        } else {
            $startDate = $this->getStartDate($dateRange);
            $endDate = Carbon::now();
        }

        // Apply new logic: specific branch = only POS sales
        if ($branchId) {
            $source = 'pos';
        }

        $productProfits = $this->getProductProfits($startDate, $endDate, $source, $categoryId, $branchId);
        $categoryProfits = $this->getCategoryProfits($startDate, $endDate, $source, $categoryId, $branchId);
        $variationProfits = $this->getVariationProfits($startDate, $endDate, $source, $categoryId, $branchId);

        $categories = $this->getFormattedCategories();
        $branches = $restrictedBranchId ? Branch::where('id', $restrictedBranchId)->get() : Branch::all();

        return view('erp.simple-accounting.sales-report', compact(
            'productProfits',
            'categoryProfits',
            'variationProfits',
            'dateRange',
            'startDate',
            'endDate',
            'branchId',
            'categoryId',
            'source',
            'categories',
            'branches'
        ));
    }

    /**
     * Top Products Report
     */
    public function topProducts(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $dateRange = $request->get('range', 'month');
        $source = $request->get('source', 'all');
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        $limit = $request->get('limit', 10);
        $search = $request->get('search');
        $brandId = $request->get('brand_id');
        $seasonId = $request->get('season_id');
        $genderId = $request->get('gender_id');
        $variationValueId = $request->get('variation_value_id');
        
        $groupBy = $request->get('group_by', 'product');
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branchId = $restrictedBranchId;
            $source = 'pos';
        }

        // Quick Month/Year Filters
        if ($request->filled('filter_year')) {
            $year = $request->filter_year;
            $month = $request->filled('filter_month') ? str_pad($request->filter_month, 2, '0', STR_PAD_LEFT) : null;
            
            if ($month) {
                $startDate = Carbon::parse("$year-$month-01")->startOfDay();
                $endDate = Carbon::parse("$year-$month-01")->endOfMonth()->endOfDay();
                $request->merge(['date_from' => $startDate->toDateString(), 'date_to' => $endDate->toDateString()]);
            } else {
                $startDate = Carbon::parse("$year-01-01")->startOfDay();
                $endDate = Carbon::parse("$year-12-31")->endOfDay();
                $request->merge(['date_from' => $startDate->toDateString(), 'date_to' => $endDate->toDateString()]);
            }
            $dateRange = 'custom';
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $dateRange = 'custom';
        }

        if ($dateRange === 'custom') {
            $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
            $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        } else {
            $startDate = $this->getStartDate($dateRange);
            $endDate = Carbon::now();
        }

        // Get top products with filters
        $topByRevenue = $this->getTopProductsByRevenue($startDate, $endDate, $limit, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);
        $topByProfit = $this->getTopProductsByProfit($startDate, $endDate, $limit, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);
        $topByQuantity = $this->getTopProductsByQuantity($startDate, $endDate, $limit, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);

        $categories = $this->getFormattedCategories();
        $branches = $restrictedBranchId ? Branch::where('id', $restrictedBranchId)->get() : Branch::all();
        $brands = Brand::all();
        $seasons = Season::all();
        $genders = Gender::all();
        $variationValues = \App\Models\VariationAttributeValue::whereHas('variations')->orderBy('value')->get();

        if ($request->ajax()) {
            $html = view('erp.simple-accounting.partials.top-products-content', compact(
                'topByRevenue',
                'topByProfit',
                'topByQuantity',
                'limit',
                'groupBy'
            ))->render();

            return response()->json([
                'html' => $html,
                'date_from' => $startDate->toDateString(),
                'date_to' => $endDate->toDateString(),
                'range' => $dateRange,
                'date_text' => 'from <span class="badge bg-light text-primary border">' . $startDate->format('d M, Y') . '</span> to <span class="badge bg-light text-primary border">' . $endDate->format('d M, Y') . '</span>'
            ]);
        }

        return view('erp.simple-accounting.top-products', compact(
            'topByRevenue',
            'topByProfit',
            'topByQuantity',
            'dateRange',
            'startDate',
            'endDate',
            'source',
            'categoryId',
            'branchId',
            'limit',
            'search',
            'brandId',
            'seasonId',
            'genderId',
            'variationValueId',
            'groupBy',
            'categories',
            'branches',
            'brands',
            'seasons',
            'genders',
            'variationValues'
        ));
    }

    /**
     * Stock Value Report
     */
    public function stockValue(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $lowStock = $request->get('low_stock');
        
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId) {
            $branchId = $restrictedBranchId;
        }

        $productStockValues = $this->getProductStockValues($branchId, $categoryId, $lowStock);
        $categoryStockValues = $this->getCategoryStockValues($branchId, $categoryId, $lowStock);
        $totalStockValue = $productStockValues->sum('total_value');

        $categories = $this->getFormattedCategories();
        $branches = $restrictedBranchId ? Branch::where('id', $restrictedBranchId)->get() : Branch::all();

        return view('erp.simple-accounting.stock-value', compact(
            'productStockValues',
            'categoryStockValues',
            'totalStockValue',
            'branchId',
            'categoryId',
            'lowStock',
            'categories',
            'branches'
        ));
    }


    /**
     * Get Sales Report Data for Modal
     */
    public function getSalesDataReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonth();
        $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');

        $type = $request->get('type', 'product');
        $source = $request->get('source', 'all');

        // Logic: specific branch = only POS
        if ($branchId) { $source = 'pos'; }

        if ($type === 'category') {
            $data = $this->getCategoryProfits($startDate, $endDate, $source, $categoryId, $branchId);
            $transformed = $data->values()->map(function($item) {
                return [
                    'name' => $item['category_name'],
                    'product_count' => $item['product_count'],
                    'quantity_sold' => $item['quantity_sold'],
                    'revenue' => number_format($item['revenue'], 2),
                    'cost' => number_format($item['cost'], 2),
                    'profit' => number_format($item['profit'], 2),
                ];
            });
            $summary = [
                'total_revenue' => number_format($data->sum('revenue'), 2),
                'total_profit' => number_format($data->sum('profit'), 2),
                'total_items' => $data->sum('quantity_sold'),
            ];
        } else {
            $data = $this->getProductProfits($startDate, $endDate, $source, $categoryId, $branchId);
            $transformed = $data->values()->map(function($item) {
                return [
                    'name' => $item['product']->name ?? 'Deleted Product',
                    'category' => $item['product']->category->name ?? 'Uncategorized',
                    'quantity_sold' => $item['quantity_sold'],
                    'revenue' => number_format($item['revenue'], 2),
                    'cost' => number_format($item['cost'], 2),
                    'profit' => number_format($item['profit'], 2),
                ];
            });
            $summary = [
                'total_revenue' => number_format($data->sum('revenue'), 2),
                'total_profit' => number_format($data->sum('profit'), 2),
                'total_items' => $data->sum('quantity_sold'),
            ];
        }

        return response()->json([
            'data' => $transformed,
            'summary' => $summary
        ]);
    }

    /**
     * Export Sales Report to Excel
     */
    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonth();
        $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        $type = $request->get('type', 'product');
        $source = $request->get('source', 'all');
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');

        if ($branchId) { $source = 'pos'; }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        if ($type === 'category') {
            $data = $this->getCategoryProfits($startDate, $endDate, $source, $categoryId, $branchId);
            $sheet->setCellValue('A1', 'Category Wise Sales Report');
            $sheet->setCellValue('A3', 'Category');
            $sheet->setCellValue('B3', 'Products');
            $sheet->setCellValue('C3', 'Qty Sold');
            $sheet->setCellValue('D3', 'Revenue');
            $sheet->setCellValue('E3', 'Cost');
            $sheet->setCellValue('F3', 'Profit');
            
            $row = 4;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['category_name']);
                $sheet->setCellValue('B' . $row, $item['product_count']);
                $sheet->setCellValue('C' . $row, $item['quantity_sold']);
                $sheet->setCellValue('D' . $row, $item['revenue']);
                $sheet->setCellValue('E' . $row, $item['cost']);
                $sheet->setCellValue('F' . $row, $item['profit']);
                $row++;
            }
        } else {
            $data = $this->getProductProfits($startDate, $endDate, $source, $categoryId, $branchId);
            $sheet->setCellValue('A1', 'Product Wise Sales Report');
            $sheet->setCellValue('A3', 'Product');
            $sheet->setCellValue('B3', 'Category');
            $sheet->setCellValue('C3', 'Qty Sold');
            $sheet->setCellValue('D3', 'Revenue');
            $sheet->setCellValue('E3', 'Cost');
            $sheet->setCellValue('F3', 'Profit');
            
            $row = 4;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['product']->name ?? 'Deleted Product');
                $sheet->setCellValue('B' . $row, $item['product']->category->name ?? 'Uncategorized');
                $sheet->setCellValue('C' . $row, $item['quantity_sold']);
                $sheet->setCellValue('D' . $row, $item['revenue']);
                $sheet->setCellValue('E' . $row, $item['cost']);
                $sheet->setCellValue('F' . $row, $item['profit']);
                $row++;
            }
        }

        $filename = "sales_report_{$type}_" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Sales Report to PDF
     */
    public function exportPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonth();
        $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        $type = $request->get('type', 'product');
        $source = $request->get('source', 'all');
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');

        if ($branchId) { $source = 'pos'; }

        if ($type === 'category') {
            $data = $this->getCategoryProfits($startDate, $endDate, $source, $categoryId, $branchId);
            $view = 'erp.simple-accounting.exports.category-sales-pdf';
        } else {
            $data = $this->getProductProfits($startDate, $endDate, $source, $categoryId, $branchId);
            $view = 'erp.simple-accounting.exports.product-sales-pdf';
        }

        $pdf = Pdf::loadView($view, [
            'data' => $data,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        return $pdf->download("sales_report_{$type}_" . date('Y-m-d') . ".pdf");
    }

    /**
     * Export Sales Summary to Excel
     */
    public function exportSummaryExcel(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $dateRange = $request->get('range', 'week');
        $source = $request->get('source', 'all');
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        
        if ($dateRange === 'custom') {
            $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subWeek()->startOfDay();
            $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        } else {
            $startDate = $this->getStartDate($dateRange);
            $endDate = Carbon::now();
        }

        $salesData = $this->getSalesData($startDate, $endDate, $source, $categoryId, $branchId);
        $costData = $this->getCostData($startDate, $endDate, $source, $categoryId, $branchId);
        $profitData = $this->calculateProfitData($salesData, $costData);
        $variationProfits = $this->getVariationProfits($startDate, $endDate, $source, $categoryId, $branchId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Summary');
        
        // --- Header Section ---
        $sheet->setCellValue('A1', 'SALES SUMMARY REPORT');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'Period: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') . ' (' . ucfirst($dateRange) . ')');
        
        // Filter Metadata
        $branchName = $branchId ? (Branch::find($branchId)->name ?? 'Unknown') : 'All Branches';
        $categoryName = $categoryId ? (ProductServiceCategory::find($categoryId)->name ?? 'Unknown') : 'All Categories';
        $sheet->setCellValue('D2', 'Branch: ' . $branchName);
        $sheet->setCellValue('D3', 'Category: ' . $categoryName);
        $sheet->setCellValue('D4', 'Source: ' . ucfirst($source));

        // --- Summary Table ---
        $sheet->setCellValue('A6', 'KEY PERFORMANCE METRICS');
        $sheet->getStyle('A6')->getFont()->setBold(true);
        
        $metrics = [
            ['Metric', 'Value'],
            ['Total Revenue', $salesData['total_revenue']],
            ['Total Costs', $costData['total_costs']],
            ['Gross Profit', $profitData['gross_profit']],
            ['Profit Margin (%)', number_format($profitData['profit_margin'], 2) . '%'],
            ['Transaction Count', $salesData['total_sales_count']],
            ['Online Revenue', $salesData['order_revenue']],
            ['POS Revenue', $salesData['pos_revenue']]
        ];

        $row = 7;
        foreach ($metrics as $metric) {
            $sheet->setCellValue('A' . $row, $metric[0]);
            $sheet->setCellValue('B' . $row, $metric[1]);
            if ($row == 7) $sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
            $row++;
        }

        // --- Product Breakdown Table ---
        $row += 2;
        $sheet->setCellValue('A' . $row, 'PRODUCT & VARIATION PERFORMANCE');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $tableHeaders = ['Product / Variation', 'Category', 'Source', 'Qty Sold', 'Revenue', 'Cost', 'Gross Profit'];
        $col = 'A';
        foreach ($tableHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9ECEF');
            $col++;
        }
        $startRow = $row;
        $row++;

        foreach ($variationProfits as $v) {
            $name = $v['product']->name;
            if ($v['variation']) {
                $attrs = [];
                foreach ($v['variation']->combinations as $c) {
                    $attrs[] = $c->attribute->name . ': ' . $c->attributeValue->value;
                }
                $name .= ' (' . implode(', ', $attrs) . ')';
            }
            
            $sheet->setCellValue('A' . $row, $name);
            $sheet->setCellValue('B' . $row, $v['product']->category->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $v['source']);
            $sheet->setCellValue('D' . $row, $v['quantity_sold']);
            $sheet->setCellValue('E' . $row, $v['revenue']);
            $sheet->setCellValue('F' . $row, $v['cost']);
            $sheet->setCellValue('G' . $row, $v['profit']);
            $row++;
        }

        // Common table formatting
        foreach(range('A','G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $filename = "sales_summary_" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Sales Summary to PDF
     */
    public function exportSummaryPdf(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view reports')) {
            abort(403, 'Unauthorized action.');
        }
        $dateRange = $request->get('range', 'week');
        $source = $request->get('source', 'all');
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        
        if ($dateRange === 'custom') {
            $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subWeek()->startOfDay();
            $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        } else {
            $startDate = $this->getStartDate($dateRange);
            $endDate = Carbon::now();
        }

        $salesData = $this->getSalesData($startDate, $endDate, $source, $categoryId, $branchId);
        $costData = $this->getCostData($startDate, $endDate, $source, $categoryId, $branchId);
        $profitData = $this->calculateProfitData($salesData, $costData);
        $variationProfits = $this->getVariationProfits($startDate, $endDate, $source, $categoryId, $branchId);

        $branchName = $branchId ? (Branch::find($branchId)->name ?? 'Unknown') : 'All Branches';
        $categoryName = $categoryId ? (ProductServiceCategory::find($categoryId)->name ?? 'Unknown') : 'All Categories';

        $pdf = Pdf::loadView('erp.simple-accounting.exports.sales-summary-pdf', [
            'salesData' => $salesData,
            'costData' => $costData,
            'profitData' => $profitData,
            'variationProfits' => $variationProfits,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dateRange' => $dateRange,
            'branchName' => $branchName,
            'categoryName' => $categoryName,
            'source' => $source
        ]);

        return $pdf->download("sales_summary_" . date('Y-m-d') . ".pdf");
    }

    /**
     * Get sales data for date range
     */
    private function getSalesData($startDate, $endDate, $source = 'all', $categoryId = null, $branchId = null)
    {
        $orderRevenue = 0;
        $orderCount = 0;
        $posRevenue = 0;
        $posCount = 0;

        // Get COD percentage from settings
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;

        // Ecommerce logic: Only include if no specific branch is selected OR if source is specifically 'online'
        // But per new rule: specific branch = POS only. All branches = All.
        if (($source === 'all' || $source === 'online') && !$branchId) {
            // Get orders data
            $ordersQuery = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled');
            
            if ($categoryId) {
                $ordersQuery->whereHas('items.product', function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }

            $orders = $ordersQuery->get();
            
            // Calculate revenue ...
            $orderRevenue = $orders->sum(function($order) use ($categoryId, $codPercentage) {
                if ($categoryId) {
                    $orderItemsTotal = $order->items->sum(fn($item) => $item->unit_price * $item->quantity);
                    $itemRevenue = $order->items->filter(function($item) use ($categoryId) {
                        return $item->product && $item->product->category_id == $categoryId;
                    })->sum(function($item) {
                        return $item->unit_price * $item->quantity;
                    });

                    if ($order->payment_method === 'cash' && $codPercentage > 0) {
                        $orderCodDiscount = round($order->total * $codPercentage, 2);
                        if ($orderItemsTotal > 0) {
                            $itemCodDiscount = round(($itemRevenue / $orderItemsTotal) * $orderCodDiscount, 2);
                            $itemRevenue -= $itemCodDiscount;
                        }
                    }
                    return $itemRevenue;
                }

                $revenue = $order->total - ($order->delivery ?? 0);
                if ($order->payment_method === 'cash' && $codPercentage > 0) {
                    $codDiscount = round($order->total * $codPercentage, 2);
                    $revenue = $revenue - $codDiscount;
                }
                return $revenue;
            });
            $orderCount = $orders->count();
        }

        if ($source === 'all' || $source === 'pos') {
            // Get POS data
            $posQuery = Pos::whereBetween('sale_date', [$startDate, $endDate]);

            if ($categoryId) {
                $posQuery->whereHas('items.product', function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }

            if ($branchId) {
                $posQuery->where('branch_id', $branchId);
            }

            $posSales = $posQuery->get();
            
            // Calculate revenue excluding delivery charges (only product prices)
            $posRevenue = $posSales->sum(function($pos) use ($categoryId) {
                if ($categoryId) {
                    return $pos->items->filter(function($item) use ($categoryId) {
                        return $item->product && $item->product->category_id == $categoryId;
                    })->sum(function($item) {
                        return $item->unit_price * $item->quantity;
                    });
                }
                return $pos->total_amount - ($pos->delivery ?? 0);
            });
            $posCount = $posSales->count();
        }

        return [
            'order_revenue' => $orderRevenue,
            'order_count' => $orderCount,
            'pos_revenue' => $posRevenue,
            'pos_count' => $posCount,
            'total_revenue' => $orderRevenue + $posRevenue,
            'total_sales_count' => $orderCount + $posCount
        ];
    }

    /**
     * Get cost data for date range
     */
    private function getCostData($startDate, $endDate, $source = 'all', $categoryId = null, $branchId = null)
    {
        $orderCosts = 0;
        $posCosts = 0;

        // Online only if no branch specified
        if (($source === 'all' || $source === 'online') && !$branchId) {
            // Get order items with costs
            $orderItemsQuery = OrderItem::whereHas('order', function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', '!=', 'cancelled');
            })->with('product');

            if ($categoryId) {
                $orderItemsQuery->whereHas('product', function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }

            $orderItems = $orderItemsQuery->get();
            foreach ($orderItems as $item) {
                $cost = $item->product->cost ?? 0;
                $orderCosts += $cost * $item->quantity;
            }
        }

        if ($source === 'all' || $source === 'pos') {
            // Get POS items with costs
            $posItemsQuery = PosItem::whereHas('pos', function($query) use ($startDate, $endDate, $branchId) {
                $query->whereBetween('sale_date', [$startDate, $endDate]);
                
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            })->with('product');

            if ($categoryId) {
                $posItemsQuery->whereHas('product', function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }

            $posItems = $posItemsQuery->get();
            foreach ($posItems as $item) {
                $cost = $item->product->cost ?? 0;
                $posCosts += $cost * $item->quantity;
            }
        }

        return [
            'order_costs' => $orderCosts,
            'pos_costs' => $posCosts,
            'total_costs' => $orderCosts + $posCosts
        ];
    }

    /**
     * Calculate profit data
     */
    private function calculateProfitData($salesData, $costData)
    {
        $grossProfit = $salesData['total_revenue'] - $costData['total_costs'];
        $profitMargin = $salesData['total_revenue'] > 0 ? 
            ($grossProfit / $salesData['total_revenue']) * 100 : 0;

        return [
            'gross_profit' => $grossProfit,
            'profit_margin' => $profitMargin,
            'cost_percentage' => $salesData['total_revenue'] > 0 ? 
                ($costData['total_costs'] / $salesData['total_revenue']) * 100 : 0
        ];
    }

    /**
     * Get product profits
     */
    private function getProductProfits($startDate, $endDate, $source = 'all', $categoryId = null, $branchId = null, $search = null, $brandId = null, $seasonId = null, $genderId = null, $variationValueId = null, $groupBy = 'product')
    {
        // Get COD percentage from settings
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;

        $orders = collect();
        // Online only if NO branch selected
        if (($source === 'all' || $source === 'online') && !$branchId) {
            $ordersQuery = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->with(['items.product', 'items.variation.attributeValues']);
            
            if ($categoryId) {
                $ordersQuery->whereHas('items.product', function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }

            if ($search) {
                $ordersQuery->whereHas('items.product', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('style_number', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            if ($brandId) {
                $ordersQuery->whereHas('items.product', function($q) use ($brandId) {
                    $q->where('brand_id', $brandId);
                });
            }

            if ($seasonId) {
                $ordersQuery->whereHas('items.product', function($q) use ($seasonId) {
                    $q->where('season_id', $seasonId);
                });
            }

            if ($genderId) {
                $ordersQuery->whereHas('items.product', function($q) use ($genderId) {
                    $q->where('gender_id', $genderId);
                });
            }

            if ($variationValueId) {
                $ordersQuery->whereHas('items.variation.attributeValues', function($q) use ($variationValueId) {
                    $q->where('variation_attribute_values.id', $variationValueId);
                });
            }

            $orders = $ordersQuery->get();
        }

        $posItems = collect();
        if ($source === 'all' || $source === 'pos') {
            $posItemsQuery = PosItem::whereHas('pos', function($query) use ($startDate, $endDate, $branchId) {
                $query->whereBetween('sale_date', [$startDate, $endDate]);
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            })->with(['product', 'variation.attributeValues', 'pos.branch']);

            if ($categoryId) {
                $posItemsQuery->whereHas('product', function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }

            if ($search) {
                $posItemsQuery->whereHas('product', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('style_number', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            if ($brandId) {
                $posItemsQuery->whereHas('product', function($q) use ($brandId) {
                    $q->where('brand_id', $brandId);
                });
            }

            if ($seasonId) {
                $posItemsQuery->whereHas('product', function($q) use ($seasonId) {
                    $q->where('season_id', $seasonId);
                });
            }

            if ($genderId) {
                $posItemsQuery->whereHas('product', function($q) use ($genderId) {
                    $q->where('gender_id', $genderId);
                });
            }

            if ($variationValueId) {
                $posItemsQuery->whereHas('variation.attributeValues', function($q) use ($variationValueId) {
                    $q->where('variation_attribute_values.id', $variationValueId);
                });
            }

            $posItems = $posItemsQuery->get();
        }

        $productProfits = collect();

        // Process order items with COD discount applied
        foreach ($orders as $order) {
            // Calculate COD discount for this order if it's COD
            $orderCodDiscount = 0;
            if ($order->payment_method === 'cash' && $codPercentage > 0) {
                $orderCodDiscount = round($order->total * $codPercentage, 2);
            }

            // Calculate total item revenue for this order (excluding delivery)
            $orderItemsTotal = $order->items->sum(function($item) {
                return $item->unit_price * $item->quantity;
            });

            // Process each item in the order
            foreach ($order->items as $item) {
                // Skip if product is null (deleted product)
                if (!$item->product) {
                    continue;
                }

                $productId = $item->product_id;
                $branchIdVal = 0; // Online
                $branchName = 'Online Store';

                $var = $item->variation;
                $varId = $item->variation_id ?? 0;
                $varValues = $var ? $var->attributeValues->pluck('value')->implode(' / ') : '';

                if ($groupBy === 'variation') {
                    $displayName = $item->product->name . ($varValues ? ' (' . $varValues . ')' : '');
                    $key = $productId . '_' . $varId . '_' . $branchIdVal;
                } else {
                    $displayName = $item->product->name;
                    $key = $productId . '_' . $branchIdVal;
                }

                $itemRevenue = $item->unit_price * $item->quantity;
                
                // Apply COD discount proportionally to this item
                $itemCodDiscount = 0;
                if ($orderCodDiscount > 0 && $orderItemsTotal > 0) {
                    // Distribute COD discount proportionally based on item's share of order
                    $itemCodDiscount = round(($itemRevenue / $orderItemsTotal) * $orderCodDiscount, 2);
                }
                
                $revenue = $itemRevenue - $itemCodDiscount;
                $cost = ($item->product->cost ?? 0) * $item->quantity;
                $profit = $revenue - $cost;

                if (!$productProfits->has($key)) {
                    $productProfits->put($key, [
                        'product' => $item->product,
                        'display_name' => $displayName,
                        'variation_name' => $varValues,
                        'branch_name' => $branchName,
                        'revenue' => 0,
                        'cost' => 0,
                        'profit' => 0,
                        'quantity_sold' => 0
                    ]);
                }

                $current = $productProfits->get($key);
                $productProfits->put($key, [
                    'product' => $current['product'],
                    'display_name' => $current['display_name'],
                    'variation_name' => $current['variation_name'],
                    'branch_name' => $current['branch_name'],
                    'revenue' => $current['revenue'] + $revenue,
                    'cost' => $current['cost'] + $cost,
                    'profit' => $current['profit'] + $profit,
                    'quantity_sold' => $current['quantity_sold'] + $item->quantity
                ]);
            }
        }

        // Process POS items
        foreach ($posItems as $item) {
            // Skip if product is null (deleted product)
            if (!$item->product) {
                continue;
            }

            $branchIdVal = $item->pos->branch_id ?? 0;
            $branchName = $item->pos->branch->name ?? 'Unknown Branch';
            $productId = $item->product_id;

            $var = $item->variation;
            $varId = $item->product_variation_id ?? $item->variation_id ?? 0;
            $varValues = $var ? $var->attributeValues->pluck('value')->implode(' / ') : '';

            if ($groupBy === 'variation') {
                $displayName = $item->product->name . ($varValues ? ' (' . $varValues . ')' : '');
                $key = $productId . '_' . $varId . '_' . $branchIdVal;
            } else {
                $displayName = $item->product->name;
                $key = $productId . '_' . $branchIdVal;
            }

            $revenue = $item->unit_price * $item->quantity;
            $cost = ($item->product->cost ?? 0) * $item->quantity;
            $profit = $revenue - $cost;

            if (!$productProfits->has($key)) {
                $productProfits->put($key, [
                    'product' => $item->product,
                    'display_name' => $displayName,
                    'variation_name' => $varValues,
                    'branch_name' => $branchName,
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                    'quantity_sold' => 0
                ]);
            }

            $current = $productProfits->get($key);
            $productProfits->put($key, [
                'product' => $current['product'],
                'display_name' => $current['display_name'],
                'variation_name' => $current['variation_name'],
                'branch_name' => $current['branch_name'],
                'revenue' => $current['revenue'] + $revenue,
                'cost' => $current['cost'] + $cost,
                'profit' => $current['profit'] + $profit,
                'quantity_sold' => $current['quantity_sold'] + $item->quantity
            ]);
        }

        return $productProfits->sortByDesc('profit');
    }

    /**
     * Get category profits
     */
    private function getCategoryProfits($startDate, $endDate, $source = 'all', $categoryId = null, $branchId = null)
    {
        $productProfits = $this->getProductProfits($startDate, $endDate, $source, $categoryId, $branchId);
        
        $categoryProfits = collect();
        
        foreach ($productProfits as $productId => $data) {
            // Skip if product is null (deleted product)
            if (!$data['product']) {
                continue;
            }

            $categoryId = $data['product']->category_id;
            $categoryName = $data['product']->category->name ?? 'Uncategorized';
            
            if (!$categoryProfits->has($categoryId)) {
                $categoryProfits->put($categoryId, [
                    'category_name' => $categoryName,
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                    'product_count' => 0,
                    'quantity_sold' => 0
                ]);
            }

            $current = $categoryProfits->get($categoryId);
            $categoryProfits->put($categoryId, [
                'category_name' => $current['category_name'],
                'revenue' => $current['revenue'] + $data['revenue'],
                'cost' => $current['cost'] + $data['cost'],
                'profit' => $current['profit'] + $data['profit'],
                'product_count' => $current['product_count'] + 1,
                'quantity_sold' => $current['quantity_sold'] + $data['quantity_sold']
            ]);
        }

        return $categoryProfits->sortByDesc('profit');
    }

    /**
     * Get top products by revenue
     */
    private function getTopProductsByRevenue($startDate, $endDate, $limit, $source = 'all', $categoryId = null, $branchId = null, $search = null, $brandId = null, $seasonId = null, $genderId = null, $variationValueId = null, $groupBy = 'product')
    {
        $productProfits = $this->getProductProfits($startDate, $endDate, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);
        return $productProfits->sortByDesc('revenue')->take($limit);
    }

    /**
     * Get top products by profit
     */
    private function getTopProductsByProfit($startDate, $endDate, $limit, $source = 'all', $categoryId = null, $branchId = null, $search = null, $brandId = null, $seasonId = null, $genderId = null, $variationValueId = null, $groupBy = 'product')
    {
        $productProfits = $this->getProductProfits($startDate, $endDate, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);
        return $productProfits->sortByDesc('profit')->take($limit);
    }

    /**
     * Get top products by quantity sold
     */
    private function getTopProductsByQuantity($startDate, $endDate, $limit, $source = 'all', $categoryId = null, $branchId = null, $search = null, $brandId = null, $seasonId = null, $genderId = null, $variationValueId = null, $groupBy = 'product')
    {
        $productProfits = $this->getProductProfits($startDate, $endDate, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);
        return $productProfits->sortByDesc('quantity_sold')->take($limit);
    }

    /**
     * Export Top Products to Excel
     */
    public function exportTopProductsExcel(Request $request)
    {
        $dateRange = $request->get('range', 'month');
        $source = $request->get('source', 'all');
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        $limit = $request->get('limit', 10);
        $search = $request->get('search');
        $brandId = $request->get('brand_id');
        $seasonId = $request->get('season_id');
        $genderId = $request->get('gender_id');
        $variationValueId = $request->get('variation_value_id');

        $groupBy = $request->get('group_by', 'product');

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $dateRange = 'custom';
        }

        if ($dateRange === 'custom') {
            $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
            $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        } else {
            $startDate = $this->getStartDate($dateRange);
            $endDate = Carbon::now();
        }

        $topByRevenue = $this->getTopProductsByRevenue($startDate, $endDate, $limit, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);
        $topByProfit = $this->getTopProductsByProfit($startDate, $endDate, $limit, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Top Products');

        $sheet->setCellValue('A1', 'TOP PRODUCTS REPORT (' . strtoupper($groupBy) . ' WISE)');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', "Period: " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d'));
        
        $row = 4;
        $sheet->setCellValue('A' . $row, 'TOP PRODUCTS BY REVENUE');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $headers = ['Rank', 'Product / Variation', 'Style No.', 'Category', 'Branch', 'Qty Sold', 'Revenue', 'Profit'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;

        $rank = 1;
        foreach ($topByRevenue as $data) {
            $sheet->setCellValue('A' . $row, $rank++);
            $sheet->setCellValue('B' . $row, $data['display_name'] ?? $data['product']->name);
            $sheet->setCellValue('C' . $row, $data['product']->style_number ?? $data['product']->sku ?? 'N/A');
            $sheet->setCellValue('D' . $row, $data['product']->category->name ?? 'N/A');
            $sheet->setCellValue('E' . $row, $data['branch_name']);
            $sheet->setCellValue('F' . $row, $data['quantity_sold']);
            $sheet->setCellValue('G' . $row, $data['revenue']);
            $sheet->setCellValue('H' . $row, $data['profit']);
            $row++;
        }

        $row += 2;
        $sheet->setCellValue('A' . $row, 'TOP PRODUCTS BY PROFIT');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;

        $rank = 1;
        foreach ($topByProfit as $data) {
            $sheet->setCellValue('A' . $row, $rank++);
            $sheet->setCellValue('B' . $row, $data['display_name'] ?? $data['product']->name);
            $sheet->setCellValue('C' . $row, $data['product']->style_number ?? $data['product']->sku ?? 'N/A');
            $sheet->setCellValue('D' . $row, $data['product']->category->name ?? 'N/A');
            $sheet->setCellValue('E' . $row, $data['branch_name']);
            $sheet->setCellValue('F' . $row, $data['quantity_sold']);
            $sheet->setCellValue('G' . $row, $data['revenue']);
            $sheet->setCellValue('H' . $row, $data['profit']);
            $row++;
        }

        foreach(range('A','H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $filename = "top_products_" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Top Products to PDF
     */
    public function exportTopProductsPdf(Request $request)
    {
        $dateRange = $request->get('range', 'month');
        $source = $request->get('source', 'all');
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        $limit = $request->get('limit', 10);
        $search = $request->get('search');
        $brandId = $request->get('brand_id');
        $seasonId = $request->get('season_id');
        $genderId = $request->get('gender_id');
        $variationValueId = $request->get('variation_value_id');

        $groupBy = $request->get('group_by', 'product');

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $dateRange = 'custom';
        }

        if ($dateRange === 'custom') {
            $startDate = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
            $endDate = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
        } else {
            $startDate = $this->getStartDate($dateRange);
            $endDate = Carbon::now();
        }

        $topByRevenue = $this->getTopProductsByRevenue($startDate, $endDate, $limit, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);
        $topByProfit = $this->getTopProductsByProfit($startDate, $endDate, $limit, $source, $categoryId, $branchId, $search, $brandId, $seasonId, $genderId, $variationValueId, $groupBy);

        $pdf = Pdf::loadView('erp.simple-accounting.exports.top-products-pdf', [
            'topByRevenue' => $topByRevenue,
            'topByProfit' => $topByProfit,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'limit' => $limit,
            'groupBy' => $groupBy
        ]);

        return $pdf->download("top_products_" . date('Y-m-d') . ".pdf");
    }

    /**
     * Get product stock values
     */
    private function getProductStockValues($branchId = null, $categoryId = null, $lowStockOnly = false)
    {
        $query = Product::with(['category', 'branchStock', 'warehouseStock', 'variations.stocks']);
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $products = $query->get();
        $stockValues = collect();
        
        foreach ($products as $product) {
            $totalStock = 0;
            $totalValue = 0;
            $items = collect(); // For variations or single product
            
            if ($product->has_variations) {
                foreach ($product->variations as $variation) {
                    $vStockQuery = $variation->stocks();
                    if ($branchId) {
                        $vStockQuery->where('branch_id', $branchId);
                    }
                    $variationStock = $vStockQuery->sum('quantity');
                    
                    if ($lowStockOnly) {
                        // Check if any stock record is low
                        $isLow = $vStockQuery->get()->contains(fn($s) => $s->quantity <= ($s->min_stock_level ?? 5));
                        if (!$isLow && $variationStock > 5) continue; 
                    }

                    if ($variationStock > 0 || !$lowStockOnly) {
                        $totalStock += $variationStock;
                        $totalValue += $variationStock * ($product->cost ?? 0);
                        $items->push([
                            'name' => $product->name . ' (' . $variation->name . ')',
                            'stock' => $variationStock,
                            'value' => $variationStock * ($product->cost ?? 0)
                        ]);
                    }
                }
            } else {
                $bStockQuery = $product->branchStock();
                if ($branchId) {
                    $bStockQuery->where('branch_id', $branchId);
                }
                $branchStock = $bStockQuery->sum('quantity');
                
                $wStockQuery = $product->warehouseStock();
                // Warehouse stock is usually global or branch-linked, assuming global if no branchId
                $warehouseStock = $branchId ? 0 : $wStockQuery->sum('quantity');
                
                $totalStock = $branchStock + $warehouseStock;
                
                if ($lowStockOnly && $totalStock > 5) {
                    continue;
                }

                $totalValue = $totalStock * ($product->cost ?? 0);
            }
            
            if ($totalStock > 0 || (!$lowStockOnly && $product->manage_stock)) {
                if ($lowStockOnly && $totalStock > 5) continue;

                $stockValues->push([
                    'product' => $product,
                    'total_stock' => $totalStock,
                    'unit_cost' => $product->cost ?? 0,
                    'total_value' => $totalValue,
                    'is_low' => $totalStock <= 5
                ]);
            }
        }
        
        return $stockValues->sortByDesc('total_value');
    }

    /**
     * Get category stock values
     */
    private function getCategoryStockValues($branchId = null, $categoryId = null, $lowStockOnly = false)
    {
        $productStockValues = $this->getProductStockValues($branchId, $categoryId, $lowStockOnly);
        
        $categoryValues = collect();
        
        foreach ($productStockValues as $data) {
            $catId = $data['product']->category_id;
            $categoryName = $data['product']->category->name ?? 'Uncategorized';
            
            if (!$categoryValues->has($catId)) {
                $categoryValues->put($catId, [
                    'category_name' => $categoryName,
                    'total_stock' => 0,
                    'total_value' => 0,
                    'product_count' => 0
                ]);
            }

            $current = $categoryValues->get($catId);
            $categoryValues->put($catId, [
                'category_name' => $current['category_name'],
                'total_stock' => $current['total_stock'] + $data['total_stock'],
                'total_value' => $current['total_value'] + $data['total_value'],
                'product_count' => $current['product_count'] + 1
            ]);
        }

        return $categoryValues->sortByDesc('total_value');
    }

    /**
     * Export Stock Value to Excel
     */
    public function exportStockExcel(Request $request)
    {
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $lowStock = $request->get('low_stock');

        $data = $this->getProductStockValues($branchId, $categoryId, $lowStock);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Value');

        $sheet->setCellValue('A1', 'STOCK VALUE REPORT');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $headers = ['Product Name', 'Category', 'Quantity', 'Unit Cost', 'Total Value'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '3', $h);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $col++;
        }

        $row = 4;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['product']->name);
            $sheet->setCellValue('B' . $row, $item['product']->category->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item['total_stock']);
            $sheet->setCellValue('D' . $row, $item['unit_cost']);
            $sheet->setCellValue('E' . $row, $item['total_value']);
            $row++;
        }

        foreach(range('A','E') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $filename = "stock_report_" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Stock Value to PDF
     */
    public function exportStockPdf(Request $request)
    {
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $lowStock = $request->get('low_stock');

        $data = $this->getProductStockValues($branchId, $categoryId, $lowStock);
        $totalValue = $data->sum('total_value');

        $pdf = Pdf::loadView('erp.simple-accounting.exports.stock-pdf', [
            'data' => $data,
            'totalValue' => $totalValue,
            'branchName' => $branchId ? Branch::find($branchId)->name : 'All Branches',
            'categoryName' => $categoryId ? ProductServiceCategory::find($categoryId)->name : 'All Categories'
        ]);

        return $pdf->download("stock_report_" . date('Y-m-d') . ".pdf");
    }

    /**
     * Get variation profits
     */
    private function getVariationProfits($startDate, $endDate, $source = 'all', $categoryId = null, $branchId = null)
    {
        // Get COD percentage from settings
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;

        $orders = collect();
        // Online only if NO branch selected
        if (($source === 'all' || $source === 'online') && !$branchId) {
            $ordersQuery = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->with(['items.product', 'items.variation.combinations.attributeValue']);
            
            if ($categoryId) {
                $ordersQuery->whereHas('items.product', function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }

            $orders = $ordersQuery->get();
        }

        $posItems = collect();
        if ($source === 'all' || $source === 'pos') {
            $posItemsQuery = PosItem::whereHas('pos', function($query) use ($startDate, $endDate, $branchId) {
                $query->whereBetween('sale_date', [$startDate, $endDate]);
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            })->with(['product', 'variation.combinations.attributeValue']);

            if ($categoryId) {
                $posItemsQuery->whereHas('product', function($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }
            $posItems = $posItemsQuery->get();
        }

        $variationProfits = collect();

        // Process order items
        foreach ($orders as $order) {
            $orderCodDiscount = 0;
            if ($order->payment_method === 'cash' && $codPercentage > 0) {
                $orderCodDiscount = round($order->total * $codPercentage, 2);
            }

            $orderItemsTotal = $order->items->sum(fn($item) => $item->unit_price * $item->quantity);

            foreach ($order->items as $item) {
                if (!$item->product) continue;
                if ($categoryId && $item->product->category_id != $categoryId) continue;

                $variationId = $item->variation_id ?? 0;
                $key = $item->product_id . '_' . $variationId . '_Online';
                
                $itemRevenue = $item->unit_price * $item->quantity;
                $itemCodDiscount = ($orderCodDiscount > 0 && $orderItemsTotal > 0) ? 
                    round(($itemRevenue / $orderItemsTotal) * $orderCodDiscount, 2) : 0;
                
                $revenue = $itemRevenue - $itemCodDiscount;
                $cost = ($item->product->cost ?? 0) * $item->quantity;
                $profit = $revenue - $cost;

                if (!$variationProfits->has($key)) {
                    $variationName = 'Standard';
                    if ($item->variation) {
                        $attrs = [];
                        foreach ($item->variation->combinations as $c) {
                            $attrs[] = ($c->attribute->name ?? 'Attr') . ': ' . ($c->attributeValue->value ?? 'Val');
                        }
                        $variationName = implode(', ', $attrs);
                    }

                    $variationProfits->put($key, [
                        'product' => $item->product,
                        'variation' => $item->variation,
                        'variation_name' => $variationName,
                        'revenue' => 0,
                        'cost' => 0,
                        'profit' => 0,
                        'quantity_sold' => 0,
                        'source' => 'Online'
                    ]);
                }

                $current = $variationProfits->get($key);
                $variationProfits->put($key, array_merge($current, [
                    'revenue' => $current['revenue'] + $revenue,
                    'cost' => $current['cost'] + $cost,
                    'profit' => $current['profit'] + $profit,
                    'quantity_sold' => $current['quantity_sold'] + $item->quantity
                ]));
            }
        }

        // Process POS items
        foreach ($posItems as $item) {
            if (!$item->product) continue;
            if ($categoryId && $item->product->category_id != $categoryId) continue;

            $variationId = $item->variation_id ?? 0;
            $key = $item->product_id . '_' . $variationId . '_POS';
            
            $revenue = $item->unit_price * $item->quantity;
            $cost = ($item->product->cost ?? 0) * $item->quantity;
            $profit = $revenue - $cost;

            if (!$variationProfits->has($key)) {
                $variationName = 'Standard';
                if ($item->variation) {
                    $attrs = [];
                    foreach ($item->variation->combinations as $c) {
                        $attrs[] = ($c->attribute->name ?? 'Attr') . ': ' . ($c->attributeValue->value ?? 'Val');
                    }
                    $variationName = implode(', ', $attrs);
                }

                $variationProfits->put($key, [
                    'product' => $item->product,
                    'variation' => $item->variation,
                    'variation_name' => $variationName,
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                    'quantity_sold' => 0,
                    'source' => 'POS'
                ]);
            }

            $current = $variationProfits->get($key);
            $variationProfits->put($key, array_merge($current, [
                'revenue' => $current['revenue'] + $revenue,
                'cost' => $current['cost'] + $cost,
                'profit' => $current['profit'] + $profit,
                'quantity_sold' => $current['quantity_sold'] + $item->quantity
            ]));
        }

        return $variationProfits->sortByDesc('profit');
    }

    /**
     * Get formatted categories for dropdown
     */
    private function getFormattedCategories()
    {
        $allCategories = ProductServiceCategory::all();
        $formatted = collect();
        $this->formatCategoryHierarchy($allCategories, null, 0, $formatted);
        return $formatted;
    }

    /**
     * Recursive helper to build flat list with indentation prefixes
     */
    private function formatCategoryHierarchy($categories, $parentId, $level, &$formatted)
    {
        foreach ($categories->where('parent_id', $parentId) as $category) {
            $category->display_name = str_repeat('— ', $level) . $category->name;
            $formatted->push($category);
            $this->formatCategoryHierarchy($categories, $category->id, $level + 1, $formatted);
        }
    }

    /**
     * Get start date based on range
     */
    private function getStartDate($range)
    {
        switch ($range) {
            case 'today':
                return Carbon::today();
            case 'week':
                return Carbon::now()->subWeek()->startOfDay();
            case 'month':
                return Carbon::now()->subMonth()->startOfDay();
            case 'quarter':
                return Carbon::now()->subQuarter()->startOfDay();
            case 'year':
                return Carbon::now()->subYear()->startOfDay();
            default:
                return Carbon::now()->subMonth()->startOfDay();
        }
    }
}
