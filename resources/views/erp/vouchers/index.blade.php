@extends('erp.master')

@section('title', 'Voucher List')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')

        <!-- Premium Header -->
        <div class="glass-header">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 breadcrumb-premium">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">Vouchers</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <div
                            class="avatar-sm bg-info text-white d-flex align-items-center justify-content-center rounded-circle fw-bold">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark">Voucher Registry</h4>
                    </div>
                </div>
                <div
                    class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2 align-items-md-center">
                    @can('manage vouchers')
                        <a href="{{ route('vouchers.create') }}" class="btn btn-create-premium">
                            <i class="fas fa-plus-circle me-2"></i>New Voucher
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            <!-- Filters Area -->
            <div class="premium-card mb-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i
                            class="fas fa-filter me-2 text-primary"></i>Voucher Filtering</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('vouchers.index') }}" method="GET" id="filterForm">

                        <!-- Report Type Radios -->
                        <div class="d-flex gap-4 mb-4">
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="dailyReport" value="daily" {{ request('report_type', 'yearly') == 'daily' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="dailyReport">Daily
                                    Reports</label>
                            </div>
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="monthlyReport" value="monthly" {{ request('report_type') == 'monthly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="monthlyReport">Monthly
                                    Reports</label>
                            </div>
                            <div class="form-check custom-radio">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type"
                                    id="yearlyReport" value="yearly" {{ request('report_type', 'yearly') == 'yearly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="yearlyReport">Yearly
                                    Reports</label>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Field Blocks (Daily, Monthly, Yearly) -->
                            <div class="col-md-3 report-field daily-group {{ $reportType != 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Start Date *</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ ($reportType == 'daily' && request('start_date')) ? request('start_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>
                            <div class="col-md-3 report-field daily-group {{ $reportType != 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">End Date *</label>
                                <input type="date" name="end_date" class="form-control" value="{{ ($reportType == 'daily' && request('end_date')) ? request('end_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>

                            <!-- Monthly Fields -->
                            <div class="col-md-3 report-field monthly-group {{ $reportType != 'monthly' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Select Month
                                    *</label>
                                <select name="month" class="form-select select2-setup">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ (request('month') ?? date('n')) == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Yearly Fields -->
                            <div class="col-md-3 report-field monthly-group yearly-group {{ ($reportType == 'monthly' || $reportType == 'yearly') ? '' : 'd-none' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Select Year *</label>
                                <select name="year" class="form-select select2-setup">
                                    @foreach(range(date('Y'), date('Y') - 10) as $y)
                                        <option value="{{ $y }}" {{ (request('year') ?? date('Y')) == $y ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Customer *</label>
                                <select name="customer_id" class="form-select select2">
                                    <option value="all">All Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Supplier *</label>
                                <select name="supplier_id" class="form-select select2">
                                    <option value="all">All Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Voucher Type
                                    *</label>
                                <select name="voucher_type" class="form-select">
                                    <option value="all">All</option>
                                    <option value="Payment" {{ request('voucher_type') == 'Payment' ? 'selected' : '' }}>
                                        Payment</option>
                                    <option value="Receipt" {{ request('voucher_type') == 'Receipt' ? 'selected' : '' }}>
                                        Receipt</option>
                                    <option value="Journal" {{ request('voucher_type') == 'Journal' ? 'selected' : '' }}>
                                        Journal</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Select Account
                                    *</label>
                                <select name="account_id" class="form-select select2">
                                    <option value="all">All Account</option>
                                    @foreach($expenseAccounts as $acc)
                                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="card-footer bg-light border-top p-3 mt-4 mx-n4 mb-n4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-2">
                                    <button type="button" id="exportExcelBtn"
                                        class="btn btn-outline-success btn-sm fw-bold px-3 shadow-sm no-loader">
                                        <i class="fas fa-file-excel me-2"></i>Excel
                                    </button>
                                    <button type="button" id="exportPdfBtn"
                                        class="btn btn-outline-danger btn-sm fw-bold px-3 shadow-sm no-loader">
                                        <i class="fas fa-file-pdf me-2"></i>PDF
                                    </button>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" id="resetBtn"
                                        class="btn btn-light border px-4 fw-bold text-muted justify-content-center"
                                        style="height: 42px; display: flex; align-items: center;">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-create-premium px-5" style="height: 42px;">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table Section Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i
                        class="fas fa-list me-2 text-primary"></i>Voucher Data List</h6>
                <div class="search-wrapper-premium" style="width: 300px;">
                    <input type="text" id="voucherSearch" class="form-control rounded-pill search-input-premium"
                        placeholder="Quick find in this registry...">
                    <i class="fas fa-search search-icon-premium"></i>
                </div>
            </div>

            <!-- Table Area -->
            <div class="premium-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table premium-table reporting-table compact table-hover align-middle mb-0"
                            id="voucherTable">
                            <thead>
                                <tr>
                                    <th class="ps-3">SL.</th>
                                    <th>Voucher No.</th>
                                    <th>Voucher Type</th>
                                    <th>Date</th>
                                    <th>Outlet</th>
                                    <th>Customer</th>
                                    <th>Ledger Account</th>
                                    <th>Details</th>
                                    <th class="text-end">Voucher Amount</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th>Account</th>
                                    <th class="text-center pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                @include('erp.vouchers.table_rows', ['vouchers' => $vouchers])
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="8" class="text-end fw-bold">Grand Total (Filtered)</td>
                                    <td class="text-end fw-bold text-dark" id="totalVoucherAmount">
                                        {{ number_format($totals->total_voucher ?? 0, 2) }}৳</td>
                                    <td class="text-end fw-bold text-success" id="totalPaidAmount">
                                        {{ number_format($totals->total_paid ?? 0, 2) }}৳</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center"
                    id="paginationContainer">
                    @if($vouchers->hasPages())
                        <small class="text-muted fw-500">Showing {{ $vouchers->firstItem() ?? 0 }} to
                            {{ $vouchers->lastItem() ?? 0 }} of {{ $vouchers->total() }} entries</small>
                        {{ $vouchers->links('vendor.pagination.bootstrap-5') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('css')
        <style>
            .breadcrumb-premium {
                font-size: 0.8rem;
            }

            .search-wrapper-premium {
                position: relative;
            }

            .search-input-premium {
                padding-left: 35px;
                border: 1px solid #e0e0e0;
                font-size: 0.85rem;
            }

            .search-icon-premium {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #9e9e9e;
                font-size: 0.8rem;
            }

            .select2-container .select2-selection--single {
                height: 38px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 38px;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 36px;
            }
        </style>
    @endpush



    @push('scripts')
        <script>
            $(document).ready(function () {
                // Live Search Helper local (or can trigger AJAX if configured)
                $("#voucherSearch").on("keyup", function () {
                    var value = $(this).val().toLowerCase();
                    $("#voucherTable tbody tr").filter(function () {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });

                // Handle Report Toggles (Daily, Monthly, Yearly)
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

                // Initial setup run
                toggleDateGroups();

                // Prevent form submit & Handle AJAX Request
                $('#filterForm').on('submit', function (e) {
                    e.preventDefault();
                    fetchData();
                });

                // AJAX Reset
                $('#resetBtn').on('click', function () {
                    $('#filterForm')[0].reset();
                    // Reset report type to yearly
                    $('#yearlyReport').prop('checked', true);
                    toggleDateGroups();
                    // Set date fields back to today
                    const today = new Date().toISOString().split('T')[0];
                    $('input[name="start_date"]').val(today);
                    $('input[name="end_date"]').val(today);
                    // Reset select2 dropdowns
                    $('.select2').val('all').trigger('change.select2');
                    fetchData();
                });

                $(document).on('click', '.pagination a', function (e) {
                    e.preventDefault();
                    let url = $(this).attr('href');
                    fetchData(url);
                });

                // Listen for changes on select elements to trigger fetching automatically
                // Removed auto-fetch to only filter on 'Filter' button click per user request
                // $('.select2, select[name="voucher_type"]').on('change', function(e) {
                //     if($(this).attr('name') !== 'month' && $(this).attr('name') !== 'year') {
                //         fetchData();
                //     }
                // });

                function fetchData(url = null) {
                    let form = $('#filterForm');
                    let targetUrl = url ? url : form.attr('action');
                    let formData = form.serialize();

                    $.ajax({
                        url: targetUrl,
                        type: 'GET',
                        data: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        beforeSend: function () {
                            $('#tableBody').css('opacity', '0.5');
                        },
                        success: function (response) {
                            $('#tableBody').css('opacity', '1').html(response.html);
                            $('#paginationContainer').html(response.pagination);
                            $('#totalVoucherAmount').text(response.total_voucher + '৳');
                            $('#totalPaidAmount').text(response.total_paid + '৳');
                        },
                        error: function (xhr) {
                            console.error("Error fetching data.", xhr);
                            $('#tableBody').css('opacity', '1');
                        }
                    });
                }

                window.fetchVouchersData = fetchData;

                // Export handlers (inside ready so buttons are available)
                function handleExport(route) {
                    const formData = $('#filterForm').serialize();
                    window.isDownloadNavigation = true;
                    window.location.href = route + '?' + formData;
                    setTimeout(() => { window.isDownloadNavigation = false; }, 2000);
                }

                $('#exportExcelBtn').on('click', function () {
                    handleExport("{{ route('vouchers.export.excel') }}");
                });

                $('#exportPdfBtn').on('click', function () {
                    handleExport("{{ route('vouchers.export.pdf') }}");
                });

            });
        </script>
        <script>
            function deleteVoucher(id, voucherNo) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Do you want to delete voucher ' + voucherNo + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0',
                        confirmButton: 'px-4 py-2 rounded-3 fw-bold',
                        cancelButton: 'px-4 py-2 rounded-3 fw-bold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ url("erp/double-entry/vouchers") }}/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.message,
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false,
                                        customClass: {
                                            popup: 'rounded-4 shadow-lg border-0'
                                        }
                                    });
                                    if (typeof window.fetchVouchersData === 'function') {
                                        window.fetchVouchersData();
                                    }
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: response.message,
                                        icon: 'error',
                                        customClass: {
                                            popup: 'rounded-4 shadow-lg border-0'
                                        }
                                    });
                                }
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Something went wrong. Please try again.',
                                    icon: 'error',
                                    customClass: {
                                        popup: 'rounded-4 shadow-lg border-0'
                                    }
                                });
                            }
                        });
                    }
                });
            }
        </script>
    @endpush
@endsection