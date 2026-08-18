@extends('erp.master')

@section('title', 'Dashboard')

@push('css')
<style>
    /* Executive KPI Cards System */
    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }
    .kpi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .kpi-icon-wrapper {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .kpi-title {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .kpi-value {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        margin-bottom: 0;
    }
    .kpi-watermark {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 4.5rem;
        opacity: 0.04;
        pointer-events: none;
    }

    /* Color variations for icon wrappers */
    .kpi-blue { background: rgba(37, 99, 235, 0.08); color: #2563eb; }
    .kpi-green { background: rgba(16, 185, 129, 0.08); color: #10b981; }
    .kpi-red { background: rgba(239, 68, 68, 0.08); color: #ef4444; }
    .kpi-amber { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }

    /* Scrollable Container Rules for Tables */
    .dash-scroll-container {
        max-height: 380px;
        overflow-y: auto;
        overflow-x: auto;
        border-radius: 0 0 12px 12px;
        border: 1px solid #e2e8f0;
        border-top: none;
    }
    .dash-scroll-container::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .dash-scroll-container::-webkit-scrollbar-track {
        background: #f8fafc;
    }
    .dash-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .dash-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .dash-scroll-container table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc !important;
        box-shadow: inset 0 -1px 0 #e2e8f0;
    }
    .dash-scroll-container table tfoot td {
        position: sticky;
        bottom: 0;
        z-index: 10;
        background: #f8fafc !important;
        box-shadow: inset 0 1px 0 #e2e8f0;
    }
    .dash-section-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        margin-bottom: 1.5rem;
    }
    .dash-section-title-bar {
        padding: 14px 20px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px 12px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dash-section-title-bar h5 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>
@endpush

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')
        
        <div class="container-fluid p-3 p-md-4">
            
            <!-- Dashboard Welcome Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">{{ $siteTitle }}</h1>
                    <p class="text-muted small mb-0">Overview & Real-time Business Analytics</p>
                </div>
                <div class="text-muted small fw-semibold">
                    <i class="far fa-calendar-alt me-1"></i> {{ date('F d, Y') }}
                </div>
            </div>

            <!-- Unified Executive KPI Cards Row -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Today's Sales</span>
                            <div class="kpi-icon-wrapper kpi-blue">
                                <i class="fas fa-cash-register"></i>
                            </div>
                        </div>
                        <h2 class="kpi-value">{{ $financialKPIs['total_sales'] }}</h2>
                        <i class="fas fa-cash-register kpi-watermark"></i>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Today's Collection</span>
                            <div class="kpi-icon-wrapper kpi-green">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                        </div>
                        <h2 class="kpi-value">{{ $financialKPIs['total_collection'] }}</h2>
                        <i class="fas fa-hand-holding-usd kpi-watermark"></i>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Today's Due</span>
                            <div class="kpi-icon-wrapper kpi-red">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                        </div>
                        <h2 class="kpi-value">{{ $financialKPIs['total_due'] }}</h2>
                        <i class="fas fa-file-invoice kpi-watermark"></i>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-title">Today's Expense</span>
                            <div class="kpi-icon-wrapper kpi-amber">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                        <h2 class="kpi-value">{{ $todayExpenses }}</h2>
                        <i class="fas fa-wallet kpi-watermark"></i>
                    </div>
                </div>
            </div>

            <!-- Main Split Row: Top Selling Products & Outlet Performance -->
            <div class="row g-4 mb-4">
                <!-- Top Selling Products -->
                <div class="col-lg-5">
                    <div class="dash-section-card h-100 mb-0">
                        <div class="dash-section-title-bar">
                            <h5>
                                <i class="fas fa-fire text-danger me-1"></i> Top Selling Products
                            </h5>
                            <a href="{{ route('simple-accounting.top-products') }}" class="btn btn-sm btn-link text-decoration-none fw-bold p-0 text-primary">
                                View All <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="dash-scroll-container">
                            <table class="premium-table compact table-hover w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Product Details</th>
                                        <th class="text-center">Sold Qty</th>
                                        <th class="text-end pe-3">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topSellingItems as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center">
                                                <div class="action-circle me-2 bg-{{ $item['color'] }}-soft text-{{ $item['color'] }}">
                                                    <i class="{{ $item['icon'] }}"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $item['name'] }}</div>
                                                    <div class="extra-small text-muted">{{ $item['category'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold text-dark">{{ (int)$item['sales'] }} pcs</td>
                                        <td class="text-end pe-3 text-success fw-bold">{{ $item['revenue'] }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                                            <p class="mb-0 fw-semibold">No selling data available yet</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Outlet Performance -->
                <div class="col-lg-7">
                    <div class="dash-section-card h-100 mb-0">
                        <div class="dash-section-title-bar">
                            <h5>
                                <i class="fas fa-store text-primary me-1"></i> Outlet Monthly Performance
                            </h5>
                            <a href="{{ route('branches.index') }}" class="btn btn-sm btn-link text-decoration-none fw-bold p-0 text-primary">
                                View Outlets <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="dash-scroll-container">
                            <table class="premium-table compact table-hover w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Outlet</th>
                                        <th class="text-center">Today Sales</th>
                                        <th class="text-center">Today Qty</th>
                                        <th class="text-center">Month Sales</th>
                                        <th class="text-end pe-3">Month Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php 
                                        $gtTodayAmount = 0; 
                                        $gtTodayQty = 0; 
                                        $gtAmountMonth = 0; 
                                        $gtQtyMonth = 0; 
                                    @endphp
                                    @forelse($outletPerformance as $outlet)
                                        @php 
                                            $gtTodayAmount += $outlet['today_amount'];
                                            $gtTodayQty += $outlet['today_qty'];
                                            $gtAmountMonth += $outlet['month_amount']; 
                                            $gtQtyMonth += $outlet['month_qty']; 
                                        @endphp
                                        <tr>
                                            <td class="ps-3 fw-bold text-dark">
                                                <i class="fas fa-building text-primary me-2 opacity-50"></i>{{ $outlet['name'] }}
                                            </td>
                                            <td class="text-center fw-semibold text-dark">৳{{ number_format($outlet['today_amount'], 2) }}</td>
                                            <td class="text-center"><span class="badge bg-light text-dark border px-2 py-1">{{ $outlet['today_qty'] }} pcs</span></td>
                                            <td class="text-center fw-bold text-primary">৳{{ number_format($outlet['month_amount'], 2) }}</td>
                                            <td class="text-end pe-3"><span class="badge bg-light text-dark border px-2 py-1">{{ $outlet['month_qty'] }} pcs</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fas fa-store-slash fa-3x mb-3 opacity-25"></i>
                                                <p class="mb-0 fw-semibold">No outlet performance data recorded</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if(count($outletPerformance) > 0)
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td class="ps-3">TOTAL</td>
                                        <td class="text-center text-dark">৳{{ number_format($gtTodayAmount, 2) }}</td>
                                        <td class="text-center text-dark">{{ $gtTodayQty }} pcs</td>
                                        <td class="text-center text-success fs-6">৳{{ number_format($gtAmountMonth, 2) }}</td>
                                        <td class="text-end pe-3 text-success fs-6">{{ $gtQtyMonth }} pcs</td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Warning Section -->
            <div class="dash-section-card mb-4">
                <div class="dash-section-title-bar">
                    <h5>
                        <i class="fas fa-exclamation-triangle text-warning me-1"></i> Low Stock Alerts
                    </h5>
                    <a href="{{ route('productstock.list') }}" class="btn btn-sm btn-link text-decoration-none fw-bold p-0 text-primary">
                        View Stock List <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="dash-scroll-container" style="max-height: 340px;">
                    <table class="premium-table compact table-hover w-100 mb-0">
                        <thead>
                            <tr>
                                <th class="text-center ps-3" style="width: 50px;">#</th>
                                <th>Product Name & Details</th>
                                <th>Branch</th>
                                <th class="text-center">Variant (Size / Color)</th>
                                <th class="text-center">Current Stock</th>
                                <th class="text-center pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockDetailed as $index => $item)
                            <tr>
                                <td class="text-center ps-3 fw-semibold text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item['product_name'] }}</div>
                                    <div class="extra-small text-muted">
                                        @if(!empty($item['style_number']) && $item['style_number'] !== 'N/A')
                                            <span class="me-2">SKU: <strong>{{ $item['style_number'] }}</strong></span>
                                        @endif
                                        @if(!empty($item['category']) && $item['category'] !== 'N/A')
                                            <span class="me-2">Cat: {{ $item['category'] }}</span>
                                        @endif
                                        @if(!empty($item['brand']) && $item['brand'] !== 'N/A')
                                            <span>Brand: {{ $item['brand'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">
                                        <i class="fas fa-building text-secondary me-1 opacity-50"></i>{{ $item['branch'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border me-1">Size: {{ $item['size'] }}</span>
                                    <span class="badge bg-light text-dark border">Color: {{ $item['color'] }}</span>
                                </td>
                                <td class="text-center fw-bold">
                                    <span class="@if($item['current'] <= 3) text-danger @else text-warning @endif fs-6">
                                        {{ $item['current'] }} pcs
                                    </span>
                                </td>
                                <td class="text-center pe-3">
                                    @if($item['current'] <= 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            <i class="fas fa-times-circle me-1"></i> Out of Stock
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            <i class="fas fa-exclamation-circle me-1"></i> Low Stock
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2 opacity-50"></i>
                                    <p class="mb-0 fw-semibold text-success">All products have sufficient stock levels</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Sales Section -->
            <div class="dash-section-card mb-4">
                <div class="dash-section-title-bar">
                    <h5>
                        <i class="fas fa-history text-info me-1"></i> Recent Sales
                    </h5>
                    <a href="{{ route('pos.list') }}" class="btn btn-sm btn-link text-decoration-none fw-bold p-0 text-primary">
                        View All Sales <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="dash-scroll-container" style="max-height: 340px;">
                    <table class="premium-table compact table-hover w-100 mb-0">
                        <thead>
                            <tr>
                                <th class="text-center ps-3" style="width: 60px;">#</th>
                                <th>Invoice / Challan</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Paid</th>
                                <th class="text-center">Due</th>
                                <th class="text-center pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSalesDetailed as $index => $sale)
                            <tr>
                                <td class="text-center ps-3">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $sale['invoice_no'] }}</div>
                                    <div class="extra-small text-muted">CH: {{ $sale['challan_no'] }}</div>
                                </td>
                                <td>{{ $sale['date'] }}</td>
                                <td>{{ $sale['customer'] }}</td>
                                <td class="text-center fw-bold text-dark">৳{{ number_format($sale['total'], 2) }}</td>
                                <td class="text-center text-success fw-semibold">৳{{ number_format($sale['paid'], 2) }}</td>
                                <td class="text-center text-danger fw-semibold">৳{{ number_format($sale['due'], 2) }}</td>
                                <td class="text-center pe-3">
                                    <span class="badge @if($sale['status'] == 'delivered') bg-success-subtle text-success border border-success-subtle @else bg-primary-subtle text-primary border border-primary-subtle @endif px-2 py-1">
                                        {{ ucfirst($sale['status']) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <p class="mb-0">No recent sales records found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="dash-section-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="fas fa-chart-line text-primary me-2"></i>Sales Performance Trends
                        </h5>
                        <p class="text-muted small mb-0">Daily quantity and revenue analysis for the last 7 days</p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                            Last 7 Days
                        </button>
                    </div>
                </div>
                <div style="height: 380px; position: relative;">
                    <canvas id="salesTrendsChart"></canvas>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesTrendsChart').getContext('2d');
    const chartLabels = @json($salesQtyChart['labels']);
    const qtyData = @json($salesQtyChart['qtyData']);
    const revData = @json($salesQtyChart['revData']);

    // Create Gradients
    const gradientRev = ctx.createLinearGradient(0, 0, 0, 400);
    gradientRev.addColorStop(0, 'rgba(15, 118, 110, 0.2)');
    gradientRev.addColorStop(1, 'rgba(15, 118, 110, 0)');

    const gradientQty = ctx.createLinearGradient(0, 0, 0, 400);
    gradientQty.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
    gradientQty.addColorStop(1, 'rgba(59, 130, 246, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Revenue (৳)',
                    data: revData,
                    borderColor: '#0f766e',
                    backgroundColor: gradientRev,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#0f766e',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'yRevenue'
                },
                {
                    label: 'Quantity',
                    data: qtyData,
                    borderColor: '#3b82f6',
                    backgroundColor: gradientQty,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    yAxisID: 'yQty'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 12, weight: '600' }
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 13 },
                    bodyFont: { size: 13 },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.datasetIndex === 0) {
                                label += context.raw.toLocaleString() + ' ৳';
                            } else {
                                label += context.raw;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                yRevenue: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    title: { display: true, text: 'Revenue (৳)', font: { weight: 'bold' } },
                    grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false },
                    ticks: { color: '#64748b' }
                },
                yQty: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity', font: { weight: 'bold' } },
                    grid: { display: false },
                    ticks: { color: '#64748b' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { size: 11 } }
                }
            }
        }
    });
});
</script>
@endpush