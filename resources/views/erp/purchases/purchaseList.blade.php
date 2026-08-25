@extends('erp.master')

@section('title', 'Purchase List')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')

        <style>
            /* Premium Sticky Header & Horizontal Scroll Fix */

            /* 1. Maintain card containment to fix the layout breakage */
            .premium-card {
                overflow: hidden !important;
                border: 1px solid #edf2f7;
            }

            /* 2. Create an internal scrolling area for the table */
            .table-responsive {
                max-height: 80vh;
                /* Large height to feel like page scroll */
                overflow: auto !important;
                position: relative;
                background: #fff;
            }

            /* 3. Stick headers to the top of the scrollable box */
            #procurementTable {
                border-collapse: separate;
                /* Required for sticky header compatibility */
                border-spacing: 0;
                width: 100%;
            }

            #procurementTable thead th {
                position: sticky;
                top: 0;
                /* Sticks to the top of .table-responsive */
                z-index: 100 !important;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                /* Subtle depth shadow */
                padding-top: 12px !important;
                padding-bottom: 12px !important;
            }

            /* 4. Fix for cell backgrounds to ensure they don't overlap shadows */
            #procurementTable tbody td {
                background-color: #fff;
            }

            /* Custom Slim Scrollbar */
            .table-responsive::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            .table-responsive::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            .table-responsive::-webkit-scrollbar-track {
                background: #f8fafc;
            }

            /* Maximize vertical view by making the header static on this page */
            .glass-header {
                position: relative !important;
                top: 0 !important;
                box-shadow: none !important;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
                margin-bottom: 1rem !important;
            }
        </style>

        <!-- Premium Header Area -->
        <div class="glass-header px-4 py-3 bg-white">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 breadcrumb-premium text-uppercase">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none text-muted small">Dashboard</a></li>
                            <li class="breadcrumb-item active text-primary fw-bold small">Purchase History</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="fw-bold mb-0 text-dark">Purchase Procurement Report</h4>
                        <span
                            class="badge bg-light text-primary border border-primary small rounded-pill px-3 py-1">{{ $items->total() }}
                            Records</span>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    @can('create purchases')
                        <a href="{{ route('purchase.create') }}" class="btn btn-create-premium text-nowrap">
                            <i class="fas fa-plus-circle me-2"></i>New Purchase
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            <!-- Advanced Analytics Filters -->
            <div class="premium-card mb-4">
                <div class="card-body p-4">
                    <form action="{{ route('purchase.list') }}" method="GET" id="filterForm">
                        <div class="d-flex gap-4 mb-3">
                            <div class="form-check">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="dailyReport" value="daily" {{ request('report_type', 'yearly') == 'daily' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="dailyReport">Daily
                                    Reports</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="monthlyReport" value="monthly" {{ request('report_type') == 'monthly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="monthlyReport">Monthly
                                    Reports</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="yearlyReport" value="yearly" {{ request('report_type', 'yearly') == 'yearly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="yearlyReport">Yearly
                                    Reports</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-2 date-group daily-group" style="{{ $reportType == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Start Date
                                    Registry</label>
                                <input type="date" name="start_date" class="form-control shadow-none"
                                    value="{{ ($reportType == 'daily' && request('start_date')) ? request('start_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>
                            <div class="col-md-2 date-group daily-group" style="{{ $reportType == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">End Date
                                    Registry</label>
                                <input type="date" name="end_date" class="form-control shadow-none"
                                    value="{{ ($reportType == 'daily' && request('end_date')) ? request('end_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>

                            <div class="col-md-2 date-group monthly-group" style="{{ $reportType == 'monthly' ? '' : 'display: none;' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Fiscal Month</label>
                                <select name="month" class="form-select select2-setup shadow-none">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ (request('month') ?? date('m')) == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 date-group monthly-group yearly-group" style="{{ ($reportType == 'monthly' || $reportType == 'yearly') ? '' : 'display: none;' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Fiscal Year</label>
                                <select name="year" class="form-select select2-setup shadow-none">
                                    @foreach(range(date('Y'), date('Y') - 5) as $y)
                                        <option value="{{ $y }}" {{ (request('year') ?? date('Y')) == $y ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Branch</label>
                                <select name="branch_id" class="form-select select2-setup shadow-none"
                                    data-placeholder="All Branches">
                                    <option value=""></option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Warehouse</label>
                                <select name="warehouse_id" class="form-select select2-setup shadow-none"
                                    data-placeholder="All Warehouses">
                                    <option value=""></option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Search / Inv / Style</label>
                                <input type="text" name="search" class="form-control shadow-none"
                                    placeholder="Search ID, Style, SKU..." value="{{ request('search') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Vested
                                    Supplier</label>
                                <select name="supplier_id" class="form-select select2-setup shadow-none"
                                    data-placeholder="Choose Supplier">
                                    <option value=""></option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Allocated
                                    Product</label>
                                <select name="product_id" class="form-select select2-setup shadow-none"
                                    data-placeholder="Choose Product">
                                    <option value=""></option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Style Ref
                                    Code</label>
                                <input type="text" name="style_number" class="form-control shadow-none"
                                    placeholder="Style SKU..." value="{{ request('style_number') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Product
                                    Category</label>
                                <select name="category_id" class="form-select select2-setup shadow-none"
                                    data-placeholder="Choose Category">
                                    <option value=""></option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Vested Brand</label>
                                <select name="brand_id" class="form-select select2-setup shadow-none"
                                    data-placeholder="Choose Brand">
                                    <option value=""></option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Inventory
                                    Season</label>
                                <select name="season_id" class="form-select select2-setup shadow-none"
                                    data-placeholder="Choose Season">
                                    <option value=""></option>
                                    @foreach($seasons as $season)
                                        <option value="{{ $season->id }}" {{ request('season_id') == $season->id ? 'selected' : '' }}>{{ $season->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Target Gender</label>
                                <select name="gender_id" class="form-select select2-setup shadow-none"
                                    data-placeholder="Choose Gender">
                                    <option value=""></option>
                                    @foreach($genders as $gender)
                                        <option value="{{ $gender->id }}" {{ request('gender_id') == $gender->id ? 'selected' : '' }}>{{ $gender->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Purchase
                                    Status</label>
                                <select name="status" class="form-select shadow-none">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pending
                                        Approval</option>
                                    <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>✅
                                        Received</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌
                                        Cancelled</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Account
                                    Registry</label>
                                <select name="account" class="form-select select2-setup shadow-none"
                                    data-placeholder="Select A/C">
                                    <option value=""></option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}" {{ request('account') == $account->id ? 'selected' : '' }}>{{ $account->provider_name }} ({{ $account->account_number }})</option>
                                    @endforeach
                                </select>
                            </div>


                        </div>

                        <div class="card-footer bg-light border-top p-3 mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3"
                                        onclick="exportData('excel')">
                                        <i class="fas fa-file-excel me-2"></i>Excel
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm fw-bold px-3"
                                        onclick="exportData('pdf')">
                                        <i class="fas fa-file-pdf me-2"></i>PDF
                                    </button>

                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" id="resetFilters"
                                        class="btn btn-light border px-4 fw-bold text-muted"
                                        style="height: 42px; display: flex; align-items: center;">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-create-premium px-5" style="height: 42px;">
                                        <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Procurement Audit Registry Table -->
            <div class="premium-card shadow-sm border-0">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-muted small text-uppercase"><i
                            class="fas fa-list me-2 text-primary"></i>Audit Registry</h6>
                    <div class="search-wrapper-premium">
                        <input type="text" id="procurementSearch" class="form-control rounded-pill search-input-premium"
                            placeholder="Search by Invoice, Supplier, Registry...">
                        <i class="fas fa-search search-icon-premium"></i>
                    </div>
                </div>
                <div class="card-body p-0" id="table-container">
                    @include('erp.purchases.partials.table')
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <!-- Select2 Configuration -->
        <script>
            $(document).ready(function () {

                const reportRadios = document.querySelectorAll('input[name="report_type"]');
                function toggleDateGroups() {
                    const type = document.querySelector('input[name="report_type"]:checked').value;
                    document.querySelectorAll('.date-group').forEach(el => el.style.display = 'none');

                    if (type === 'daily') {
                        document.querySelectorAll('.daily-group').forEach(el => el.style.display = 'block');
                    } else if (type === 'monthly') {
                        document.querySelectorAll('.monthly-group').forEach(el => el.style.display = 'block');
                    } else if (type === 'yearly') {
                        document.querySelectorAll('.yearly-group').forEach(el => el.style.display = 'block');
                    }
                }
                reportRadios.forEach(radio => radio.addEventListener('change', () => {
                    toggleDateGroups();
                    if (radio.value === 'daily') {
                        const today = new Date().toISOString().split('T')[0];
                        if (!$('input[name="start_date"]').val()) {
                            $('input[name="start_date"]').val(today);
                            $('input[name="end_date"]').val(today);
                        }
                    }
                }));
                toggleDateGroups();

                // AJAX Filtering Logic
                function fetchPurchasesData(url = null) {
                    const form = $('#filterForm');
                    const targetUrl = url || form.attr('action');
                    const data = url ? null : form.serialize();

                    $('#table-container').css('opacity', '0.5');

                    $.ajax({
                        url: targetUrl,
                        data: data,
                        success: function (response) {
                            $('#table-container').html(response);
                            $('#table-container').css('opacity', '1');
                            initializeTableScripts();
                        },
                        error: function () {
                            $('#table-container').css('opacity', '1');
                            alert('Error loading data. Please try again.');
                        }
                    });
                }

                // Reset Filters Button
                $('#resetFilters').on('click', function () {
                    const form = $('#filterForm');
                    form[0].reset();
                    $('.select2-setup').val('').trigger('change');
                    
                    const today = new Date().toISOString().split('T')[0];
                    $('input[name="start_date"]').val(today);
                    $('input[name="end_date"]').val(today);

                    $('#yearlyReport').prop('checked', true).trigger('change');
                    fetchPurchasesData("{{ route('purchase.list') }}");
                });

                // Intercept Filter Form Submission
                $('#filterForm').on('submit', function (e) {
                    e.preventDefault();
                    fetchPurchasesData();
                });

                // Intercept Pagination Clicks
                $(document).on('click', '.pagination a', function (e) {
                    e.preventDefault();
                    const url = $(this).attr('href');
                    if (url) {
                        fetchPurchasesData(url);
                        $('html, body').animate({
                            scrollTop: $("#table-container").offset().top - 100
                        }, 200);
                    }
                });

                function initializeTableScripts() {
                    let searchTimeout;
                    $('#procurementSearch').off('input').on('input', function () {
                        const value = $(this).val().toLowerCase();
                        clearTimeout(searchTimeout);

                        searchTimeout = setTimeout(function () {
                            $('#procurementTable tbody tr').filter(function () {
                                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                            });
                        }, 300);
                    });

                    // Delete Confirmation
                    $('.delete-purchase').off('click').on('click', function () {
                        const id = $(this).data('id');
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "This procurement record will be permanently deleted!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete it!',
                            cancelButtonText: 'No, cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $(`#delete-form-${id}`).submit();
                            }
                        });
                    });
                }

                initializeTableScripts();
            });

            function exportData(format) {
                const form = document.getElementById('filterForm');
                const originalAction = form.action;
                const originalTarget = form.target;

                if (format === 'excel') {
                    form.action = "{{ route('purchase.export.excel') }}";
                    form.target = "_blank";
                    form.submit();
                } else if (format === 'pdf') {
                    form.action = "{{ route('purchase.export.pdf') }}";
                    form.target = "_blank";
                    form.submit();
                }

                // Restore after submission is processed by the browser event loop
                setTimeout(() => {
                    form.action = originalAction;
                    form.target = originalTarget;
                }, 100);
            }
        </script>
    @endpush
@endsection