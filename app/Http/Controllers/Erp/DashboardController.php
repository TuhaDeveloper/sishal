<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Pos;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Branch;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view dashboard')) {
            // If user has ERP access but no dashboard permission, redirect to profile
            return redirect()->route('erp.profile')->with('error', 'You do not have permission to view the dashboard.');
        }
        $dateRange = $request->get('range', 'week');
        $branchId = $this->getRestrictedBranchId() ?? 0;
        
        // Cache dashboard for 5 minutes (300 seconds)
        $cacheKey = "dash_v2_{$branchId}_{$dateRange}";
        
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function() use ($dateRange) {
            $startDate = $this->getStartDate($dateRange);
            $endDate = Carbon::now();

            return [
                'siteTitle' => \App\Models\GeneralSetting::value('site_title') ?? 'ERP',
                'financialKPIs' => $this->getFinancialKPIs(),
                'topSellingItems' => $this->getTopSellingItems($startDate, $endDate),
                'todayPurchases' => $this->getTodayPurchaseStats(),
                'todayExpenses' => $this->getTodayExpenseStats(),
                'outletPerformance' => $this->getOutletSummary(),
                'lowStockDetailed' => $this->getLowStockDetailed(),
                'recentSalesDetailed' => $this->getRecentSalesDetailed(),
                'salesQtyChart' => $this->getRecentSalesQtyChart()
            ];
        });

        return view('erp.dashboard', array_merge($data, ['range' => $dateRange]));
    }

    public function getDashboardData(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view dashboard')) {
            abort(403, 'Unauthorized action.');
        }
        $dateRange = $request->get('range', 'week');
        $startDate = $this->getStartDate($dateRange);
        $endDate = Carbon::now();

        // Get statistics
        $stats = $this->getStatistics($startDate, $endDate, $dateRange);
        
        // Get sales overview data
        $salesOverview = $this->getSalesOverview($startDate, $endDate, $dateRange);
        
        // Get order status distribution
        $orderStatus = $this->getOrderStatus($startDate, $endDate);
        
        // Get top selling items
        $topSellingItems = $this->getTopSellingItems($startDate, $endDate);
        
        // Get location performance
        $locationPerformance = $this->getLocationPerformance($startDate, $endDate);
        
        // Get current invoices
        $currentInvoices = $this->getCurrentInvoices();
        
        // Get order vs sale comparison
        $comparison = $this->getOrderVsSaleComparison($dateRange);

        return response()->json([
            'stats' => $stats,
            'salesOverview' => $salesOverview,
            'orderStatus' => $orderStatus,
            'topSellingItems' => $topSellingItems,
            'locationPerformance' => $locationPerformance,
            'currentInvoices' => $currentInvoices,
            'comparison' => $comparison
        ]);
    }

    private function getStartDate($range)
    {
        switch ($range) {
            case 'day':
                return Carbon::today();
            case 'week':
                return Carbon::now()->startOfWeek();
            case 'month':
                return Carbon::now()->startOfMonth();
            case 'year':
                return Carbon::now()->startOfYear();
            default:
                return Carbon::now()->startOfWeek();
        }
    }

    private function getStatistics($startDate, $endDate, $range)
    {
        // Get COD percentage from settings
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;

        $branchId = $this->getRestrictedBranchId();

        // Optimized Aggregates for Current Period
        $currentPosQuery = DB::table('pos')
            ->whereBetween('sale_date', [$startDate, $endDate]);
        
        if ($branchId) {
            $currentPosQuery->where('branch_id', $branchId);
        }
        
        $currentPosData = $currentPosQuery->selectRaw('COUNT(*) as total_orders, SUM(total_amount - COALESCE(delivery, 0)) as total_sales')
            ->first();

        // Online orders restricted for branch-level users
        $currentOrderSales = 0;
        $currentOrderOrders = 0;
        
        if (!$branchId) {
            $currentOrderOrders = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            
            $currentOrderSales = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("SUM(
                    (total - COALESCE(delivery, 0)) - 
                    CASE 
                        WHEN payment_method = 'cash' THEN ROUND(total * $codPercentage, 2)
                        ELSE 0 
                    END
                ) as total_sales")
                ->value('total_sales') ?? 0;
        }

        $previousStartDate = $this->getPreviousPeriodStart($startDate, $range);
        $previousEndDate = $startDate->copy()->subDay();

        // Optimized Aggregates for Previous Period
        $previousPosQuery = DB::table('pos')
            ->whereBetween('sale_date', [$previousStartDate, $previousEndDate]);
            
        if ($branchId) {
            $previousPosQuery->where('branch_id', $branchId);
        }
        
        $previousPosData = $previousPosQuery->selectRaw('COUNT(*) as total_orders, SUM(total_amount - COALESCE(delivery, 0)) as total_sales')
            ->first();

        $previousOrderSales = 0;
        $previousOrderOrders = 0;
        
        if (!$branchId) {
            $previousOrderSales = DB::table('orders')
                ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
                ->selectRaw("SUM(
                    (total - COALESCE(delivery, 0)) - 
                    CASE 
                        WHEN payment_method = 'cash' THEN ROUND(total * $codPercentage, 2)
                        ELSE 0 
                    END
                ) as total_sales")
                ->value('total_sales') ?? 0;

            $previousOrderOrders = DB::table('orders')
                ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
                ->count();
        }

        // Combine Totals
        $currentSales = ($currentPosData->total_sales ?? 0) + $currentOrderSales;
        $currentOrders = ($currentPosData->total_orders ?? 0) + $currentOrderOrders;
        $currentAvgOrder = $currentOrders > 0 ? $currentSales / $currentOrders : 0;

        $previousSales = ($previousPosData->total_sales ?? 0) + $previousOrderSales;
        $previousOrders = ($previousPosData->total_orders ?? 0) + $previousOrderOrders;
        $previousAvgOrder = $previousOrders > 0 ? $previousSales / $previousOrders : 0;

        // Calculate percentages
        $salesPercentage = $previousSales > 0 ? (($currentSales - $previousSales) / $previousSales) * 100 : 0;
        $ordersPercentage = $previousOrders > 0 ? (($currentOrders - $previousOrders) / $previousOrders) * 100 : 0;
        $avgOrderPercentage = $previousAvgOrder > 0 ? (($currentAvgOrder - $previousAvgOrder) / $previousAvgOrder) * 100 : 0;

        $satisfactionData = $this->getCustomerSatisfaction($startDate, $endDate);

        return [
            'totalSales' => [
                'value' => number_format($currentSales, 2),
                'percentage' => round($salesPercentage, 1),
                'trend' => $salesPercentage >= 0 ? 'up' : 'down'
            ],
            'totalOrders' => [
                'value' => (int)$currentOrders,
                'percentage' => round($ordersPercentage, 1),
                'trend' => $ordersPercentage >= 0 ? 'up' : 'down'
            ],
            'averageOrder' => [
                'value' => number_format($currentAvgOrder, 2),
                'percentage' => round($avgOrderPercentage, 1),
                'trend' => $avgOrderPercentage >= 0 ? 'up' : 'down'
            ],
            'customerSatisfaction' => [
                'value' => $satisfactionData['rating'],
                'percentage' => $satisfactionData['percentage'],
                'trend' => 'up'
            ]
        ];
    }

    private function getPreviousPeriodStart($startDate, $range)
    {
        switch ($range) {
            case 'day':
                return $startDate->copy()->subDay();
            case 'week':
                return $startDate->copy()->subWeek();
            case 'month':
                return $startDate->copy()->subMonth();
            case 'year':
                return $startDate->copy()->subYear();
            default:
                return $startDate->copy()->subWeek();
        }
    }

    private function getSalesOverview($startDate, $endDate, $range)
    {
        $branchId = $this->getRestrictedBranchId();
        
        // Get POS data
        $posQuery = Pos::query();
        if ($branchId) {
            $posQuery->where('branch_id', $branchId);
        }
        
        // Get Online Order data (blocked if branch restricted)
        $orderQuery = Order::query();
        if ($branchId) {
            $orderQuery->whereRaw('1 = 0'); // Empty set
        }
        
        switch ($range) {
            case 'day':
                $posQuery->selectRaw('HOUR(sale_date) as period, SUM(total_amount - COALESCE(delivery, 0)) as total')
                      ->whereDate('sale_date', $startDate)
                      ->groupBy('period')
                      ->orderBy('period');
                $orderQuery->selectRaw('HOUR(created_at) as period, SUM(total - COALESCE(delivery, 0)) as total')
                      ->whereDate('created_at', $startDate)
                      ->groupBy('period')
                      ->orderBy('period');
                // 0..23 hours
                $labels = range(0, 23);
                break;
            case 'week':
                $posQuery->selectRaw("DATE_FORMAT(sale_date, '%a') as period, DAYOFWEEK(sale_date) as sort_key, SUM(total_amount - COALESCE(delivery, 0)) as total")
                      ->whereBetween('sale_date', [$startDate, $endDate])
                      ->groupBy('sort_key', 'period')
                      ->orderBy('sort_key');
                $orderQuery->selectRaw("DATE_FORMAT(created_at, '%a') as period, DAYOFWEEK(created_at) as sort_key, SUM(total - COALESCE(delivery, 0)) as total")
                      ->whereBetween('created_at', [$startDate, $endDate])
                      ->groupBy('sort_key', 'period')
                      ->orderBy('sort_key');
                $labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                break;
            case 'month':
                $posQuery->selectRaw('DATE(sale_date) as period, SUM(total_amount - COALESCE(delivery, 0)) as total')
                      ->whereBetween('sale_date', [$startDate, $endDate])
                      ->groupBy('period')
                      ->orderBy('period');
                $orderQuery->selectRaw('DATE(created_at) as period, SUM(total - COALESCE(delivery, 0)) as total')
                      ->whereBetween('created_at', [$startDate, $endDate])
                      ->groupBy('period')
                      ->orderBy('period');
                // Generate a label for each day in range
                $labels = [];
                $cursor = $startDate->copy();
                while ($cursor->lte($endDate)) {
                    $labels[] = $cursor->toDateString();
                    $cursor->addDay();
                }
                break;
            case 'year':
                $posQuery->selectRaw("DATE_FORMAT(sale_date, '%b') as period, MONTH(sale_date) as sort_key, SUM(total_amount - COALESCE(delivery, 0)) as total")
                      ->whereBetween('sale_date', [$startDate, $endDate])
                      ->groupBy('sort_key', 'period')
                      ->orderBy('sort_key');
                $orderQuery->selectRaw("DATE_FORMAT(created_at, '%b') as period, MONTH(created_at) as sort_key, SUM(total - COALESCE(delivery, 0)) as total")
                      ->whereBetween('created_at', [$startDate, $endDate])
                      ->groupBy('sort_key', 'period')
                      ->orderBy('sort_key');
                $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                break;
        }

        $posData = $posQuery->get();
        $orderData = $orderQuery->get();
        $salesData = [];
        
        foreach ($labels as $label) {
            $posPeriodData = $posData->firstWhere('period', $label);
            $orderPeriodData = $orderData->firstWhere('period', $label);
            
            $posTotal = $posPeriodData ? (float)$posPeriodData->total : 0.0;
            $orderTotal = $orderPeriodData ? (float)$orderPeriodData->total : 0.0;
            
            $salesData[] = $posTotal + $orderTotal;
        }

        $totalSales = array_sum($salesData);
        $average = count($salesData) > 0 ? $totalSales / count($salesData) : 0;
        $peakDay = 'N/A';
        if (!empty($salesData)) {
            $maxVal = max($salesData);
            $peakIndex = array_search($maxVal, $salesData, true);
            if ($peakIndex !== false && isset($labels[$peakIndex])) {
                $peakDay = $labels[$peakIndex];
            }
        }

        return [
            'labels' => $labels,
            'data' => $salesData,
            'totalSales' => number_format($totalSales, 2),
            'average' => number_format($average, 2),
            'peakDay' => $peakDay
        ];
    }

    private function getOrderStatus($startDate, $endDate)
    {
        $branchId = $this->getRestrictedBranchId();
        
        // Get POS status data
        $posSubQuery = Pos::whereBetween('sale_date', [$startDate, $endDate]);
        if ($branchId) {
            $posSubQuery->where('branch_id', $branchId);
        }
        
        $posStatuses = $posSubQuery->selectRaw('status, COUNT(*) as count')
                               ->groupBy('status')
                               ->get();

        // Get Online Order status data
        $orderStatuses = collect();
        if (!$branchId) {
            $orderQuery = Order::query();
            $orderQuery->whereBetween('created_at', [$startDate, $endDate]);
            $orderStatuses = $orderQuery->selectRaw('status, COUNT(*) as count')
                                       ->groupBy('status')
                                       ->get();
        }

        // Combine status counts
        $pending = ($posStatuses->where('status', 'pending')->first()->count ?? 0) + 
                   ($orderStatuses->where('status', 'pending')->first()->count ?? 0);
        $delivered = ($posStatuses->where('status', 'delivered')->first()->count ?? 0) + 
                     ($orderStatuses->where('status', 'delivered')->first()->count ?? 0);
        $shipping = ($posStatuses->where('status', 'shipping')->first()->count ?? 0) + 
                    ($orderStatuses->where('status', 'shipping')->first()->count ?? 0);
        $cancelled = ($posStatuses->where('status', 'cancelled')->first()->count ?? 0) + 
                     ($orderStatuses->where('status', 'cancelled')->first()->count ?? 0);

        // Add online order specific statuses
        $approved = $orderStatuses->where('status', 'approved')->first()->count ?? 0;
        $processing = $orderStatuses->where('status', 'processing')->first()->count ?? 0;

        return [
            'pending' => $pending,
            'delivered' => $delivered,
            'shipping' => $shipping,
            'cancelled' => $cancelled,
            'approved' => $approved,
            'processing' => $processing,
            'total' => $pending + $delivered + $shipping + $cancelled + $approved + $processing
        ];
    }

    private function getTopSellingItems($startDate, $endDate)
    {
        $branchId = $this->getRestrictedBranchId();
        try {
            // Get POS sales aggregates per product
            $posQuery = DB::table('pos_items')
                ->whereBetween('created_at', [$startDate, $endDate]);
            
            if ($branchId) {
                $posQuery->whereHas('pos', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $posSales = $posQuery->select('product_id', DB::raw('SUM(quantity) as pos_qty, SUM(total_price) as pos_rev'))
                ->groupBy('product_id');

            // Get Online sales (only if not restricted to a branch)
            $orderSales = null;
            if (!$branchId) {
                $orderSales = DB::table('order_items')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->select('product_id', DB::raw('SUM(quantity) as order_qty, SUM(total_price) as order_rev'))
                    ->groupBy('product_id');
            }

            // Combine using Products table as base
            $query = DB::table('products')
                ->leftJoinSub($posSales, 'pos_summary', 'products.id', '=', 'pos_summary.product_id');
                
            if ($orderSales) {
                $query->leftJoinSub($orderSales, 'order_summary', 'products.id', '=', 'order_summary.product_id');
            }
            
            $topItems = $query->leftJoinSub(DB::table('product_service_categories'), 'cats', 'products.category_id', '=', 'cats.id')
                ->selectRaw('products.name, 
                    cats.name as category_name,
                    (COALESCE(pos_summary.pos_qty, 0) + ' . ($orderSales ? 'COALESCE(order_summary.order_qty, 0)' : '0') . ') as total_sold,
                    (COALESCE(pos_summary.pos_rev, 0) + ' . ($orderSales ? 'COALESCE(order_summary.order_rev, 0)' : '0') . ') as total_revenue')
                ->where('products.type', 'product')
                ->where('products.status', 'active')
                ->where(function($q) {
                    $q->whereNotNull('pos_summary.product_id')->orWhereNotNull('order_summary.product_id');
                })
                ->orderByDesc('total_sold')
                ->take(5)
                ->get();

            $totalSoldAll = $topItems->sum('total_sold');
            $colors = ['primary', 'success', 'warning', 'info', 'danger'];
            $icons = ['fas fa-box', 'fas fa-shopping-cart', 'fas fa-star', 'fas fa-trophy', 'fas fa-fire'];

            return $topItems->map(function ($item, $index) use ($totalSoldAll, $colors, $icons) {
                return [
                    'name' => $item->name,
                    'category' => $item->category_name ?? 'Uncategorized',
                    'sales' => (float)$item->total_sold,
                    'revenue' => number_format($item->total_revenue, 2),
                    'percentage' => $totalSoldAll > 0 ? round(($item->total_sold / $totalSoldAll) * 100, 1) : 0,
                    'icon' => $icons[$index % 5],
                    'color' => $colors[$index % 5]
                ];
            })->toArray();
            
        } catch (\Exception $e) {
            \Log::error('Error getting top selling items: ' . $e->getMessage());
            return [];
        }
    }

    private function getLocationPerformance($startDate, $endDate)
    {
        $branchId = $this->getRestrictedBranchId();
        $query = Pos::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $locations = $query->join('branches', 'pos.branch_id', '=', 'branches.id')
                          ->selectRaw('branches.name, SUM(pos.total_amount - COALESCE(pos.delivery, 0)) as total_sales')
                          ->whereBetween('pos.sale_date', [$startDate, $endDate])
                          ->groupBy('branches.id', 'branches.name')
                          ->orderBy('total_sales', 'desc')
                          ->get();

        return [
            'labels' => $locations->pluck('name')->toArray(),
            'data' => $locations->pluck('total_sales')->toArray()
        ];
    }

    private function getCurrentInvoices()
    {
        $branchId = $this->getRestrictedBranchId();
        $query = Invoice::query();
        
        if ($branchId) {
            $query->whereHas('pos', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        return $query->with(['pos.customer'])
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function($invoice) {
                        return [
                            'id' => $invoice->invoice_number,
                            'customer' => $invoice->pos->customer->name ?? 'Walk-in Customer',
                            'amount' => number_format($invoice->total_amount, 2),
                            'status' => $invoice->status
                        ];
                    });
    }

    private function getOrderVsSaleComparison($range)
    {
        $startDate = $this->getStartDate($range);
        $endDate = Carbon::now();

        // This would typically compare orders vs actual sales
        // For now, returning mock data
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $orders = [65, 78, 82, 75, 89, 95];
        $sales = [58, 72, 76, 68, 82, 88];

        return [
            'labels' => $labels,
            'orders' => $orders,
            'sales' => $sales
        ];
    }

    private function getCustomerSatisfaction($startDate, $endDate)
    {
        // Get reviews from the current period
        $currentReviews = Review::where('is_approved', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Get reviews from the previous period for comparison
        $previousStartDate = $startDate->copy()->subWeek();
        $previousEndDate = $startDate->copy()->subDay();
        $previousReviews = Review::where('is_approved', true)
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->get();

        // Calculate current period satisfaction
        $currentRating = 0;
        $currentCount = $currentReviews->count();
        if ($currentCount > 0) {
            $currentRating = $currentReviews->avg('rating');
        }

        // Calculate previous period satisfaction
        $previousRating = 0;
        $previousCount = $previousReviews->count();
        if ($previousCount > 0) {
            $previousRating = $previousReviews->avg('rating');
        }

        // If no reviews in current period, use overall average
        if ($currentCount == 0) {
            $overallReviews = Review::where('is_approved', true)->get();
            $currentRating = $overallReviews->count() > 0 ? $overallReviews->avg('rating') : 0;
            $currentCount = $overallReviews->count();
        }

        // Calculate percentage change
        $percentage = 0;
        if ($previousRating > 0) {
            $percentage = (($currentRating - $previousRating) / $previousRating) * 100;
        } elseif ($currentRating > 0) {
            $percentage = 100; // New data, consider it as improvement
        }

        return [
            'rating' => round($currentRating, 1),
            'percentage' => round($percentage, 1),
            'count' => $currentCount,
            'trend' => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    private function getLowStockItems()
    {
        $branchId = $this->getRestrictedBranchId();
        
        // Optimized: Calculate total stock across all sources in SQL
        $query = \App\Models\Product::where('manage_stock', true)
            ->where('status', 'active')
            ->with('category');
            
        if ($branchId) {
            $query->withSum(['variationStocks as total_stock' => function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            }], 'quantity');
        } else {
            $query->withSum('variationStocks as total_stock', 'quantity');
        }
        
        return $query->having('total_stock', '<', 10)
            ->orderBy('total_stock', 'asc')
            ->take(5)
            ->get()
            ->map(function($product) {
                return [
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'stock' => (int)($product->total_stock ?? 0),
                    'sku' => $product->sku
                ];
            });
    }

    private function getProfitMetrics($startDate, $endDate, $range)
    {
        $branchId = $this->getRestrictedBranchId();
        
        // Get COD percentage from settings
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;

        $periods = [
            'current' => [$startDate, $endDate],
            'previous' => [$this->getPreviousPeriodStart($startDate, $range), $startDate->copy()->subDay()]
        ];

        $metrics = [];
        foreach ($periods as $key => $period) {
            // POS Revenue & Cost
            $posQuery = DB::table('pos_items')
                ->join('products', 'pos_items.product_id', '=', 'products.id')
                ->whereBetween('pos_items.created_at', [$period[0], $period[1]]);
            
            if ($branchId) {
                $posQuery->whereHas('pos', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $pos = $posQuery->selectRaw('SUM(pos_items.total_price) as revenue, SUM(pos_items.quantity * products.cost) as cost')
                ->first();

            // Online Revenue & Cost (blocked if branch restricted)
            $orderRevenue = 0;
            $orderCost = 0;
            $codDiscount = 0;

            if (!$branchId) {
                $orders = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereBetween('order_items.created_at', [$period[0], $period[1]])
                    ->selectRaw('SUM(order_items.total_price) as revenue, SUM(order_items.quantity * products.cost) as cost')
                    ->first();
                
                $orderRevenue = $orders->revenue ?? 0;
                $orderCost = $orders->cost ?? 0;

                $codDiscount = DB::table('orders')
                    ->whereBetween('created_at', [$period[0], $period[1]])
                    ->where('payment_method', 'cash')
                    ->selectRaw("SUM(ROUND(total * $codPercentage, 2)) as discount")
                    ->value('discount') ?? 0;
            }

            $rev = ($pos->revenue ?? 0) + $orderRevenue - $codDiscount;
            $totalCost = ($pos->cost ?? 0) + $orderCost;
            $metrics[$key] = ['revenue' => $rev, 'cost' => $totalCost, 'profit' => $rev - $totalCost];
        }

        $currentProfit = $metrics['current']['profit'];
        $previousProfit = $metrics['previous']['profit'];
        $currentMargin = $metrics['current']['revenue'] > 0 ? ($currentProfit / $metrics['current']['revenue']) * 100 : 0;
        
        $profitPercentage = $previousProfit > 0 ? (($currentProfit - $previousProfit) / $previousProfit) * 100 : 0;

        return [
            'profit' => number_format($currentProfit, 2),
            'margin' => round($currentMargin, 1),
            'percentage' => round($profitPercentage, 1),
            'trend' => $profitPercentage >= 0 ? 'up' : 'down'
        ];
    }

    private function getChannelBreakdown($startDate, $endDate)
    {
        $branchId = $this->getRestrictedBranchId();
        
        // Get COD percentage
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;

        // POS Sales
        $posQuery = DB::table('pos')
            ->whereBetween('sale_date', [$startDate, $endDate]);
            
        if ($branchId) {
            $posQuery->where('branch_id', $branchId);
        }
        
        $posData = $posQuery->selectRaw('COUNT(*) as total_orders, SUM(total_amount - COALESCE(delivery, 0)) as total_sales')
            ->first();
        
        $posRevenue = $posData->total_sales ?? 0;
        $posOrders = $posData->total_orders ?? 0;

        // Online Sales - Using database aggregate for revenue with COD logic
        $onlineRevenue = 0;
        $onlineOrdersCount = 0;
        $pendingOrders = 0;
        
        if (!$branchId) {
            $onlineData = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("COUNT(*) as total_orders, 
                    SUM(
                        (total - COALESCE(delivery, 0)) - 
                        CASE 
                            WHEN payment_method = 'cash' THEN ROUND(total * $codPercentage, 2)
                            ELSE 0 
                        END
                    ) as total_sales")
                ->first();

            $onlineRevenue = $onlineData->total_sales ?? 0;
            $onlineOrdersCount = $onlineData->total_orders ?? 0;

            // Pending orders (need attention)
            $pendingOrders = DB::table('orders')
                ->whereIn('status', ['pending', 'approved'])
                ->count();
        }

        $totalRevenue = $posRevenue + $onlineRevenue;

        return [
            'pos' => [
                'revenue' => number_format($posRevenue, 2),
                'orders' => $posOrders,
                'percentage' => $totalRevenue > 0 ? round(($posRevenue / $totalRevenue) * 100, 1) : 0
            ],
            'online' => [
                'revenue' => number_format($onlineRevenue, 2),
                'orders' => $onlineOrdersCount,
                'percentage' => $totalRevenue > 0 ? round(($onlineRevenue / $totalRevenue) * 100, 1) : 0
            ],
            'pending' => $pendingOrders
        ];
    }

    private function getTodayPurchaseStats()
    {
        $branchId = $this->getRestrictedBranchId();
        $today = Carbon::today();
        
        // Today's Gross Purchase
        $grossQuery = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->whereDate('purchases.purchase_date', $today);
            
        if ($branchId) {
            // Fix: Purchases table uses location_id and ship_location_type instead of branch_id
            $grossQuery->where('purchases.ship_location_type', 'branch')
                       ->where('purchases.location_id', $branchId);
        }
            
        $gross = $grossQuery->selectRaw('SUM(purchase_items.total_price) as total_amount, SUM(purchase_items.quantity) as total_qty')
            ->first();

        // Today's Actual Purchase (Billed)
        $actualQuery = DB::table('purchase_bills')
            ->whereDate('bill_date', $today);
            
        if ($branchId) {
            // Fix: Purchase bills don't have branch_id, join with purchases to filter
            $actualQuery->join('purchases', 'purchase_bills.purchase_id', '=', 'purchases.id')
                       ->where('purchases.ship_location_type', 'branch')
                       ->where('purchases.location_id', $branchId);
        }
            
        $actual = $actualQuery->selectRaw('SUM(purchase_bills.total_amount) as total_amount')
            ->first();

        return [
            'gross_amount' => number_format($gross->total_amount ?? 0, 2),
            'gross_qty' => (int)($gross->total_qty ?? 0),
            'actual_amount' => number_format($actual->total_amount ?? 0, 2),
            'actual_qty' => (int)($gross->total_qty ?? 0)
        ];
    }

    private function getTodayExpenseStats()
    {
        $branchId = $this->getRestrictedBranchId();
        
        $expenseTypeIds = DB::table('chart_of_account_types')->where('name', 'Expense')->pluck('id');
        $expenseAccountIds = DB::table('chart_of_accounts')
            ->whereIn('type_id', $expenseTypeIds)
            ->orWhereIn('parent_id', function($q) use ($expenseTypeIds) {
                $q->from('chart_of_account_parents')->select('id')->whereIn('type_id', $expenseTypeIds);
            })
            ->pluck('id');

        $query = DB::table('journals')
            ->whereDate('entry_date', Carbon::today())
            ->whereIn('expense_account_id', $expenseAccountIds);
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
            
        return number_format($query->sum('voucher_amount'), 2);
    }

    private function getOutletSummary()
    {
        $branchId = $this->getRestrictedBranchId();
        
        $query = \App\Models\Branch::query();
        if ($branchId) {
            $query->where('id', $branchId);
        }
        
        $branches = $query->get();
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        return $branches->map(function($branch) use ($today, $monthStart, $monthEnd) {
            // Today's Sales Amount
            $grossTodaySales = DB::table('pos')
                ->where('branch_id', $branch->id)
                ->whereDate('sale_date', $today)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('SUM(total_amount - COALESCE(delivery, 0)) as amount')
                ->value('amount') ?? 0;

            $todayReturnSales = DB::table('sale_returns')
                ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->where('sale_returns.return_to_id', $branch->id)
                ->whereDate('sale_returns.return_date', $today)
                ->sum('sale_return_items.total_price');

            $todaySales = max(0, $grossTodaySales - $todayReturnSales);

            // Today's Sales Qty
            $grossTodayQty = DB::table('pos_items')
                ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
                ->where('pos.branch_id', $branch->id)
                ->whereDate('pos.sale_date', $today)
                ->where('pos.status', '!=', 'cancelled')
                ->sum('pos_items.quantity');

            $todayReturnQty = DB::table('sale_returns')
                ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->where('sale_returns.return_to_id', $branch->id)
                ->whereDate('sale_returns.return_date', $today)
                ->sum('sale_return_items.returned_qty');

            $todayQty = max(0, $grossTodayQty - $todayReturnQty);

            // Monthly Sales Amount
            $grossMonthSales = DB::table('pos')
                ->where('branch_id', $branch->id)
                ->whereBetween('sale_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled')
                ->selectRaw('SUM(total_amount - COALESCE(delivery, 0)) as amount')
                ->value('amount') ?? 0;

            $monthReturnSales = DB::table('sale_returns')
                ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->where('sale_returns.return_to_id', $branch->id)
                ->whereBetween('sale_returns.return_date', [$monthStart, $monthEnd])
                ->sum('sale_return_items.total_price');

            $monthSales = max(0, $grossMonthSales - $monthReturnSales);

            // Monthly Sales Qty
            $grossMonthQty = DB::table('pos_items')
                ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
                ->where('pos.branch_id', $branch->id)
                ->whereBetween('pos.sale_date', [$monthStart, $monthEnd])
                ->where('pos.status', '!=', 'cancelled')
                ->sum('pos_items.quantity');

            $monthReturnQty = DB::table('sale_returns')
                ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->where('sale_returns.return_to_id', $branch->id)
                ->whereBetween('sale_returns.return_date', [$monthStart, $monthEnd])
                ->sum('sale_return_items.returned_qty');

            $monthQty = max(0, $grossMonthQty - $monthReturnQty);

            return [
                'name' => $branch->name,
                'today_amount' => $todaySales,
                'today_qty' => $todayQty,
                'month_amount' => $monthSales,
                'month_qty' => $monthQty
            ];
        });
    }

    private function getLowStockDetailed()
    {
        $branchId = $this->getRestrictedBranchId();
        
        $query = \App\Models\ProductVariationStock::with(['variation.product.category', 'variation.product.brand', 'variation.product.season', 'variation.product.gender', 'variation.combinations.attribute', 'variation.combinations.attributeValue', 'branch'])
            ->where('quantity', '<', 10)
            ->orderBy('quantity', 'asc')
            ->take(10);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()->map(function($stock) {
            $variation = $stock->variation;
            $product = $variation->product ?? null;
            $size = 'N/A';
            $color = 'N/A';
            
            if ($variation) {
                foreach ($variation->combinations as $combo) {
                    if (stripos($combo->attribute->name, 'size') !== false) $size = $combo->attributeValue->value;
                    if (stripos($combo->attribute->name, 'color') !== false) $color = $combo->attributeValue->value;
                }
            }

            return [
                'branch' => $stock->branch->name ?? 'N/A',
                'category' => $product->category->name ?? 'N/A',
                'brand' => $product->brand->name ?? 'N/A',
                'season' => $product->season->name ?? 'N/A',
                'gender' => $product->gender->name ?? 'N/A',
                'product_name' => $product->name ?? 'N/A',
                'style_number' => $product->sku ?? 'N/A',
                'size' => $size,
                'color' => $color,
                'limit' => 10,
                'current' => $stock->quantity
            ];
        });
    }

    public static function clearCache()
    {
        try {
            $ranges = ['day', 'week', 'month', 'year'];
            $branches = \App\Models\Branch::pluck('id')->toArray();
            $branchIds = array_merge([0, null], $branches);
            foreach ($branchIds as $bId) {
                $b = $bId ?? 0;
                foreach ($ranges as $r) {
                    \Illuminate\Support\Facades\Cache::forget("dash_v1_{$b}_{$r}");
                }
            }
        } catch (\Exception $e) {
            // Ignore cache exception
        }
    }

    private function getRecentSalesDetailed()
    {
        $branchId = $this->getRestrictedBranchId();
        
        $query = \App\Models\Pos::with(['customer', 'branch', 'invoice'])
            ->latest('sale_date')
            ->take(10);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()->map(function($sale) {
            $paid = DB::table('payments')
                ->where(function($q) use ($sale) {
                    $q->where('pos_id', $sale->id);
                    if ($sale->invoice_id) {
                        $q->orWhere('invoice_id', $sale->invoice_id);
                    }
                })
                ->sum('amount');

            $due = max(0, $sale->total_amount - $paid);

            return [
                'invoice_no' => $sale->sale_number,
                'challan_no' => $sale->challan_number ?? 'N/A',
                'date' => $sale->sale_date,
                'customer' => $sale->customer->name ?? 'Guest',
                'total' => $sale->total_amount,
                'paid' => $paid,
                'due' => $due,
                'status' => $sale->status
            ];
        });
    }

    private function getRecentSalesQtyChart()
    {
        $branchId = $this->getRestrictedBranchId();
        $startDate = Carbon::today()->subDays(6);
        
        $query = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_sale_id', '=', 'pos.id')
            ->whereDate('pos.sale_date', '>=', $startDate)
            ->selectRaw('DATE(pos.sale_date) as date, SUM(pos_items.quantity) as total_qty, SUM(pos_items.total_price) as total_rev')
            ->groupBy('date');
        
        if ($branchId) {
            $query->where('pos.branch_id', $branchId);
        }
        
        $results = $query->get()->keyBy('date');
        
        $labels = [];
        $qtyData = [];
        $revData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $labels[] = $date->format('D, M d');
            
            $stats = $results->get($dateStr);
            $qtyData[] = (int)($stats->total_qty ?? 0);
            $revData[] = (float)($stats->total_rev ?? 0);
        }

        return [
            'labels' => $labels,
            'qtyData' => $qtyData,
            'revData' => $revData
        ];
    }

    private function getFinancialKPIs()
    {
        $branchId = $this->getRestrictedBranchId();
        $today = Carbon::today();

        // Today's Total Sales
        $salesQuery = DB::table('pos')->whereDate('sale_date', $today);
        if ($branchId) $salesQuery->where('branch_id', $branchId);
        $posSales = $salesQuery->sum('total_amount');

        $onlineSales = 0;
        if (!$branchId) {
            $onlineSales = DB::table('orders')->whereDate('created_at', $today)->sum('total');
        }
        $totalSalesValue = $posSales + $onlineSales;

        // Today's Total Collection (payments made today)
        $collectionQuery = DB::table('payments')->whereDate('payment_date', $today);
        if ($branchId) {
            $collectionQuery->where(function($q) use ($branchId) {
                $q->whereIn('pos_id', function($sub) use ($branchId) {
                    $sub->from('pos')->select('id')->where('branch_id', $branchId);
                })
                ->orWhereIn('invoice_id', function($sub) use ($branchId) {
                    $sub->from('invoices')
                        ->join('pos', 'invoices.id', '=', 'pos.invoice_id')
                        ->select('invoices.id')
                        ->where('pos.branch_id', $branchId);
                })
                ->orWhereIn('customer_id', function($sub) use ($branchId) {
                    $sub->from('customers')->select('id')->where('branch_id', $branchId);
                })
                ->orWhereIn('account_id', function($sub) use ($branchId) {
                    $sub->from('financial_accounts')->select('id')->where('branch_id', $branchId);
                });
            });
        }
        $totalCollection = $collectionQuery->sum('amount') ?? 0;

        // Today's Total Due (due remaining on today's POS sales)
        $todayPosSales = DB::table('pos')->whereDate('sale_date', $today);
        if ($branchId) $todayPosSales->where('branch_id', $branchId);
        $posList = $todayPosSales->get(['id', 'invoice_id', 'total_amount']);

        $totalDue = 0;
        foreach ($posList as $posItem) {
            $paidForSale = DB::table('payments')
                ->where(function($q) use ($posItem) {
                    $q->where('pos_id', $posItem->id);
                    if ($posItem->invoice_id) {
                        $q->orWhere('invoice_id', $posItem->invoice_id);
                    }
                })
                ->sum('amount');

            $dueForSale = max(0, $posItem->total_amount - $paidForSale);
            $totalDue += $dueForSale;
        }

        return [
            'total_sales' => number_format($totalSalesValue, 2),
            'total_collection' => number_format($totalCollection, 2),
            'total_due' => number_format(max(0, $totalDue), 2)
        ];
    }
}
