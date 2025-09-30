@extends('erp.master')

@section('title', 'Profit & Loss Statement')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Profit & Loss Statement</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Profit & Loss Statement</h2>
                    <p class="text-muted mb-0">View and manage profit and loss for your financial records.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <button class="btn btn-primary" onclick="exportProfitLoss()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <form id="profitLossFilterForm" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="{{ $startDate ?? date('Y-m-01') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="{{ $endDate ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Generate Report
                        </button>
                        <a href="{{ route('profitLoss.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Debug Section (Temporary) -->
        @if(config('app.debug'))
        <div class="container-fluid px-4 py-3 bg-warning">
            <div class="row">
                <div class="col-12">
                    <h6>Debug Info - Account Types Found:</h6>
                    <ul>
                        @foreach($accountTypes as $type)
                            <li><strong>{{ $type->name }}</strong> ({{ $type->accounts->count() }} accounts)</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <!-- Profit & Loss Content -->
        <div class="container-fluid px-4 py-4">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Revenue</h6>
                                    <h4 class="mb-0">৳{{ $profitLossData['totals']['revenue_formatted'] ?? '0.00' }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-up fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Expenses</h6>
                                    <h4 class="mb-0">৳{{ $profitLossData['totals']['expenses_formatted'] ?? '0.00' }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-down fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Net Profit/Loss</h6>
                                    <h4 class="mb-0">৳{{ $profitLossData['totals']['net_profit_formatted'] ?? '0.00' }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calculator fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Profit Margin</h6>
                                    <h4 class="mb-0">{{ $profitLossData['totals']['profit_percentage'] ?? '0.0' }}%</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-percentage fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profit & Loss Statement -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">Profit & Loss Statement</h5>
                                    <small class="text-muted">Period: {{ date('M d, Y', strtotime($startDate)) }} to {{ date('M d, Y', strtotime($endDate)) }}</small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="printProfitLoss()">
                                            <i class="fas fa-print me-1"></i>Print
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="exportPDF()">
                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="exportExcel()">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="profitLossTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 15%;">Account Code</th>
                                            <th style="width: 50%;">Account Name</th>
                                            <th style="width: 20%; text-align: right;">Amount</th>
                                            <th style="width: 15%; text-align: center;">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Revenue Section -->
                                        <tr class="table-success">
                                            <td colspan="4" class="fw-bold">
                                                <i class="fas fa-arrow-up me-2"></i>REVENUE
                                            </td>
                                        </tr>
                                        @forelse($profitLossData['revenue'] ?? [] as $revenue)
                                            <tr>
                                                <td><span class="badge bg-success">{{ $revenue['code'] }}</span></td>
                                                <td>{{ $revenue['name'] }}</td>
                                                <td class="text-end">
                                                    <span class="text-success fw-bold">৳{{ $revenue['formatted_balance'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success">Income</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">
                                                    <i class="fas fa-inbox me-2"></i>No revenue accounts found for this period
                                                </td>
                                            </tr>
                                        @endforelse
                                        <tr class="table-success">
                                            <td colspan="2" class="fw-bold">Total Revenue</td>
                                            <td class="text-end fw-bold">৳{{ $profitLossData['totals']['revenue_formatted'] ?? '0.00' }}</td>
                                            <td></td>
                                        </tr>

                                        <!-- Expenses Section -->
                                        <tr class="table-danger">
                                            <td colspan="4" class="fw-bold">
                                                <i class="fas fa-arrow-down me-2"></i>EXPENSES
                                            </td>
                                        </tr>
                                        @forelse($profitLossData['expenses'] ?? [] as $expense)
                                            <tr>
                                                <td><span class="badge bg-danger">{{ $expense['code'] }}</span></td>
                                                <td>{{ $expense['name'] }}</td>
                                                <td class="text-end">
                                                    <span class="text-danger fw-bold">৳{{ $expense['formatted_balance'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger">Expense</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">
                                                    <i class="fas fa-inbox me-2"></i>No expense accounts found for this period
                                                </td>
                                            </tr>
                                        @endforelse
                                        <tr class="table-danger">
                                            <td colspan="2" class="fw-bold">Total Expenses</td>
                                            <td class="text-end fw-bold">৳{{ $profitLossData['totals']['expenses_formatted'] ?? '0.00' }}</td>
                                            <td></td>
                                        </tr>

                                        <!-- Net Profit/Loss Section -->
                                        <tr class="table-info">
                                            <td colspan="2" class="fw-bold">
                                                <i class="fas fa-calculator me-2"></i>NET PROFIT/LOSS
                                            </td>
                                            <td class="text-end fw-bold">
                                                @php
                                                    $netProfit = $profitLossData['totals']['net_profit'] ?? 0;
                                                    $profitClass = $netProfit >= 0 ? 'text-success' : 'text-danger';
                                                @endphp
                                                <span class="{{ $profitClass }}">৳{{ $profitLossData['totals']['net_profit_formatted'] ?? '0.00' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $netProfit >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Metrics -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>FINANCIAL METRICS
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h6 class="text-muted">Total Revenue</h6>
                                    <h4 class="text-success">৳{{ $profitLossData['totals']['revenue_formatted'] ?? '0.00' }}</h4>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted">Total Expenses</h6>
                                    <h4 class="text-danger">৳{{ $profitLossData['totals']['expenses_formatted'] ?? '0.00' }}</h4>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted">Net Profit/Loss</h6>
                                    @php
                                        $netProfit = $profitLossData['totals']['net_profit'] ?? 0;
                                        $profitClass = $netProfit >= 0 ? 'text-success' : 'text-danger';
                                    @endphp
                                    <h4 class="{{ $profitClass }}">৳{{ $profitLossData['totals']['net_profit_formatted'] ?? '0.00' }}</h4>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted">Profit Margin</h6>
                                    <h4 class="text-info">{{ $profitLossData['totals']['profit_percentage'] ?? '0.0' }}%</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Profit & Loss</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('profitLoss.index') }}" method="GET">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modal_start_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="modal_start_date" name="start_date" 
                                   value="{{ $startDate ?? date('Y-m-01') }}" max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label for="modal_end_date" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="modal_end_date" name="end_date" 
                                   value="{{ $endDate ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Date range validation
            $('#start_date, #end_date, #modal_start_date, #modal_end_date').on('change', function() {
                const startDate = $('#start_date').val() || $('#modal_start_date').val();
                const endDate = $('#end_date').val() || $('#modal_end_date').val();
                
                if (startDate && endDate && startDate > endDate) {
                    alert('Start date cannot be after end date!');
                    $(this).val('');
                }
            });
        });

        function exportProfitLoss() {
            const startDate = $('#start_date').val() || '{{ $startDate ?? date('Y-m-01') }}';
            const endDate = $('#end_date').val() || '{{ $endDate ?? date('Y-m-d') }}';
            
            const url = new URL(window.location);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            url.searchParams.set('export', 'pdf');
            window.open(url.toString(), '_blank');
        }

        function printProfitLoss() {
            window.print();
        }

        function exportPDF() {
            const startDate = $('#start_date').val() || '{{ $startDate ?? date('Y-m-01') }}';
            const endDate = $('#end_date').val() || '{{ $endDate ?? date('Y-m-d') }}';
            
            const url = new URL(window.location);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            url.searchParams.set('export', 'pdf');
            window.open(url.toString(), '_blank');
        }

        function exportExcel() {
            const startDate = $('#start_date').val() || '{{ $startDate ?? date('Y-m-01') }}';
            const endDate = $('#end_date').val() || '{{ $endDate ?? date('Y-m-d') }}';
            
            const url = new URL(window.location);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            url.searchParams.set('export', 'excel');
            window.open(url.toString(), '_blank');
        }
    </script>
    @endpush
@endsection 