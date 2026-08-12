<div class="row g-4">
    <!-- Top by Revenue -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-primary py-3 border-0">
                <h6 class="fw-bold mb-0 text-white"><i class="fas fa-chart-line me-2"></i>Top {{ $limit }} by Revenue {{ isset($groupBy) && $groupBy === 'variation' ? '(Size-Wise)' : '(Product-Wise)' }}</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-3 small text-muted text-uppercase py-3">#</th>
                            <th class="small text-muted text-uppercase py-3">{{ isset($groupBy) && $groupBy === 'variation' ? 'Product / Size' : 'Product' }}</th>
                            <th class="small text-muted text-uppercase py-3">Branch</th>
                            <th class="text-end pe-3 small text-muted text-uppercase py-3">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(collect($topByRevenue)->take($limit) as $index => $data)
                            <tr>
                                <td class="ps-3"><span class="badge bg-light text-primary border">{{ $loop->iteration }}</span></td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $data['display_name'] ?? $data['product']->name }}</div>
                                    <div class="extra-small text-muted">SKU: {{ $data['product']->sku }} | Sold: {{ $data['quantity_sold'] }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3">{{ $data['branch_name'] }}</span>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="fw-bold text-success fs-6">৳{{ number_format($data['revenue'], 2) }}</span>
                                    <div class="extra-small text-muted">Profit: ৳{{ number_format($data['profit'], 2) }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No sales data found for the selected criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top by Quantity -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-warning py-3 border-0">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-shopping-bag me-2"></i>Top {{ $limit }} by Quantity Sold {{ isset($groupBy) && $groupBy === 'variation' ? '(Size-Wise)' : '(Product-Wise)' }}</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr class="bg-light">
                            <th class="ps-3 small text-muted text-uppercase py-3">#</th>
                            <th class="small text-muted text-uppercase py-3">{{ isset($groupBy) && $groupBy === 'variation' ? 'Product / Size' : 'Product' }}</th>
                            <th class="small text-muted text-uppercase py-3">Branch</th>
                            <th class="text-center small text-muted text-uppercase py-3">Sold Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(collect($topByQuantity)->take($limit) as $index => $data)
                            <tr>
                                <td class="ps-3"><span class="badge bg-light text-dark border">{{ $loop->iteration }}</span></td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $data['display_name'] ?? $data['product']->name }}</div>
                                    <div class="extra-small text-muted">Category: {{ $data['product']->category->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">{{ $data['branch_name'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-dark rounded-pill px-3">{{ $data['quantity_sold'] }} units</span>
                                    <div class="extra-small text-muted mt-1">৳{{ number_format($data['revenue'], 2) }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No sales data found for the selected criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
