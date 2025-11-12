@extends('erp.master')

@section('title', 'Edit Bulk Discount')

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
                            <li class="breadcrumb-item"><a href="{{ route('bulk-discounts.index') }}" class="text-decoration-none">Bulk Discount Management</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Bulk Discount</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Edit Bulk Discount</h2>
                    <p class="text-muted mb-0">Update bulk discount settings.</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('bulk-discounts.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Discounts
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form action="{{ route('bulk-discounts.update', $bulkDiscount) }}" method="POST" id="bulkDiscountForm">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Basic Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Discount Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $bulkDiscount->name) }}" required 
                                           placeholder="e.g., Summer Sale 2024">
                                    <div class="form-text">Enter a descriptive name for this bulk discount</div>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3" 
                                              placeholder="Enter discount description...">{{ old('description', $bulkDiscount->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Discount Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Discount Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Discount Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                        <option value="percentage" {{ old('type', $bulkDiscount->type ?? 'percentage') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="fixed" {{ old('type', $bulkDiscount->type ?? '') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                        <option value="free_delivery" {{ old('type', $bulkDiscount->type ?? '') == 'free_delivery' ? 'selected' : '' }}>Free Delivery</option>
                                    </select>
                                    <div class="form-text">Choose between percentage, fixed amount discount, or free delivery</div>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3" id="discountValueField">
                                    <label for="value" class="form-label">Discount Value <span class="text-danger" id="valueRequired">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('value') is-invalid @enderror" 
                                               id="value" name="value" value="{{ old('value', $bulkDiscount->value ?? $bulkDiscount->percentage ?? '') }}" 
                                               min="0" step="0.01" placeholder="0.00">
                                        <span class="input-group-text" id="valueSuffix">%</span>
                                    </div>
                                    <div class="form-text" id="valueHelp">Enter percentage (0-100) or fixed amount in ৳</div>
                                    @error('value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Scope Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Applicability Scope</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="scope_type" class="form-label">Scope Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('scope_type') is-invalid @enderror" id="scope_type" name="scope_type" required>
                                        <option value="all" {{ old('scope_type', $bulkDiscount->scope_type) == 'all' ? 'selected' : '' }}>All Products</option>
                                        <option value="products" {{ old('scope_type', $bulkDiscount->scope_type) == 'products' ? 'selected' : '' }}>Specific Products</option>
                                    </select>
                                    @error('scope_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Products Selection -->
                                <div class="mb-3" id="productsField" style="display: none;">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-box me-1 text-primary"></i>Select Products
                                    </label>
                                    <div class="d-flex gap-2 mb-2">
                                        <input type="text" class="form-control" id="searchProducts" placeholder="Search products...">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllProducts">Select All</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllProducts">Deselect All</button>
                                    </div>
                                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background-color: #fff;" id="productsList">
                                        @php
                                            $selectedProducts = old('applicable_products', $bulkDiscount->applicable_products ?? []);
                                        @endphp
                                        @foreach($products as $index => $product)
                                            <div class="form-check mb-2 product-item {{ $index >= 30 ? 'd-none' : '' }}" data-name="{{ strtolower($product->name . ' ' . ($product->sku ?? '')) }}" data-index="{{ $index }}">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="applicable_products[]" 
                                                       value="{{ $product->id }}" 
                                                       id="prod_{{ $product->id }}"
                                                       {{ in_array($product->id, $selectedProducts) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="prod_{{ $product->id }}">
                                                    {{ $product->name }} @if($product->sku)({{ $product->sku }})@endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if(count($products) > 30)
                                        <button type="button" class="btn btn-sm btn-link p-0 mt-2" id="showMoreProducts">
                                            Show More ({{ count($products) - 30 }} remaining)
                                        </button>
                                    @endif
                                    <div class="form-text mt-2">
                                        <i class="fas fa-info-circle me-1"></i>Select one or more products to apply this discount.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Date Settings (Optional)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" 
                                                   id="start_date" name="start_date" value="{{ old('start_date', $bulkDiscount->start_date ? \Carbon\Carbon::parse($bulkDiscount->start_date)->format('Y-m-d\TH:i') : '') }}">
                                            <div class="form-text">Leave empty to start immediately</div>
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" 
                                                   id="end_date" name="end_date" value="{{ old('end_date', $bulkDiscount->end_date ? \Carbon\Carbon::parse($bulkDiscount->end_date)->format('Y-m-d\TH:i') : '') }}">
                                            <div class="form-text">Leave empty for no expiry</div>
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Status -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                           {{ old('is_active', $bulkDiscount->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                                <div class="form-text">Toggle to activate or deactivate this discount</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-save me-2"></i>Update Discount
                                    </button>
                                    <a href="{{ route('bulk-discounts.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle discount type fields
            const typeField = document.getElementById('type');
            const valueField = document.getElementById('value');
            const valueSuffix = document.getElementById('valueSuffix');
            const valueHelp = document.getElementById('valueHelp');
            const discountValueField = document.getElementById('discountValueField');
            const valueRequired = document.getElementById('valueRequired');
            
            function toggleDiscountFields() {
                if (!typeField) return;
                
                if (typeField.value === 'free_delivery') {
                    // Hide discount value field for free delivery
                    discountValueField.style.display = 'none';
                    valueField.removeAttribute('required');
                    valueField.value = '';
                } else {
                    // Show discount value field
                    discountValueField.style.display = 'block';
                    valueField.setAttribute('required', 'required');
                    
                    if (typeField.value === 'percentage') {
                        valueSuffix.textContent = '%';
                        valueField.setAttribute('max', '100');
                        valueHelp.textContent = 'Enter percentage (0-100)';
                    } else {
                        valueSuffix.textContent = '৳';
                        valueField.removeAttribute('max');
                        valueHelp.textContent = 'Enter fixed amount in ৳';
                    }
                }
            }
            
            if (typeField) {
                typeField.addEventListener('change', toggleDiscountFields);

                // Trigger on page load
                toggleDiscountFields();
            }

            // Toggle scope fields based on scope type
            const scopeTypeField = document.getElementById('scope_type');
            if (scopeTypeField) {
                scopeTypeField.addEventListener('change', function() {
                    const productsField = document.getElementById('productsField');
                    if (productsField) {
                        productsField.style.display = this.value === 'products' ? 'block' : 'none';
                    }
                });

                // Trigger on page load
                scopeTypeField.dispatchEvent(new Event('change'));
            }

            // Product search functionality
            const searchProducts = document.getElementById('searchProducts');
            if (searchProducts) {
                searchProducts.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    document.querySelectorAll('.product-item').forEach(item => {
                        const productName = item.dataset.name;
                        if (productName.includes(searchTerm)) {
                            item.classList.remove('d-none');
                        } else {
                            item.classList.add('d-none');
                        }
                    });
                });
            }

            // Select/Deselect All Products
            const selectAllProducts = document.getElementById('selectAllProducts');
            const deselectAllProducts = document.getElementById('deselectAllProducts');
            
            if (selectAllProducts) {
                selectAllProducts.addEventListener('click', function() {
                    document.querySelectorAll('#productsList input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = true;
                    });
                });
            }

            if (deselectAllProducts) {
                deselectAllProducts.addEventListener('click', function() {
                    document.querySelectorAll('#productsList input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                });
            }

            // Show More Products
            const showMoreProducts = document.getElementById('showMoreProducts');
            if (showMoreProducts) {
                showMoreProducts.addEventListener('click', function() {
                    document.querySelectorAll('.product-item.d-none').forEach(item => {
                        if (item.dataset.index && parseInt(item.dataset.index) < 100) {
                            item.classList.remove('d-none');
                        }
                    });
                    this.style.display = 'none';
                });
            }

            // Form validation
            const bulkDiscountForm = document.getElementById('bulkDiscountForm');
            if (bulkDiscountForm) {
                bulkDiscountForm.addEventListener('submit', function(e) {
                    const startDate = document.getElementById('start_date')?.value;
                    const endDate = document.getElementById('end_date')?.value;
                    
                    if (startDate && endDate && new Date(startDate) >= new Date(endDate)) {
                        e.preventDefault();
                        alert('End date must be after start date.');
                        return false;
                    }

                    const type = document.getElementById('type')?.value;
                    const value = parseFloat(document.getElementById('value')?.value || 0);
                    
                    // Skip value validation for free delivery
                    if (type !== 'free_delivery') {
                        if (type === 'percentage' && value > 100) {
                            e.preventDefault();
                            alert('Percentage discount cannot exceed 100%.');
                            return false;
                        }
                        
                        if (value <= 0) {
                            e.preventDefault();
                            alert('Discount value must be greater than 0.');
                            return false;
                        }
                    }

                    const scopeType = document.getElementById('scope_type')?.value;
                    if (scopeType === 'products') {
                        const selectedProducts = document.querySelectorAll('#productsList input[type="checkbox"]:checked');
                        if (selectedProducts.length === 0) {
                            e.preventDefault();
                            alert('Please select at least one product.');
                            return false;
                        }
                    }
                });
            }
        });
    </script>
@endsection

