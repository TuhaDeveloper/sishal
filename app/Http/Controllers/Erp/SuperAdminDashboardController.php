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
        $cacheKey = "super_admin_dash_v8_{$selectedBranchId}_{$dateRange}";

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
     * Section 1: Today's Sales — Branch Wise (Respects Timeframe)
     */
    private function getTodaySalesBranchWise($branches, $selectedBranchId, $dateRange)
    {
        $today = Carbon::today()->toDateString();
        [$startBound, $endBound] = $this->getTimeframeBounds($dateRange);

        $activeBranches = $branches;
        if ($selectedBranchId !== 'all' && is_numeric($selectedBranchId)) {
            $activeBranches = $branches->where('id', (int)$selectedBranchId);
        }

        // Aggregate today's POS sales grouped by branch
        $todayPosAggregates = DB::table('pos')
            ->join('pos_items', 'pos.id', '=', 'pos_items.pos_sale_id')
            ->whereDate('pos.sale_date', $today)
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('pos.branch_id, COALESCE(SUM(pos_items.quantity), 0) as today_qty, COALESCE(SUM(pos.total_amount), 0) as today_amount')
            ->groupBy('pos.branch_id')
            ->get()
            ->keyBy('branch_id');

        // Aggregate period POS sales grouped by branch
        $periodPosAggregates = DB::table('pos')
            ->join('pos_items', 'pos.id', '=', 'pos_items.pos_sale_id')
            ->where('pos.sale_date', '>=', $startBound)
            ->where('pos.sale_date', '<=', $endBound)
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('pos.branch_id, COALESCE(SUM(pos_items.quantity), 0) as month_qty, COALESCE(SUM(pos.total_amount), 0) as month_amount')
            ->groupBy('pos.branch_id')
            ->get()
            ->keyBy('branch_id');

        $result = [];
        $totalTodayQty = 0;
        $totalTodayAmount = 0;
        $totalMonthQty = 0;
        $totalMonthAmount = 0;

        foreach ($activeBranches as $b) {
            $todayAgg = $todayPosAggregates->get($b->id);
            $periodAgg = $periodPosAggregates->get($b->id);

            $todayQty = (int) ($todayAgg->today_qty ?? 0);
            $todayAmount = (float) ($todayAgg->today_amount ?? 0);
            $monthQty = (int) ($periodAgg->month_qty ?? 0);
            $monthAmount = (float) ($periodAgg->month_amount ?? 0);

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
     * Section 2: 6 Days Sales Graph
     */
    private function getSixDaysSalesChart($selectedBranchId)
    {
        $startDate = Carbon::now()->subDays(5)->startOfDay()->format('Y-m-d H:i:s');
        $endDate = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        $dailyData = DB::table('pos')
            ->leftJoin('pos_items', 'pos.id', '=', 'pos_items.pos_sale_id')
            ->where('pos.sale_date', '>=', $startDate)
            ->where('pos.sale_date', '<=', $endDate)
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('DATE(pos.sale_date) as sale_day, COALESCE(SUM(pos.total_amount), 0) as total_amount, COALESCE(SUM(pos_items.quantity), 0) as total_qty')
            ->groupBy(DB::raw('DATE(pos.sale_date)'))
            ->get()
            ->keyBy('sale_day');

        $labels = [];
        $amounts = [];
        $quantities = [];

        for ($i = 5; $i >= 0; $i--) {
            $dateObj = Carbon::now()->subDays($i);
            $dayKey = $dateObj->toDateString();
            $labels[] = $dateObj->format('D (d M)');

            $agg = $dailyData->get($dayKey);
            $amounts[] = (float) ($agg->total_amount ?? 0);
            $quantities[] = (int) ($agg->total_qty ?? 0);
        }

        return [
            'labels' => $labels,
            'amounts' => $amounts,
            'quantities' => $quantities,
        ];
    }

    /**
     * Section 3: Top Selling Products (Filters by selected Timeframe)
     */
    private function getTopSellingProducts($selectedBranchId, $dateRange)
    {
        [$startBound, $endBound] = $this->getTimeframeBounds($dateRange);

        $topItems = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->join('products', 'pos_items.product_id', '=', 'products.id')
            ->leftJoin('branches', 'pos.branch_id', '=', 'branches.id')
            ->where('pos.sale_date', '>=', $startBound)
            ->where('pos.sale_date', '<=', $endBound)
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw('products.name as product_name, products.image as product_image, branches.name as branch_name, COALESCE(SUM(pos_items.quantity), 0) as total_qty, COALESCE(SUM(pos_items.total_price), SUM(pos_items.unit_price * pos_items.quantity), 0) as total_amount')
            ->groupBy('pos_items.product_id', 'products.name', 'products.image', 'branches.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return $topItems->map(function ($item, $index) {
            return [
                'rank' => $index + 1,
                'product' => $item->product_name ?? 'Product #' . ($index + 1),
                'image' => $item->product_image ?? null,
                'sold_qty' => (int) $item->total_qty,
                'sales_amount' => (float) $item->total_amount,
                'branch' => $item->branch_name ?? 'Main Branch',
            ];
        });
    }

    /**
     * Section 4: Branch Sales Statement (Dynamic Month Columns)
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

        $monthlyBranchAggregates = DB::table('pos')
            ->where('sale_date', '>=', $startDate)
            ->where('sale_date', '<=', $endDate)
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("branch_id, DATE_FORMAT(sale_date, '%Y-%m') as ym_code, COALESCE(SUM(total_amount), 0) as monthly_sales")
            ->groupBy('branch_id', DB::raw("DATE_FORMAT(sale_date, '%Y-%m')"))
            ->get()
            ->groupBy('branch_id');

        $statementRows = [];
        $monthGrandTotals = array_fill(0, count($months), 0);
        $totalYearGrandTotal = 0;

        foreach ($activeBranches as $b) {
            $branchMonthlyData = $monthlyBranchAggregates->get($b->id) ?? collect();
            $mappedByMonth = $branchMonthlyData->keyBy('ym_code');

            $monthValues = [];
            $yearTotal = 0;

            foreach ($months as $mIdx => $mMeta) {
                $ymKey = $mMeta['ym_code'];
                $val = (float) ($mappedByMonth->has($ymKey) ? $mappedByMonth->get($ymKey)->monthly_sales : 0);
                
                $monthValues[] = $val;
                $yearTotal += $val;
                $monthGrandTotals[$mIdx] += $val;
            }

            $totalYearGrandTotal += $yearTotal;

            $statementRows[] = [
                'branch' => $b->name,
                'months' => $monthValues,
                'year_total' => $yearTotal
            ];
        }

        return [
            'months' => $monthHeadings,
            'rows' => $statementRows,
            'totals' => [
                'months' => $monthGrandTotals,
                'year_total' => $totalYearGrandTotal
            ]
        ];
    }

    /**
     * Section 5: Gross Sales Statement (Dynamic Month Columns)
     */
    private function getGrossSalesStatement($selectedBranchId, $months)
    {
        $monthHeadings = array_column($months, 'label');

        $startDate = $months[0]['start'];
        $endDate = $months[count($months) - 1]['end'];

        $salesAggregates = DB::table('pos')
            ->leftJoin('pos_items', 'pos.id', '=', 'pos_items.pos_sale_id')
            ->where('pos.sale_date', '>=', $startDate)
            ->where('pos.sale_date', '<=', $endDate)
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("DATE_FORMAT(pos.sale_date, '%Y-%m') as ym_code, COALESCE(SUM(pos_items.quantity), 0) as month_qty, COALESCE(SUM(pos.total_amount), 0) as month_amount")
            ->groupBy(DB::raw("DATE_FORMAT(pos.sale_date, '%Y-%m')"))
            ->get()
            ->keyBy('ym_code');

        $cogsAggregates = DB::table('pos')
            ->join('pos_items', 'pos.id', '=', 'pos_items.pos_sale_id')
            ->join('products', 'pos_items.product_id', '=', 'products.id')
            ->where('pos.sale_date', '>=', $startDate)
            ->where('pos.sale_date', '<=', $endDate)
            ->when($selectedBranchId !== 'all' && is_numeric($selectedBranchId), function ($q) use ($selectedBranchId) {
                return $q->where('pos.branch_id', (int)$selectedBranchId);
            })
            ->selectRaw("DATE_FORMAT(pos.sale_date, '%Y-%m') as ym_code, COALESCE(SUM(pos_items.quantity * COALESCE(products.cost, 0)), 0) as total_cogs")
            ->groupBy(DB::raw("DATE_FORMAT(pos.sale_date, '%Y-%m')"))
            ->get()
            ->keyBy('ym_code');

        $salesQtys = [];
        $salesAmounts = [];
        $cogsAmounts = [];
        $grossProfits = [];
        $grossProfitPcts = [];

        foreach ($months as $mMeta) {
            $ymKey = $mMeta['ym_code'];
            
            $sAgg = $salesAggregates->get($ymKey);
            $cAgg = $cogsAggregates->get($ymKey);

            $qty = (int) ($sAgg->month_qty ?? 0);
            $saleAmt = (float) ($sAgg->month_amount ?? 0);
            $cogsAmt = (float) ($cAgg->total_cogs ?? 0);

            $gp = $saleAmt - $cogsAmt;
            $pct = $saleAmt > 0 ? round(($gp / $saleAmt) * 100, 2) : 0;

            $salesQtys[] = $qty;
            $salesAmounts[] = $saleAmt;
            $cogsAmounts[] = $cogsAmt;
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
