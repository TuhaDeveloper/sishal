@extends('erp.master')

@section('title', 'Top Products')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Top Products</h4>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="dateRange" onchange="updateDateRange()">
                            <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ $dateRange == 'week' ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ $dateRange == 'month' ? 'selected' : '' }}>This Month</option>
                            <option value="quarter" {{ $dateRange == 'quarter' ? 'selected' : '' }}>This Quarter</option>
                            <option value="year" {{ $dateRange == 'year' ? 'selected' : '' }}>This Year</option>
                        </select>
                        <select class="form-select form-select-sm" id="limit" onchange="updateLimit()">
                            <option value="5" {{ $limit == 5 ? 'selected' : '' }}>Top 5</option>
                            <option value="10" {{ $limit == 10 ? 'selected' : '' }}>Top 10</option>
                            <option value="20" {{ $limit == 20 ? 'selected' : '' }}>Top 20</option>
                            <option value="50" {{ $limit == 50 ? 'selected' : '' }}>Top 50</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Date Range Info -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Showing top products from <strong>{{ $startDate->format('M d, Y') }}</strong> to <strong>{{ $endDate->format('M d, Y') }}</strong>
                    </div>

                    <!-- Top Products by Revenue -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-dollar-sign text-success"></i>
                                        Top {{ $limit }} Products by Revenue
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th>Product</th>
                                                    <th class="text-end">Revenue</th>
                                                    <th class="text-end">Quantity Sold</th>
                                                    <th class="text-end">Avg. Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($topByRevenue as $index => $data)
                                                @if($data['product'])
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $index + 1 }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if($data['product']->image)
                                                                <img src="{{ asset($data['product']->image) }}" 
                                                                     alt="{{ $data['product']->name }}" 
                                                                     class="rounded me-2" 
                                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                                            @endif
                                                            <div>
                                                                <strong>{{ $data['product']->name }}</strong>
                                                                <br>
                                                                <small class="text-muted">{{ $data['product']->category->name ?? 'Uncategorized' }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <strong class="text-success">{{ number_format($data['revenue'], 2) }}৳</strong>
                                                    </td>
                                                    <td class="text-end">{{ number_format($data['quantity_sold']) }}</td>
                                                    <td class="text-end">
                                                        {{ number_format($data['quantity_sold'] > 0 ? $data['revenue'] / $data['quantity_sold'] : 0, 2) }}৳
                                                    </td>
                                                </tr>
                                                @endif
                                                @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No sales data found for the selected period.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products by Profit -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line text-success"></i>
                                        Top {{ $limit }} Products by Profit
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th>Product</th>
                                                    <th class="text-end">Profit</th>
                                                    <th class="text-end">Revenue</th>
                                                    <th class="text-end">Margin %</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($topByProfit as $index => $data)
                                                @if($data['product'])
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-success">{{ $index + 1 }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if($data['product']->image)
                                                                <img src="{{ asset($data['product']->image) }}" 
                                                                     alt="{{ $data['product']->name }}" 
                                                                     class="rounded me-2" 
                                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                                            @endif
                                                            <div>
                                                                <strong>{{ $data['product']->name }}</strong>
                                                                <br>
                                                                <small class="text-muted">{{ $data['product']->category->name ?? 'Uncategorized' }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <strong class="{{ $data['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                            {{ number_format($data['profit'], 2) }}৳
                                                        </strong>
                                                    </td>
                                                    <td class="text-end">{{ number_format($data['revenue'], 2) }}৳</td>
                                                    <td class="text-end">
                                                        @php
                                                            $margin = $data['revenue'] > 0 ? ($data['profit'] / $data['revenue']) * 100 : 0;
                                                        @endphp
                                                        <span class="badge {{ $margin >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                            {{ number_format($margin, 1) }}%
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endif
                                                @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No profit data found for the selected period.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products by Quantity -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-shopping-cart text-primary"></i>
                                        Top {{ $limit }} Products by Quantity Sold
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th>Product</th>
                                                    <th class="text-end">Quantity Sold</th>
                                                    <th class="text-end">Revenue</th>
                                                    <th class="text-end">Avg. Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($topByQuantity as $index => $data)
                                                @if($data['product'])
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $index + 1 }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if($data['product']->image)
                                                                <img src="{{ asset($data['product']->image) }}" 
                                                                     alt="{{ $data['product']->name }}" 
                                                                     class="rounded me-2" 
                                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                                            @endif
                                                            <div>
                                                                <strong>{{ $data['product']->name }}</strong>
                                                                <br>
                                                                <small class="text-muted">{{ $data['product']->category->name ?? 'Uncategorized' }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <strong class="text-primary">{{ number_format($data['quantity_sold']) }}</strong>
                                                    </td>
                                                    <td class="text-end">{{ number_format($data['revenue'], 2) }}৳</td>
                                                    <td class="text-end">
                                                        {{ number_format($data['quantity_sold'] > 0 ? $data['revenue'] / $data['quantity_sold'] : 0, 2) }}৳
                                                    </td>
                                                </tr>
                                                @endif
                                                @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No quantity data found for the selected period.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateDateRange() {
    const range = document.getElementById('dateRange').value;
    const limit = document.getElementById('limit').value;
    const url = new URL(window.location);
    url.searchParams.set('range', range);
    url.searchParams.set('limit', limit);
    window.location.href = url.toString();
}

function updateLimit() {
    const range = document.getElementById('dateRange').value;
    const limit = document.getElementById('limit').value;
    const url = new URL(window.location);
    url.searchParams.set('range', range);
    url.searchParams.set('limit', limit);
    window.location.href = url.toString();
    }
</script>
        </div>
    </div>
@endsection
