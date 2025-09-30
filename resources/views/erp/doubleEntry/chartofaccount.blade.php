@extends('erp.master')

@section('title', 'Chart of Account Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Chart of Account</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Chart of Account</h2>
                    <p class="text-muted mb-0">Manage chart of account information, hierarchy, and transactions efficiently.</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addParentModal">
                            <i class="fas fa-plus me-2"></i>Add Parent Account
                        </button>
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                            <i class="fas fa-plus me-2"></i>Add Chart Account
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

            <!-- Account Types Overview -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-layer-group me-2"></i>Account Types Overview
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($accountTypes as $accountType)
                                    <div class="col-md-3 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="text-primary mb-2">{{ $accountType->name }}</h6>
                                            <p class="text-muted small mb-2">{{ $accountType->subTypes->count() }} Sub-types</p>
                                            
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent Accounts Grid -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-folder me-2"></i>Parent Accounts
                            </h5>
                            <span class="badge bg-light text-dark">{{ $accountParents->count() }} Parents</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @forelse($accountParents as $parent)
                                    <div class="col-md-4 mb-3">
                                        <div class="card border h-100">
                                            <div class="card-header bg-light">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">{{ $parent->name }}</h6>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                onclick="editParent({{ $parent->id }}, '{{ $parent->name }}', '{{ $parent->code }}', '{{ $parent->description }}', {{ $parent->type_id }}, {{ $parent->sub_type_id }})"
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                onclick="deleteParent({{ $parent->id }}, '{{ $parent->name }}')"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <small class="text-muted">Code:</small>
                                                        <p class="mb-1"><strong>{{ $parent->code }}</strong></p>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted">Type:</small>
                                                        <p class="mb-1"><strong>{{ $parent->type->name ?? 'N/A' }}</strong></p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <small class="text-muted">Sub-Type:</small>
                                                        <p class="mb-1"><strong>{{ $parent->subType->name ?? 'N/A' }}</strong></p>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted">Accounts:</small>
                                                        <p class="mb-1"><strong>{{ $parent->accounts->count() }}</strong></p>
                                                    </div>
                                                </div>
                                                @if($parent->description)
                                                    <small class="text-muted">Description:</small>
                                                    <p class="mb-0 small">{{ Str::limit($parent->description, 50) }}</p>
                                                @endif
                                            </div>
                                            <div class="card-footer bg-light">
                                                <small class="text-muted">
                                                    Created by: {{ $parent->createdBy->first_name . ' ' . $parent->createdBy->last_name ?? 'N/A' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                                            <h6>No Parent Accounts Found</h6>
                                            <p>Create your first parent account to get started.</p>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart of Accounts Grid -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Chart of Accounts
                            </h5>
                            <span class="badge bg-light text-dark">{{ $chartOfAccounts->count() }} Accounts</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Parent Account</th>
                                            <th>Type</th>
                                            <th>Sub-Type</th>
                                            <th>Description</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($chartOfAccounts as $index => $account)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><span class="badge bg-primary">{{ $account->code }}</span></td>
                                                <td><strong>{{ $account->name }}</strong></td>
                                                <td>{{ $account->parent->name ?? 'N/A' }}</td>
                                                <td>{{ $account->type->name ?? 'N/A' }}</td>
                                                <td>{{ $account->subType->name ?? 'N/A' }}</td>
                                                <td>{{ Str::limit($account->description, 30) }}</td>
                                                <td>{{ $account->createdBy->first_name . ' ' . $account->createdBy->last_name ?? 'N/A' }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="editAccount({{ $account->id }}, '{{ $account->name }}', '{{ $account->code }}', '{{ $account->description }}', {{ $account->parent_id }}, {{ $account->type_id }}, {{ $account->sub_type_id }})"
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteAccount({{ $account->id }}, '{{ $account->name }}')"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                                    <h6>No Chart of Accounts Found</h6>
                                                    <p>Create your first chart account to get started.</p>
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

    <!-- Add Parent Account Modal -->
    <div class="modal fade" id="addParentModal" tabindex="-1" aria-labelledby="addParentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addParentModalLabel">Add New Parent Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="parentForm" action="{{ route('chart-of-account.parent.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="parent_name" class="form-label">Parent Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="parent_name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="parent_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="parent_code" name="code" value="{{ old('code') }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="parent_type_id" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-control @error('type_id') is-invalid @enderror" id="parent_type_id" name="type_id" required>
                                <option value="">Select Account Type</option>
                                @foreach($accountTypes as $accountType)
                                    <option value="{{ $accountType->id }}">{{ $accountType->name }}</option>
                                @endforeach
                            </select>
                            @error('type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="parent_sub_type_id" class="form-label">Sub Type <span class="text-danger">*</span></label>
                            <select class="form-control @error('sub_type_id') is-invalid @enderror" id="parent_sub_type_id" name="sub_type_id" required>
                                <option value="">Select Sub Type</option>
                            </select>
                            @error('sub_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="parent_description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="parent_description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Parent Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Chart Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAccountModalLabel">Add New Chart Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="accountForm" action="{{ route('chart-of-account.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="account_name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="account_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="account_code" name="code" value="{{ old('code') }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="account_parent_id" class="form-label">Parent Account <span class="text-danger">*</span></label>
                            <select class="form-control @error('parent_id') is-invalid @enderror" id="account_parent_id" name="parent_id" required>
                                <option value="">Select Parent Account</option>
                                @foreach($accountParents as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->name }} ({{ $parent->code }})</option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="account_type_id" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-control @error('type_id') is-invalid @enderror" id="account_type_id" name="type_id" required>
                                <option value="">Select Account Type</option>
                                @foreach($accountTypes as $accountType)
                                    <option value="{{ $accountType->id }}">{{ $accountType->name }}</option>
                                @endforeach
                            </select>
                            @error('type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="account_sub_type_id" class="form-label">Sub Type <span class="text-danger">*</span></label>
                            <select class="form-control @error('sub_type_id') is-invalid @enderror" id="account_sub_type_id" name="sub_type_id" required>
                                <option value="">Select Sub Type</option>
                            </select>
                            @error('sub_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="account_description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="account_description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="account_is_cash_account" class="form-label">Affect Cash?</label>
                            <input type="checkbox" class="form-check-input" id="account_is_cash_account" name="is_cash_account" value="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save Chart Account
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
            // Handle form submissions
            $('#parentForm, #accountForm').on('submit', function() {
                var $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
            });

            // Dynamic sub-type loading for parent form
            $('#parent_type_id').on('change', function() {
                var typeId = $(this).val();
                var $subTypeSelect = $('#parent_sub_type_id');
                
                // Reset sub-type select
                $subTypeSelect.html('<option value="">Select Sub Type</option>');
                
                if (typeId) {
                    // Show loading state
                    $subTypeSelect.prop('disabled', true);
                    
                    $.ajax({
                        url: '{{ url("erp/account-type") }}/' + typeId + '/sub-types',
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data && data.length > 0) {
                                data.forEach(function(subType) {
                                    $subTypeSelect.append('<option value="' + subType.id + '">' + subType.name + '</option>');
                                });
                            } else {
                                $subTypeSelect.append('<option value="" disabled>No sub-types found</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error loading sub-types:', error);
                            $subTypeSelect.append('<option value="" disabled>Error loading sub-types</option>');
                        },
                        complete: function() {
                            $subTypeSelect.prop('disabled', false);
                        }
                    });
                }
            });

            // Dynamic sub-type loading for account form
            $('#account_type_id').on('change', function() {
                var typeId = $(this).val();
                var $subTypeSelect = $('#account_sub_type_id');
                
                // Reset sub-type select
                $subTypeSelect.html('<option value="">Select Sub Type</option>');
                
                if (typeId) {
                    // Show loading state
                    $subTypeSelect.prop('disabled', true);
                    
                    $.ajax({
                        url: '{{ url("erp/account-type") }}/' + typeId + '/sub-types',
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data && data.length > 0) {
                                data.forEach(function(subType) {
                                    $subTypeSelect.append('<option value="' + subType.id + '">' + subType.name + '</option>');
                                });
                            } else {
                                $subTypeSelect.append('<option value="" disabled>No sub-types found</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error loading sub-types:', error);
                            $subTypeSelect.append('<option value="" disabled>Error loading sub-types</option>');
                        },
                        complete: function() {
                            $subTypeSelect.prop('disabled', false);
                        }
                    });
                }
            });

            // Modal reset on close
            $('#addParentModal, #addAccountModal').on('hidden.bs.modal', function() {
                var $form = $(this).find('form');
                $form[0].reset();
                $form.find('.is-invalid').removeClass('is-invalid');
                
                // Reset form action and method for create operations
                if ($form.attr('id') === 'parentForm') {
                    $form.attr('action', '{{ route("chart-of-account.parent.store") }}');
                } else if ($form.attr('id') === 'accountForm') {
                    $form.attr('action', '{{ route("chart-of-account.store") }}');
                }
                
                // Remove any _method field (reset to POST for create)
                $form.find('input[name="_method"]').remove();
            });
        });

        // Edit parent function
        function editParent(id, name, code, description, typeId, subTypeId) {
            $('#parent_name').val(name);
            $('#parent_code').val(code);
            $('#parent_description').val(description);
            $('#parent_type_id').val(typeId);
            
            // Trigger sub-type loading and set value after loading
            $('#parent_type_id').trigger('change');
            
            // Wait for sub-types to load then set the value
            var checkSubTypes = setInterval(function() {
                if ($('#parent_sub_type_id option').length > 1) {
                    $('#parent_sub_type_id').val(subTypeId);
                    clearInterval(checkSubTypes);
                }
            }, 100);
            
            // Set the form action and method for update
            $('#parentForm').attr('action', '{{ url("erp/chart-of-account/parent") }}/' + id);
            $('#parentForm').find('input[name="_method"]').remove(); // Remove any existing _method field
            $('#parentForm').append('<input type="hidden" name="_method" value="PUT">');
            $('#addParentModal').modal('show');
        }

        // Edit account function
        function editAccount(id, name, code, description, parentId, typeId, subTypeId) {
            $('#account_name').val(name);
            $('#account_code').val(code);
            $('#account_description').val(description);
            $('#account_parent_id').val(parentId);
            $('#account_type_id').val(typeId);
            
            // Trigger sub-type loading and set value after loading
            $('#account_type_id').trigger('change');
            
            // Wait for sub-types to load then set the value
            var checkSubTypes = setInterval(function() {
                if ($('#account_sub_type_id option').length > 1) {
                    $('#account_sub_type_id').val(subTypeId);
                    clearInterval(checkSubTypes);
                }
            }, 100);
            
            // Set the form action and method for update
            $('#accountForm').attr('action', '{{ url("erp/chart-of-account") }}/' + id);
            $('#accountForm').find('input[name="_method"]').remove(); // Remove any existing _method field
            $('#accountForm').append('<input type="hidden" name="_method" value="PUT">');
            $('#addAccountModal').modal('show');
        }

        // Delete functions
        function deleteParent(id, name) {
            if (confirm('Are you sure you want to delete the parent account "' + name + '"?')) {
                $.ajax({
                    url: '{{ url("erp/chart-of-account/parent") }}/' + id,
                    type: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }

        function deleteAccount(id, name) {
            if (confirm('Are you sure you want to delete the account "' + name + '"?')) {
                $.ajax({
                    url: '{{ url("erp/chart-of-account") }}/' + id,
                    type: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }
    </script>
    @endpush
@endsection