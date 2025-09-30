<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Pos;
use App\Models\Invoice;
use App\Models\Branch;
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
        
        if (!Auth::user()->hasPermissionTo('manage global branches')) {
            $baseQuery->where('branch_id', Auth::user()->employee->branch_id);
        }

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
        if (!Auth::user()->hasPermissionTo('manage global branches')) {
            $baseQuery->where('branch_id', Auth::user()->employee->branch_id);
        }

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
        $currentPeriodQuery = clone $baseQuery;
        $currentPeriodQuery->whereBetween('sale_date', [$startDate, $endDate]);
        
        $previousPeriodQuery = clone $baseQuery;
        $previousStartDate = $this->getPreviousPeriodStart($startDate, $range);
        $previousEndDate = $startDate->copy()->subDay();
        $previousPeriodQuery->whereBetween('sale_date', [$previousStartDate, $previousEndDate]);

        // Current period stats
        $currentSales = $currentPeriodQuery->sum('total_amount');
        $currentOrders = $currentPeriodQuery->count();
        $currentAvgOrder = $currentOrders > 0 ? $currentSales / $currentOrders : 0;

        // Previous period stats
        $previousSales = $previousPeriodQuery->sum('total_amount');
        $previousOrders = $previousPeriodQuery->count();
        $previousAvgOrder = $previousOrders > 0 ? $previousSales / $previousOrders : 0;

        // Calculate percentages
        $salesPercentage = $previousSales > 0 ? (($currentSales - $previousSales) / $previousSales) * 100 : 0;
        $ordersPercentage = $previousOrders > 0 ? (($currentOrders - $previousOrders) / $previousOrders) * 100 : 0;
        $avgOrderPercentage = $previousAvgOrder > 0 ? (($currentAvgOrder - $previousAvgOrder) / $previousAvgOrder) * 100 : 0;

        // Customer satisfaction (mock data for now)
        $satisfaction = 4.7;
        $satisfactionPercentage = 0.3;

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
        $query = clone $baseQuery;
        
        switch ($range) {
            case 'day':
                $query->selectRaw('HOUR(sale_date) as period, SUM(total_amount) as total')
                      ->whereDate('sale_date', $startDate)
                      ->groupBy('period')
                      ->orderBy('period');
                // 0..23 hours
                $labels = range(0, 23);
                break;
            case 'week':
                $query->selectRaw("DATE_FORMAT(sale_date, '%a') as period, DAYOFWEEK(sale_date) as sort_key, SUM(total_amount) as total")
                      ->whereBetween('sale_date', [$startDate, $endDate])
                      ->groupBy('sort_key', 'period')
                      ->orderBy('sort_key');
                $labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                break;
            case 'month':
                $query->selectRaw('DATE(sale_date) as period, SUM(total_amount) as total')
                      ->whereBetween('sale_date', [$startDate, $endDate])
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
                $query->selectRaw("DATE_FORMAT(sale_date, '%b') as period, MONTH(sale_date) as sort_key, SUM(total_amount) as total")
                      ->whereBetween('sale_date', [$startDate, $endDate])
                      ->groupBy('sort_key', 'period')
                      ->orderBy('sort_key');
                $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                break;
        }

        $data = $query->get();
        $salesData = [];
        
        foreach ($labels as $label) {
            $periodData = $data->firstWhere('period', $label);
            $salesData[] = $periodData ? (float)$periodData->total : 0.0;
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
        $query = clone $baseQuery;
        $query->whereBetween('sale_date', [$startDate, $endDate]);

        $statuses = $query->selectRaw('status, COUNT(*) as count')
                         ->groupBy('status')
                         ->get();
        $pending = $statuses->where('status', 'pending')->first()->count ?? 0;
        $delivered = $statuses->where('status', 'delivered')->first()->count ?? 0;
        $shipping = $statuses->where('status', 'shipping')->first()->count ?? 0;
        $cancelled = $statuses->where('status', 'cancelled')->first()->count ?? 0;

        return [
            'pending' => $pending,
            'delivered' => $delivered,
            'shipping' => $shipping,
            'cancelled' => $cancelled,
            'total' => $pending + $delivered + $shipping + $cancelled
        ];
    }

    private function getTopSellingItems($baseQuery, $startDate, $endDate)
    {
        // This would typically query from a sales_items or order_items table
        // For now, returning mock data
        return [
            [
                'name' => 'Margherita Pizza',
                'category' => 'Italian Cuisine',
                'sales' => 1424,
                'percentage' => 12,
                'icon' => 'fas fa-pizza-slice',
                'color' => 'primary'
            ],
            [
                'name' => 'Chicken Alfredo Pasta',
                'category' => 'Italian Cuisine',
                'sales' => 1216,
                'percentage' => 8,
                'icon' => 'fas fa-utensils',
                'color' => 'success'
            ],
            [
                'name' => 'Classic Burger',
                'category' => 'American Cuisine',
                'sales' => 1089,
                'percentage' => 15,
                'icon' => 'fas fa-hamburger',
                'color' => 'warning'
            ]
        ];
    }

    private function getLocationPerformance($startDate, $endDate)
    {
        $query = Pos::query();
        if (!Auth::user()->hasPermissionTo('manage global branches')) {
            $query->where('branch_id', Auth::user()->employee->branch_id);
        }

        $locations = $query->join('branches', 'pos.branch_id', '=', 'branches.id')
                          ->selectRaw('branches.name, SUM(pos.total_amount) as total_sales')
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
        if (!Auth::user()->hasPermissionTo('manage global branches')) {
            $query->whereHas('pos', function($q) {
                $q->where('branch_id', Auth::user()->employee->branch_id);
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
}
