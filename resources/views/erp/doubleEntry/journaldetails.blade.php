@extends('erp.master')

@section('title', 'Journal Details')

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
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('journal.list') }}" class="text-decoration-none">Journal Entries</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Journal Details</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Journal Details</h2>
                    <p class="text-muted mb-0">View and manage journal details.</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('journal.list') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Journals
                        </a>
                        <button type="button" class="btn btn-outline-success" onclick="printJournal()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="exportJournal()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Journal Information Card -->
        <div class="container-fluid px-4 py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-invoice me-2"></i>
                                Journal Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Voucher No</label>
                                        <h6 class="mb-0 fw-bold">{{ $journal->voucher_no ?? 'N/A' }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Journal Date</label>
                                        <h6 class="mb-0 fw-bold">{{ $journal->entry_date ? $journal->entry_date->format('M d, Y') : 'N/A' }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Journal Type</label>
                                        <div>
                                            @if($journal->type)
                                                <span class="badge bg-info">{{ $journal->type }}</span>
                                            @else
                                                <span class="badge bg-secondary">General</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Created By</label>
                                        <h6 class="mb-0 fw-bold">{{ $journal->createdBy->first_name . ' ' . $journal->createdBy->last_name ?? 'N/A' }}</h6>
                                    </div>
                                </div>
                            </div>
                            @if($journal->description)
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label text-muted small">Memo/Description</label>
                                            <p class="mb-0">{{ $journal->description }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Created At</label>
                                        <h6 class="mb-0 fw-bold">{{ $journal->created_at ? $journal->created_at->format('M d, Y h:i A') : 'N/A' }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Last Updated</label>
                                        <h6 class="mb-0 fw-bold">{{ $journal->updated_at ? $journal->updated_at->format('M d, Y h:i A') : 'N/A' }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Total Debit</label>
                                        <h6 class="mb-0 fw-bold text-success">{{ number_format($journal->total_debit, 2) }}৳</h6>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Total Credit</label>
                                        <h6 class="mb-0 fw-bold text-warning">{{ number_format($journal->total_credit, 2) }}৳</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Journal Entries Section -->
        <div class="container-fluid px-4 pb-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Journal Entries
                            </h5>
                            <button type="button" class="btn btn-primary btn-sm" onclick="showAddEntryModal()">
                                <i class="fas fa-plus me-2"></i>Add Entry
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">Chart of Account</th>
                                            <th class="border-0">Financial Account</th>
                                            <th class="border-0 text-end">Debit</th>
                                            <th class="border-0 text-end">Credit</th>
                                            <th class="border-0">Memo</th>
                                            <th class="border-0 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="entriesTableBody">
                                        @forelse($journal->entries as $entry)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ $entry->chartOfAccount->name ?? 'N/A' }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $entry->chartOfAccount->code ?? 'N/A' }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($entry->financialAccount)
                                                        <div>
                                                            <strong>{{ $entry->financialAccount->provider_name ?? 'N/A' }}</strong>
                                                            <br>
                                                            <small class="text-muted">{{ $entry->financialAccount->account_number ?? 'N/A' }}</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($entry->debit > 0)
                                                        <span class="badge bg-success">{{ number_format($entry->debit, 2) }}৳</span>
                                                    @else
                                                        <span class="text-muted">0.00৳</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($entry->credit > 0)
                                                        <span class="badge bg-warning">{{ number_format($entry->credit, 2) }}৳</span>
                                                    @else
                                                        <span class="text-muted">0.00৳</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $entry->memo ?? '—' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                onclick="editEntry({{ $entry->id }})" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                onclick="deleteEntry({{ $entry->id }})" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-list fa-2x mb-2"></i>
                                                    <h6>No Journal Entries Found</h6>
                                                    <p>Add your first journal entry to get started.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">Totals:</th>
                                            <th class="text-end text-success">{{ number_format($journal->total_debit, 2) }}৳</th>
                                            <th class="text-end text-warning">{{ number_format($journal->total_credit, 2) }}৳</th>
                                            <th colspan="2"></th>
                                        </tr>
                                        <tr>
                                            <th colspan="2" class="text-end">Balance:</th>
                                            <th colspan="2" class="text-center">
                                                @if($journal->isBalanced())
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Balanced
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Unbalanced
                                                    </span>
                                                @endif
                                            </th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Entry Modal -->
    <div class="modal fade" id="addEntryModal" tabindex="-1" aria-labelledby="addEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEntryModalLabel">Add Journal Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="entryForm" action="{{ route('journal.entry.store', $journal->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="chart_of_account_id" class="form-label">Chart of Account <span class="text-danger">*</span></label>
                                <select class="form-control @error('chart_of_account_id') is-invalid @enderror" 
                                        id="chart_of_account_id" name="chart_of_account_id" required>
                                    <option value="">Select Account</option>
                                    @foreach($chartAccounts as $account)
                                        <option value="{{ $account->id }}" {{ old('chart_of_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} ({{ $account->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('chart_of_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="financial_account_id" class="form-label">Financial Account</label>
                                <select class="form-control @error('financial_account_id') is-invalid @enderror" 
                                        id="financial_account_id" name="financial_account_id">
                                    <option value="">Select Financial Account</option>
                                    @foreach($financialAccounts as $account)
                                        <option value="{{ $account->id }}" {{ old('financial_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->provider_name }} - {{ $account->account_number }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('financial_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="debit" class="form-label">Debit</label>
                                <input type="number" class="form-control @error('debit') is-invalid @enderror" 
                                       id="debit" name="debit" step="0.01" min="0" placeholder="0.00" 
                                       value="{{ old('debit') }}" onchange="validateAmounts()">
                                @error('debit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="credit" class="form-label">Credit</label>
                                <input type="number" class="form-control @error('credit') is-invalid @enderror" 
                                       id="credit" name="credit" step="0.01" min="0" placeholder="0.00" 
                                       value="{{ old('credit') }}" onchange="validateAmounts()">
                                @error('credit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="memo" class="form-label">Memo</label>
                            <textarea class="form-control @error('memo') is-invalid @enderror" 
                                      id="memo" name="memo" rows="2" placeholder="Optional memo">{{ old('memo') }}</textarea>
                            @error('memo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveEntryBtn" disabled>Add Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Entry Modal -->
    <div class="modal fade" id="editEntryModal" tabindex="-1" aria-labelledby="editEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEntryModalLabel">Edit Journal Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editEntryForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_chart_of_account_id" class="form-label">Chart of Account <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_chart_of_account_id" name="chart_of_account_id" required>
                                    <option value="">Select Account</option>
                                    @foreach($chartAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_financial_account_id" class="form-label">Financial Account</label>
                                <select class="form-control" id="edit_financial_account_id" name="financial_account_id">
                                    <option value="">Select Financial Account</option>
                                    @foreach($financialAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->provider_name }} - {{ $account->account_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_debit" class="form-label">Debit</label>
                                <input type="number" class="form-control" id="edit_debit" name="debit" 
                                       step="0.01" min="0" placeholder="0.00" onchange="validateEditAmounts()">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_credit" class="form-label">Credit</label>
                                <input type="number" class="form-control" id="edit_credit" name="credit" 
                                       step="0.01" min="0" placeholder="0.00" onchange="validateEditAmounts()">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_memo" class="form-label">Memo</label>
                            <textarea class="form-control" id="edit_memo" name="memo" rows="2" placeholder="Optional memo"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateEntryBtn" disabled>Update Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src='https://code.jquery.com/jquery-3.7.1.min.js'></script>
    <script>
        function showAddEntryModal() {
            $('#addEntryModal').modal('show');
        }

        function validateAmounts() {
            const debit = parseFloat($('#debit').val()) || 0;
            const credit = parseFloat($('#credit').val()) || 0;
            const saveBtn = $('#saveEntryBtn');
            
            if (debit > 0 || credit > 0) {
                saveBtn.prop('disabled', false);
            } else {
                saveBtn.prop('disabled', true);
            }
        }

        function validateEditAmounts() {
            const debit = parseFloat($('#edit_debit').val()) || 0;
            const credit = parseFloat($('#edit_credit').val()) || 0;
            const updateBtn = $('#updateEntryBtn');
            
            if (debit > 0 || credit > 0) {
                updateBtn.prop('disabled', false);
            } else {
                updateBtn.prop('disabled', true);
            }
        }

        function editEntry(entryId) {
            // Fetch entry data via AJAX
            $.ajax({
                url: '{{ url("erp/journal-entry") }}/' + entryId,
                type: 'GET',
                success: function(response) {
                    const entry = response.entry;
                    
                    // Populate form fields
                    $('#edit_chart_of_account_id').val(entry.chart_of_account_id);
                    $('#edit_financial_account_id').val(entry.financial_account_id);
                    $('#edit_debit').val(entry.debit);
                    $('#edit_credit').val(entry.credit);
                    $('#edit_memo').val(entry.memo);
                    
                    // Set form action
                    $('#editEntryForm').attr('action', '{{ url("erp/journal-entry") }}/' + entryId);
                    
                    // Show modal
                    $('#editEntryModal').modal('show');
                    
                    // Validate amounts
                    validateEditAmounts();
                },
                error: function() {
                    alert('Error loading entry data');
                }
            });
        }

        function deleteEntry(entryId) {
            if (confirm('Are you sure you want to delete this entry?')) {
                $.ajax({
                    url: '{{ url("erp/journal-entry") }}/' + entryId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting entry');
                        }
                    },
                    error: function() {
                        alert('Error deleting entry');
                    }
                });
            }
        }

        

        // Reset form when modal is closed
        $('#addEntryModal').on('hidden.bs.modal', function() {
            $('#entryForm')[0].reset();
            $('#saveEntryBtn').prop('disabled', true);
        });

        $('#editEntryModal').on('hidden.bs.modal', function() {
            $('#editEntryForm')[0].reset();
            $('#updateEntryBtn').prop('disabled', true);
        });
    </script>
@endsection