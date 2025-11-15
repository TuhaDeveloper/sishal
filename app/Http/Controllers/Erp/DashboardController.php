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
        $dateRange = $request->get('range', 'week');
        $startDate = $this->getStartDate($dateRange);
        $endDate = Carbon::now();

        $baseQuery = Pos::query();

        $stats = $this->getStatistics($baseQuery, $startDate, $endDate, $dateRange);
        $salesOverview = $this->getSalesOverview($baseQuery, $startDate, $endDate, $dateRange);
        $orderStatus = $this->getOrderStatus($baseQuery, $startDate, $endDate);
        $currentInvoices = $this->getCurrentInvoices();

        return view('erp.dashboard', [
            'range' => $dateRange,
            'stats' => $stats,
            'salesOverview' => $salesOverview,
            'orderStatus' => $orderStatus,
            'currentInvoices' => $currentInvoices,
        ]);
    }

    public function getDashboardData(Request $request)
    {
        $dateRange = $request->get('range', 'week');
        $startDate = $this->getStartDate($dateRange);
        $endDate = Carbon::now();

        // Base query with branch filter
        $baseQuery = Pos::query();

        // Get statistics
        $stats = $this->getStatistics($baseQuery, $startDate, $endDate, $dateRange);
        
        // Get sales overview data
        $salesOverview = $this->getSalesOverview($baseQuery, $startDate, $endDate, $dateRange);
        
        // Get order status distribution
        $orderStatus = $this->getOrderStatus($baseQuery, $startDate, $endDate);
        
        // Get top selling items
        $topSellingItems = $this->getTopSellingItems($baseQuery, $startDate, $endDate);
        
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

    private function getStatistics($baseQuery, $startDate, $endDate, $range)
    {
        // Get POS data
        $currentPosQuery = clone $baseQuery;
        $currentPosQuery->whereBetween('sale_date', [$startDate, $endDate]);
        
        $previousPosQuery = clone $baseQuery;
        $previousStartDate = $this->getPreviousPeriodStart($startDate, $range);
        $previousEndDate = $startDate->copy()->subDay();
        $previousPosQuery->whereBetween('sale_date', [$previousStartDate, $previousEndDate]);

        // Get Online Order data
        $currentOrderQuery = Order::query();
        $currentOrderQuery->whereBetween('created_at', [$startDate, $endDate]);

        $previousOrderQuery = Order::query();
        $previousOrderQuery->whereBetween('created_at', [$previousStartDate, $previousEndDate]);

        // Current period stats - combine POS and Online orders
        // Exclude delivery charges from accounting (only product prices)
        $currentPosData = $currentPosQuery->get();
        $currentPosSales = $currentPosData->sum(function($pos) {
            return $pos->total_amount - ($pos->delivery ?? 0);
        });
        $currentPosOrders = $currentPosData->count();
        
        // Get COD percentage from settings
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;
        
        $currentOrderData = $currentOrderQuery->get();
        $currentOrderSales = $currentOrderData->sum(function($order) use ($codPercentage) {
            $revenue = $order->total - ($order->delivery ?? 0);
            
            // Apply COD discount for COD orders (cash payment method)
            if ($order->payment_method === 'cash' && $codPercentage > 0) {
                $codDiscount = round($order->total * $codPercentage, 2);
                $revenue = $revenue - $codDiscount;
            }
            
            return $revenue;
        });
        $currentOrderOrders = $currentOrderData->count();
        
        $currentSales = $currentPosSales + $currentOrderSales;
        $currentOrders = $currentPosOrders + $currentOrderOrders;
        $currentAvgOrder = $currentOrders > 0 ? $currentSales / $currentOrders : 0;

        // Previous period stats - combine POS and Online orders
        // Exclude delivery charges from accounting (only product prices)
        $previousPosData = $previousPosQuery->get();
        $previousPosSales = $previousPosData->sum(function($pos) {
            return $pos->total_amount - ($pos->delivery ?? 0);
        });
        $previousPosOrders = $previousPosData->count();
        
        $previousOrderData = $previousOrderQuery->get();
        $previousOrderSales = $previousOrderData->sum(function($order) use ($codPercentage) {
            $revenue = $order->total - ($order->delivery ?? 0);
            
            // Apply COD discount for COD orders (cash payment method)
            if ($order->payment_method === 'cash' && $codPercentage > 0) {
                $codDiscount = round($order->total * $codPercentage, 2);
                $revenue = $revenue - $codDiscount;
            }
            
            return $revenue;
        });
        $previousOrderOrders = $previousOrderData->count();
        
        $previousSales = $previousPosSales + $previousOrderSales;
        $previousOrders = $previousPosOrders + $previousOrderOrders;
        $previousAvgOrder = $previousOrders > 0 ? $previousSales / $previousOrders : 0;

        // Calculate percentages
        $salesPercentage = $previousSales > 0 ? (($currentSales - $previousSales) / $previousSales) * 100 : 0;
        $ordersPercentage = $previousOrders > 0 ? (($currentOrders - $previousOrders) / $previousOrders) * 100 : 0;
        $avgOrderPercentage = $previousAvgOrder > 0 ? (($currentAvgOrder - $previousAvgOrder) / $previousAvgOrder) * 100 : 0;

        // Customer satisfaction from real review data
        $satisfactionData = $this->getCustomerSatisfaction($startDate, $endDate);
        $satisfaction = $satisfactionData['rating'];
        $satisfactionPercentage = $satisfactionData['percentage'];

        return [
            'totalSales' => [
                'value' => number_format($currentSales, 2),
                'percentage' => round($salesPercentage, 1),
                'trend' => $salesPercentage >= 0 ? 'up' : 'down'
            ],
            'totalOrders' => [
                'value' => $currentOrders,
                'percentage' => round($ordersPercentage, 1),
                'trend' => $ordersPercentage >= 0 ? 'up' : 'down'
            ],
            'averageOrder' => [
                'value' => number_format($currentAvgOrder, 2),
                'percentage' => round($avgOrderPercentage, 1),
                'trend' => $avgOrderPercentage >= 0 ? 'up' : 'down'
            ],
            'customerSatisfaction' => [
                'value' => $satisfaction,
                'percentage' => $satisfactionPercentage,
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

    private function getSalesOverview($baseQuery, $startDate, $endDate, $range)
    {
        // Get POS data
        $posQuery = clone $baseQuery;
        
        // Get Online Order data
        $orderQuery = Order::query();
        
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

    private function getOrderStatus($baseQuery, $startDate, $endDate)
    {
        // Get POS status data
        $posQuery = clone $baseQuery;
        $posQuery->whereBetween('sale_date', [$startDate, $endDate]);
        $posStatuses = $posQuery->selectRaw('status, COUNT(*) as count')
                               ->groupBy('status')
                               ->get();

        // Get Online Order status data
        $orderQuery = Order::query();
        $orderQuery->whereBetween('created_at', [$startDate, $endDate]);
        $orderStatuses = $orderQuery->selectRaw('status, COUNT(*) as count')
                                   ->groupBy('status')
                                   ->get();

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

    private function getTopSellingItems($baseQuery, $startDate, $endDate)
    {
        try {
            // Get real top selling items from actual sales data
            $topSellingItems = DB::table('products')
                ->leftJoin('pos_items', function($join) use ($startDate, $endDate) {
                    $join->on('products.id', '=', 'pos_items.product_id')
                         ->whereBetween('pos_items.created_at', [$startDate, $endDate]);
                })
                ->leftJoin('order_items', function($join) use ($startDate, $endDate) {
                    $join->on('products.id', '=', 'order_items.product_id')
                         ->whereBetween('order_items.created_at', [$startDate, $endDate]);
                })
                ->leftJoin('product_service_categories', 'products.category_id', '=', 'product_service_categories.id')
                ->selectRaw('products.name, 
                    product_service_categories.name as category_name,
                    COALESCE(SUM(pos_items.quantity), 0) + COALESCE(SUM(order_items.quantity), 0) as total_sold,
                    COALESCE(SUM(pos_items.total_price), 0) + COALESCE(SUM(order_items.total_price), 0) as total_revenue')
                ->where('products.type', 'product')
                ->where('products.status', 'active')
                ->groupBy('products.id', 'products.name', 'product_service_categories.name')
                ->orderByDesc('total_sold')
                ->take(3)
                ->get();

            // Calculate total sales for percentage calculation
            $totalSales = $topSellingItems->sum('total_sold');
            
            // Transform data with icons and colors
            $colors = ['primary', 'success', 'warning', 'info', 'danger'];
            $icons = ['fas fa-box', 'fas fa-shopping-cart', 'fas fa-star', 'fas fa-trophy', 'fas fa-fire'];
            
            return $topSellingItems->map(function ($item, $index) use ($totalSales, $colors, $icons) {
                $percentage = $totalSales > 0 ? round(($item->total_sold / $totalSales) * 100, 1) : 0;
                
                return [
                    'name' => $item->name,
                    'category' => $item->category_name ?? 'Uncategorized',
                    'sales' => $item->total_sold,
                    'revenue' => number_format($item->total_revenue, 2),
                    'percentage' => $percentage,
                    'icon' => $icons[$index % count($icons)],
                    'color' => $colors[$index % count($colors)]
                ];
            })->toArray();
            
        } catch (\Exception $e) {
            \Log::error('Error getting top selling items: ' . $e->getMessage());
            
            // Return fallback data if there's an error
            return [
                [
                    'name' => 'No Data Available',
                    'category' => 'System',
                    'sales' => 0,
                    'revenue' => '0.00',
                    'percentage' => 0,
                    'icon' => 'fas fa-exclamation-triangle',
                    'color' => 'secondary'
                ]
            ];
        }
    }

    private function getLocationPerformance($startDate, $endDate)
    {
        $query = Pos::query();

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
        $query = Invoice::query();

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
}
