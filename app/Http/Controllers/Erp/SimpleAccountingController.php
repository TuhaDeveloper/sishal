<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pos;
use App\Models\PosItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\BranchProductStock;
use App\Models\WarehouseProductStock;
use App\Models\ProductVariationStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SimpleAccountingController extends Controller
{
    /**
     * Sales Summary Report
     */
    public function salesSummary(Request $request)
    {
        $dateRange = $request->get('range', 'week');
        $startDate = $this->getStartDate($dateRange);
        $endDate = Carbon::now();

        // Get sales data
        $salesData = $this->getSalesData($startDate, $endDate);
        
        // Get cost data
        $costData = $this->getCostData($startDate, $endDate);
        
        // Calculate profit
        $profitData = $this->calculateProfitData($salesData, $costData);

        return view('erp.simple-accounting.sales-summary', compact(
            'salesData',
            'costData', 
            'profitData',
            'dateRange',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Profit Report
     */
    public function profitReport(Request $request)
    {
        $dateRange = $request->get('range', 'month');
        $startDate = $this->getStartDate($dateRange);
        $endDate = Carbon::now();

        // Get profit by product
        $productProfits = $this->getProductProfits($startDate, $endDate);
        
        // Get profit by category
        $categoryProfits = $this->getCategoryProfits($startDate, $endDate);

        return view('erp.simple-accounting.profit-report', compact(
            'productProfits',
            'categoryProfits',
            'dateRange',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Top Products Report
     */
    public function topProducts(Request $request)
    {
        $dateRange = $request->get('range', 'month');
        $startDate = $this->getStartDate($dateRange);
        $endDate = Carbon::now();
        $limit = $request->get('limit', 10);

        // Get top products by revenue
        $topByRevenue = $this->getTopProductsByRevenue($startDate, $endDate, $limit);
        
        // Get top products by profit
        $topByProfit = $this->getTopProductsByProfit($startDate, $endDate, $limit);
        
        // Get top products by quantity sold
        $topByQuantity = $this->getTopProductsByQuantity($startDate, $endDate, $limit);

        return view('erp.simple-accounting.top-products', compact(
            'topByRevenue',
            'topByProfit',
            'topByQuantity',
            'dateRange',
            'startDate',
            'endDate',
            'limit'
        ));
    }

    /**
     * Stock Value Report
     */
    public function stockValue(Request $request)
    {
        // Get stock value by product
        $productStockValues = $this->getProductStockValues();
        
        // Get stock value by category
        $categoryStockValues = $this->getCategoryStockValues();
        
        // Get total stock value
        $totalStockValue = $productStockValues->sum('total_value');

        return view('erp.simple-accounting.stock-value', compact(
            'productStockValues',
            'categoryStockValues',
            'totalStockValue'
        ));
    }

    /**
     * Get sales data for date range
     */
    private function getSalesData($startDate, $endDate)
    {
        // Get orders data
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get();

        // Get COD percentage from settings
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;

        // Calculate revenue excluding delivery charges and applying COD discount for COD orders
        $orderRevenue = $orders->sum(function($order) use ($codPercentage) {
            $revenue = $order->total - ($order->delivery ?? 0);
            
            // Apply COD discount for COD orders (cash payment method)
            if ($order->payment_method === 'cash' && $codPercentage > 0) {
                $codDiscount = round($order->total * $codPercentage, 2);
                $revenue = $revenue - $codDiscount;
            }
            
            return $revenue;
        });
        $orderCount = $orders->count();

        // Get POS data
        $posSales = Pos::whereBetween('sale_date', [$startDate, $endDate])->get();
        
        // Calculate revenue excluding delivery charges (only product prices)
        $posRevenue = $posSales->sum(function($pos) {
            return $pos->total_amount - ($pos->delivery ?? 0);
        });
        $posCount = $posSales->count();

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
    private function getCostData($startDate, $endDate)
    {
        // Get order items with costs
        $orderItems = OrderItem::whereHas('order', function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                  ->where('status', '!=', 'cancelled');
        })->with('product')->get();

        $orderCosts = 0;
        foreach ($orderItems as $item) {
            $cost = $item->product->cost ?? 0;
            $orderCosts += $cost * $item->quantity;
        }

        // Get POS items with costs
        $posItems = PosItem::whereHas('pos', function($query) use ($startDate, $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        })->with('product')->get();

        $posCosts = 0;
        foreach ($posItems as $item) {
            $cost = $item->product->cost ?? 0;
            $posCosts += $cost * $item->quantity;
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
    private function getProductProfits($startDate, $endDate)
    {
        // Get COD percentage from settings
        $generalSetting = \App\Models\GeneralSetting::first();
        $codPercentage = $generalSetting ? ($generalSetting->cod_percentage / 100) : 0.00;

        // Get orders with their items
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->with('items.product')
            ->get();

        $posItems = PosItem::whereHas('pos', function($query) use ($startDate, $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        })->with('product')->get();

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

                if (!$productProfits->has($productId)) {
                    $productProfits->put($productId, [
                        'product' => $item->product,
                        'revenue' => 0,
                        'cost' => 0,
                        'profit' => 0,
                        'quantity_sold' => 0
                    ]);
                }

                $current = $productProfits->get($productId);
                $productProfits->put($productId, [
                    'product' => $current['product'],
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

            $productId = $item->product_id;
            $revenue = $item->unit_price * $item->quantity;
            $cost = ($item->product->cost ?? 0) * $item->quantity;
            $profit = $revenue - $cost;

            if (!$productProfits->has($productId)) {
                $productProfits->put($productId, [
                    'product' => $item->product,
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                    'quantity_sold' => 0
                ]);
            }

            $current = $productProfits->get($productId);
            $productProfits->put($productId, [
                'product' => $current['product'],
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
    private function getCategoryProfits($startDate, $endDate)
    {
        $productProfits = $this->getProductProfits($startDate, $endDate);
        
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
                    'product_count' => 0
                ]);
            }

            $current = $categoryProfits->get($categoryId);
            $categoryProfits->put($categoryId, [
                'category_name' => $current['category_name'],
                'revenue' => $current['revenue'] + $data['revenue'],
                'cost' => $current['cost'] + $data['cost'],
                'profit' => $current['profit'] + $data['profit'],
                'product_count' => $current['product_count'] + 1
            ]);
        }

        return $categoryProfits->sortByDesc('profit');
    }

    /**
     * Get top products by revenue
     */
    private function getTopProductsByRevenue($startDate, $endDate, $limit)
    {
        $productProfits = $this->getProductProfits($startDate, $endDate);
        return $productProfits->sortByDesc('revenue')->take($limit);
    }

    /**
     * Get top products by profit
     */
    private function getTopProductsByProfit($startDate, $endDate, $limit)
    {
        $productProfits = $this->getProductProfits($startDate, $endDate);
        return $productProfits->sortByDesc('profit')->take($limit);
    }

    /**
     * Get top products by quantity sold
     */
    private function getTopProductsByQuantity($startDate, $endDate, $limit)
    {
        $productProfits = $this->getProductProfits($startDate, $endDate);
        return $productProfits->sortByDesc('quantity_sold')->take($limit);
    }

    /**
     * Get product stock values
     */
    private function getProductStockValues()
    {
        $products = Product::with(['category', 'branchStock', 'warehouseStock', 'variations.stocks'])->get();
        
        $stockValues = collect();
        
        foreach ($products as $product) {
            $totalStock = 0;
            $totalValue = 0;
            
            if ($product->has_variations) {
                foreach ($product->variations as $variation) {
                    $variationStock = $variation->stocks->sum('quantity');
                    $totalStock += $variationStock;
                    $totalValue += $variationStock * ($product->cost ?? 0);
                }
            } else {
                $branchStock = $product->branchStock->sum('quantity');
                $warehouseStock = $product->warehouseStock->sum('quantity');
                $totalStock = $branchStock + $warehouseStock;
                $totalValue = $totalStock * ($product->cost ?? 0);
            }
            
            if ($totalStock > 0) {
                $stockValues->push([
                    'product' => $product,
                    'total_stock' => $totalStock,
                    'unit_cost' => $product->cost ?? 0,
                    'total_value' => $totalValue
                ]);
            }
        }
        
        return $stockValues->sortByDesc('total_value');
    }

    /**
     * Get category stock values
     */
    private function getCategoryStockValues()
    {
        $productStockValues = $this->getProductStockValues();
        
        $categoryValues = collect();
        
        foreach ($productStockValues as $data) {
            $categoryId = $data['product']->category_id;
            $categoryName = $data['product']->category->name ?? 'Uncategorized';
            
            if (!$categoryValues->has($categoryId)) {
                $categoryValues->put($categoryId, [
                    'category_name' => $categoryName,
                    'total_stock' => 0,
                    'total_value' => 0,
                    'product_count' => 0
                ]);
            }

            $current = $categoryValues->get($categoryId);
            $categoryValues->put($categoryId, [
                'category_name' => $current['category_name'],
                'total_stock' => $current['total_stock'] + $data['total_stock'],
                'total_value' => $current['total_value'] + $data['total_value'],
                'product_count' => $current['product_count'] + 1
            ]);
        }

        return $categoryValues->sortByDesc('total_value');
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
                return Carbon::now()->subWeek();
            case 'month':
                return Carbon::now()->subMonth();
            case 'quarter':
                return Carbon::now()->subQuarter();
            case 'year':
                return Carbon::now()->subYear();
            default:
                return Carbon::now()->subMonth();
        }
    }
}
