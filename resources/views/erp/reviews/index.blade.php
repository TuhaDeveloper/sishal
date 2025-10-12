@extends('erp.master')

@section('title', 'Review Management')

@section('body')
@include('erp.components.sidebar')
<div class="main-content bg-light min-vh-100" id="mainContent">
    @include('erp.components.header')
    
    <!-- Breadcrumb -->
    <div class="container-fluid px-4 py-3 bg-white border-bottom">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Review Management</li>
            </ol>
        </nav>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Review Management</h3>
                        <div class="card-tools">
                            <a href="{{ route('reviews.index', ['status' => 'pending']) }}" class="btn btn-warning btn-sm me-2">
                                <i class="fas fa-clock"></i> Pending Reviews
                            </a>
                            <a href="{{ route('reviews.index', ['status' => 'approved']) }}" class="btn btn-success btn-sm me-2">
                                <i class="fas fa-check"></i> Approved Reviews
                            </a>
                            <a href="{{ route('reviews.index', ['status' => 'featured']) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-star"></i> Featured Reviews
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <form method="GET" action="{{ route('reviews.index') }}" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2" placeholder="Search reviews..." value="{{ request('search') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="product-filter" onchange="filterByProduct()">
                                    <option value="">All Products</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ Str::limit($product->name, 30) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="rating-filter" onchange="filterByRating()">
                                    <option value="">All Ratings</option>
                                    <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars</option>
                                    <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars</option>
                                    <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars</option>
                                    <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars</option>
                                    <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="status-filter" onchange="filterByStatus()">
                                    <option value="">All Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="featured" {{ request('status') == 'featured' ? 'selected' : '' }}>Featured</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100" id="bulk-action-btn" disabled onclick="toggleBulkActions()">
                                    <i class="fas fa-tasks"></i> Bulk Actions
                                </button>
                            </div>
                        </div>

                        <!-- Bulk Action Form -->
                        <div id="bulk-action-form" class="row mb-3" style="display: none;">
                            <div class="col-md-4">
                                <select name="action" class="form-control" id="bulk-action-select">
                                    <option value="">Select Action</option>
                                    <option value="approve">Approve Selected</option>
                                    <option value="reject">Reject Selected</option>
                                    <option value="feature">Feature Selected</option>
                                    <option value="unfeature">Unfeature Selected</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-warning" onclick="executeBulkAction()">Execute</button>
                                <button type="button" class="btn btn-secondary" onclick="cancelBulkActions()">Cancel</button>
                            </div>
                        </div>

                        <!-- Reviews Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                        </th>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Customer</th>
                                        <th>Rating</th>
                                        <th>Title</th>
                                        <th>Comment</th>
                                        <th>Status</th>
                                        <th>Featured</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reviews as $review)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="review-checkbox" value="{{ $review->id }}" onchange="updateBulkActionButton()">
                                            </td>
                                            <td>{{ $review->id }}</td>
                                            <td>
                                                @if($review->product)
                                                    <a href="{{ route('product.show', $review->product->id) }}" class="text-decoration-none">
                                                        {{ Str::limit($review->product->name, 30) }}
                                                    </a>
                                                    <br><small class="text-muted">ID: {{ $review->product->id }}</small>
                                                @else
                                                    <span class="text-muted">Deleted Product</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($review->user)
                                                    <div>
                                                        <strong>{{ $review->user->first_name }} {{ $review->user->last_name }}</strong>
                                                        <br><small class="text-muted">{{ $review->user->email }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Deleted User</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="stars me-2">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            @if($i <= $review->rating)
                                                                <i class="fas fa-star text-warning"></i>
                                                            @else
                                                                <i class="far fa-star text-muted"></i>
                                                            @endif
                                                        @endfor
                                                    </div>
                                                    <span class="badge bg-primary">{{ $review->rating }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">-</span>
                                            </td>
                                            <td>
                                                <div class="comment-preview" style="max-width: 200px;" title="{{ $review->comment }}">
                                                    {{ Str::limit($review->comment, 50) }}
                                                </div>
                                            </td>
                                            <td>
                                                @if($review->is_approved)
                                                    <span class="badge bg-success">Approved</span>
                                                @else
                                                    <span class="badge bg-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($review->is_featured)
                                                    <span class="badge bg-info">Featured</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted">-</span>
                                            </td>
                                            <td>{{ $review->formatted_date }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('reviews.show', $review->id) }}" class="btn btn-info btn-sm" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(!$review->is_approved)
                                                        <button class="btn btn-success btn-sm" title="Approve" onclick="approveReview({{ $review->id }})">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @else
                                                        <button class="btn btn-warning btn-sm" title="Reject" onclick="rejectReview({{ $review->id }})">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                    <button class="btn btn-{{ $review->is_featured ? 'warning' : 'primary' }} btn-sm" 
                                                            title="{{ $review->is_featured ? 'Unfeature' : 'Feature' }}" 
                                                            onclick="toggleFeatured({{ $review->id }})">
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" title="Delete" onclick="deleteReview({{ $review->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-comments fa-3x mb-3"></i>
                                                    <p>No reviews found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $reviews->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Filter functions
function filterByProduct() {
    const productId = document.getElementById('product-filter').value;
    updateUrl('product_id', productId);
}

function filterByRating() {
    const rating = document.getElementById('rating-filter').value;
    updateUrl('rating', rating);
}

function filterByStatus() {
    const status = document.getElementById('status-filter').value;
    updateUrl('status', status);
}

function updateUrl(param, value) {
    const url = new URL(window.location);
    if (value) {
        url.searchParams.set(param, value);
    } else {
        url.searchParams.delete(param);
    }
    window.location.href = url.toString();
}

// Bulk actions
function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.review-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    updateBulkActionButton();
}

function updateBulkActionButton() {
    const checkedBoxes = document.querySelectorAll('.review-checkbox:checked');
    const bulkBtn = document.getElementById('bulk-action-btn');
    bulkBtn.disabled = checkedBoxes.length === 0;
}

function toggleBulkActions() {
    const form = document.getElementById('bulk-action-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function cancelBulkActions() {
    document.getElementById('bulk-action-form').style.display = 'none';
    document.querySelectorAll('.review-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('select-all').checked = false;
    updateBulkActionButton();
}

function executeBulkAction() {
    const action = document.getElementById('bulk-action-select').value;
    const checkedBoxes = document.querySelectorAll('.review-checkbox:checked');
    const reviewIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    if (reviewIds.length === 0) {
        alert('Please select at least one review');
        return;
    }
    
    if (confirm(`Are you sure you want to ${action} ${reviewIds.length} review(s)?`)) {
        fetch('{{ route("reviews.bulk-action") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                action: action,
                review_ids: reviewIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}

// Individual actions
function approveReview(id) {
    if (confirm('Approve this review?')) {
        performAction('approve', id);
    }
}

function rejectReview(id) {
    if (confirm('Reject this review?')) {
        performAction('reject', id);
    }
}

function toggleFeatured(id) {
    performAction('toggle-featured', id);
}

function deleteReview(id) {
    if (confirm('Are you sure you want to delete this review?')) {
        performAction('delete', id);
    }
}

function performAction(action, id) {
    const url = action === 'delete' ? 
        `{{ url('erp/reviews') }}/${id}` : 
        `{{ url('erp/reviews') }}/${id}/${action}`;
    
    const method = action === 'delete' ? 'DELETE' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>
@endsection
