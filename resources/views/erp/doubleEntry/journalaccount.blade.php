@extends('erp.master')

@section('title', 'Journal Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Journal Entries</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Journal Entries</h2>
                    <p class="text-muted mb-0">Manage double-entry bookkeeping journal entries and transactions.</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#addJournalModal">
                            <i class="fas fa-plus me-2"></i>Add Journal
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="container-fluid px-4 py-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Journal Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Journals</h6>
                                    <h3 class="mb-0">{{ $journals->count() ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-book fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Debit</h6>
                                    <h3 class="mb-0">{{ number_format($journals->sum('total_debit') ?? 0, 2) }}৳</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-down fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Credit</h6>
                                    <h3 class="mb-0">{{ number_format($journals->sum('total_credit') ?? 0, 2) }}৳</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-up fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Balanced</h6>
                                    <h3 class="mb-0">
                                        {{ $journals->where('total_debit', '=', 'total_credit')->count() ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-balance-scale fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Journal Entries Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Journal Entries
                            </h5>
                            <span class="badge bg-light text-dark">{{ $journals->count() ?? 0 }} Entries</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Voucher No</th>
                                            <th>Date</th>
                                            <th>Memo</th>
                                            <th>Type</th>
                                            <th>Total Debit</th>
                                            <th>Total Credit</th>
                                            <th>Balance</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($journals ?? [] as $index => $journal)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <span class="badge bg-primary">{{ $journal->voucher_no }}</span>
                                                </td>
                                                <td>{{ $journal->entry_date->format('M d, Y') }}</td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 200px;"
                                                        title="{{ $journal->description }}">
                                                        {{ $journal->description ?? 'No description' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($journal->type)
                                                        @php
                                                            $typeColors = [
                                                                'Journal' => 'bg-primary',
                                                                'Payment' => 'bg-success',
                                                                'Receipt' => 'bg-info',
                                                                'Contra' => 'bg-warning',
                                                                'Adjustment' => 'bg-secondary'
                                                            ];
                                                            $color = $typeColors[$journal->type] ?? 'bg-secondary';
                                                        @endphp
                                                        <span class="badge {{ $color }}">{{ $journal->type }}</span>
                                                    @else
                                                        <span class="badge bg-light text-dark">General</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        {{ number_format($journal->total_debit, 2) }}৳
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning">
                                                        {{ number_format($journal->total_credit, 2) }}৳
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($journal->isBalanced())
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Balanced
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>Unbalanced
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>{{ $journal->createdBy->first_name . ' ' . $journal->createdBy->last_name ?? 'N/A' }}
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('journal.show', $journal->id) }}"
                                                            class="btn btn-outline-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-primary"
                                                            onclick="editJournal({{ $journal->id }}, '{{ $journal->entry_date->format('Y-m-d') }}', '{{ addslashes($journal->description) }}', '{{ $journal->type }}')"
                                                            title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger"
                                                            onclick="deleteJournal({{ $journal->id }}, '{{ $journal->voucher_no }}')"
                                                            title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">
                                                    <i class="fas fa-book fa-2x mb-2"></i>
                                                    <h6>No Journal Entries Found</h6>
                                                    <p>Create your first journal entry to get started.</p>
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
        </div>
    </div>

    <!-- Add Journal Modal -->
    <div class="modal fade" id="addJournalModal" tabindex="-1" aria-labelledby="addJournalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addJournalModalLabel">New Journal Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="journalForm" action="{{ route('journal.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="entry_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('entry_date') is-invalid @enderror"
                                    id="entry_date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}"
                                    required>
                                @error('entry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="type" class="form-label">Journal Type</label>
                                <select class="form-control @error('type') is-invalid @enderror" id="type" name="type">
                                    <option value="">Select Type</option>
                                    <option value="Journal" {{ old('type') == 'Journal' ? 'selected' : '' }}>Journal</option>
                                    <option value="Payment" {{ old('type') == 'Payment' ? 'selected' : '' }}>Payment</option>
                                    <option value="Receipt" {{ old('type') == 'Receipt' ? 'selected' : '' }}>Receipt</option>
                                    <option value="Contra" {{ old('type') == 'Contra' ? 'selected' : '' }}>Contra</option>
                                    <option value="Adjustment" {{ old('type') == 'Adjustment' ? 'selected' : '' }}>Adjustment
                                    </option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Balance Status</label>
                                <div class="d-flex align-items-center">
                                    <span id="balanceStatus" class="badge bg-danger me-2">Unbalanced</span>
                                    <small id="balanceDifference" class="text-muted">Difference: 0.00৳</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Memo/Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                name="description" rows="2"
                                placeholder="Enter journal entry description...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Journal Entries</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEntryRow()">
                                    <i class="fas fa-plus me-1"></i>Add Line
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="entriesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Chart of Account</th>
                                            <th>Financial Account</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                            <th>Memo</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="entriesTableBody">
                                        <!-- Entry rows will be added here dynamically -->
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">Totals:</th>
                                            <th id="totalDebit">0.00৳</th>
                                            <th id="totalCredit">0.00৳</th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveJournalBtn">
                            <i class="fas fa-save me-2"></i>Save Journal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            let entryRowCount = 0;
            const chartAccounts = @json($chartAccounts ?? []);
            const financialAccounts = @json($financialAccounts ?? []);

            $(document).ready(function () {
                // Initialize with at least 2 entry rows
                addEntryRow();
                addEntryRow();

                // Handle form submission
                $('#journalForm').on('submit', function () {
                    // Set default values for empty debit/credit fields
                    $('.debit-input').each(function () {
                        if ($(this).val() === '') {
                            $(this).val('0');
                        }
                    });
                    $('.credit-input').each(function () {
                        if ($(this).val() === '') {
                            $(this).val('0');
                        }
                    });

                    var $submitBtn = $(this).find('button[type="submit"]');
                    $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
                });

                // Modal reset on close
                $('#addJournalModal').on('hidden.bs.modal', function () {
                    var $form = $(this).find('form');
                    $form[0].reset();
                    $form.find('.is-invalid').removeClass('is-invalid');

                    // Reset entries table
                    $('#entriesTableBody').empty();
                    entryRowCount = 0;
                    addEntryRow();
                    addEntryRow();

                    // Reset form action and method for create operations
                    $form.attr('action', '{{ route("journal.store") }}');
                    $form.find('input[name="_method"]').remove();

                    // Reset modal title
                    $('#addJournalModalLabel').text('New Journal Entry');

                    // Reset balance status
                    updateBalanceStatus();
                });
            });

            function addEntryRow() {
                entryRowCount++;
                const rowHtml = `
                        <tr id="entryRow${entryRowCount}">
                            <td>
                                <select class="form-control chart-account-select" name="entries[${entryRowCount}][chart_of_account_id]" required>
                                    <option value="">Select Account</option>
                                    ${chartAccounts.map(account =>
                    `<option value="${account.id}">${account.name} (${account.code}) - ${account.parent ? account.parent.name : 'N/A'}</option>`
                ).join('')}
                                </select>
                            </td>
                            <td>
                                <select class="form-control financial-account-select" name="entries[${entryRowCount}][financial_account_id]">
                                    <option value="">Select Financial Account</option>
                                    ${financialAccounts.map(account =>
                    `<option value="${account.id}">${account.provider_name} - ${account.account_number}</option>`
                ).join('')}
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control debit-input" name="entries[${entryRowCount}][debit]" 
                                       step="0.01" min="0" placeholder="0.00" onchange="updateTotals()">
                            </td>
                            <td>
                                <input type="number" class="form-control credit-input" name="entries[${entryRowCount}][credit]" 
                                       step="0.01" min="0" placeholder="0.00" onchange="updateTotals()">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="entries[${entryRowCount}][memo]" 
                                       placeholder="Memo">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEntryRow(${entryRowCount})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                $('#entriesTableBody').append(rowHtml);
            }

            function removeEntryRow(rowId) {
                $(`#entryRow${rowId}`).remove();
                updateTotals();
            }

            function updateTotals() {
                let totalDebit = 0;
                let totalCredit = 0;

                $('.debit-input').each(function () {
                    totalDebit += parseFloat($(this).val()) || 0;
                });

                $('.credit-input').each(function () {
                    totalCredit += parseFloat($(this).val()) || 0;
                });

                $('#totalDebit').text(totalDebit.toFixed(2) + '৳');
                $('#totalCredit').text(totalCredit.toFixed(2) + '৳');

                updateBalanceStatus(totalDebit, totalCredit);
            }

            function updateBalanceStatus(totalDebit = 0, totalCredit = 0) {
                if (totalDebit === 0 && totalCredit === 0) {
                    // Calculate from current inputs
                    $('.debit-input').each(function () {
                        totalDebit += parseFloat($(this).val()) || 0;
                    });
                    $('.credit-input').each(function () {
                        totalCredit += parseFloat($(this).val()) || 0;
                    });
                }

                const difference = Math.abs(totalDebit - totalCredit);
                const isBalanced = difference < 0.01;

                if (isBalanced) {
                    $('#balanceStatus').removeClass('bg-danger').addClass('bg-success').text('Balanced');
                    // $('#saveJournalBtn').prop('disabled', false);
                } else {
                    $('#balanceStatus').removeClass('bg-success').addClass('bg-danger').text('Unbalanced');
                    // $('#saveJournalBtn').prop('disabled', true);
                }

                $('#balanceDifference').text('Difference: ' + difference.toFixed(2) + '৳');
            }

            function editJournal(id, date, description, type) {
                // Clear existing entries
                $('#entriesTableBody').empty();
                entryRowCount = 0;

                // Populate form fields
                $('#entry_date').val(date);
                $('#description').val(description);
                $('#type').val(type);

                // Set the form action and method for update
                $('#journalForm').attr('action', '{{ url("erp/journal") }}/' + id);
                $('#journalForm').find('input[name="_method"]').remove();
                $('#journalForm').append('<input type="hidden" name="_method" value="PUT">');

                // Update modal title
                $('#addJournalModalLabel').text('Edit Journal Entry');

                // Show the modal first
                $('#addJournalModal').modal('show');

                // Fetch journal entries via AJAX
                $.ajax({
                    url: '{{ url("erp/journal") }}/' + id + '/entries',
                    type: 'GET',
                    success: function (response) {
                        if (response.entries && response.entries.length > 0) {
                            response.entries.forEach(function (entry) {
                                entryRowCount++;

                                // Create chart account options
                                let chartAccountOptions = '<option value="">Select Account</option>';
                                chartAccounts.forEach(function (account) {
                                    const selected = account.id == entry.chart_of_account_id ? 'selected' : '';
                                    const parentName = account.parent ? account.parent.name : 'N/A';
                                    chartAccountOptions += '<option value="' + account.id + '" ' + selected + '>' +
                                        account.name + ' (' + account.code + ') - ' + parentName + '</option>';
                                });

                                // Create financial account options
                                let financialAccountOptions = '<option value="">Select Financial Account</option>';
                                financialAccounts.forEach(function (account) {
                                    const selected = account.id == entry.financial_account_id ? 'selected' : '';
                                    financialAccountOptions += '<option value="' + account.id + '" ' + selected + '>' +
                                        account.provider_name + ' - ' + account.account_number + '</option>';
                                });

                                const rowHtml = '<tr id="entryRow' + entryRowCount + '">' +
                                    '<td>' +
                                    '<select class="form-control chart-account-select" name="entries[' + entryRowCount + '][chart_of_account_id]" required>' +
                                    chartAccountOptions +
                                    '</select>' +
                                    '</td>' +
                                    '<td>' +
                                    '<select class="form-control financial-account-select" name="entries[' + entryRowCount + '][financial_account_id]">' +
                                    financialAccountOptions +
                                    '</select>' +
                                    '</td>' +
                                    '<td>' +
                                    '<input type="number" class="form-control debit-input" name="entries[' + entryRowCount + '][debit]" ' +
                                    'step="0.01" min="0" placeholder="0.00" value="' + (entry.debit || 0) + '" onchange="updateTotals()">' +
                                    '</td>' +
                                    '<td>' +
                                    '<input type="number" class="form-control credit-input" name="entries[' + entryRowCount + '][credit]" ' +
                                    'step="0.01" min="0" placeholder="0.00" value="' + (entry.credit || 0) + '" onchange="updateTotals()">' +
                                    '</td>' +
                                    '<td>' +
                                    '<input type="text" class="form-control" name="entries[' + entryRowCount + '][memo]" ' +
                                    'placeholder="Memo" value="' + (entry.memo || '') + '">' +
                                    '</td>' +
                                    '<td>' +
                                    '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEntryRow(' + entryRowCount + ')">' +
                                    '<i class="fas fa-trash"></i>' +
                                    '</button>' +
                                    '</td>' +
                                    '</tr>';

                                $('#entriesTableBody').append(rowHtml);
                            });
                        } else {
                            // Add at least 2 empty rows if no entries
                            addEntryRow();
                            addEntryRow();
                        }

                        // Update totals and balance status
                        updateTotals();
                    },
                    error: function () {
                        // Add default rows if AJAX fails
                        addEntryRow();
                        addEntryRow();
                        updateTotals();
                    }
                });
            }

            function deleteJournal(id, voucherNo) {
                if (confirm('Are you sure you want to delete the journal entry "' + voucherNo + '"?')) {
                    $.ajax({
                        url: '{{ url("erp/journal") }}/' + id,
                        type: 'DELETE',
                        data: { _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            alert('An error occurred while deleting the journal entry.');
                        }
                    });
                }
            }
        </script>
    @endpush
@endsection