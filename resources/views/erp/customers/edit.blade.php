@extends('erp.master')

@section('title', 'Edit Customer - ' . $customer->name)

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        
        <!-- Header Section -->
        <div class="container-fluid px-4 py-4 bg-white border-bottom shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-2 fw-bold text-dark">Edit Customer</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-primary">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customers.list') }}" class="text-decoration-none text-primary">Customers</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer.show', $customer->id) }}" class="text-decoration-none text-primary">{{ $customer->name }}</a></li>
                            <li class="breadcrumb-item active text-muted">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('customer.show', $customer->id) }}" class="btn btn-outline-secondary border-2 px-4 py-2 rounded-pill">
                        <i class="fas fa-arrow-left me-2"></i>Back to Customer
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container-fluid px-4 py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-primary text-white border-0 rounded-top-4 py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Customer Information</h5>
                        </div>
                        <div class="card-body p-5">
                            <form action="{{ route('customers.update', $customer->id) }}" method="POST" id="editCustomerForm">
                                @csrf
                                @method('PUT')
                                
                                <!-- Basic Information -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-user me-2"></i>Basic Information
                                        </h6>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label fw-semibold text-dark">Email Address</label>
                                        <input type="email" class="form-control border-2 rounded-3 @error('email') is-invalid @enderror" 
                                               id="email" name="email" value="{{ old('email', $customer->email) }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label fw-semibold text-dark">Phone Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('phone') is-invalid @enderror" 
                                               id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="tax_number" class="form-label fw-semibold text-dark">Tax Number</label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('tax_number') is-invalid @enderror" 
                                               id="tax_number" name="tax_number" value="{{ old('tax_number', $customer->tax_number) }}">
                                        @error('tax_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-map-marker-alt me-2"></i>Address Information
                                        </h6>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="address_1" class="form-label fw-semibold text-dark">Address Line 1</label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('address_1') is-invalid @enderror" 
                                               id="address_1" name="address_1" value="{{ old('address_1', $customer->address_1) }}">
                                        @error('address_1')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="address_2" class="form-label fw-semibold text-dark">Address Line 2</label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('address_2') is-invalid @enderror" 
                                               id="address_2" name="address_2" value="{{ old('address_2', $customer->address_2) }}">
                                        @error('address_2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="city" class="form-label fw-semibold text-dark">City</label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('city') is-invalid @enderror" 
                                               id="city" name="city" value="{{ old('city', $customer->city) }}">
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="state" class="form-label fw-semibold text-dark">State/Province</label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('state') is-invalid @enderror" 
                                               id="state" name="state" value="{{ old('state', $customer->state) }}">
                                        @error('state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="zip_code" class="form-label fw-semibold text-dark">ZIP/Postal Code</label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('zip_code') is-invalid @enderror" 
                                               id="zip_code" name="zip_code" value="{{ old('zip_code', $customer->zip_code) }}">
                                        @error('zip_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="country" class="form-label fw-semibold text-dark">Country</label>
                                        <input type="text" class="form-control border-2 rounded-3 @error('country') is-invalid @enderror" 
                                               id="country" name="country" value="{{ old('country', $customer->country) }}">
                                        @error('country')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Additional Settings -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-cog me-2"></i>Additional Settings
                                        </h6>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold text-dark">Customer Status</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                                   {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                Active Customer
                                            </label>
                                        </div>
                                        <small class="text-muted">Inactive customers won't be able to place orders</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold text-dark">Premium Status</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_premium" name="is_premium" value="1" 
                                                   {{ old('is_premium', $customer->is_premium) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_premium">
                                                Premium Customer
                                            </label>
                                        </div>
                                        <small class="text-muted">Premium customers get special benefits and priority</small>
                                    </div>
                                </div>

                                <!-- Customer Notes -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-sticky-note me-2"></i>Customer Notes
                                        </h6>
                                        <div class="mb-3">
                                            <label for="notes" class="form-label fw-semibold text-dark">Notes</label>
                                            <textarea class="form-control border-2 rounded-3 @error('notes') is-invalid @enderror" 
                                                      id="notes" name="notes" rows="4" 
                                                      placeholder="Add any important information about this customer...">{{ old('notes', $customer->notes) }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">These notes are only visible to staff members.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="row">
                                    <div class="col-12">
                                        <hr class="my-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="{{ route('customer.show', $customer->id) }}" class="btn btn-outline-secondary border-2 px-4 py-2 rounded-pill">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                            <div class="d-flex gap-3">
                                                <button type="button" class="btn btn-outline-danger border-2 px-4 py-2 rounded-pill" 
                                                        onclick="confirmDelete()">
                                                    <i class="fas fa-trash me-2"></i>Delete Customer
                                                </button>
                                                <button type="submit" class="btn btn-primary border-2 px-4 py-2 rounded-pill shadow-sm">
                                                    <i class="fas fa-save me-2"></i>Update Customer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to delete <strong>{{ $customer->name }}</strong>? This action cannot be undone and will remove all associated data.</p>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger rounded-pill px-4">
                            <i class="fas fa-trash me-2"></i>Delete Customer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Success!</strong> Customer updated successfully.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Show success toast if there's a success message
        @if(session('success'))
            const successToast = new bootstrap.Toast(document.getElementById('successToast'));
            successToast.show();
        @endif

        // Form validation
        document.getElementById('editCustomerForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable submit button and show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            
            // Re-enable after a delay (in case of validation errors)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 3000);
        });
    </script>
@endsection
