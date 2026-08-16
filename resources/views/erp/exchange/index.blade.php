@extends('erp.master')

@section('title', 'Exchange List')

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
                            <li class="breadcrumb-item active text-primary fw-600">Exchange</li>
                        </ol>
                    </nav>
                    <h4 class="fw-bold mb-0 text-dark">Exchange List</h4>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2 align-items-md-center">
                @can('manage exchanges')
                <a href="{{ route('exchange.create') }}" class="btn btn-create-premium text-nowrap">
                        <i class="fas fa-plus me-2"></i>New Exchange
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
                    <form action="{{ route('exchange.list') }}" method="GET" id="filterForm">
                        <div class="mb-4">
                            <div class="d-flex gap-4">
                                <div class="form-check custom-radio">
                                    <input class="form-check-input report-type-radio" type="radio" name="report_type" id="dailyReport" value="daily" {{ request('report_type', 'yearly') == 'daily' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold small text-muted" for="dailyReport">Daily Reports</label>
                                </div>
                                <div class="form-check custom-radio">
                                    <input class="form-check-input report-type-radio" type="radio" name="report_type" id="monthlyReport" value="monthly" {{ request('report_type') == 'monthly' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold small text-muted" for="monthlyReport">Monthly Reports</label>
                                </div>
                                <div class="form-check custom-radio">
                                    <input class="form-check-input report-type-radio" type="radio" name="report_type" id="yearlyReport" value="yearly" {{ request('report_type', 'yearly') == 'yearly' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold small text-muted" for="yearlyReport">Yearly Reports</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                             <div class="col-md-2 date-group daily-group" style="{{ $reportType == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ ($reportType == 'daily' && request('start_date')) ? request('start_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>
                            <div class="col-md-2 date-group daily-group" style="{{ $reportType == 'daily' ? '' : 'display: none;' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ ($reportType == 'daily' && request('end_date')) ? request('end_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>

                            <div class="col-md-2 date-group monthly-group" style="{{ $reportType == 'monthly' ? '' : 'display: none;' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Month</label>
                                <select name="month" class="form-select select2-simple">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ (request('month') ?? date('m')) == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 date-group monthly-group yearly-group" style="{{ ($reportType == 'monthly' || $reportType == 'yearly') ? '' : 'display: none;' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Year</label>
                                <select name="year" class="form-select select2-simple">
                                    @foreach(range(date('Y'), date('Y') - 10) as $y)
                                        <option value="{{ $y }}" {{ (request('year') ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Customer</label>
                                <select name="customer_id" class="form-select select2-simple" data-placeholder="All Customers">
                                    <option value=""></option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Branch</label>
                                <select name="branch_id" class="form-select select2-simple" data-placeholder="All Branches">
                                    <option value=""></option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Category</label>
                                <select name="category_id" class="form-select select2-simple" data-placeholder="All Categories">
                                    <option value=""></option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Brand</label>
                                <select name="brand_id" class="form-select select2-simple" data-placeholder="All Brands">
                                    <option value=""></option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Season</label>
                                <select name="season_id" class="form-select select2-simple" data-placeholder="All Seasons">
                                    <option value=""></option>
                                    @foreach($seasons as $season)
                                        <option value="{{ $season->id }}" {{ request('season_id') == $season->id ? 'selected' : '' }}>{{ $season->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Gender</label>
                                <select name="gender_id" class="form-select select2-simple" data-placeholder="All Genders">
                                    <option value=""></option>
                                    @foreach($genders as $gender)
                                        <option value="{{ $gender->id }}" {{ request('gender_id') == $gender->id ? 'selected' : '' }}>{{ $gender->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Style #</label>
                                <input type="text" name="style_number" class="form-control" placeholder="Style..." value="{{ request('style_number') }}">
                            </div>
                             <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Action</label>
                                 <div class="d-flex gap-2">
                                     <button type="button" id="resetBtn" class="btn btn-light border flex-fill" title="Reset" style="height: 42px;">
                                         <i class="fas fa-undo"></i>
                                     </button>
                                     <button type="submit" class="btn btn-create-premium flex-fill" style="height: 42px;">
                                         <i class="fas fa-search me-2"></i>Apply
                                     </button>
                                 </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light border-top p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3" onclick="exportData('excel')">
                                <i class="fas fa-file-excel me-2"></i>Excel
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm fw-bold px-3" onclick="exportData('pdf')">
                                <i class="fas fa-file-pdf me-2"></i>PDF
                            </button>
                        </div>
                        <div class="search-wrapper-premium" style="width: 300px;">
                            <input type="text" id="exchangeSearch" class="form-control rounded-pill search-input-premium" placeholder="Quick find in this registry...">
                            <i class="fas fa-search search-icon-premium"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Container for AJAX -->
            <div id="table-container">
                @include('erp.exchange.partials.table')
            </div>
        </div>
    </div>

@push('scripts')
<script>
    function exportData(format) {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        
        let url = '';
        if (format === 'excel') {
            url = "{{ route('exchange.export.excel') }}";
        } else {
            url = "{{ route('exchange.export.pdf') }}";
        }
        
        window.isDownloadNavigation = true;
        window.location.href = url + '?' + params;
        // Reset the flag shortly after to allow normal navigation later
        setTimeout(() => { window.isDownloadNavigation = false; }, 1000);
    }

    $(document).ready(function() {
        $('.report-type-radio').on('change', function() {
            const type = $(this).val();
            $('.date-group').hide();
            
            if (type === 'daily') {
                $('.daily-group').show();
                const today = new Date().toISOString().split('T')[0];
                if (!$('input[name="start_date"]').val()) {
                    $('input[name="start_date"]').val(today);
                    $('input[name="end_date"]').val(today);
                }
            } else if (type === 'monthly') {
                $('.monthly-group').show();
            } else if (type === 'yearly') {
                $('.yearly-group').show();
            }
        });
        
        // Trigger on load
        $('input[name="report_type"]:checked').trigger('change');

        // AJAX Filtering Logic
        function fetchExchangeData(url = null) {
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
            fetchExchangeData();
        });

        // Intercept Pagination Clicks
        $(document).on('click', '.pagination a', function (e) {
            e.preventDefault();
            const url = $(this).attr('href');
            if (url) {
                fetchExchangeData(url);
                $('html, body').animate({
                    scrollTop: $("#table-container").offset().top - 100
                }, 200);
            }
        });

        // Reset Filters Button
        $('#resetBtn').on('click', function () {
            const form = $('#filterForm');
            form[0].reset();
            $('.select2-simple').val('').trigger('change');
            
            const today = new Date().toISOString().split('T')[0];
            $('input[name="start_date"]').val(today);
            $('input[name="end_date"]').val(today);

            $('#yearlyReport').prop('checked', true).trigger('change');
            fetchExchangeData("{{ route('exchange.list') }}");
        });

        // Quick Search Table Functionality with Debounce via Event Delegation
        let exchangeTimeout;
        $(document).on('input', '#exchangeSearch', function() {
            const value = $(this).val().toLowerCase();
            clearTimeout(exchangeTimeout);
            
            exchangeTimeout = setTimeout(function() {
                $('#exchangeTable tbody tr').each(function() {
                        const text = $(this).text().toLowerCase();
                        $(this).toggle(text.indexOf(value) > -1);
                });
            }, 250);
        });

        // AJAX Delete Exchange
        $(document).on('click', '.delete-exchange-btn', function (e) {
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
                                text: response.message || 'Exchange deleted successfully.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            alert(response.message || 'Exchange deleted successfully.');
                        }
                        fetchExchangeData();
                    },
                    error: function (xhr) {
                        const errMsg = xhr.responseJSON?.message || 'Failed to delete exchange.';
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
                    text: 'All transactions and inventory movements will be rolled back!',
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
                if (confirm('Are you sure you want to delete this exchange? All transactions and inventory movements will be rolled back!')) {
                    performDelete();
                }
            }
        });
    });
</script>
@endpush
@endsection
