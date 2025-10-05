@extends('erp.master')

@section('title', 'Product Reviews')

@section('body')
@include('erp.components.sidebar')
<div class="main-content bg-light min-vh-100" id="mainContent">
    @include('erp.components.header')
    
    <!-- Breadcrumb -->
    <div class="container-fluid px-4 py-3 bg-white border-bottom">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('product.list') }}" class="text-decoration-none">Products</a></li>
                <li class="breadcrumb-item"><a href="{{ route('product.show', $product->id) }}" class="text-decoration-none">{{ $product->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reviews</li>
            </ol>
        </nav>
    </div>

    <div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Reviews for {{ $product->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('product.show', $product->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Product
                        </a>
                        <a href="{{ route('reviews.index') }}" class="btn btn-primary">
                            <i class="fas fa-list"></i> All Reviews
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Product Summary -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="img-fluid">
                        </div>
                        <div class="col-md-10">
                            <h4>{{ $product->name }}</h4>
                            <p class="text-muted">{{ $product->short_desc }}</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Total Reviews:</strong> {{ $product->totalReviews() }}
                                </div>
                                <div class="col-md-3">
                                    <strong>Average Rating:</strong> {{ number_format($product->averageRating(), 1) }}/5
                                </div>
                                <div class="col-md-3">
                                    <strong>Approved Reviews:</strong> {{ $product->approvedReviews()->count() }}
                                </div>
                                <div class="col-md-3">
                                    <strong>Pending Reviews:</strong> {{ $product->reviews()->where('is_approved', false)->count() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <form method="GET" action="{{ route('product.reviews', $product->id) }}">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search reviews..." value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="status-filter">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary" id="bulk-action-btn" disabled>
                                <i class="fas fa-tasks"></i> Bulk Action
                            </button>
                        </div>
                    </div>

                    <!-- Bulk Action Form -->
                    <form id="bulk-action-form" style="display: none;">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <select name="action" class="form-control" required>
                                    <option value="">Select Action</option>
                                    <option value="approve">Approve Selected</option>
                                    <option value="reject">Reject Selected</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-warning">Execute</button>
                                <button type="button" class="btn btn-secondary" id="cancel-bulk">Cancel</button>
                            </div>
                        </div>
                    </form>

                    <!-- Reviews Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Rating</th>
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
                                            <input type="checkbox" class="review-checkbox" value="{{ $review->id }}">
                                        </td>
                                        <td>{{ $review->id }}</td>
                                        <td>
                                            @if($review->user)
                                                {{ $review->user->first_name }} {{ $review->user->last_name }}
                                                <br><small class="text-muted">{{ $review->user->email }}</small>
                                            @else
                                                <span class="text-muted">Deleted User</span>
                                                <br><small class="text-muted">User ID: {{ $review->user_id }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="stars">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $review->rating)
                                                        <span class="text-warning">★</span>
                                                    @else
                                                        <span class="text-muted">☆</span>
                                                    @endif
                                                @endfor
                                                <span class="ml-1">({{ $review->rating }})</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($review->comment)
                                                <div class="comment-preview" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $review->comment }}">
                                                    {{ $review->comment }}
                                                </div>
                                            @else
                                                <span class="text-muted">No comment</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($review->is_approved)
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($review->is_featured)
                                                <span class="badge badge-info">Featured</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $review->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('reviews.show', $review->id) }}" class="btn btn-info btn-sm" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if(!$review->is_approved)
                                                    <form action="{{ route('reviews.approve', $review->id) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-success btn-sm" title="Approve" onclick="return confirm('Approve this review?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('reviews.reject', $review->id) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-warning btn-sm" title="Reject" onclick="return confirm('Reject this review?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('reviews.feature', $review->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-{{ $review->is_featured ? 'warning' : 'primary' }} btn-sm" title="{{ $review->is_featured ? 'Unfeature' : 'Feature' }}" onclick="return confirm('{{ $review->is_featured ? 'Unfeature' : 'Feature' }} this review?')">
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('reviews.destroy', $review->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this review?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No reviews found for this product</td>
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

<script>
    $(document).ready(function() {
        // Select all checkbox
    $('#select-all').on('change', function() {
        $('.review-checkbox').prop('checked', this.checked);
        updateBulkActionButton();
    });

    // Individual checkboxes
    $('.review-checkbox').on('change', function() {
        updateBulkActionButton();
    });

    // Update bulk action button state
    function updateBulkActionButton() {
        const checkedBoxes = $('.review-checkbox:checked');
        $('#bulk-action-btn').prop('disabled', checkedBoxes.length === 0);
        
        if (checkedBoxes.length > 0) {
            $('#bulk-action-form').show();
            // Update hidden inputs with selected IDs
            $('#bulk-action-form').find('input[name="review_ids[]"]').remove();
            checkedBoxes.each(function() {
                $('#bulk-action-form').append('<input type="hidden" name="review_ids[]" value="' + $(this).val() + '">');
            });
        } else {
            $('#bulk-action-form').hide();
        }
    }

    // Cancel bulk action
    $('#cancel-bulk').on('click', function() {
        $('.review-checkbox').prop('checked', false);
        $('#select-all').prop('checked', false);
        updateBulkActionButton();
    });

    // Bulk action form submission
    $('#bulk-action-form').on('submit', function(e) {
        e.preventDefault();
        const action = $('select[name="action"]').val();
        if (!action) {
            alert('Please select an action');
            return;
        }
        
        if (confirm('Are you sure you want to ' + action + ' selected reviews?')) {
            this.submit();
        }
    });

    // Status filter
    $('#status-filter').on('change', function() {
        const url = new URL(window.location);
        const status = $(this).val();
        
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        
        window.location.href = url.toString();
    });
});
</script>
    </div>
</div>
@endsection
