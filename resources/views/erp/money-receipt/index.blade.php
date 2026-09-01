@extends('erp.master')

@section('title', 'Money Receipt List')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')

        <div class="glass-header">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1" style="font-size: 0.85rem;">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">Money Receipt</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="fw-bold mb-0 text-dark">Money Receipt Registry</h4>
                    </div>
                </div>
                <div
                    class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2 align-items-md-center">
                    @can('manage money receipts')
                        <a href="{{ route('money-receipt.create') }}" class="btn btn-create-premium text-nowrap">
                            <i class="fas fa-plus-circle me-2"></i>New Receipt
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            {{-- Alert --}}
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="alert-icon-me-3">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="alert-heading mb-1 fw-bold">Success!</h6>
                            <p class="mb-0 small text-dark">{{ session('success') }}</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Advanced Filters -->
            <div class="premium-card mb-4 shadow-sm border-0">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i
                            class="fas fa-filter me-2 text-primary"></i>Filter Search</h6>
                </div>
                <div class="card-body p-4">
                    <form id="filterForm" onsubmit="event.preventDefault(); fetchData();">
                        <!-- Report Type Radios -->
                        <div class="d-flex gap-4 mb-4">
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="report_daily" value="daily" {{ request('report_type', 'yearly') == 'daily' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="report_daily">Daily
                                    Reports</label>
                            </div>
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="report_monthly" value="monthly" {{ request('report_type') == 'monthly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="report_monthly">Monthly
                                    Reports</label>
                            </div>
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="report_yearly" value="yearly" {{ request('report_type', 'yearly') == 'yearly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="report_yearly">Yearly
                                    Reports</label>
                            </div>
                        </div>

                        <!-- Filter Fields Row -->
                        <div class="row g-3">
                            <div class="col-md-2 report-field daily-group {{ $reportType != 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">From Date</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ ($reportType == 'daily' && request('start_date')) ? request('start_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>
                            <div class="col-md-2 report-field daily-group {{ $reportType != 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">To Date</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ ($reportType == 'daily' && request('end_date')) ? request('end_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>
                            <div class="col-md-2 report-field monthly-group {{ $reportType != 'monthly' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Month</label>
                                <select name="month" class="form-select select2-setup">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ (request('month') ?? date('m')) == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 report-field monthly-group yearly-group {{ ($reportType == 'monthly' || $reportType == 'yearly') ? '' : 'd-none' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Year</label>
                                <select name="year" class="form-select select2-setup">
                                    @foreach(range(date('Y'), date('Y') - 10) as $y)
                                        <option value="{{ $y }}" {{ (request('year') ?? date('Y')) == $y ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Outlet</label>
                                <select name="branch_id" class="form-select select2-setup" data-placeholder="All Outlets">
                                    <option value=""></option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Customer</label>
                                <select name="customer_id" class="form-select select2-setup"
                                    data-placeholder="All Customers">
                                    <option value=""></option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Receipt No.</label>
                                <select name="receipt_no" class="form-select select2-setup"
                                    data-placeholder="Select Receipt">
                                    <option value=""></option>
                                    @foreach($recentReceipts as $rr)
                                        <option value="{{ $rr->payment_reference }}">{{ $rr->payment_reference }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Invoice No.</label>
                                <select name="invoice_no" class="form-select select2-setup"
                                    data-placeholder="Select Invoice">
                                    <option value=""></option>
                                    @foreach($recentInvoices as $ri)
                                        <option value="{{ $ri->invoice_number }}">{{ $ri->invoice_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Search
                                    Anything</label>
                                <input type="text" name="search" id="searchInput" class="form-control"
                                    placeholder="Name, Invoice, Phone...">
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Per Page</label>
                                <select name="per_page" class="form-select">
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Action</label>
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-create-premium flex-fill" title="Filter">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                    <button type="button" id="resetFilters" class="btn btn-light border" title="Reset">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light border-top p-3">
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
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="premium-card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Receipt No.</th>
                                    <th>Receipt Date</th>
                                    <th>Inv. Date</th>
                                    <th>Customer</th>
                                    <th>Outlet</th>
                                    <th>Sales Invoice</th>
                                    <th class="text-end">Due</th>
                                    <th class="text-end">Paid</th>
                                    <th>Account</th>
                                    <th>Collector</th>
                                    <th class="text-center" style="width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                @include('erp.money-receipt.table_rows', ['receipts' => $receipts])
                            </tbody>
                            <tfoot class="bg-indigo-50 fw-bold">
                                <tr>
                                    <td colspan="8" class="text-end text-uppercase small py-3">Grand Total Paid</td>
                                    <td class="text-end text-success py-3" id="totalAmount">৳
                                        {{ number_format($totalAmount, 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer bg-white py-3 border-top-0" id="paginationLinks">
                        {{ $receipts->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-indigo-50 {
            background-color: #f8faff !important;
        }

        .premium-table thead th {
            background-color: #2d5a4c !important;
            color: white !important;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            height: 50px;
            vertical-align: middle;
        }
    </style>

    @push('scripts')
        <script>
            function exportData(format) {
                if (typeof isDownloadNavigation !== 'undefined') isDownloadNavigation = true;
                const form = document.getElementById('filterForm');
                const data = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of data.entries()) {
                    params.append(key, value);
                }

                let url = format === 'excel' ? "{{ route('money-receipt.export.excel') }}" : "{{ route('money-receipt.export.pdf') }}";
                window.location.href = url + '?' + params.toString();
            }

            $(document).ready(function () {
                $('.select2-setup').each(function () {
                    let tags = false;
                    if ($(this).attr('name') === 'receipt_no' || $(this).attr('name') === 'invoice_no') {
                        tags = true;
                    }
                    $(this).select2({
                        width: '100%',
                        placeholder: $(this).data('placeholder'),
                        allowClear: true,
                        tags: tags
                    });
                });

                function toggleDateGroups() {
                    const type = $('.report-type-radio:checked').val() || 'yearly';

                    $('.report-field').addClass('d-none');

                    if (type === 'daily') {
                        $('.daily-group').removeClass('d-none');
                        const today = new Date().toISOString().split('T')[0];
                        if (!$('input[name="start_date"]').val()) {
                            $('input[name="start_date"]').val(today);
                            $('input[name="end_date"]').val(today);
                        }
                    } else if (type === 'monthly') {
                        $('.monthly-group').removeClass('d-none');
                    } else if (type === 'yearly') {
                        $('.yearly-group').removeClass('d-none');
                    }
                }

                $('.report-type-radio').on('change', function () {
                    toggleDateGroups();
                });

                // AJAX Reset Functionality
                $('#resetFilters').on('click', function (e) {
                    e.preventDefault();
                    const form = $('#filterForm')[0];
                    form.reset();

                    // Reset Select2s
                    $('.select2-setup').val('').trigger('change.select2');

                    // Set default report type
                    $('#report_yearly').prop('checked', true);
                    
                    const today = new Date().toISOString().split('T')[0];
                    $('input[name="start_date"]').val(today);
                    $('input[name="end_date"]').val(today);

                    toggleDateGroups();

                    // Reset Global Search
                    $('#searchInput').val('');

                    fetchData();
                });

                // Initial toggle
                toggleDateGroups();
            });

            function fetchData(page = 1) {
                const form = document.getElementById('filterForm');
                const data = new FormData(form);
                data.append('page', page);

                const params = new URLSearchParams();
                for (const [key, value] of data.entries()) {
                    params.append(key, value);
                }

                fetch(`{{ route('money-receipt.index') }}?` + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('tableBody').innerHTML = data.html;
                        document.getElementById('totalAmount').innerText = '৳ ' + data.totalAmount;
                        document.getElementById('paginationLinks').innerHTML = data.pagination;
                        bindPagination();
                    })
                    .catch(error => console.error('Error:', error));
            }

            function bindPagination() {
                $('#paginationLinks .pagination a').on('click', function (e) {
                    e.preventDefault();
                    const url = $(this).attr('href');
                    const urlParams = new URLSearchParams(url.split('?')[1]);
                    fetchData(urlParams.get('page'));
                });
            }

            bindPagination();
        </script>
    @endpush
@endsection