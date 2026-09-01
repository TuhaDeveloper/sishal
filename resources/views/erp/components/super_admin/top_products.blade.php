<!-- 4. Top Selling Products Component -->
<div class="sa-card mb-4" id="cardTopProducts">
    <div class="sa-card-overlay"><div class="spinner-border text-primary" role="status"></div></div>
    <div class="sa-card-header">
        <h2 class="sa-card-title">
            <i class="fas fa-fire sa-icon-products"></i>
            Top Selling Products
        </h2>
        <a href="{{ url('/erp/simple-accounting/top-products') }}" class="btn btn-link btn-sm text-decoration-none fw-bold p-0 text-primary">
            View All <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-container-scroll">
        <table class="sa-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px;">#</th>
                    <th>Product</th>
                    <th>Branch</th>
                    <th class="text-center">Sold Qty</th>
                    <th class="text-end">Sales Amount</th>
                </tr>
            </thead>
            <tbody id="topSellingTableBody">
                @foreach($topSellingProducts as $tp)
                <tr>
                    <td class="text-center">
                        <span class="rank-badge {{ $tp['rank'] == 1 ? 'rank-1' : ($tp['rank'] == 2 ? 'rank-2' : ($tp['rank'] == 3 ? 'rank-3' : 'rank-other')) }}">
                            {{ $tp['rank'] }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark">{{ $tp['product'] }}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-code-branch me-1 text-primary"></i>{{ $tp['branch'] }}</span>
                    </td>
                    <td class="text-center fw-semibold">{{ number_format($tp['sold_qty']) }} pcs</td>
                    <td class="text-end fw-bold text-dark">৳{{ number_format($tp['sales_amount']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
