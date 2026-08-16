@extends('erp.master')

@section('title', 'Supplier Payment')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')
        
        <div class="glass-header">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 breadcrumb-premium">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">Supplier Payments</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm bg-success text-white d-flex align-items-center justify-content-center rounded-circle fw-bold">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark">Supplier Payment History</h4>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2 align-items-md-center">
                    @can('create payments')
                        <a href="{{ route('supplier-payments.pay-on-sale') }}" class="btn btn-outline-success fw-bold me-2">
                            <i class="fas fa-handshake me-2"></i>Pay-on-Sale Settlement
                        </a>
                        <a href="{{ route('supplier-payments.create') }}" class="btn btn-create-premium">
                            <i class="fas fa-plus-circle me-2"></i>Record New Payment
                        </a>
                    @endcan   
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4 fw-bold">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif

            <!-- Summary Bar -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="summary-card-premium p-3 d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white bg-opacity-20 p-3">
                            <i class="fas fa-file-invoice-dollar fs-4"></i>
                        </div>
                        <div>
                            <div class="small opacity-75 text-uppercase fw-bold">Month Total</div>
                            <h4 class="fw-bold mb-0">{{ number_format($payments->where('payment_date', '>=', now()->startOfMonth())->sum('amount'), 2) }}৳</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="premium-card p-3 d-flex align-items-center gap-3 border-0 shadow-sm">
                        <div class="rounded-circle bg-success-subtle p-3 text-success">
                            <i class="fas fa-check-double fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold">Total Vouchers</div>
                            <h4 class="fw-bold mb-0 text-dark">{{ $payments->total() }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="premium-card p-3 d-flex align-items-center gap-3 border-0 shadow-sm">
                        <div class="rounded-circle bg-info-subtle p-3 text-info">
                            <i class="fas fa-university fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold">Active Suppliers</div>
                            <h4 class="fw-bold mb-0 text-dark">{{ $suppliers->count() }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="premium-card p-3 d-flex align-items-center gap-3 border-0 shadow-sm">
                        <div class="rounded-circle bg-warning-subtle p-3 text-warning">
                            <i class="fas fa-money-bill-wave fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold">Total Disbursed</div>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($payments->sum('amount'), 2) }}৳</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Analytics Filters -->
            <div class="premium-card mb-4 shadow-sm">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('supplier-payments.index') }}" id="filterForm">
                        <input type="hidden" name="report_type" id="report_type_hidden" value="{{ $reportType }}">
                        
                        <div class="d-flex gap-4 mb-3 align-items-center">
                            <div class="form-check mb-0">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type_active" id="dailyReport" value="daily" {{ $reportType == 'daily' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="dailyReport">Daily Reports</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type_active" id="monthlyReport" value="monthly" {{ $reportType == 'monthly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="monthlyReport">Monthly Reports</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input report-type-radio" type="radio" name="report_type_active" id="yearlyReport" value="yearly" {{ $reportType == 'yearly' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold small text-muted" for="yearlyReport">Yearly Reports</label>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <!-- Daily Range -->
                            <div class="col-md-2 date-range-field {{ $reportType != 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ ($reportType == 'daily' && request('start_date')) ? request('start_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>

                            <div class="col-md-2 date-range-field {{ $reportType != 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ ($reportType == 'daily' && request('end_date')) ? request('end_date') : \Carbon\Carbon::today()->toDateString() }}">
                            </div>

                            <div class="col-md-2 month-field {{ $reportType != 'monthly' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Month</label>
                                <select name="month" class="form-select select2-setup">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ request('month', date('n')) == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 year-field {{ $reportType == 'daily' ? 'd-none' : '' }}">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Year</label>
                                <select name="year" class="form-select select2-setup">
                                    @foreach(range(date('Y'), date('Y') - 5) as $y)
                                        <option value="{{ $y }}" {{ request('year', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Supplier</label>
                                <select name="supplier_id" class="form-select select2-setup shadow-none">
                                    <option value="all">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Method</label>
                                <select name="payment_method" class="form-select select2-setup">
                                    <option value="all">Any Method</option>
                                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="check" {{ request('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">
                                    <i class="fas fa-hashtag me-1 text-primary"></i>Voucher Info
                                </label>
                                <select name="payment_no" class="form-select select2-setup">
                                    <option value="all">All Vouchers</option>
                                    @foreach($allPayments as $p)
                                        <option value="{{ $p->id }}" {{ request('payment_no') == $p->id ? 'selected' : '' }}>
                                            SP-{{ str_pad($p->id, 6, '0', STR_PAD_LEFT) }}
                                            @if($p->reference) — {{ $p->reference }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">
                                    <i class="fas fa-file-invoice me-1 text-warning"></i>Bill Reference
                                </label>
                                <select name="challan_no" class="form-select select2-setup">
                                    <option value="all">All Bills</option>
                                    <option value="0" {{ request('challan_no') == '0' ? 'selected' : '' }}>Advance (No Bill)</option>
                                    @foreach($allBills as $bill)
                                        <option value="{{ $bill->id }}" {{ request('challan_no') == $bill->id ? 'selected' : '' }}>
                                            {{ $bill->bill_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        <div class="card-footer bg-light border-top p-3 mt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-2">
                                    <button type="button" id="btn-excel-export" class="btn btn-outline-success btn-sm fw-bold px-3">
                                        <i class="fas fa-file-excel me-2"></i>Excel
                                    </button>
                                    <button type="button" id="btn-pdf-export" class="btn btn-outline-danger btn-sm fw-bold px-3">
                                        <i class="fas fa-file-pdf me-2"></i>PDF
                                    </button>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" id="resetBtn" class="btn btn-light border px-4 fw-bold text-muted" style="height: 42px; display: flex; align-items: center;">
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

            <!-- Table Search Wrapper -->
            <div class="d-flex justify-content-end align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted mb-0">Search:</label>
                    <input type="text" id="tableSearch" class="form-control form-control-sm table-search-input" placeholder="Quick Search..." style="width: 250px;">
                </div>
            </div>

            <!-- Table Card -->
            <div class="premium-card shadow-sm border-0 position-relative">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-money-bill-wave me-2 text-success"></i>Disbursement Registry</h6>
                </div>
                
                <!-- Soft Loader Overlay -->
                <div id="tableLoader" class="position-absolute w-100 h-100 bg-white bg-opacity-75 d-none align-items-center justify-content-center" style="z-index: 10; top: 0; left: 0;">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div class="card-body p-0" id="tableContainer">
                    @include('erp.supplier-payments.partials.table')
                </div>
            </div>
        </div>
    </div>


@push('css')
    <style>
        /* Dropdown Priority */
        .premium-dropdown {
            z-index: 1060 !important;
        }

        /* Select2 Fix for truncated dropdowns */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            padding: 8px 12px !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 8px !important;
            width: 100% !important;
        }
        .select2-container {
            width: 100% !important;
        }
        .select2-dropdown {
            border: 0 !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
            border-radius: 8px !important;
            z-index: 1050 !important;
        }

        /* Filter Pills Theme Fix */
        .btn-check:checked + .btn-outline-primary {
            background-color: var(--primary-green, #198754) !important;
            border-color: var(--primary-green, #198754) !important;
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.2);
        }
        .btn-outline-primary {
            color: var(--primary-green, #198754) !important;
            border-color: transparent !important;
            font-weight: 600;
        }
        .btn-outline-primary:hover {
            background-color: rgba(25, 135, 84, 0.08) !important;
            color: #157347 !important;
        }

        /* Theme Button Utility */
        .btn-theme {
            background-color: var(--primary-green, #198754) !important;
            border-color: var(--primary-green, #198754) !important;
            color: white !important;
            transition: all 0.3s;
        }
        .btn-theme:hover {
            background-color: #157347 !important;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
            transform: translateY(-1px);
            color: white !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2 with full width
            $('.select2-setup').select2({
                width: '100%',
                placeholder: "Select Option"
            });

            // Report type toggles
            $('.report-type-radio').on('change', function() {
                const val = $(this).val();
                $('#report_type_hidden').val(val);
                
                // Toggle visibility
                if(val === 'daily') {
                    $('.date-range-field').removeClass('d-none').show();
                    $('.month-field, .year-field').addClass('d-none').hide();
                    const today = new Date().toISOString().split('T')[0];
                    if (!$('input[name="start_date"]').val()) {
                        $('input[name="start_date"]').val(today);
                        $('input[name="end_date"]').val(today);
                    }
                } else if(val === 'monthly') {
                    $('.month-field, .year-field').removeClass('d-none').show();
                    $('.date-range-field').addClass('d-none').hide();
                } else if(val === 'yearly') {
                    $('.year-field').removeClass('d-none').show();
                    $('.date-range-field, .month-field').addClass('d-none').hide();
                }
            });

            // AJAX Filtering Logic
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                updateTable();
            });

            // AJAX Reset Logic
            $('#resetBtn').on('click', function() {
                const form = $('#filterForm');
                
                // Reset standard fields
                form[0].reset();
                
                // Reset Select2 fields specifically
                $('.select2-setup').val('all').trigger('change');
                
                // Special case for specific radio default
                $('#yearlyReport').prop('checked', true).trigger('change');
                $('#report_type_hidden').val('yearly');

                const today = new Date().toISOString().split('T')[0];
                $('input[name="start_date"]').val(today);
                $('input[name="end_date"]').val(today);

                // Trigger update
                updateTable();
            });

            // Excel & PDF Export Click Handlers
            $('#btn-excel-export').on('click', function () {
                let data = $('#filterForm').serialize();
                window.open("{{ route('supplier-payments.export.excel') }}?" + data, '_blank');
            });

            $('#btn-pdf-export').on('click', function () {
                let data = $('#filterForm').serialize();
                window.open("{{ route('supplier-payments.export.pdf') }}?" + data, '_blank');
            });

            function updateTable() {
                const form = $('#filterForm');
                const url = form.attr('action');
                const formData = form.serialize();

                $('#tableLoader').removeClass('d-none').addClass('d-flex');

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: formData,
                    success: function(response) {
                        $('#tableContainer').html(response);
                        $('#tableLoader').removeClass('d-flex').addClass('d-none');
                        
                        // Update export links
                        $('.no-loader').each(function() {
                            const link = $(this);
                            const baseUrl = link.attr('href').split('?')[0];
                            link.attr('href', baseUrl + '?' + formData);
                        });
                    },
                    error: function() {
                        $('#tableLoader').removeClass('d-flex').addClass('d-none');
                        alert('Error loading data.');
                    }
                });
            }

            // AJAX Pagination
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                $('#tableLoader').removeClass('d-none').addClass('d-flex');

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        $('#tableContainer').html(response);
                        $('#tableLoader').removeClass('d-flex').addClass('d-none');
                        window.scrollTo(0, $('#tableContainer').offset().top - 100);
                    }
                });
            });

            // Table search functionality with Debounce
            let searchTimeout;
            $(document).on('input', '#tableSearch', function() {
                const value = $(this).val().toLowerCase();
                clearTimeout(searchTimeout);
                
                searchTimeout = setTimeout(function() {
                    $('#paymentTable tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                }, 300);
            });
        });
    </script>
    @endpush
@endsection
