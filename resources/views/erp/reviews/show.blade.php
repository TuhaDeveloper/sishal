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
                    <h3 class="card-title">Review Details</h3>
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
                                            <a href="{{ route('product.show', $review->product->id) }}" class="text-decoration-none">
                                                {{ $review->product->name }}
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Customer:</strong></div>
                                        <div class="col-sm-9">
                                            @if($review->user)
                                                {{ $review->user->first_name }} {{ $review->user->last_name }}
                                                <br><small class="text-muted">{{ $review->user->email }}</small>
                                            @else
                                                <span class="text-muted">Deleted User</span>
                                                <br><small class="text-muted">User ID: {{ $review->user_id }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Rating:</strong></div>
                                        <div class="col-sm-9">
                                            <div class="stars">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $review->rating)
                                                        <span class="text-warning" style="font-size: 20px;">★</span>
                                                    @else
                                                        <span class="text-muted" style="font-size: 20px;">☆</span>
                                                    @endif
                                                @endfor
                                                <span class="ml-2">{{ $review->rating }}/5</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Comment:</strong></div>
                                        <div class="col-sm-9">
                                            @if($review->comment)
                                                <div class="border p-3 rounded bg-light">
                                                    {{ $review->comment }}
                                                </div>
                                            @else
                                                <span class="text-muted">No comment provided</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Status:</strong></div>
                                        <div class="col-sm-9">
                                            @if($review->is_approved)
                                                <span class="badge badge-success badge-lg">Approved</span>
                                            @else
                                                <span class="badge badge-warning badge-lg">Pending Approval</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Featured:</strong></div>
                                        <div class="col-sm-9">
                                            @if($review->is_featured)
                                                <span class="badge badge-info badge-lg">Featured</span>
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
                                            <form action="{{ route('reviews.approve', $review->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this review?')">
                                                    <i class="fas fa-check"></i> Approve Review
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('reviews.reject', $review->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-warning" onclick="return confirm('Reject this review?')">
                                                    <i class="fas fa-times"></i> Reject Review
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <form action="{{ route('reviews.feature', $review->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-{{ $review->is_featured ? 'warning' : 'primary' }}" onclick="return confirm('{{ $review->is_featured ? 'Unfeature' : 'Feature' }} this review?')">
                                                <i class="fas fa-star"></i> {{ $review->is_featured ? 'Unfeature' : 'Feature' }} Review
                                            </button>
                                        </form>
                                        
                                        <a href="{{ route('product.show', $review->product->id) }}" class="btn btn-info">
                                            <i class="fas fa-eye"></i> View Product
                                        </a>
                                        
                                        <form action="{{ route('reviews.destroy', $review->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this review?')">
                                                <i class="fas fa-trash"></i> Delete Review
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
@endsection
