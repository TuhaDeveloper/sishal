@extends('erp.master')

@section('title', 'Sales vs Purchase Performance')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-gray-50 min-vh-100" id="mainContent">
        @include('erp.components.header')
        
        <div class="container-fluid px-4 py-4">
            <!-- Header section -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Sales vs Purchase Performance</h4>
                    <p class="text-muted small mb-0">Track actual sales revenue against purchase costs (Sales - Returns - Exchanges)</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-white shadow-sm border-0 fw-bold px-3" onclick="exportData('pdf')">
                        <i class="fas fa-file-pdf text-danger me-2"></i>PDF
                    </button>
                    <button type="button" class="btn btn-white shadow-sm border-0 fw-bold px-3" onclick="exportData('excel')">
                        <i class="fas fa-file-excel text-success me-2"></i>Excel
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-primary text-white overflow-hidden">
                        <div class="card-body p-4 position-relative">
                            <div class="position-relative z-1">
                                <h6 class="text-white-50 fw-bold text-uppercase mb-2">Net Sales Revenue</h6>
                                <h3 class="fw-bold mb-0" id="summaryNetSale">৳ 0.00</h3>
                                <small class="text-white-50 mt-2 d-block">After Returns & Exchanges</small>
                            </div>
                            <i class="fas fa-shopping-bag position-absolute end-0 bottom-0 opacity-10 m-n3 display-1"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-dark text-white overflow-hidden">
                        <div class="card-body p-4 position-relative">
                            <div class="position-relative z-1">
                                <h6 class="text-white-50 fw-bold text-uppercase mb-2">Net Purchase Cost</h6>
                                <h3 class="fw-bold mb-0" id="summaryNetCost">৳ 0.00</h3>
                                <small class="text-white-50 mt-2 d-block">Cost of goods sold (COGS)</small>
                            </div>
                            <i class="fas fa-truck-loading position-absolute end-0 bottom-0 opacity-10 m-n3 display-1"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-success text-white overflow-hidden">
                        <div class="card-body p-4 position-relative">
                            <div class="position-relative z-1">
                                <h6 class="text-white-50 fw-bold text-uppercase mb-2">Estimated Gross Profit</h6>
                                <h3 class="fw-bold mb-0" id="summaryProfit">৳ 0.00</h3>
                                <small class="text-white-50 mt-2 d-block">Real-time margin calculation</small>
                            </div>
                            <i class="fas fa-hand-holding-usd position-absolute end-0 bottom-0 opacity-10 m-n3 display-1"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <form id="filterForm" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">START DATE</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">END DATE</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">OUTLET/BRANCH</label>
                            <select name="branch_id" class="form-select select2-setup">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">CATEGORY</label>
                            <select name="category_id" class="form-select select2-setup">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">PRODUCT</label>
                            <select name="product_id" id="productFilter" class="form-select select2-ajax-search">
                                <option value="">All Products</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">REPORT VIEW</label>
                            <select name="group_by" id="groupBySelect" class="form-select fw-bold border-primary text-primary">
                                <option value="variation">Size-Wise (Detailed)</option>
                                <option value="product">Style-Wise (Product Summary)</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <button type="reset" class="btn btn-light fw-bold" id="resetBtn">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 premium-table" id="performanceTable">
                        <thead>
                            <tr>
                                <th class="ps-4">Product Details</th>
                                <th class="text-center">Sold Qty</th>
                                <th class="text-center">Ret Qty</th>
                                <th class="text-center">Net Qty</th>
                                <th class="text-end">Avg Sale Price</th>
                                <th class="text-end">Avg Cost Price</th>
                                <th class="text-end">Net Revenue</th>
                                <th class="text-end">Net Cost</th>
                                <th class="text-end pe-4">Profit / Margin</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                    <span class="text-muted fw-medium">Loading performance data...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-soft-primary { background-color: rgba(67, 97, 238, 0.1); }
        .bg-soft-success { background-color: rgba(76, 175, 80, 0.1); }
        .text-primary { color: #4361ee !important; }
        .border-primary { border-color: #4361ee !important; }
        .premium-table thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #e2e8f0; padding: 15px 10px; }
        .premium-table tbody td { padding: 15px 10px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .profit-badge { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; }
    </style>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Ajax Select2 for Products
            $('.select2-ajax-search').select2({
                placeholder: "Search Product...",
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('products.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name + (item.style_number ? ' (' + item.style_number + ')' : '')
                                };
                            })
                        };
                    },
                    cache: true
                }
            });

            fetchData();

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                fetchData();
            });

            $('#groupBySelect').on('change', function() {
                fetchData();
            });

            $('#resetBtn').on('click', function() {
                setTimeout(fetchData, 100);
            });
        });

        function fetchData() {
            const tbody = $('#tableBody');
            tbody.html(`
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                        <span class="text-muted fw-medium">Processing big data...</span>
                    </td>
                </tr>
            `);

            const formData = $('#filterForm').serialize();
            const groupBy = $('#groupBySelect').val();

            $.ajax({
                url: "{{ route('reports.performance') }}",
                data: formData,
                success: function(response) {
                    renderTable(response.data, groupBy);
                    renderSummary(response.summary);
                },
                error: function() {
                    tbody.html('<tr><td colspan="9" class="text-center py-5 text-danger">Error loading data.</td></tr>');
                }
            });
        }

        function renderTable(data, groupBy) {
            const tbody = $('#tableBody');
            if (data.length === 0) {
                tbody.html('<tr><td colspan="9" class="text-center py-5">No performance data found for this period.</td></tr>');
                return;
            }

            let html = '';
            data.forEach(item => {
                const avgSale = item.net_qty > 0 ? (item.net_sale_amount / item.net_qty) : 0;
                const marginColor = item.gross_profit >= 0 ? 'success' : 'danger';
                const variationTag = groupBy === 'product'
                    ? `<span class="badge bg-soft-primary text-primary fw-bold">Style Summary (All Sizes)</span>`
                    : `<span class="text-primary">${item.variation_name}</span>`;
                
                html += `
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark">${item.product_name}</div>
                            <div class="text-muted small">Style: ${item.style_number} | ${variationTag}</div>
                        </td>
                        <td class="text-center fw-medium">${item.sold_qty}</td>
                        <td class="text-center text-danger">${item.returned_qty}</td>
                        <td class="text-center fw-bold">${item.net_qty}</td>
                        <td class="text-end">৳${avgSale.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-muted">৳${item.unit_cost.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end fw-bold">৳${item.net_sale_amount.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-muted">৳${item.net_purchase_cost.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="text-end pe-4">
                            <div class="text-${marginColor} fw-bold">৳${item.gross_profit.toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                            <div class="badge bg-soft-${marginColor} text-${marginColor} profit-badge mt-1">${item.profit_margin.toFixed(2)}%</div>
                        </td>
                    </tr>
                `;
            });
            tbody.html(html);
        }

        function renderSummary(summary) {
            $('#summaryNetSale').text('৳ ' + summary.total_net_sale.toLocaleString(undefined, {minimumFractionDigits: 2}));
            $('#summaryNetCost').text('৳ ' + summary.total_net_cost.toLocaleString(undefined, {minimumFractionDigits: 2}));
            $('#summaryProfit').text('৳ ' + summary.total_profit.toLocaleString(undefined, {minimumFractionDigits: 2}));
        }

        function exportData(format) {
            if (typeof isDownloadNavigation !== 'undefined') isDownloadNavigation = true;
            const params = $('#filterForm').serialize();
            window.location.href = "{{ route('reports.performance') }}?export=" + format + "&" + params;
        }
    </script>
    @endpush
@endsection
