@extends('erp.master')

@section('title', 'Bulk Discount Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Bulk Discount Management</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Bulk Discount Management</h2>
                    <p class="text-muted mb-0">Apply percentage discounts, fixed amount discounts, or free delivery to all products or specific products.</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('bulk-discounts.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Bulk Discount
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" placeholder="Search by name..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="{{ route('bulk-discounts.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Discounts Table -->
            <div class="card">
                <div class="card-body">
                    @if($discounts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Discount Type</th>
                                        <th>Scope</th>
                                        <th>Status</th>
                                        <th>Valid Period</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($discounts as $discount)
                                        <tr>
                                            <td>
                                                <strong class="text-primary">{{ $discount->name }}</strong>
                                                @if($discount->description)
                                                    <br><small class="text-muted">{{ Str::limit($discount->description, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $discountType = $discount->type ?? 'percentage';
                                                    $discountValue = $discount->value ?? $discount->percentage ?? 0;
                                                @endphp
                                                @if($discountType === 'free_delivery')
                                                    <span class="badge bg-primary"><i class="fas fa-truck me-1"></i>Free Delivery</span>
                                                @elseif($discountType === 'percentage')
                                                    <span class="badge bg-success">{{ number_format($discountValue, 2) }}%</span>
                                                @else
                                                    <span class="badge bg-info">{{ number_format($discountValue, 2) }}৳</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($discount->scope_type === 'all')
                                                    <span class="badge bg-info">All Products</span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        {{ count($discount->applicable_products ?? []) }} Product(s)
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <button class="btn btn-sm status-toggle {{ $discount->is_active ? 'btn-success' : 'btn-secondary' }}" 
                                                        data-discount-id="{{ $discount->id }}" 
                                                        data-current-status="{{ $discount->is_active }}">
                                                    {{ $discount->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </td>
                                            <td>
                                                @if($discount->start_date || $discount->end_date)
                                                    <small>
                                                        @if($discount->start_date)
                                                            {{ \Carbon\Carbon::parse($discount->start_date)->format('M d, Y') }}
                                                        @else
                                                            Immediate
                                                        @endif
                                                        @if($discount->end_date)
                                                            <br>to {{ \Carbon\Carbon::parse($discount->end_date)->format('M d, Y') }}
                                                        @else
                                                            <br><span class="text-muted">No expiry</span>
                                                        @endif
                                                    </small>
                                                @else
                                                    <span class="text-muted">Always active</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('bulk-discounts.show', $discount) }}" class="btn btn-sm btn-outline-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('bulk-discounts.edit', $discount) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('bulk-discounts.destroy', $discount) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this bulk discount?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Showing {{ $discounts->firstItem() }} to {{ $discounts->lastItem() }} of {{ $discounts->total() }} results
                            </div>
                            <div>
                                {{ $discounts->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-percent fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No bulk discounts found</h5>
                            <p class="text-muted">Get started by creating your first bulk discount.</p>
                            <a href="{{ route('bulk-discounts.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Bulk Discount
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Status toggle functionality
        document.querySelectorAll('.status-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const discountId = this.dataset.discountId;
                
                fetch(`/erp/bulk-discounts/${discountId}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button appearance
                        this.className = `btn btn-sm ${data.is_active ? 'btn-success' : 'btn-secondary'} status-toggle`;
                        this.textContent = data.is_active ? 'Active' : 'Inactive';
                        this.dataset.currentStatus = data.is_active;
                        
                        // Show success message
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
                        alert.style.top = '20px';
                        alert.style.right = '20px';
                        alert.style.zIndex = '9999';
                        alert.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        document.body.appendChild(alert);
                        
                        setTimeout(() => {
                            alert.remove();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the discount status.');
                });
            });
        });
    </script>
@endsection

