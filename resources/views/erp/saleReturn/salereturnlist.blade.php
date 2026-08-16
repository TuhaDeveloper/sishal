@extends('erp.master')

@section('title', 'Sale Return List')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')
        
        <div class="glass-header">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1" style="font-size: 0.85rem;">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">Return History</li>
                        </ol>
                    </nav>
                    <h4 class="fw-bold mb-0 text-dark">Sale Return Report</h4>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2 align-items-md-center">
                    @can('create sale returns')
                    <a href="{{ route('saleReturn.create') }}" class="btn btn-create-premium text-nowrap">
                        <i class="fas fa-plus me-2"></i>New Return
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        <div class="container-fluid px-4 py-4">
            <!-- Advanced Filters -->
            <div class="premium-card mb-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-filter me-2 text-primary"></i>Filter Search</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('saleReturn.list') }}" method="GET" id="filterForm">
                        <!-- Report Type Radios -->
                        <div class="d-flex gap-4 mb-4">
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type" id="report_daily" value="daily" {{ request('report_type', 'yearly') == 'daily' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="report_daily">Daily Reports</label>
                            </div>
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type" id="report_monthly" value="monthly" {{ request('report_type') == 'monthly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="report_monthly">Monthly Reports</label>
                            </div>
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type" id="report_yearly" value="yearly" {{ request('report_type', 'yearly') == 'yearly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="report_yearly">Yearly Reports</label>
                            </div>
                        </div>

                        <!-- Filter Fields Row -->
                        <div class="row g-3">
                            <!-- Date Range Group -->
                            <div class="col-md-3 report-field daily-group {{ $reportType != 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ ($reportType == 'daily' && request('start_date')) ? request('start_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>
                            <div class="col-md-3 report-field daily-group {{ $reportType != 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ ($reportType == 'daily' && request('end_date')) ? request('end_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>

                            <!-- Month Group -->
                            <div class="col-md-3 report-field monthly-group {{ $reportType != 'monthly' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Month</label>
                                <select name="month" class="form-select select2-setup">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ (request('month') ?? date('m')) == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Year Group -->
                            <div class="col-md-3 report-field yearly-group monthly-group {{ ($reportType == 'monthly' || $reportType == 'yearly') ? '' : 'd-none' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Year</label>
                                <select name="year" class="form-select select2-setup">
                                    @foreach(range(date('Y'), date('Y') - 10) as $y)
                                        <option value="{{ $y }}" {{ (request('year') ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Branch</label>
                                <select name="branch_id" class="form-select select2-setup" data-placeholder="All Branches">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Customer</label>
                                <select name="customer_id" class="form-select select2-setup" data-placeholder="Select Customer">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 2 -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Category</label>
                                <select name="category_id" class="form-select select2-setup" data-placeholder="All Categories">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Brand</label>
                                <select name="brand_id" class="form-select select2-setup" data-placeholder="All Brands">
                                    <option value="">All Brands</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Season</label>
                                <select name="season_id" class="form-select select2-setup" data-placeholder="All Seasons">
                                    <option value="">All Seasons</option>
                                    @foreach($seasons as $season)
                                        <option value="{{ $season->id }}" {{ request('season_id') == $season->id ? 'selected' : '' }}>{{ $season->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Gender</label>
                                <select name="gender_id" class="form-select select2-setup" data-placeholder="All Genders">
                                    <option value="">All Genders</option>
                                    @foreach($genders as $gender)
                                        <option value="{{ $gender->id }}" {{ request('gender_id') == $gender->id ? 'selected' : '' }}>{{ $gender->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Style #</label>
                                <input type="text" name="style_number" class="form-control" placeholder="Style Number" value="{{ request('style_number') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Quick Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="card-footer bg-light border-top p-3 mt-4 mx-n4 mb-n4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3" onclick="exportData('excel')">
                                        <i class="fas fa-file-excel me-2"></i>Excel
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm fw-bold px-3" onclick="exportData('pdf')">
                                        <i class="fas fa-file-pdf me-2"></i>PDF
                                    </button>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" id="resetBtn" class="btn btn-light border px-4 fw-bold text-muted" style="height: 42px;">
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

            <!-- Table Container for AJAX -->
            <div id="table-container">
                @include('erp.saleReturn.partials.table')
            </div>
        </div>
    </div>

    @push('scripts')
    <!-- Select2 Configuration -->
    <script>
        $(document).ready(function() {
            function updateReportToggles() {
                const reportType = $('.report-type-radio:checked').val();
                console.log('Toggling fields for:', reportType);
                
                // Hide using standard Bootstrap class d-none
                $('.report-field').addClass('d-none');
                
                if (reportType === 'daily') {
                    $('.daily-group').removeClass('d-none');
                    const today = new Date().toISOString().split('T')[0];
                    if (!$('input[name="start_date"]').val()) {
                        $('input[name="start_date"]').val(today);
                        $('input[name="end_date"]').val(today);
                    }
                } else if (reportType === 'monthly') {
                    $('.monthly-group').removeClass('d-none');
                } else if (reportType === 'yearly') {
                    $('.yearly-group').removeClass('d-none');
                }
            }

            // Bind change event
            $('.report-type-radio').on('change', updateReportToggles);
            
            // Initial call
            updateReportToggles();
            
            // Safety re-calls for select2 initialization delays
            setTimeout(updateReportToggles, 100);
            setTimeout(updateReportToggles, 500);

            // Initialize select2 if available
            if ($.fn.select2) {
                $('.select2-setup').select2({
                    width: '100%',
                    theme: 'bootstrap-5',
                    dropdownParent: $('#filterForm')
                });
            }

            // AJAX Filtering Logic
            function fetchReturnsData(url = null) {
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
                    },
                    error: function () {
                        $('#table-container').css('opacity', '1');
                        alert('Error loading data. Please try again.');
                    }
                });
            }

            // Intercept Filter Form Submission
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                fetchReturnsData();
            });

            // Intercept Pagination Clicks
            $(document).on('click', '.pagination a', function (e) {
                e.preventDefault();
                const url = $(this).attr('href');
                if (url) {
                    fetchReturnsData(url);
                    $('html, body').animate({
                        scrollTop: $("#table-container").offset().top - 100
                    }, 200);
                }
            });

            // Reset Filters Button
            $('#resetBtn').on('click', function () {
                const form = $('#filterForm');
                form[0].reset();
                $('.select2-setup').val('').trigger('change');
                
                const today = new Date().toISOString().split('T')[0];
                $('input[name="start_date"]').val(today);
                $('input[name="end_date"]').val(today);

                $('#report_yearly').prop('checked', true).trigger('change');
                fetchReturnsData("{{ route('saleReturn.list') }}");
            });

            // Quick Search Table Functionality with Debounce via Event Delegation
            let returnSearchTimeout;
            $(document).on('input', '#returnSearch', function() {
                const value = $(this).val().toLowerCase();
                clearTimeout(returnSearchTimeout);
                
                returnSearchTimeout = setTimeout(function() {
                    $('#returnTable tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                }, 300);
            });

            // AJAX Delete Sale Return
            $(document).on('click', '.delete-return-btn', function (e) {
                e.preventDefault();
                const deleteUrl = $(this).data('url');

                const performDelete = function() {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || 'Sale return deleted successfully.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                alert(response.message || 'Sale return deleted successfully.');
                            }
                            fetchReturnsData();
                        },
                        error: function (xhr) {
                            const errMsg = xhr.responseJSON?.message || 'Failed to delete sale return.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire('Error', errMsg, 'error');
                            } else {
                                alert(errMsg);
                            }
                        }
                    });
                };

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'All stock and accounting entries will be rolled back!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performDelete();
                        }
                    });
                } else {
                    if (confirm('Are you sure you want to delete this return? All stock and accounting entries will be rolled back!')) {
                        performDelete();
                    }
                }
            });
        });

        function exportData(format) {
            const form = document.getElementById('filterForm');
            const originalAction = form.action;
            const originalTarget = form.target;

            if (format === 'excel' || format === 'csv') {
                form.action = "{{ route('saleReturn.export.excel') }}";
                form.target = "_blank";
                form.submit();
            } else if (format === 'pdf') {
                form.action = "{{ route('saleReturn.export.pdf') }}";
                form.target = "_blank";
                form.submit();
            } else if (format === 'print') {
                form.action = "{{ route('saleReturn.export.pdf') }}";
                form.target = "_blank";
                let input = document.createElement("input");
                input.setAttribute("type", "hidden");
                input.setAttribute("name", "action");
                input.setAttribute("value", "print");
                form.appendChild(input);
                form.submit();
                form.removeChild(input);
            }

            form.action = originalAction;
            form.target = originalTarget;
        }
    </script>
    @endpush
@endsection