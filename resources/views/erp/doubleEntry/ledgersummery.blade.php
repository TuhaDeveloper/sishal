@extends('erp.master')

@section('title', 'Ledger Summary')

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
                            <li class="breadcrumb-item active" aria-current="page">Ledger Summary</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Ledger Summary</h2>
                    <p class="text-muted mb-0">View and manage ledger summaries for your financial records.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <button class="btn btn-primary" onclick="exportLedger()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <form id="ledgerFilterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="account_filter" class="form-label">Chart of Account</label>
                        <select class="form-control" id="account_filter" name="account_id">
                            <option value="">All Accounts</option>
                            @foreach($chartAccounts ?? [] as $account)
                                <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} ({{ $account->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="start_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="{{ request('start_date', date('Y-m-01')) }}">
                    </div>
                    <div class="col-md-2">
                        <label for="end_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="{{ request('end_date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2">
                        <label for="type_filter" class="form-label">Account Type</label>
                        <select class="form-control" id="type_filter" name="account_type">
                            <option value="">All Types</option>
                            <option value="Asset" {{ request('account_type') == 'Asset' ? 'selected' : '' }}>Asset</option>
                            <option value="Liability" {{ request('account_type') == 'Liability' ? 'selected' : '' }}>Liability</option>
                            <option value="Equity" {{ request('account_type') == 'Equity' ? 'selected' : '' }}>Equity</option>
                            <option value="Income" {{ request('account_type') == 'Income' ? 'selected' : '' }}>Income</option>
                            <option value="Expense" {{ request('account_type') == 'Expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>Generate Report
                        </button>
                        <a href="{{ route('ledger.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Ledger Summary Content -->
        <div class="container-fluid px-4 py-4">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Debits</h6>
                                    <h4 class="mb-0">৳{{ number_format($totalDebits ?? 0, 2) }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-down fa-2x"></i>
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
                                    <h6 class="card-title">Total Credits</h6>
                                    <h4 class="mb-0">৳{{ number_format($totalCredits ?? 0, 2) }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-up fa-2x"></i>
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
                                    <h6 class="card-title">Net Balance</h6>
                                    <h4 class="mb-0">৳{{ number_format(($totalDebits ?? 0) - ($totalCredits ?? 0), 2) }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-balance-scale fa-2x"></i>
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
                                    <h6 class="card-title">Total Entries</h6>
                                    <h4 class="mb-0">{{ $totalEntries ?? 0 }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">Ledger Entries</h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="printLedger()">
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
                        <table class="table table-hover" id="ledgerTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher No</th>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledgerEntries ?? [] as $entry)
                                    <tr>
                                        <td>{{ $entry->journal->entry_date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ $entry->journal->voucher_no }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">
                                                <a href="{{ route('ledger.account', $entry->chartOfAccount->id) }}" 
                                                   class="text-decoration-none text-primary">
                                                    {{ $entry->chartOfAccount->name }}
                                                </a>
                                            </div>
                                            <small class="text-muted">{{ $entry->chartOfAccount->code }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $entry->journal->description }}</div>
                                            @if($entry->memo)
                                                <small class="text-muted">{{ $entry->memo }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($entry->debit > 0)
                                                <span class="text-danger fw-bold">৳{{ number_format($entry->debit, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($entry->credit > 0)
                                                <span class="text-success fw-bold">৳{{ number_format($entry->credit, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $balance = $entry->running_balance ?? 0;
                                                $balanceClass = $balance >= 0 ? 'text-success' : 'text-danger';
                                            @endphp
                                            <span class="fw-bold {{ $balanceClass }}">৳{{ number_format($balance, 2) }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="View Details" 
                                                        onclick="viewEntry({{ $entry->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-info" title="View Journal" 
                                                        onclick="viewJournal({{ $entry->journal_id }})">
                                                    <i class="fas fa-book"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-3"></i>
                                            <p>No ledger entries found</p>
                                            <p class="small">Try adjusting your filters or date range</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if(isset($ledgerEntries) && $ledgerEntries->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $ledgerEntries->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- View Entry Modal -->
    <div class="modal fade" id="viewEntryModal" tabindex="-1" aria-labelledby="viewEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewEntryModalLabel">Ledger Entry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="entryDetails">
                    <!-- Entry details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Auto-submit form when filters change
            $('#account_filter, #type_filter').on('change', function() {
                $('#ledgerFilterForm').submit();
            });

            // Date range validation
            $('#start_date, #end_date').on('change', function() {
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                
                if (startDate && endDate && startDate > endDate) {
                    alert('Start date cannot be after end date!');
                    $(this).val('');
                }
            });
        });

        function resetFilters() {
            $('#account_filter').val('');
            $('#type_filter').val('');
            $('#start_date').val('{{ date('Y-m-01') }}');
            $('#end_date').val('{{ date('Y-m-d') }}');
            $('#ledgerFilterForm').submit();
        }

        function viewEntry(entryId) {
            $.get(`/ledger/${entryId}`)
                .done(function(data) {
                    $('#entryDetails').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Entry Information</h6>
                                <table class="table table-sm">
                                    <tr><td>Entry ID:</td><td>${data.entry.id}</td></tr>
                                    <tr><td>Date:</td><td>${data.entry.journal.entry_date}</td></tr>
                                    <tr><td>Voucher:</td><td>${data.entry.journal.voucher_no}</td></tr>
                                    <tr><td>Account:</td><td>${data.entry.chart_of_account.name}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Amount Details</h6>
                                <table class="table table-sm">
                                    <tr><td>Debit:</td><td>৳${parseFloat(data.entry.debit).toFixed(2)}</td></tr>
                                    <tr><td>Credit:</td><td>৳${parseFloat(data.entry.credit).toFixed(2)}</td></tr>
                                    <tr><td>Memo:</td><td>${data.entry.memo || '-'}</td></tr>
                                </table>
                            </div>
                        </div>
                    `);
                    $('#viewEntryModal').modal('show');
                })
                .fail(function() {
                    alert('Failed to load entry details');
                });
        }

        function viewJournal(journalId) {
            window.open(`/journal/${journalId}`, '_blank');
        }

        function exportLedger() {
            const form = $('#ledgerFilterForm');
            const url = new URL(window.location);
            const params = new URLSearchParams(form.serialize());
            // Default to PDF export for the main button
            params.append('export', 'pdf');
            window.open(`${url.pathname}?${params.toString()}`, '_blank');
        }

        function printLedger() {
            window.print();
        }

        function exportPDF() {
            const form = $('#ledgerFilterForm');
            const url = new URL(window.location);
            const params = new URLSearchParams(form.serialize());
            params.append('export', 'pdf');
            window.open(`${url.pathname}?${params.toString()}`, '_blank');
        }

        function exportExcel() {
            const form = $('#ledgerFilterForm');
            const url = new URL(window.location);
            const params = new URLSearchParams(form.serialize());
            params.append('export', 'excel');
            window.open(`${url.pathname}?${params.toString()}`, '_blank');
        }
    </script>
    @endpush
@endsection