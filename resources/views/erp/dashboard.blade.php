@extends('erp.master')

@section('title', 'Dashboard')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-end mb-3">
                <form method="GET">
                    <select id="range-select" name="range" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="week" {{ ($range ?? 'week')==='week' ? 'selected' : '' }}>Last 7 days</option>
                        <option value="month" {{ ($range ?? 'week')==='month' ? 'selected' : '' }}>Last 30 days</option>
                        <option value="year" {{ ($range ?? 'week')==='year' ? 'selected' : '' }}>Last 12 months</option>
                        <option value="day" {{ ($range ?? 'week')==='day' ? 'selected' : '' }}>Today</option>
                    </select>
                </form>
            </div>
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Revenue</p>
                                <h3 class="mb-1"><span id="stat-total-sales">{{ $stats['totalSales']['value'] ?? '0.00' }}</span>৳</h3>
                                <small id="stat-total-sales-pct" class="{{ ($stats['totalSales']['percentage'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ ($stats['totalSales']['percentage'] ?? 0) >= 0 ? '+' : '' }}{{ $stats['totalSales']['percentage'] ?? 0 }}% vs prev</small>
                            </div>
                            <div class="stats-icon green">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Orders</p>
                                <h3 class="mb-1" id="stat-total-orders">{{ $stats['totalOrders']['value'] ?? 0 }}</h3>
                                <small id="stat-total-orders-pct" class="{{ ($stats['totalOrders']['percentage'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ ($stats['totalOrders']['percentage'] ?? 0) >= 0 ? '+' : '' }}{{ $stats['totalOrders']['percentage'] ?? 0 }}% vs prev</small>
                            </div>
                            <div class="stats-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Avg. Order</p>
                                <h3 class="mb-1"><span id="stat-avg-order">{{ $stats['averageOrder']['value'] ?? '0.00' }}</span>৳</h3>
                                <small id="stat-avg-order-pct" class="{{ ($stats['averageOrder']['percentage'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ ($stats['averageOrder']['percentage'] ?? 0) >= 0 ? '+' : '' }}{{ $stats['averageOrder']['percentage'] ?? 0 }}% vs prev</small>
                            </div>
                            <div class="stats-icon orange">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Satisfaction</p>
                                <h3 class="mb-1"><span id="stat-satisfaction">{{ $stats['customerSatisfaction']['value'] ?? '0.0' }}</span>/5</h3>
                                <small class="text-success">Improving</small>
                            </div>
                            <div class="stats-icon purple">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Charts and Activities Row -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card card-custom">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Revenue Overview</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="chart-revenue" class="w-100" style="max-height: 320px;"></canvas>
                            <div class="mt-3 small text-muted">
                                Total: <span id="revenue-total">{{ $salesOverview['totalSales'] ?? '0.00' }}</span>৳ • Avg: <span id="revenue-avg">{{ $salesOverview['average'] ?? '0.00' }}</span>৳ • Peak: <span id="revenue-peak">{{ $salesOverview['peakDay'] ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card card-custom">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Order Status</h5>
                        </div>
                        <div class="card-body">
                            @if(($orderStatus['total'] ?? 0) > 0)
                                <canvas id="chart-status" class="w-100" style="max-height: 240px;"></canvas>
                            @else
                                <div class="text-center text-muted py-4">No order data for selected range</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- Data Table -->
            <div class="card card-custom">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Invoices</h5>
                        <span class="small text-muted">Latest 5</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="table-invoices">
                                @forelse(($currentInvoices ?? []) as $inv)
                                <tr>
                                    <td class="fw-bold">{{ $inv['id'] }}</td>
                                    <td>{{ $inv['customer'] }}</td>
                                    <td><span class="badge badge-status {{ strtolower($inv['status'])==='completed' ? 'bg-success' : (in_array(strtolower($inv['status']),['pending','in_progress']) ? 'bg-warning' : 'bg-secondary') }}">{{ $inv['status'] }}</span></td>
                                    <td>{{ $inv['amount'] }}৳</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const revenueLabels = @json($salesOverview['labels'] ?? []);
    const revenueData = @json(array_map('floatval', $salesOverview['data'] ?? []));
    const ctxRev = document.getElementById('chart-revenue');
    if (ctxRev) {
        new Chart(ctxRev, {
            type: 'line',
            data: { labels: revenueLabels, datasets: [{ label: 'Sales', data: revenueData, borderColor: '#17a2b8', backgroundColor: 'rgba(23,162,184,.15)', fill: true, tension: .35 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }

    const statusTotal = {{ $orderStatus['total'] ?? 0 }};
    const statusCanvas = document.getElementById('chart-status');
    if (statusCanvas && statusTotal > 0) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Pending','Delivered','Shipping','Cancelled'],
                datasets: [{ data: [{{ $orderStatus['pending'] ?? 0 }}, {{ $orderStatus['delivered'] ?? 0 }}, {{ $orderStatus['shipping'] ?? 0 }}, {{ $orderStatus['cancelled'] ?? 0 }}], backgroundColor: ['#ffc107','#28a745','#ffc107','#dc3545'] }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
})();
</script>
@endpush