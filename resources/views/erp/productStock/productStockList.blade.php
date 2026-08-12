@extends('erp.master')

@section('title', 'Inventory Dashboard')

@section('body')
    @include('erp.components.sidebar')

    <div class="main-content" id="mainContent">
        @include('erp.components.header')

        <!-- Premium Header -->
        <div class="glass-header">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1" style="font-size: 0.85rem;">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">Stock Management</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="fw-bold mb-0 text-dark">Live Inventory</h4>
                        <span
                            class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1">
                            Live Tracking
                        </span>
                    </div>
                </div>
                <div
                    class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2 align-items-md-center">
                    @can('view stock')
                        <a href="{{ route('stock.adjustment.list') }}" class="btn btn-light border shadow-sm fw-bold">
                            <i class="fas fa-history me-2"></i>History
                        </a>
                    @endcan
                    @can('adjust stock')
                        <a href="{{ route('stock.adjustment.create') }}" class="btn btn-create-premium text-nowrap">
                            <i class="fas fa-sliders-h me-2"></i>New Adjustment
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            <!-- Advanced Filters -->
            <div class="premium-card mb-3 shadow-sm">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('productstock.list') }}" id="filterForm" autocomplete="off">
                        <input type="hidden" name="page" id="currentPage" value="{{ $productStocks->currentPage() }}">
                        <!-- Row 1: Search & Settings -->
                        <div class="row g-3 align-items-end mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i
                                        class="fas fa-search me-1"></i> Global Search</label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="fas fa-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0" name="search"
                                        value="{{ request('search') }}" placeholder="Search by Name, SKU, Style Number...">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i
                                        class="fas fa-layer-group me-1"></i> Report View</label>
                                <select class="form-select shadow-sm fw-bold border-primary text-primary" name="group_by"
                                    id="groupBySelect">
                                    <option value="variation" {{ request('group_by', 'variation') == 'variation' ? 'selected' : '' }}>Size-Wise (Detailed)</option>
                                    <option value="product" {{ request('group_by') == 'product' ? 'selected' : '' }}>
                                        Style-Wise (Product Summary)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i
                                        class="fas fa-list-ol me-1"></i> Per Page</label>
                                <select class="form-select shadow-sm" name="per_page">
                                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 Products
                                    </option>
                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Products</option>
                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Products
                                    </option>
                                </select>
                            </div>
                            <!-- <div class="col-md-2 d-none">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i
                                            class="fas fa-sort me-1"></i> Sort Stock</label>
                                    <select class="form-select shadow-sm" name="sort">
                                        <option value="">Default (Latest)</option>
                                        <option value="low_to_high" {{ request('sort') == 'low_to_high' ? 'selected' : '' }}>Low
                                            to High</option>
                                        <option value="high_to_low" {{ request('sort') == 'high_to_low' ? 'selected' : '' }}>High
                                            to Low</option>
                                    </select>
                                </div> -->
                        </div>

                        <!-- Row 2: Time & Date Filters -->
                        <div class="row g-3 align-items-end mb-4 bg-light p-3 rounded-3 mx-0 border">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Year</label>
                                <select class="form-select form-select-sm" name="filter_year">
                                    <option value="">All Years</option>
                                    <option value="{{ date('Y') }}" {{ request('filter_year') == date('Y') ? 'selected' : '' }}>{{ date('Y') }} (Current)</option>
                                    @for($i = date('Y') + 1; $i <= date('Y') + 5; $i++)
                                        <option value="{{ $i }}" {{ request('filter_year') == $i ? 'selected' : '' }}>{{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Month</label>
                                <select class="form-select form-select-sm" name="filter_month">
                                    <option value="">All Months</option>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ request('filter_month') == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Start Date</label>
                                <input type="date" class="form-control form-control-sm" name="start_date"
                                    value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">End Date</label>
                                <input type="date" class="form-control form-control-sm" name="end_date"
                                    value="{{ request('end_date') }}">
                            </div>
                        </div>

                        <!-- Row 3: Organizational Filters -->
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Branch</label>
                                <select class="form-select form-select-sm select2-simple" name="branch_id"
                                    data-placeholder="All Branches" {{ $restrictedBranchId ? 'disabled' : '' }}>
                                    <option value="">All Branches</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @if($restrictedBranchId)
                                    <input type="hidden" name="branch_id" value="{{ $restrictedBranchId }}">
                                @endif
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Warehouse</label>
                                <select class="form-select form-select-sm select2-simple" name="warehouse_id"
                                    data-placeholder="All Warehouses">
                                    <option value="">All Warehouses</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ isset($selectedWarehouseId) && $selectedWarehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Category</label>
                                <select class="form-select form-select-sm select2-simple" name="category_id" id="categoryFilter"
                                    data-placeholder="All Categories">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Subcategory</label>
                                <select class="form-select form-select-sm select2-simple" name="subcategory_id" id="subcategoryFilter"
                                    data-placeholder="All Subcategories">
                                    <option value="">All Subcategories</option>
                                    @foreach($subcategories as $subcat)
                                        <option value="{{ $subcat->id }}" data-parent="{{ $subcat->parent_id }}" {{ request('subcategory_id') == $subcat->id ? 'selected' : '' }}>
                                            {{ $subcat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Brand</label>
                                <select class="form-select form-select-sm select2-simple" name="brand_id"
                                    data-placeholder="All Brands">
                                    <option value="">All Brands</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Season</label>
                                <select class="form-select form-select-sm select2-simple" name="season_id"
                                    data-placeholder="All Seasons">
                                    <option value="">All Seasons</option>
                                    @foreach ($seasons as $season)
                                        <option value="{{ $season->id }}" {{ request('season_id') == $season->id ? 'selected' : '' }}>{{ $season->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Gender</label>
                                <select class="form-select form-select-sm select2-simple" name="gender_id"
                                    data-placeholder="All Genders">
                                    <option value="">All Genders</option>
                                    @foreach ($genders as $gender)
                                        <option value="{{ $gender->id }}" {{ request('gender_id') == $gender->id ? 'selected' : '' }}>{{ $gender->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Style #</label>
                                <select class="form-select form-select-sm select2-tags" name="style_number"
                                    id="styleNumberSelect" data-placeholder="Search or Select Style #">
                                    <option value="">All Styles</option>
                                    @if(request('style_number') && !$styles->contains(request('style_number')))
                                        <option value="{{ request('style_number') }}" selected>{{ request('style_number') }}
                                        </option>
                                    @endif
                                    @foreach ($styles as $style)
                                        <option value="{{ $style }}" {{ request('style_number') == $style ? 'selected' : '' }}>
                                            {{ $style }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Specific
                                    Variation</label>
                                <select class="form-select form-select-sm select2-simple" name="variation_value_id"
                                    data-placeholder="All Variations">
                                    <option value="">All Variations</option>
                                    @foreach($variationValues as $val)
                                        <option value="{{ $val->id }}" {{ request('variation_value_id') == $val->id ? 'selected' : '' }}>{{ $val->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="card-footer bg-light border-top p-3 mt-4 mx-n3 mb-n3"
                            style="border-bottom-left-radius: var(--bs-border-radius-xl); border-bottom-right-radius: var(--bs-border-radius-xl);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-outline-success btn-sm fw-bold px-3 shadow-sm no-loader"
                                        onclick="exportData('excel')">
                                        <i class="fas fa-file-excel me-2"></i>Excel
                                    </button>
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm fw-bold px-3 shadow-sm no-loader"
                                        onclick="exportData('pdf')">
                                        <i class="fas fa-file-pdf me-2"></i>PDF
                                    </button>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" id="resetFilters"
                                        class="btn btn-light border px-4 fw-bold text-muted justify-content-center"
                                        style="height: 42px; display: flex; align-items: center;">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-create-premium px-5 filter-btn"
                                        style="height: 42px;">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="stock-container">
                @include('erp.productStock.partials.table')
            </div>
        </div>

        <!-- Stock Breakdown Modal -->
        <div class="modal fade" id="stockBreakdownModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-white border-bottom p-4">
                        <h5 class="modal-title fw-bold"><i class="fas fa-warehouse me-2 text-primary"></i> <span
                                id="modalProductName">Stock Distribution</span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light small text-uppercase text-muted">
                                    <tr>
                                        <th class="ps-4 py-3">Location Type</th>
                                        <th class="py-3">Name</th>
                                        <th class="py-3">Size/Variation</th>
                                        <th class="text-end pe-4 py-3">Available Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="stockBreakdownTableBody">
                                    <!-- JS Populated -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 bg-light p-3">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('.select2-simple').select2({
                    width: '100%',
                    dropdownParent: $('body')
                });

                $('.select2-tags').select2({
                    width: '100%',
                    tags: true,
                    allowClear: true,
                    placeholder: "Search or Select Style #",
                    dropdownParent: $('body')
                });

                // Filter Subcategories based on selected Parent Category
                $('#categoryFilter').on('change', function() {
                    const parentId = $(this).val();
                    const subcatSelect = $('#subcategoryFilter');
                    subcatSelect.val('');
                    
                    if (parentId) {
                        subcatSelect.find('option').each(function() {
                            const p = $(this).data('parent');
                            if (!p || p == parentId) {
                                $(this).prop('disabled', false);
                            } else {
                                $(this).prop('disabled', true);
                            }
                        });
                    } else {
                        subcatSelect.find('option').prop('disabled', false);
                    }
                    subcatSelect.select2({ width: '100%', dropdownParent: $('body') });
                });

                // AJAX Filtering Logic
                function fetchStockData(url = null) {
                    const form = $('#filterForm');
                    const targetUrl = url || form.attr('action');
                    const data = form.serialize();

                    $('#stock-container').css('opacity', '0.5');

                    $.ajax({
                        url: targetUrl,
                        data: data,
                        success: function (response) {
                            $('#stock-container').html(response);
                            $('#stock-container').css('opacity', '1');
                            initializeTableScripts();
                        },
                        error: function () {
                            $('#stock-container').css('opacity', '1');
                            alert('Error loading data. Please try again.');
                        }
                    });
                }

                // Reset Filters Button
                $('#resetFilters').on('click', function () {
                    const form = $('#filterForm');
                    form[0].reset();
                    $('.select2-simple').val('').trigger('change');
                    $('#currentPage').val(1);
                    fetchStockData();
                });

                // Intercept Filter Form Submission
                $('#filterForm').on('submit', function (e) {
                    e.preventDefault();
                    $('#currentPage').val(1);
                    fetchStockData();
                });

                // Intercept Pagination Clicks
                $(document).on('click', '.pagination a', function (e) {
                    e.preventDefault();
                    const url = $(this).attr('href');
                    if (url) {
                        // Update the hidden page input from the URL
                        const urlParams = new URLSearchParams(url.split('?')[1]);
                        const pageValue = urlParams.get('page') || 1;
                        $('#currentPage').val(pageValue);

                        fetchStockData(url);
                        $('html, body').animate({
                            scrollTop: $("#stock-container").offset().top - 100
                        }, 200);
                    }
                });

                function initializeTableScripts() {
                    // Breakdown Modal Logic (re-attach)
                    $('.view-breakdown').off('click').on('click', function () {
                        var branchData = $(this).data('branch-stock') || {};
                        var warehouseData = $(this).data('warehouse-stock') || {};
                        var productName = $(this).data('name');

                        $('#modalProductName').text(productName + ' - Stock Breakdown');
                        var tbody = $('#stockBreakdownTableBody');
                        tbody.empty();
                        let hasData = false;

                        Object.entries(branchData).forEach(([locName, items]) => {
                            items.forEach((item, index) => {
                                hasData = true;
                                tbody.append(`
                                                <tr>
                                                    <td class="ps-4">${index === 0 ? '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Branch</span>' : ''}</td>
                                                    <td class="fw-bold text-dark">${index === 0 ? locName : ''}</td>
                                                    <td><span class="badge bg-light text-muted border fw-normal">${item.size}</span></td>
                                                    <td class="text-end pe-4"><span class="fw-bold ${item.qty < 5 ? 'text-danger' : 'text-success'}">${item.qty}</span></td>
                                                </tr>
                                            `);
                            });
                        });

                        Object.entries(warehouseData).forEach(([locName, items]) => {
                            items.forEach((item, index) => {
                                hasData = true;
                                tbody.append(`
                                                <tr>
                                                    <td class="ps-4">${index === 0 ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Warehouse</span>' : ''}</td>
                                                    <td class="fw-bold text-dark">${index === 0 ? locName : ''}</td>
                                                    <td><span class="badge bg-light text-muted border fw-normal">${item.size}</span></td>
                                                    <td class="text-end pe-4"><span class="fw-bold ${item.qty < 5 ? 'text-danger' : 'text-success'}">${item.qty}</span></td>
                                                </tr>
                                            `);
                            });
                        });

                        if (!hasData) {
                            tbody.append('<tr><td colspan="4" class="text-center py-5 text-muted"><i class="fas fa-box-open fa-2x mb-3 opacity-25"></i><p class="mb-0">No stock allocated to specific locations.</p></td></tr>');
                        }
                        $('#stockBreakdownModal').modal('show');
                    });

                    // Live Search removed in favor of global server-side search

                    // Initialize Bootstrap tooltips
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    });

                    // Date Filter Interactions
                    const filterYear = $('select[name="filter_year"]');
                    const filterMonth = $('select[name="filter_month"]');
                    const startDate = $('input[name="start_date"]');
                    const endDate = $('input[name="end_date"]');

                    filterYear.on('change', function () {
                        if ($(this).val()) {
                            startDate.val('');
                            endDate.val('');
                        }
                    });

                    filterMonth.on('change', function () {
                        if ($(this).val() && !filterYear.val()) {
                            filterYear.val(new Date().getFullYear());
                        }
                        if ($(this).val()) {
                            startDate.val('');
                            endDate.val('');
                        }
                    });

                    startDate.on('change', function () {
                        if ($(this).val()) {
                            filterYear.val('');
                            filterMonth.val('');
                        }
                    });

                    endDate.on('change', function () {
                        if ($(this).val()) {
                            filterYear.val('');
                            filterMonth.val('');
                        }
                    });
                }

                initializeTableScripts();
            });

            function exportData(format) {
                const form = document.getElementById('filterForm');
                const originalAction = form.action;
                const originalTarget = form.target;

                if (format === 'excel') {
                    form.action = "{{ route('productstock.export.excel') }}";
                    form.target = "_blank";
                    form.submit();
                } else if (format === 'pdf') {
                    form.action = "{{ route('productstock.export.pdf') }}";
                    form.target = "_blank";
                    form.submit();
                }

                // Restore
                setTimeout(() => {
                    form.action = originalAction;
                    form.target = originalTarget;
                }, 100);
            }
        </script>
    @endpush
@endsection