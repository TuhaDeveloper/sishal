@extends('erp.master')

@section('title', 'Customer Service Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Account Type</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Account Type</h2>
                    <p class="text-muted mb-0">Manage account type information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAccountTypeModal">
                            <i class="fas fa-plus me-2"></i>Add Account Type
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
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Account Types</h5>
                            <p class="text-muted">List of all account types in the system.</p>
                            
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

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($accountTypes as $index => $accountType)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $accountType->name }}</td>
                                                <td>{{ $accountType->createdBy->first_name . ' ' . $accountType->createdBy->last_name ?? 'N/A' }}</td>
                                                <td>{{ $accountType->created_at->format('M d, Y h:i A') }}</td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="editAccountType({{ $accountType->id }}, '{{ $accountType->name }}')"
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteAccountType({{ $accountType->id }}, '{{ $accountType->name }}')"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @if($accountType->subTypes->count() > 0)
                                                @foreach($accountType->subTypes as $subindex => $subType)
                                                    <tr>
                                                        <td></td>
                                                        <td>{{ $subindex + 1 }}/ {{ $subType->name }}</td>
                                                        <td>{{ $subType->createdBy->first_name . ' ' . $subType->createdBy->last_name ?? 'N/A' }}</td>
                                                        <td>{{ $subType->created_at->format('M d, Y h:i A') }}</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                                                                         <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                 onclick="editAccountType({{ $subType->id }}, '{{ $subType->name }}', {{ $subType->type_id }})"
                                                                 title="Edit">
                                                             <i class="fas fa-edit"></i>
                                                         </button>
                                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                        onclick="deleteAccountType({{ $subType->id }}, '{{ $subType->name }}')"
                                                                        title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No account types found.</td>
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

    <!-- Add Account Type Modal -->
    <div class="modal fade" id="addAccountTypeModal" tabindex="-1" aria-labelledby="addAccountTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAccountTypeModalLabel">Add New Account Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="accountTypeForm" action="{{ route('account-type.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Account Type Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="type_id" class="form-label">Account Type</label>
                            <select class="form-control @error('type_id') is-invalid @enderror" id="type_id" name="type_id">
                                <option value="">Select Account Type</option>
                                @foreach($accountTypes as $accountType)
                                    <option value="{{ $accountType->id }}">{{ $accountType->name }}</option>
                                @endforeach
                            </select>
                            @error('type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Account Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Account Type Modal -->
    <div class="modal fade" id="editAccountTypeModal" tabindex="-1" aria-labelledby="editAccountTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAccountTypeModalLabel">Edit Account Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editAccountTypeForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Account Type Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_type_id" class="form-label">Account Type</label>
                            <select class="form-control @error('type_id') is-invalid @enderror" id="edit_type_id" name="type_id">
                                <option value="">Select Account Type</option>
                                @foreach($accountTypes as $accountType)
                                    <option value="{{ $accountType->id }}">{{ $accountType->name }}</option>
                                @endforeach
                            </select>
                            @error('type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Account Type
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
            // Initialize all account type actions
            initializeAccountTypeActions();
            
            // Handle form submission for adding account type - Normal form submission
            $('#accountTypeForm').on('submit', function() {
                var $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
            });

            // Handle form submission for editing account type - Normal form submission
            $('#editAccountTypeForm').on('submit', function() {
                var $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Updating...');
            });

            // Handle modal events
            $('#addAccountTypeModal').on('hidden.bs.modal', function() {
                // Reset form when modal is closed
                $('#accountTypeForm')[0].reset();
                
                // Remove validation classes
                $(this).find('.is-invalid').removeClass('is-invalid');
            });

            $('#editAccountTypeModal').on('hidden.bs.modal', function() {
                // Reset form when modal is closed
                $('#editAccountTypeForm')[0].reset();
                
                // Remove validation classes
                $(this).find('.is-invalid').removeClass('is-invalid');
            });
        });

        // Function to initialize all account type actions
        function initializeAccountTypeActions() {
            // Edit account type functionality
            window.editAccountType = function(id, name, typeId = null) {
                $('#edit_name').val(name);
                $('#editAccountTypeForm').attr('action', '{{ url("erp/account-type") }}/' + id);
                
                // Set the type_id if it's a sub-type
                if (typeId) {
                    $('#edit_type_id').val(typeId);
                } else {
                    $('#edit_type_id').val('');
                }
                
                $('#editAccountTypeModal').modal('show');
            };

            // Delete account type functionality
            window.deleteAccountType = function(id, name) {
                if (confirm('Are you sure you want to delete the account type "' + name + '"?')) {
                    $.ajax({
                        url: '{{ url("erp/account-type") }}/' + id,
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showAlert('success', response.message);
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                showAlert('error', response.message || 'An error occurred while deleting the account type.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            showAlert('error', 'An error occurred while deleting the account type.');
                        }
                    });
                }
            };
        }

        // Function to show alerts
        function showAlert(type, message) {
            var alertClass = type === 'success' ? 'success' : 'danger';
            var alertHtml = '<div class="alert alert-' + alertClass + ' alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 16000; min-width: 300px;">' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>';
            
            var $alertDiv = $(alertHtml);
            $('body').append($alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(function() {
                $alertDiv.remove();
            }, 5000);
        }
    </script>
    @endpush
@endsection