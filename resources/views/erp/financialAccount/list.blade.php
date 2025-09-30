@extends('erp.master')

@section('title', 'Financial Account Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Financial Accounts</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Financial Accounts</h2>
                    <p class="text-muted mb-0">Manage financial accounts, transactions, and account balances efficiently.</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                            <i class="fas fa-plus me-2"></i>Add Account
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

            <!-- Account Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Accounts</h6>
                                    <h3 class="mb-0">{{ $accounts->count() ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-wallet fa-2x"></i>
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
                                    <h6 class="card-title">Bank Accounts</h6>
                                    <h3 class="mb-0">{{ $accounts->where('type', 'bank')->count() ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-university fa-2x"></i>
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
                                    <h6 class="card-title">Mobile Accounts</h6>
                                    <h3 class="mb-0">{{ $accounts->where('type', 'mobile')->count() ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-mobile-alt fa-2x"></i>
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
                                    <h6 class="card-title">Currencies</h6>
                                    <h3 class="mb-0">{{ $accounts->unique('currency')->count() ?? 0 }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Accounts Grid -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-university me-2"></i>Financial Accounts
                            </h5>
                            <span class="badge bg-light text-dark">{{ $accounts->count() ?? 0 }} Accounts</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Chart Account</th>
                                            <th>Account Type</th>
                                            <th>Provider</th>
                                            <th>Account Number</th>
                                            <th>Account Holder</th>
                                            <th>Currency</th>
                                            <th>Branch/Swift</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($accounts ?? [] as $index => $account)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $account->account->name ?? 'N/A' }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $account->account->code ?? '' }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $account->type === 'bank' ? 'bg-primary' : 'bg-warning' }}">
                                                        <i class="fas {{ $account->type === 'bank' ? 'fa-university' : 'fa-mobile-alt' }} me-1"></i>
                                                        {{ ucfirst($account->type) }}
                                                    </span>
                                                </td>
                                                <td><strong>{{ $account->provider_name }}</strong></td>
                                                <td><span class="badge bg-secondary">{{ $account->account_number }}</span></td>
                                                <td>{{ $account->account_holder_name ?? 'N/A' }}</td>
                                                <td><span class="badge bg-info">{{ $account->currency }}</span></td>
                                                <td>
                                                    @if($account->type === 'bank')
                                                        @if($account->branch_name)
                                                            <div><strong>Branch:</strong> {{ $account->branch_name }}</div>
                                                        @endif
                                                        @if($account->swift_code)
                                                            <div><strong>Swift:</strong> {{ $account->swift_code }}</div>
                                                        @endif
                                                    @else
                                                        @if($account->mobile_number)
                                                            <div><strong>Mobile:</strong> {{ $account->mobile_number }}</div>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="editAccount({{ $account->id }}, {{ $account->account_id }}, '{{ $account->type }}', '{{ $account->provider_name }}', '{{ $account->account_number }}', '{{ $account->account_holder_name }}', '{{ $account->currency }}', '{{ $account->branch_name }}', '{{ $account->swift_code }}', '{{ $account->mobile_number }}')"
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteAccount({{ $account->id }}, '{{ $account->provider_name }}')"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-university fa-2x mb-2"></i>
                                                    <h6>No Financial Accounts Found</h6>
                                                    <p>Create your first financial account to get started.</p>
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

    <!-- Add Financial Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAccountModalLabel">Add New Financial Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="accountForm" action="{{ route('financial-accounts.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_id" class="form-label">Chart of Account <span class="text-danger">*</span></label>
                                    <select class="form-control @error('account_id') is-invalid @enderror" id="account_id" name="account_id" required>
                                        <option value="">Select Chart Account</option>
                                        @foreach($chartAccounts ?? [] as $chartAccount)
                                            <option value="{{ $chartAccount->id }}" {{ old('account_id') == $chartAccount->id ? 'selected' : '' }}>
                                                {{ $chartAccount->name }} ({{ $chartAccount->code }}) - {{ $chartAccount->parent->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                                        <option value="">Select Account Type</option>
                                        <option value="bank" {{ old('type') == 'bank' ? 'selected' : '' }}>Bank Account</option>
                                        <option value="mobile" {{ old('type') == 'mobile' ? 'selected' : '' }}>Mobile Banking</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="provider_name" class="form-label">Provider Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('provider_name') is-invalid @enderror" id="provider_name" name="provider_name" value="{{ old('provider_name') }}" placeholder="e.g., DBBL, bKash, Nagad" required>
                                    @error('provider_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number') }}" required>
                                    @error('account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_holder_name" class="form-label">Account Holder Name</label>
                                    <input type="text" class="form-control @error('account_holder_name') is-invalid @enderror" id="account_holder_name" name="account_holder_name" value="{{ old('account_holder_name') }}">
                                    @error('account_holder_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                    <select class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency" required>
                                        <option value="">Select Currency</option>
                                        <option value="BDT" {{ old('currency') == 'BDT' ? 'selected' : '' }}>BDT (Bangladeshi Taka)</option>
                                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD (US Dollar)</option>
                                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                                        <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP (British Pound)</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Bank-specific fields -->
                        <div id="bankFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="branch_name" class="form-label">Branch Name</label>
                                        <input type="text" class="form-control @error('branch_name') is-invalid @enderror" id="branch_name" name="branch_name" value="{{ old('branch_name') }}" placeholder="e.g., Dhanmondi Branch">
                                        @error('branch_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="swift_code" class="form-label">Swift Code</label>
                                        <input type="text" class="form-control @error('swift_code') is-invalid @enderror" id="swift_code" name="swift_code" value="{{ old('swift_code') }}" placeholder="e.g., DBBLBDDH">
                                        @error('swift_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile-specific fields -->
                        <div id="mobileFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mobile_number" class="form-label">Mobile Number</label>
                                        <input type="text" class="form-control @error('mobile_number') is-invalid @enderror" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}" placeholder="e.g., 01712345678">
                                        @error('mobile_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle form submission
            $('#accountForm').on('submit', function() {
                var $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
            });

            // Handle account type change
            $('#type').on('change', function() {
                var type = $(this).val();
                if (type === 'bank') {
                    $('#bankFields').show();
                    $('#mobileFields').hide();
                    $('#branch_name, #swift_code').prop('required', false);
                    $('#mobile_number').prop('required', false);
                } else if (type === 'mobile') {
                    $('#bankFields').hide();
                    $('#mobileFields').show();
                    $('#branch_name, #swift_code').prop('required', false);
                    $('#mobile_number').prop('required', false);
                } else {
                    $('#bankFields, #mobileFields').hide();
                    $('#branch_name, #swift_code, #mobile_number').prop('required', false);
                }
            });

            // Modal reset on close
            $('#addAccountModal').on('hidden.bs.modal', function() {
                var $form = $(this).find('form');
                $form[0].reset();
                $form.find('.is-invalid').removeClass('is-invalid');
                
                // Hide conditional fields
                $('#bankFields, #mobileFields').hide();
                $('#branch_name, #swift_code, #mobile_number').prop('required', false);
                
                // Reset form action and method for create operations
                $form.attr('action', '{{ route("financial-accounts.store") }}');
                $form.find('input[name="_method"]').remove();
            });
        });

        // Edit account function
        function editAccount(id, accountId, type, providerName, accountNumber, accountHolderName, currency, branchName, swiftCode, mobileNumber) {
            $('#account_id').val(accountId);
            $('#type').val(type);
            $('#provider_name').val(providerName);
            $('#account_number').val(accountNumber);
            $('#account_holder_name').val(accountHolderName);
            $('#currency').val(currency);
            
            // Handle conditional fields based on type
            if (type === 'bank') {
                $('#bankFields').show();
                $('#mobileFields').hide();
                $('#branch_name').val(branchName);
                $('#swift_code').val(swiftCode);
            } else if (type === 'mobile') {
                $('#bankFields').hide();
                $('#mobileFields').show();
                $('#mobile_number').val(mobileNumber);
            }
            
            // Trigger change event to show/hide fields
            $('#type').trigger('change');
            
            // Set the form action and method for update
            $('#accountForm').attr('action', '{{ url("erp/financial-accounts") }}/' + id);
            $('#accountForm').find('input[name="_method"]').remove();
            $('#accountForm').append('<input type="hidden" name="_method" value="PUT">');
            
            $('#addAccountModal').modal('show');
        }

        // Delete account function
        function deleteAccount(id, providerName) {
            if (confirm('Are you sure you want to delete the account "' + providerName + '"?')) {
                $.ajax({
                    url: '{{ url("erp/financial-accounts") }}/' + id,
                    type: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred while deleting the account.');
                    }
                });
            }
        }
    </script>
    @endpush
@endsection