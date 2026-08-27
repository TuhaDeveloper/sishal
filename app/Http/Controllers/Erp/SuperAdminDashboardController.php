<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SuperAdminDashboardController extends Controller
{
    private function checkSuperAdminAccess()
    {
        if (!auth()->check() || !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized action. Super Admin access required.');
        }
    }

    public function index(Request $request)
    {
        $this->checkSuperAdminAccess();

        $selectedBranchId = $request->get('branch_id', 'all');
        $dateRange = $request->get('range', 'this_month');

        $branches = Branch::where('status', 'active')->select('id', 'name')->get();
        $dashboardData = $this->fetchDashboardData($selectedBranchId, $dateRange, $branches);

        $siteTitle = GeneralSetting::value('site_title') ?? 'ERP SaaS';

        return view('erp.super_admin_dashboard', array_merge($dashboardData, [
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'dateRange' => $dateRange,
            'siteTitle' => $siteTitle
        ]));
    }

    public function getData(Request $request)
    {
        $this->checkSuperAdminAccess();

        $selectedBranchId = $request->get('branch_id', 'all');
        $dateRange = $request->get('range', 'this_month');

        $branches = Branch::where('status', 'active')->select('id', 'name')->get();
        $dashboardData = $this->fetchDashboardData($selectedBranchId, $dateRange, $branches);

        return response()->json([
            'success' => true,
            'data' => $dashboardData
        ]);
    }

    /**
     * High-Performance Cache-Wrapped Dashboard Data Retrieval
     */
    private function fetchDashboardData($selectedBranchId, $dateRange, $branches)
    {
        $cacheKey = "super_admin_dash_v16_{$selectedBranchId}_{$dateRange}";

        return Cache::remember($cacheKey, 180, function () use ($selectedBranchId, $dateRange, $branches) {
            $monthsWindow = $this->getMonthsArrayForRange($dateRange);

            return [
                'todaySalesBranchWise' => $this->getTodaySalesBranchWise($branches, $selectedBranchId, $dateRange),
                'sixDaysSalesChart' => $this->getSixDaysSalesChart($selectedBranchId),
                'topSellingProducts' => $this->getTopSellingProducts($selectedBranchId, $dateRange),
                'branchSalesStatement' => $this->getBranchSalesStatement($branches, $selectedBranchId, $monthsWindow),
                'grossSalesStatement' => $this->getGrossSalesStatement($selectedBranchId, $monthsWindow),
                'expenseStatement' => $this->getExpenseStatement($selectedBranchId, $monthsWindow),
            ];
        });
    }

    /**
     * Generate dynamic months array window based on Timeframe filter
     */
    private function getMonthsArrayForRange($dateRange)
    {
        $months = [];
        $now = Carbon::now();

        if ($dateRange === 'this_year') {
            // All months of current year
            $startOfYear = $now->copy()->startOfYear();
            for ($i = 0; $i < 12; $i++) {
                $dt = $startOfYear->copy()->addMonths($i);
                $months[] = [
                    'label' => $dt->format('F'),
                    'short' => $dt->format('M'),
                    'ym_code' => $dt->format('Y-m'),
                    'start' => $dt->copy()->startOfMonth()->format('Y-m-d H:i:s'),
                    'end' => $dt->copy()->endOfMonth()->format('Y-m-d H:i:s'),
                ];
            }
        } elseif ($dateRange === 'this_quarter') {
            // Months of current quarter
            $startOfQuarter = $now->copy()->startOfQuarter();
            for ($i = 0; $i < 3; $i++) {
                $dt = $startOfQuarter->copy()->addMonths($i);
                $months[] = [
                    'label' => $dt->format('F'),
                    'short' => $dt->format('M'),
                    'ym_code' => $dt->format('Y-m'),
                    'start' => $dt->copy()->startOfMonth()->format('Y-m-d H:i:s'),
                    'end' => $dt->copy()->endOfMonth()->format('Y-m-d H:i:s'),
                ];
            }
        } else {
            // Default 6 months window
            for ($i = 5; $i >= 0; $i--) {
                $dt = $now->copy()->subMonths($i);
                $months[] = [
                    'label' => $dt->format('F'),
                    'short' => $dt->format('M'),
                    'ym_code' => $dt->format('Y-m'),
                    'start' => $dt->copy()->startOfMonth()->format('Y-m-d H:i:s'),
                    'end' => $dt->copy()->endOfMonth()->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $months;
    }

    /**
     * Get start and end datetime bounds for timeframe
     */
    private function getTimeframeBounds($dateRange)
    {
        $now = Carbon::now();
        switch ($dateRange) {
            case 'today':
                return [$now->copy()->startOfDay()->format('Y-m-d H:i:s'), $now->copy()->endOfDay()->format('Y-m-d H:i:s')];
            case 'this_quarter':
                return [$now->copy()->startOfQuarter()->format('Y-m-d H:i:s'), $now->copy()->endOfQuarter()->format('Y-m-d H:i:s')];
            case 'this_year':
                return [$now->copy()->startOfYear()->format('Y-m-d H:i:s'), $now->copy()->endOfYear()->format('Y-m-d H:i:s')];
            case 'this_month':
            default:
                return [$now->copy()->startOfMonth()->format('Y-m-d H:i:s'), $now->copy()->endOfMonth()->format('Y-m-d H:i:s')];
        }
    }

    /**
     * Section 1: Today's Sales — Branch Wise (Respects Timeframe & Sale Returns)
     */
    private function getTodaySalesBranchWise($branches, $selectedBranchId, $dateRange)
    {
        $today = Carbon::today()->toDateString();
        [$startBound, $endBound] = $this->getTimeframeBounds($dateRange);

        $activeBranches = $branches;
        if ($selectedBranchId !== 'all' && is_numeric($selectedBranchId)) {
            $activeBranches = $branches->where('id', (int)$selectedBranchId);
        }

        // 1. Gross Today POS Sales Amount
        $todayPosAggregates = DB::table('pos')
            ->whereDate('sale_date', $today)
            ->where('status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('branch_id, COALESCE(SUM(total_amount), 0) as today_amount')
            ->groupBy('branch_id')
            ->pluck('today_amount', 'branch_id');

        // 1b. Gross Today POS Sales Qty
        $todayPosQtyAggregates = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->whereDate('pos.sale_date', $today)
            ->where('pos.status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('pos.branch_id, COALESCE(SUM(pos_items.quantity), 0) as today_qty')
            ->groupBy('pos.branch_id')
            ->pluck('today_qty', 'pos.branch_id');

        // 1c. Today Sale Returns
        $todayReturnAggregates = DB::table('sale_returns')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->whereDate('sale_returns.return_date', $today)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw('sale_returns.return_to_id as branch_id, COALESCE(SUM(sale_return_items.returned_qty), 0) as ret_qty, COALESCE(SUM(sale_return_items.total_price), 0) as ret_amount')
            ->groupBy('sale_returns.return_to_id')
            ->get()
            ->keyBy('branch_id');

        // 2. Gross Period POS Sales Amount
        $periodPosAggregates = DB::table('pos')
            ->where('sale_date', '>=', $startBound)
            ->where('sale_date', '<=', $endBound)
            ->where('status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('branch_id, COALESCE(SUM(total_amount), 0) as month_amount')
            ->groupBy('branch_id')
            ->pluck('month_amount', 'branch_id');

        // 2b. Gross Period POS Sales Qty
        $periodPosQtyAggregates = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->where('pos.sale_date', '>=', $startBound)
            ->where('pos.sale_date', '<=', $endBound)
            ->where('pos.status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('pos.branch_id, COALESCE(SUM(pos_items.quantity), 0) as month_qty')
            ->groupBy('pos.branch_id')
            ->pluck('month_qty', 'pos.branch_id');

        // 2c. Period Sale Returns
        $periodReturnAggregates = DB::table('sale_returns')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->where('sale_returns.return_date', '>=', $startBound)
            ->where('sale_returns.return_date', '<=', $endBound)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw('sale_returns.return_to_id as branch_id, COALESCE(SUM(sale_return_items.returned_qty), 0) as ret_qty, COALESCE(SUM(sale_return_items.total_price), 0) as ret_amount')
            ->groupBy('sale_returns.return_to_id')
            ->get()
            ->keyBy('branch_id');

        $result = [];
        $totalTodayQty = 0;
        $totalTodayAmount = 0;
        $totalMonthQty = 0;
        $totalMonthAmount = 0;

        foreach ($activeBranches as $b) {
            $grossTodayQty = (float) ($todayPosQtyAggregates->get($b->id) ?? 0);
            $grossTodayAmt = (float) ($todayPosAggregates->get($b->id) ?? 0);
            $todayRet = $todayReturnAggregates->get($b->id);
            $todayRetQty = (float) ($todayRet->ret_qty ?? 0);
            $todayRetAmt = (float) ($todayRet->ret_amount ?? 0);

            $todayQty = max(0, $grossTodayQty - $todayRetQty);
            $todayAmount = max(0, $grossTodayAmt - $todayRetAmt);

            $grossMonthQty = (float) ($periodPosQtyAggregates->get($b->id) ?? 0);
            $grossMonthAmt = (float) ($periodPosAggregates->get($b->id) ?? 0);
            $periodRet = $periodReturnAggregates->get($b->id);
            $periodRetQty = (float) ($periodRet->ret_qty ?? 0);
            $periodRetAmt = (float) ($periodRet->ret_amount ?? 0);

            $monthQty = max(0, $grossMonthQty - $periodRetQty);
            $monthAmount = max(0, $grossMonthAmt - $periodRetAmt);

            $totalTodayQty += $todayQty;
            $totalTodayAmount += $todayAmount;
            $totalMonthQty += $monthQty;
            $totalMonthAmount += $monthAmount;

            $result[] = [
                'branch_id' => $b->id,
                'branch_name' => $b->name,
                'today_qty' => $todayQty,
                'today_amount' => $todayAmount,
                'month_qty' => $monthQty,
                'month_amount' => $monthAmount,
            ];
        }

        return [
            'branches' => $result,
            'total' => [
                'today_qty' => $totalTodayQty,
                'today_amount' => $totalTodayAmount,
                'month_qty' => $totalMonthQty,
                'month_amount' => $totalMonthAmount,
            ]
        ];
    }

    /**
     * Section 2: 6 Days Sales Graph (Net of Sale Returns)
     */
    private function getSixDaysSalesChart($selectedBranchId)
    {
        $startDate = Carbon::now()->subDays(5)->startOfDay()->format('Y-m-d H:i:s');
        $endDate = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        // Gross daily sales amount
        $dailyPosAmount = DB::table('pos')
            ->where('sale_date', '>=', $startDate)
            ->where('sale_date', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('DATE(sale_date) as sale_day, COALESCE(SUM(total_amount), 0) as total_amount')
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->pluck('total_amount', 'sale_day');

        // Gross daily sales qty
        $dailyPosQty = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->where('pos.sale_date', '>=', $startDate)
            ->where('pos.sale_date', '<=', $endDate)
            ->where('pos.status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('DATE(pos.sale_date) as sale_day, COALESCE(SUM(pos_items.quantity), 0) as total_qty')
            ->groupBy(DB::raw('DATE(pos.sale_date)'))
            ->pluck('total_qty', 'sale_day');

        // Daily sale returns
        $dailyReturns = DB::table('sale_returns')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->where('sale_returns.return_date', '>=', $startDate)
            ->where('sale_returns.return_date', '<=', $endDate)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw('DATE(sale_returns.return_date) as return_day, COALESCE(SUM(sale_return_items.returned_qty), 0) as ret_qty, COALESCE(SUM(sale_return_items.total_price), 0) as ret_amount')
            ->groupBy(DB::raw('DATE(sale_returns.return_date)'))
            ->get()
            ->keyBy('return_day');

        $labels = [];
        $amounts = [];
        $quantities = [];

        for ($i = 5; $i >= 0; $i--) {
            $dateObj = Carbon::now()->subDays($i);
            $dayKey = $dateObj->toDateString();
            $labels[] = $dateObj->format('D (d M)');

            $grossAmt = (float) ($dailyPosAmount->get($dayKey) ?? 0);
            $grossQty = (float) ($dailyPosQty->get($dayKey) ?? 0);

            $retAgg = $dailyReturns->get($dayKey);
            $retAmt = (float) ($retAgg->ret_amount ?? 0);
            $retQty = (float) ($retAgg->ret_qty ?? 0);

            $amounts[] = max(0, $grossAmt - $retAmt);
            $quantities[] = (int) max(0, $grossQty - $retQty);
        }

        return [
            'labels' => $labels,
            'amounts' => $amounts,
            'quantities' => $quantities,
        ];
    }

    /**
     * Section 3: Top Selling Products (Net of Returns, Filters by selected Timeframe)
     */
    private function getTopSellingProducts($selectedBranchId, $dateRange)
    {
        [$startBound, $endBound] = $this->getTimeframeBounds($dateRange);

        // 1. Gross sold per product
        $posItems = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->join('products', 'pos_items.product_id', '=', 'products.id')
            ->leftJoin('branches', 'pos.branch_id', '=', 'branches.id')
            ->where('pos.sale_date', '>=', $startBound)
            ->where('pos.sale_date', '<=', $endBound)
            ->where('pos.status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('pos_items.product_id, products.name as product_name, products.image as product_image, branches.name as branch_name, COALESCE(SUM(pos_items.quantity), 0) as gross_qty, COALESCE(SUM(pos_items.total_price), SUM(pos_items.unit_price * pos_items.quantity), 0) as gross_amount')
            ->groupBy('pos_items.product_id', 'products.name', 'products.image', 'branches.name')
            ->get();

        // 2. Returns per product in this timeframe
        $returnItems = DB::table('sale_return_items')
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.return_date', '>=', $startBound)
            ->where('sale_returns.return_date', '<=', $endBound)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw('sale_return_items.product_id, COALESCE(SUM(sale_return_items.returned_qty), 0) as ret_qty, COALESCE(SUM(sale_return_items.total_price), 0) as ret_amount')
            ->groupBy('sale_return_items.product_id')
            ->get()
            ->keyBy('product_id');

        $topItems = $posItems->map(function ($item) use ($returnItems) {
            $ret = $returnItems->get($item->product_id);
            $retQty = (float) ($ret->ret_qty ?? 0);
            $retAmt = (float) ($ret->ret_amount ?? 0);

            $netQty = max(0, (float)$item->gross_qty - $retQty);
            $netAmt = max(0, (float)$item->gross_amount - $retAmt);

            return [
                'product_name' => $item->product_name ?? 'Unknown',
                'product_image' => $item->product_image ?? null,
                'branch_name' => $item->branch_name ?? 'Main Branch',
                'sold_qty' => $netQty,
                'sales_amount' => $netAmt,
            ];
        })
        ->filter(fn($item) => $item['sold_qty'] > 0)
        ->sortByDesc('sold_qty')
        ->values()
        ->take(10);

        return $topItems->map(function ($item, $index) {
            return [
                'rank' => $index + 1,
                'product' => $item['product_name'],
                'image' => $item['product_image'],
                'sold_qty' => (int) $item['sold_qty'],
                'sales_amount' => (float) $item['sales_amount'],
                'branch' => $item['branch_name'],
            ];
        });
    }

    /**
     * Section 4: Branch Sales Statement (Dynamic Month Columns & Returns Deducted)
     */
    private function getBranchSalesStatement($branches, $selectedBranchId, $months)
    {
        $monthHeadings = array_column($months, 'label');
        $activeBranches = $branches;
        if ($selectedBranchId !== 'all' && is_numeric($selectedBranchId)) {
            $activeBranches = $branches->where('id', (int)$selectedBranchId);
        }

        $startDate = $months[0]['start'];
        $endDate = $months[count($months) - 1]['end'];

        // 1. Monthly Quantity Aggregates (Gross POS Qty)
        $monthlyBranchAggregates = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->where('pos.sale_date', '>=', $startDate)
            ->where('pos.sale_date', '<=', $endDate)
            ->where('pos.status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("pos.branch_id, DATE_FORMAT(pos.sale_date, '%Y-%m') as ym_code, COALESCE(SUM(pos_items.quantity), 0) as monthly_qty")
            ->groupBy('pos.branch_id', DB::raw("DATE_FORMAT(pos.sale_date, '%Y-%m')"))
            ->get()
            ->groupBy('branch_id');

        // 1b. Monthly Sales Return Qty
        $monthlyReturnAggregates = DB::table('sale_return_items')
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.return_date', '>=', $startDate)
            ->where('sale_returns.return_date', '<=', $endDate)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw("sale_returns.return_to_id as branch_id, DATE_FORMAT(sale_returns.return_date, '%Y-%m') as ym_code, COALESCE(SUM(sale_return_items.returned_qty), 0) as return_qty")
            ->groupBy('sale_returns.return_to_id', DB::raw("DATE_FORMAT(sale_returns.return_date, '%Y-%m')"))
            ->get()
            ->groupBy('branch_id');

        // 2. Branch Total Sales Value for the range (Gross POS Amount)
        $branchSalesValues = DB::table('pos')
            ->where('sale_date', '>=', $startDate)
            ->where('sale_date', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("branch_id, COALESCE(SUM(total_amount), 0) as total_value")
            ->groupBy('branch_id')
            ->pluck('total_value', 'branch_id');

        // 2b. Branch Total Sale Return Amount for the range
        $branchReturnValues = DB::table('sale_returns')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->where('sale_returns.return_date', '>=', $startDate)
            ->where('sale_returns.return_date', '<=', $endDate)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw("sale_returns.return_to_id as branch_id, COALESCE(SUM(sale_return_items.total_price), 0) as return_value")
            ->groupBy('sale_returns.return_to_id')
            ->pluck('return_value', 'branch_id');

        // 3. Branch Total COGS for the range (Gross COGS)
        $branchCogsValues = DB::table('pos')
            ->join('pos_items', 'pos.id', '=', 'pos_items.pos_sale_id')
            ->join('products', 'pos_items.product_id', '=', 'products.id')
            ->where('pos.sale_date', '>=', $startDate)
            ->where('pos.sale_date', '<=', $endDate)
            ->where('pos.status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("pos.branch_id, COALESCE(SUM(pos_items.quantity * COALESCE(products.cost, 0)), 0) as total_cogs")
            ->groupBy('pos.branch_id')
            ->pluck('total_cogs', 'branch_id');

        // 3b. Branch Total Returned COGS for the range
        $branchReturnCogsValues = DB::table('sale_returns')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'sale_return_items.product_id', '=', 'products.id')
            ->where('sale_returns.return_date', '>=', $startDate)
            ->where('sale_returns.return_date', '<=', $endDate)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw("sale_returns.return_to_id as branch_id, COALESCE(SUM(sale_return_items.returned_qty * COALESCE(products.cost, 0)), 0) as return_cogs")
            ->groupBy('sale_returns.return_to_id')
            ->pluck('return_cogs', 'branch_id');

        $statementRows = [];
        $monthGrandTotals = array_fill(0, count($months), 0);
        $totalYearGrandTotal = 0;
        $totalGrandValue = 0;
        $totalGrandProfit = 0;

        foreach ($activeBranches as $b) {
            $branchMonthlyData = $monthlyBranchAggregates->get($b->id) ?? collect();
            $mappedByMonth = $branchMonthlyData->keyBy('ym_code');

            $branchReturnMonthlyData = $monthlyReturnAggregates->get($b->id) ?? collect();
            $mappedReturnByMonth = $branchReturnMonthlyData->keyBy('ym_code');

            $monthValues = [];
            $yearTotal = 0;

            foreach ($months as $mIdx => $mMeta) {
                $ymKey = $mMeta['ym_code'];
                $grossQty = (float) ($mappedByMonth->has($ymKey) ? $mappedByMonth->get($ymKey)->monthly_qty : 0);
                $retQty = (float) ($mappedReturnByMonth->has($ymKey) ? $mappedReturnByMonth->get($ymKey)->return_qty : 0);
                $netQty = max(0, $grossQty - $retQty);

                $monthValues[] = $netQty;
                $yearTotal += $netQty;
                $monthGrandTotals[$mIdx] += $netQty;
            }

            $grossValue = (float) ($branchSalesValues->get($b->id) ?? 0);
            $returnValue = (float) ($branchReturnValues->get($b->id) ?? 0);
            $netValue = max(0, $grossValue - $returnValue);

            $grossCogs = (float) ($branchCogsValues->get($b->id) ?? 0);
            $returnCogs = (float) ($branchReturnCogsValues->get($b->id) ?? 0);
            $netCogs = max(0, $grossCogs - $returnCogs);

            $totalProfit = $netValue - $netCogs;
            $profitPct = $netValue > 0 ? round(($totalProfit / $netValue) * 100, 2) : 0;

            $totalYearGrandTotal += $yearTotal;
            $totalGrandValue += $netValue;
            $totalGrandProfit += $totalProfit;

            $statementRows[] = [
                'branch' => $b->name,
                'months' => $monthValues,
                'year_total' => $yearTotal,
                'total_value' => $netValue,
                'total_profit' => $totalProfit,
                'profit_pct' => $profitPct
            ];
        }

        $totalGrandProfitPct = $totalGrandValue > 0 ? round(($totalGrandProfit / $totalGrandValue) * 100, 2) : 0;

        return [
            'months' => $monthHeadings,
            'rows' => $statementRows,
            'totals' => [
                'months' => $monthGrandTotals,
                'year_total' => $totalYearGrandTotal,
                'total_value' => $totalGrandValue,
                'total_profit' => $totalGrandProfit,
                'profit_pct' => $totalGrandProfitPct
            ]
        ];
    }

    /**
     * Section 5: Gross Sales Statement (Dynamic Month Columns & Net of Returns)
     */
    private function getGrossSalesStatement($selectedBranchId, $months)
    {
        $monthHeadings = array_column($months, 'label');

        $startDate = $months[0]['start'];
        $endDate = $months[count($months) - 1]['end'];

        // 1. Gross POS Sales Amount per month
        $salesAmountAggregates = DB::table('pos')
            ->where('sale_date', '>=', $startDate)
            ->where('sale_date', '<=', $endDate)
            ->where('status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') as ym_code, COALESCE(SUM(total_amount), 0) as month_amount")
            ->groupBy(DB::raw("DATE_FORMAT(sale_date, '%Y-%m')"))
            ->pluck('month_amount', 'ym_code');

        // 2. Gross POS Sales Qty per month
        $salesQtyAggregates = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->where('pos.sale_date', '>=', $startDate)
            ->where('pos.sale_date', '<=', $endDate)
            ->where('pos.status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("DATE_FORMAT(pos.sale_date, '%Y-%m') as ym_code, COALESCE(SUM(pos_items.quantity), 0) as month_qty")
            ->groupBy(DB::raw("DATE_FORMAT(pos.sale_date, '%Y-%m')"))
            ->pluck('month_qty', 'ym_code');

        // 3. Gross COGS per month
        $cogsAggregates = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->join('products', 'pos_items.product_id', '=', 'products.id')
            ->where('pos.sale_date', '>=', $startDate)
            ->where('pos.sale_date', '<=', $endDate)
            ->where('pos.status', '!=', 'cancelled')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("DATE_FORMAT(pos.sale_date, '%Y-%m') as ym_code, COALESCE(SUM(pos_items.quantity * COALESCE(products.cost, 0)), 0) as total_cogs")
            ->groupBy(DB::raw("DATE_FORMAT(pos.sale_date, '%Y-%m')"))
            ->pluck('total_cogs', 'ym_code');

        // 4. Monthly Sale Returns (Qty and Amount)
        $monthlyReturnAggregates = DB::table('sale_returns')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->where('sale_returns.return_date', '>=', $startDate)
            ->where('sale_returns.return_date', '<=', $endDate)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw("DATE_FORMAT(sale_returns.return_date, '%Y-%m') as ym_code, COALESCE(SUM(sale_return_items.returned_qty), 0) as ret_qty, COALESCE(SUM(sale_return_items.total_price), 0) as ret_amount")
            ->groupBy(DB::raw("DATE_FORMAT(sale_returns.return_date, '%Y-%m')"))
            ->get()
            ->keyBy('ym_code');

        // 5. Monthly Returned COGS
        $monthlyReturnCogsAggregates = DB::table('sale_returns')
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'sale_return_items.product_id', '=', 'products.id')
            ->where('sale_returns.return_date', '>=', $startDate)
            ->where('sale_returns.return_date', '<=', $endDate)
            ->where('sale_returns.status', '!=', 'rejected')
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('sale_returns.return_to_id', (int)$selectedBranchId);
            })
            ->selectRaw("DATE_FORMAT(sale_returns.return_date, '%Y-%m') as ym_code, COALESCE(SUM(sale_return_items.returned_qty * COALESCE(products.cost, 0)), 0) as return_cogs")
            ->groupBy(DB::raw("DATE_FORMAT(sale_returns.return_date, '%Y-%m')"))
            ->pluck('return_cogs', 'ym_code');

        $salesQtys = [];
        $salesAmounts = [];
        $cogsAmounts = [];
        $grossProfits = [];
        $grossProfitPcts = [];

        foreach ($months as $mMeta) {
            $ymKey = $mMeta['ym_code'];
            
            $grossQty = (float) ($salesQtyAggregates->get($ymKey) ?? 0);
            $grossAmt = (float) ($salesAmountAggregates->get($ymKey) ?? 0);
            $grossCogs = (float) ($cogsAggregates->get($ymKey) ?? 0);

            $retAgg = $monthlyReturnAggregates->get($ymKey);
            $retQty = (float) ($retAgg->ret_qty ?? 0);
            $retAmt = (float) ($retAgg->ret_amount ?? 0);
            $retCogs = (float) ($monthlyReturnCogsAggregates->get($ymKey) ?? 0);

            $netQty = max(0, $grossQty - $retQty);
            $netAmt = max(0, $grossAmt - $retAmt);
            $netCogs = max(0, $grossCogs - $retCogs);

            $gp = $netAmt - $netCogs;
            $pct = $netAmt > 0 ? round(($gp / $netAmt) * 100, 2) : 0;

            $salesQtys[] = $netQty;
            $salesAmounts[] = $netAmt;
            $cogsAmounts[] = $netCogs;
            $grossProfits[] = $gp;
            $grossProfitPcts[] = $pct;
        }

        $totalQty = array_sum($salesQtys);
        $totalSalesAmt = array_sum($salesAmounts);
        $totalCogsAmt = array_sum($cogsAmounts);
        $totalGrossProfit = $totalSalesAmt - $totalCogsAmt;
        $totalGrossProfitPct = $totalSalesAmt > 0 ? round(($totalGrossProfit / $totalSalesAmt) * 100, 2) : 0;

        return [
            'months' => $monthHeadings,
            'rows' => [
                'month_sales_qty' => [
                    'label' => 'Month Sales Qty',
                    'values' => $salesQtys,
                    'year_total' => $totalQty,
                    'format' => 'qty'
                ],
                'month_sales_amount' => [
                    'label' => 'Month Sales Amount',
                    'values' => $salesAmounts,
                    'year_total' => $totalSalesAmt,
                    'format' => 'currency'
                ],
                'cogs' => [
                    'label' => 'Cost of Goods Sold (COGS)',
                    'values' => $cogsAmounts,
                    'year_total' => $totalCogsAmt,
                    'format' => 'currency'
                ],
                'gross_profit' => [
                    'label' => 'Gross Profit',
                    'values' => $grossProfits,
                    'year_total' => $totalGrossProfit,
                    'format' => 'currency_highlight'
                ],
                'gross_profit_pct' => [
                    'label' => 'Gross Profit %',
                    'values' => $grossProfitPcts,
                    'year_total' => $totalGrossProfitPct,
                    'format' => 'percent'
                ],
            ]
        ];
    }

    /**
     * Section 6: Expense Statement (Dynamic Month Columns)
     */
    private function getExpenseStatement($selectedBranchId, $months)
    {
        $monthHeadings = array_column($months, 'label');
        $startDate = $months[0]['start'];
        $endDate = $months[count($months) - 1]['end'];

        $allCategories = DB::table('chart_of_accounts')
            ->join('chart_of_account_types', 'chart_of_accounts.type_id', '=', 'chart_of_account_types.id')
            ->where('chart_of_account_types.name', 'like', 'Expense%')
            ->where('chart_of_accounts.name', 'not like', '%Purchase%')
            ->pluck('chart_of_accounts.name')
            ->unique()
            ->values()
            ->toArray();

        $expenseJournalQuery = DB::table('journal_entries')
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('chart_of_accounts', 'journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type_id', '=', 'chart_of_account_types.id')
            ->where('chart_of_account_types.name', 'like', 'Expense%')
            ->where('chart_of_accounts.name', 'not like', '%Purchase%')
            ->where('journals.entry_date', '>=', $startDate)
            ->where('journals.entry_date', '<=', $endDate);

        if ($selectedBranchId !== 'all' && is_numeric($selectedBranchId)) {
            $expenseJournalQuery->where('journals.branch_id', (int)$selectedBranchId);
        }

        $expenseData = $expenseJournalQuery
            ->selectRaw("chart_of_accounts.name as category_name, DATE_FORMAT(journals.entry_date, '%Y-%m') as ym_code, COALESCE(SUM(journal_entries.debit) - SUM(journal_entries.credit), 0) as amount")
            ->groupBy('chart_of_accounts.name', DB::raw("DATE_FORMAT(journals.entry_date, '%Y-%m')"))
            ->get()
            ->groupBy('category_name');

        $categoryRows = [];
        $monthlyTotals = array_fill(0, count($months), 0);
        $totalYearExpense = 0;

        foreach ($allCategories as $catName) {
            $catMonthlyData = $expenseData->get($catName) ?? collect();
            $mappedByMonth = $catMonthlyData->keyBy('ym_code');

            $monthlyVals = [];
            $yearTotal = 0;

            foreach ($months as $mIdx => $mMeta) {
                $ymKey = $mMeta['ym_code'];
                $val = (float) ($mappedByMonth->has($ymKey) ? max(0, $mappedByMonth->get($ymKey)->amount) : 0);

                $monthlyVals[] = $val;
                $yearTotal += $val;
                $monthlyTotals[$mIdx] += $val;
            }

            $totalYearExpense += $yearTotal;

            $categoryRows[] = [
                'category' => $catName,
                'months' => $monthlyVals,
                'year_total' => $yearTotal
            ];
        }

        return [
            'months' => $monthHeadings,
            'categories' => $categoryRows,
            'total' => [
                'months' => $monthlyTotals,
                'year_total' => $totalYearExpense
            ]
        ];
    }
}
