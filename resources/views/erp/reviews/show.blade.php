@extends('erp.master')

@section('title', 'Review Details')

@section('body')
@include('erp.components.sidebar')
<div class="main-content bg-light min-vh-100" id="mainContent">
    @include('erp.components.header')
    
    <!-- Breadcrumb -->
    <div class="container-fluid px-4 py-3 bg-white border-bottom">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reviews.index') }}" class="text-decoration-none">Reviews</a></li>
                <li class="breadcrumb-item active" aria-current="page">Review Details</li>
            </ol>
        </nav>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Review Details</h3>
                        <div class="card-tools">
                            <a href="{{ route('reviews.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Reviews
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Review Information</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Review ID:</strong></div>
                                            <div class="col-sm-9">{{ $review->id }}</div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Product:</strong></div>
                                            <div class="col-sm-9">
                                                @if($review->product)
                                                    <a href="{{ route('product.show', $review->product->id) }}" class="text-decoration-none">
                                                        {{ $review->product->name }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">Deleted Product</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Customer:</strong></div>
                                            <div class="col-sm-9">
                                                @if($review->user)
                                                    <div>
                                                        <strong>{{ $review->user->first_name }} {{ $review->user->last_name }}</strong>
                                                        <br><small class="text-muted">{{ $review->user->email }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Deleted User</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Rating:</strong></div>
                                            <div class="col-sm-9">
                                                <div class="d-flex align-items-center">
                                                    <div class="stars me-3">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            @if($i <= $review->rating)
                                                                <i class="fas fa-star text-warning" style="font-size: 20px;"></i>
                                                            @else
                                                                <i class="far fa-star text-muted" style="font-size: 20px;"></i>
                                                            @endif
                                                        @endfor
                                                    </div>
                                                    <span class="badge bg-primary fs-6">{{ $review->rating }}/5</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Comment:</strong></div>
                                            <div class="col-sm-9">
                                                <div class="border p-3 rounded bg-light">
                                                    {{ $review->comment }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Status:</strong></div>
                                            <div class="col-sm-9">
                                                @if($review->is_approved)
                                                    <span class="badge bg-success fs-6">Approved</span>
                                                @else
                                                    <span class="badge bg-warning fs-6">Pending Approval</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Featured:</strong></div>
                                            <div class="col-sm-9">
                                                @if($review->is_featured)
                                                    <span class="badge bg-info fs-6">Featured</span>
                                                @else
                                                    <span class="text-muted">Not Featured</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Created:</strong></div>
                                            <div class="col-sm-9">{{ $review->created_at->format('M d, Y H:i A') }}</div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Updated:</strong></div>
                                            <div class="col-sm-9">{{ $review->updated_at->format('M d, Y H:i A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Actions</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            @if(!$review->is_approved)
                                                <button class="btn btn-success" onclick="approveReview({{ $review->id }})">
                                                    <i class="fas fa-check"></i> Approve Review
                                                </button>
                                            @else
                                                <button class="btn btn-warning" onclick="rejectReview({{ $review->id }})">
                                                    <i class="fas fa-times"></i> Reject Review
                                                </button>
                                            @endif
                                            
                                            <button class="btn btn-{{ $review->is_featured ? 'warning' : 'primary' }}" onclick="toggleFeatured({{ $review->id }})">
                                                <i class="fas fa-star"></i> {{ $review->is_featured ? 'Unfeature' : 'Feature' }} Review
                                            </button>
                                            
                                            @if($review->product)
                                                <a href="{{ route('product.show', $review->product->id) }}" class="btn btn-info">
                                                    <i class="fas fa-eye"></i> View Product
                                                </a>
                                            @else
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-eye-slash"></i> Product Deleted
                                                </button>
                                            @endif
                                            
                                            <button class="btn btn-danger" onclick="deleteReview({{ $review->id }})">
                                                <i class="fas fa-trash"></i> Delete Review
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                @if($review->product)
                                    <div class="card mt-3">
                                        <div class="card-header">
                                            <h4>Product Information</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center">
                                                <img src="{{ asset($review->product->image) }}" alt="{{ $review->product->name }}" class="img-fluid mb-3" style="max-height: 150px;">
                                                <h5>{{ $review->product->name }}</h5>
                                                <p class="text-muted">{{ $review->product->short_desc }}</p>
                                                <div class="price">
                                                    @if($review->product->discount && $review->product->discount > 0)
                                                        <span class="fw-bold text-primary">{{ number_format($review->product->discount, 2) }}৳</span>
                                                        <span class="text-decoration-line-through text-muted ms-2">{{ number_format($review->product->price, 2) }}৳</span>
                                                    @else
                                                        <span class="fw-bold text-primary">{{ number_format($review->product->price, 2) }}৳</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="card mt-3">
                                        <div class="card-header">
                                            <h4>Product Information</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center text-muted">
                                                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                                <h5>Product Deleted</h5>
                                                <p>This product has been removed from the system.</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
