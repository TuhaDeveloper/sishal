@extends('erp.master')

@section('title', 'Balance Sheet')

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
                            <li class="breadcrumb-item active" aria-current="page">Balance Sheet</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Balance Sheet</h2>
                    <p class="text-muted mb-0">View and manage balance sheet for your financial records.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <button class="btn btn-primary" onclick="exportBalanceSheet()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <form id="balanceSheetFilterForm" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="as_of_date" class="form-label">As of Date</label>
                        <input type="date" class="form-control" id="as_of_date" name="as_of_date" 
                               value="{{ $asOfDate ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Generate Report
                        </button>
                        <a href="{{ route('balanceSheet.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Balance Sheet Content -->
        <div class="container-fluid px-4 py-4">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Assets</h6>
                                    <h4 class="mb-0">{{ $balanceSheetData['totals']['assets_formatted'] ?? '0.00' }}৳</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-line fa-2x"></i>
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
                                    <h6 class="card-title">Total Liabilities</h6>
                                    <h4 class="mb-0">{{ $balanceSheetData['totals']['liabilities_formatted'] ?? '0.00' }}৳</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-bar fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Equity</h6>
                                    <h4 class="mb-0">{{ $balanceSheetData['totals']['equity_formatted'] ?? '0.00' }}৳</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-balance-scale fa-2x"></i>
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
                                    <h6 class="card-title">Net Worth</h6>
                                    <h4 class="mb-0">{{ $balanceSheetData['net_worth_formatted'] ?? '0.00' }}৳</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calculator fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Sheet Sections -->
            <div class="row">
                <!-- Assets Section -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>ASSETS
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Code</th>
                                            <th>Account Name</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($balanceSheetData['assets'] ?? [] as $asset)
                                            <tr>
                                                <td><span class="badge bg-secondary">{{ $asset['code'] }}</span></td>
                                                <td>{{ $asset['name'] }}</td>
                                                <td class="text-end">
                                                    <span class="fw-bold {{ $asset['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $asset['formatted_balance'] }}৳
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No asset accounts found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <td colspan="2" class="fw-bold">Total Assets</td>
                                            <td class="text-end fw-bold">{{ $balanceSheetData['totals']['assets_formatted'] ?? '0.00' }}৳</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liabilities & Equity Section -->
                <div class="col-md-6">
                    <!-- Liabilities -->
                    <div class="card mb-3">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>LIABILITIES
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Code</th>
                                            <th>Account Name</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($balanceSheetData['liabilities'] ?? [] as $liability)
                                            <tr>
                                                <td><span class="badge bg-secondary">{{ $liability['code'] }}</span></td>
                                                <td>{{ $liability['name'] }}</td>
                                                <td class="text-end">
                                                    <span class="fw-bold {{ $liability['balance'] >= 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ $liability['formatted_balance'] }}৳
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No liability accounts found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <td colspan="2" class="fw-bold">Total Liabilities</td>
                                            <td class="text-end fw-bold">{{ $balanceSheetData['totals']['liabilities_formatted'] ?? '0.00' }}৳</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Equity -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-balance-scale me-2"></i>EQUITY
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Code</th>
                                            <th>Account Name</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($balanceSheetData['equity'] ?? [] as $equity)
                                            <tr>
                                                <td><span class="badge bg-secondary">{{ $equity['code'] }}</span></td>
                                                <td>{{ $equity['name'] }}</td>
                                                <td class="text-end">
                                                    <span class="fw-bold {{ $equity['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $equity['formatted_balance'] }}৳
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No equity accounts found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <td colspan="2" class="fw-bold">Total Equity</td>
                                            <td class="text-end fw-bold">{{ $balanceSheetData['totals']['equity_formatted'] ?? '0.00' }}৳</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Net Worth Summary -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>NET WORTH SUMMARY
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h6 class="text-muted">Total Assets</h6>
                                    <h4 class="text-success">{{ $balanceSheetData['totals']['assets_formatted'] ?? '0.00' }}৳</h4>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted">Total Liabilities</h6>
                                    <h4 class="text-danger">{{ $balanceSheetData['totals']['liabilities_formatted'] ?? '0.00' }}৳</h4>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted">Total Equity</h6>
                                    <h4 class="text-success">{{ $balanceSheetData['totals']['equity_formatted'] ?? '0.00' }}৳</h4>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="text-muted">Net Worth</h6>
                                    <h4 class="{{ ($balanceSheetData['net_worth'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $balanceSheetData['net_worth_formatted'] ?? '0.00' }}৳
                                    </h4>
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
                    <h5 class="modal-title" id="filterModalLabel">Filter Balance Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('balanceSheet.index') }}" method="GET">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modal_as_of_date" class="form-label">As of Date</label>
                            <input type="date" class="form-control" id="modal_as_of_date" name="as_of_date" 
                                   value="{{ $asOfDate ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
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
            // Date validation
            $('#as_of_date, #modal_as_of_date').on('change', function() {
                const selectedDate = $(this).val();
                const today = new Date().toISOString().split('T')[0];
                
                if (selectedDate > today) {
                    alert('Date cannot be in the future!');
                    $(this).val(today);
                }
            });
        });

        function exportBalanceSheet() {
            const asOfDate = $('#as_of_date').val() || '{{ $asOfDate ?? date('Y-m-d') }}';
            const url = new URL(window.location);
            url.searchParams.set('as_of_date', asOfDate);
            url.searchParams.set('export', 'pdf');
            window.open(url.toString(), '_blank');
        }

        function exportPDF() {
            const asOfDate = $('#as_of_date').val() || '{{ $asOfDate ?? date('Y-m-d') }}';
            const url = new URL(window.location);
            url.searchParams.set('as_of_date', asOfDate);
            url.searchParams.set('export', 'pdf');
            window.open(url.toString(), '_blank');
        }

        function exportExcel() {
            const asOfDate = $('#as_of_date').val() || '{{ $asOfDate ?? date('Y-m-d') }}';
            const url = new URL(window.location);
            url.searchParams.set('as_of_date', asOfDate);
            url.searchParams.set('export', 'excel');
            window.open(url.toString(), '_blank');
        }

        function printBalanceSheet() {
            window.print();
        }
    </script>
    @endpush
@endsection