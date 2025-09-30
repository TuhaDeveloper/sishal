@extends('erp.master')

@section('title', 'Transfer List')



@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Transfer List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Transfer List</h2>
                    <p class="text-muted mb-0">Manage transfers between financial accounts.</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#addTransferModal">
                            <i class="fas fa-plus me-2"></i>Add Transfer
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer List Table -->
        <div class="container-fluid px-4 py-4">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>From Account</th>
                                    <th>To Account</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                    <th>Memo</th>
                                    <th>Journal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                    <tr>
                                        <td>{{ $transfer->transfer_date->format('M d, Y') }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $transfer->fromFinancialAccount->provider_name }}</div>
                                            <small class="text-muted">{{ $transfer->fromFinancialAccount->account_number }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $transfer->toFinancialAccount->provider_name }}</div>
                                            <small class="text-muted">{{ $transfer->toFinancialAccount->account_number }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary">৳{{ number_format($transfer->amount, 2) }}</span>
                                        </td>
                                        <td>{{ $transfer->reference ?? '-' }}</td>
                                        <td>{{ $transfer->memo ?? '-' }}</td>
                                        <td>
                                            @if($transfer->journal)
                                                <span class="badge bg-info">{{ $transfer->journal->voucher_no }}</span>
                                            @else
                                                <span class="badge bg-secondary">No Journal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-3"></i>
                                            <p>No transfers found</p>
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

    <!-- Add Transfer Modal -->
    <div class="modal fade" id="addTransferModal" tabindex="-1" aria-labelledby="addTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransferModalLabel">Create New Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="transferForm" action="{{ route('transfer.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <!-- Alert for existing journals -->
                        <div id="existingJournalAlert" class="alert alert-info d-none">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Existing Journal Found!</strong> There are existing journals for this date. 
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="useExistingJournalBtn">
                                    Use Existing Journal
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="createNewJournalBtn">
                                    Create New Journal
                                </button>
                            </div>
                        </div>

                        <!-- Existing Journal Selection (hidden by default) -->
                        <div id="existingJournalSelection" class="d-none">
                            <div class="mb-3">
                                <label for="existing_journal_id" class="form-label">Select Existing Journal</label>
                                <select class="form-control" id="existing_journal_id" name="existing_journal_id">
                                    <option value="">Choose a journal...</option>
                                </select>
                                <div class="form-text">Select an existing journal to add this transfer to, or create a new one.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_financial_account_id" class="form-label">From Account <span class="text-danger">*</span></label>
                                    <select class="form-control @error('from_financial_account_id') is-invalid @enderror" 
                                            id="from_financial_account_id" name="from_financial_account_id" required>
                                        <option value="">Select Source Account</option>
                                        @foreach($financialAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('from_financial_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->provider_name }} - {{ $account->account_number }}
                                                @if($account->account_holder_name)
                                                    ({{ $account->account_holder_name }})
                                                @endif
                                                - Balance: ৳{{ number_format($account->balance, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('from_financial_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="to_financial_account_id" class="form-label">To Account <span class="text-danger">*</span></label>
                                    <select class="form-control @error('to_financial_account_id') is-invalid @enderror" 
                                            id="to_financial_account_id" name="to_financial_account_id" required>
                                        <option value="">Select Destination Account</option>
                                        @foreach($financialAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('to_financial_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->provider_name }} - {{ $account->account_number }}
                                                @if($account->account_holder_name)
                                                    ({{ $account->account_holder_name }})
                                                @endif
                                                - Balance: ৳{{ number_format($account->balance, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('to_financial_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">৳</span>
                                        <input type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror" 
                                               id="amount" name="amount" value="{{ old('amount') }}" required>
                                    </div>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="transfer_date" class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('transfer_date') is-invalid @enderror" 
                                           id="transfer_date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                                    @error('transfer_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reference" class="form-label">Reference</label>
                                    <input type="text" class="form-control @error('reference') is-invalid @enderror" 
                                           id="reference" name="reference" value="{{ old('reference') }}" 
                                           placeholder="e.g., Transfer Ref #123">
                                    @error('reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="memo" class="form-label">Memo</label>
                                    <input type="text" class="form-control @error('memo') is-invalid @enderror" 
                                           id="memo" name="memo" value="{{ old('memo') }}" 
                                           placeholder="Additional notes...">
                                    @error('memo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Hidden fields for journal handling -->
                        <input type="hidden" id="use_existing_journal" name="use_existing_journal" value="0">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Check for existing journals when date changes
            $('#transfer_date').on('change', function() {
                checkExistingJournals();
            });

            // Handle existing journal selection
            $('#useExistingJournalBtn').on('click', function() {
                $('#existingJournalSelection').removeClass('d-none');
                $('#use_existing_journal').val('1');
                $('#transferForm').attr('action', '{{ route("transfer.storeWithJournal") }}');
            });

            $('#createNewJournalBtn').on('click', function() {
                $('#existingJournalSelection').addClass('d-none');
                $('#use_existing_journal').val('0');
                $('#transferForm').attr('action', '{{ route("transfer.store") }}');
            });

            // Validate same account selection
            $('#from_financial_account_id, #to_financial_account_id').on('change', function() {
                validateAccountSelection();
            });

            function checkExistingJournals() {
                const date = $('#transfer_date').val();
                if (!date) return;

                $.get('{{ route("transfer.existingJournals") }}', { date: date })
                    .done(function(data) {
                        if (data.count > 0) {
                            // Populate existing journals dropdown
                            let options = '<option value="">Choose a journal...</option>';
                            data.journals.forEach(function(journal) {
                                options += `<option value="${journal.id}">${journal.voucher_no} - ${journal.description}</option>`;
                            });
                            $('#existing_journal_id').html(options);
                            
                            // Show alert
                            $('#existingJournalAlert').removeClass('d-none');
                        } else {
                            $('#existingJournalAlert').addClass('d-none');
                            $('#existingJournalSelection').addClass('d-none');
                            $('#use_existing_journal').val('0');
                            $('#transferForm').attr('action', '{{ route("transfer.store") }}');
                        }
                    })
                    .fail(function() {
                        console.error('Failed to check existing journals');
                    });
            }

            function validateAccountSelection() {
                const fromAccount = $('#from_financial_account_id').val();
                const toAccount = $('#to_financial_account_id').val();
                
                if (fromAccount && toAccount && fromAccount === toAccount) {
                    alert('From and To accounts must be different!');
                    $('#to_financial_account_id').val('');
                }
            }

            // Form submission handling
            $('#transferForm').on('submit', function(e) {
                const fromAccount = $('#from_financial_account_id').val();
                const toAccount = $('#to_financial_account_id').val();
                
                if (fromAccount === toAccount) {
                    e.preventDefault();
                    alert('From and To accounts must be different!');
                    return false;
                }

                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Creating...');
                submitBtn.prop('disabled', true);
            });

            // Reset form when modal is closed
            $('#addTransferModal').on('hidden.bs.modal', function() {
                $('#transferForm')[0].reset();
                $('#existingJournalAlert').addClass('d-none');
                $('#existingJournalSelection').addClass('d-none');
                $('#use_existing_journal').val('0');
                $('#transferForm').attr('action', '{{ route("transfer.store") }}');
                $('.is-invalid').removeClass('is-invalid');
                
                // Reset date to today
                $('#transfer_date').val(new Date().toISOString().split('T')[0]);
            });

            // Add loading indicator for AJAX requests
            $(document).ajaxStart(function() {
                $('#existingJournalAlert').html('<i class="fas fa-spinner fa-spin me-2"></i>Checking for existing journals...');
                $('#existingJournalAlert').removeClass('d-none alert-info').addClass('alert-warning');
            });

            $(document).ajaxComplete(function() {
                // Reset alert if no journals found
                if ($('#existingJournalAlert').hasClass('alert-warning')) {
                    $('#existingJournalAlert').addClass('d-none');
                }
            });

            // Prevent future dates
            $('#transfer_date').on('input', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                today.setHours(23, 59, 59, 999); // End of today
                
                if (selectedDate > today) {
                    alert('Cannot select future dates!');
                    this.value = new Date().toISOString().split('T')[0];
                }
            });
        });
    </script>
    @endpush
@endsection