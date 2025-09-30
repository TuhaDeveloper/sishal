@extends('erp.master')

@section('title', 'Account Ledger - ' . $account->name)

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
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('ledger.index') }}" class="text-decoration-none">Ledger Summary</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Account Ledger</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">{{ $account->name }}</h2>
                    <p class="text-muted mb-0">Account Code: {{ $account->code }} | Type: {{ $account->type->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <button class="btn btn-primary" onclick="exportAccountLedger()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Summary Cards -->
        <div class="container-fluid px-4 py-4">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Debits</h6>
                                    <h4 class="mb-0">৳{{ number_format($totalDebits, 2) }}</h4>
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
                                    <h4 class="mb-0">৳{{ number_format($totalCredits, 2) }}</h4>
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
                                    <h4 class="mb-0">৳{{ number_format($totalDebits - $totalCredits, 2) }}</h4>
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
                                    <h4 class="mb-0">{{ $entries->count() }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Ledger Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">Account Ledger Entries</h5>
                            <small class="text-muted">Showing entries from {{ request('start_date', date('Y-m-01')) }} to {{ request('end_date', date('Y-m-d')) }}</small>
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
                        <table class="table table-hover" id="accountLedgerTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher No</th>
                                    <th>Description</th>
                                    <th>Memo</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                    <tr>
                                        <td>{{ $entry->journal->entry_date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ $entry->journal->voucher_no }}</span>
                                        </td>
                                        <td>
                                            <div>{{ $entry->journal->description }}</div>
                                            @if($entry->financialAccount)
                                                <small class="text-muted">{{ $entry->financialAccount->provider_name }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($entry->memo)
                                                <span class="text-muted">{{ Str::limit($entry->memo, 50) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
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
                                                $balance = $entry->running_balance;
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
                                            <p>No ledger entries found for this account</p>
                                            <p class="small">Try adjusting your date range</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                    <h5 class="modal-title" id="filterModalLabel">Filter Ledger Entries</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('ledger.account', $account->id) }}" method="GET">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ request('start_date', date('Y-m-01')) }}">
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ request('end_date', date('Y-m-d')) }}">
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
    <script>
        $(document).ready(function() {
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

        function exportAccountLedger() {
            const startDate = $('#start_date').val() || '{{ request('start_date', date('Y-m-01')) }}';
            const endDate = $('#end_date').val() || '{{ request('end_date', date('Y-m-d')) }}';
            
            const url = new URL(window.location);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            url.searchParams.set('export', 'pdf');
            
            window.open(url.toString(), '_blank');
        }

        function printLedger() {
            window.print();
        }

        function exportPDF() {
            const startDate = $('#start_date').val() || '{{ request('start_date', date('Y-m-01')) }}';
            const endDate = $('#end_date').val() || '{{ request('end_date', date('Y-m-d')) }}';
            
            const url = new URL(window.location);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            url.searchParams.set('export', 'pdf');
            
            window.open(url.toString(), '_blank');
        }

        function exportExcel() {
            const startDate = $('#start_date').val() || '{{ request('start_date', date('Y-m-01')) }}';
            const endDate = $('#end_date').val() || '{{ request('end_date', date('Y-m-d')) }}';
            
            const url = new URL(window.location);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            url.searchParams.set('export', 'excel');
            
            window.open(url.toString(), '_blank');
        }
    </script>
    @endpush
@endsection 