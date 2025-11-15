@extends('erp.master')

@section('title', 'Profit Report')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid p-4">
            <!-- Date Range Info -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Showing profit data from <strong>{{ $startDate->format('M d, Y') }}</strong> to <strong>{{ $endDate->format('M d, Y') }}</strong>
            </div>

            <!-- Product Profits -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Profit by Product</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-end">Quantity Sold</th>
                                            <th class="text-end">Revenue</th>
                                            <th class="text-end">Cost</th>
                                            <th class="text-end">Profit</th>
                                            <th class="text-end">Margin %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($productProfits as $productId => $data)
                                        @if($data['product'])
                                        <tr>
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
                                            <td class="text-end">{{ number_format($data['quantity_sold']) }}</td>
                                            <td class="text-end">{{ number_format($data['revenue'], 2) }}৳</td>
                                            <td class="text-end">{{ number_format($data['cost'], 2) }}৳</td>
                                            <td class="text-end">
                                                <span class="badge {{ $data['profit'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ number_format($data['profit'], 2) }}৳
                                                </span>
                                            </td>
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
                                            <td colspan="6" class="text-center text-muted">No sales data found for the selected period.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Profits -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Profit by Category</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-end">Products</th>
                                            <th class="text-end">Revenue</th>
                                            <th class="text-end">Cost</th>
                                            <th class="text-end">Profit</th>
                                            <th class="text-end">Margin %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($categoryProfits as $categoryId => $data)
                                        <tr>
                                            <td>
                                                <strong>{{ $data['category_name'] }}</strong>
                                            </td>
                                            <td class="text-end">{{ $data['product_count'] }}</td>
                                            <td class="text-end">{{ number_format($data['revenue'], 2) }}৳</td>
                                            <td class="text-end">{{ number_format($data['cost'], 2) }}৳</td>
                                            <td class="text-end">
                                                <span class="badge {{ $data['profit'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ number_format($data['profit'], 2) }}৳
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                @php
                                                    $margin = $data['revenue'] > 0 ? ($data['profit'] / $data['revenue']) * 100 : 0;
                                                @endphp
                                                <span class="badge {{ $margin >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ number_format($margin, 1) }}%
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No category data found for the selected period.</td>
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

    <script>
    function updateDateRange() {
        const range = document.getElementById('dateRange').value;
        const url = new URL(window.location);
        url.searchParams.set('range', range);
        window.location.href = url.toString();
    }
    </script>
@endsection