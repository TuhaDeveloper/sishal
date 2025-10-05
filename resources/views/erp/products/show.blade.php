@extends('erp.master')

@section('title', 'Product Details')

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
                <li class="breadcrumb-item active" aria-current="page">Product Details</li>
            </ol>
        </nav>
    </div>

    <div class="container-fluid px-4 py-4">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-1">{{ $product->name ?? 'N/A' }}</h2>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-success fs-6 px-3 py-2">{{ $product->status == 'active' ? 'Active' : 'Inactive' }}</span>
                            <span class="text-muted">SKU: {{ $product->sku ?? 'N/A' }}</span>
                            <span class="text-muted">•</span>
                            <span class="text-muted">{{ $product->category->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('product.edit', $product->id) }}"><i class="fas fa-edit me-2"></i>Edit Product</a></li>
                            <li><a class="dropdown-item" href="{{ route('product.reviews', $product->id) }}"><i class="fas fa-star me-2"></i>View Reviews ({{ $product->totalReviews() }})</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('product.delete', $product->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Delete</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Product Overview Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="position-relative">
                                    <img src="{{ asset($product->image) }}" 
                                         alt="Product Image" 
                                         class="img-fluid rounded-3 border"
                                         style="width: 100%; height: 250px; object-fit: cover;">
                                    <span class="position-absolute top-0 end-0 m-2">
                                        <button class="btn btn-light btn-sm rounded-pill">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h5 class="fw-bold mb-3">Product Information</h5>
                                
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="border-start border-primary border-4 ps-3">
                                            <h6 class="text-primary mb-1">{{ $product->price ?? 'N/A' }}৳</h6>
                                            <small class="text-muted">Selling Price</small>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border-start border-success border-4 ps-3">
                                            <h6 class="text-success mb-1">{{ (($product->price || $product->discount) - $product->cost) ?? 'N/A' }}৳</h6>
                                            <small class="text-muted">Profit Margin</small>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border-start border-warning border-4 ps-3">
                                            <h6 class="text-warning mb-1">{{ $product->discount ?? 'N/A' }}৳</h6>
                                            <small class="text-muted">Discount</small>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border-start border-info border-4 ps-3">
                                            <h6 class="text-info mb-1">{{ $product->cost ?? 'N/A' }}৳</h6>
                                            <small class="text-muted">Cost Price</small>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">
                                
                                <div>
                                    <h6 class="fw-bold mb-2">Short Description</h6>
                                    <p class="text-muted mb-0">{{ $product->short_desc ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-12 mb-4 card border-0 shadow-sm p-4">
                        <h6 class="fw-bold mb-2">Description</h6>
                        <p class="text-muted mb-0">{!! $product->description ?? 'N/A' !!}</p>
                    </div>
                </div>

                <!-- Stock Analytics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">Stock Trend</h6>
                                    <span class="badge bg-light text-dark">30 Days</span>
                                </div>
                                <div class="bg-gradient rounded-3 p-4 text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 200px;">
                                    <div class="text-white">
                                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                                        <p class="mb-0">Interactive Chart</p>
                                        <small>Stock levels over time</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">Sales Performance</h6>
                                    <span class="badge bg-light text-dark">This Month</span>
                                </div>
                                <div class="bg-gradient rounded-3 p-4 text-center" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); height: 200px;">
                                    <div class="text-white">
                                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                        <p class="mb-0">Sales Analytics</p>
                                        <small>Revenue & units sold</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Gallery -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Product Gallery</h5>

                            <button class="btn btn-outline-primary btn-sm" id="addGalleryBtn">
                                <i class="fas fa-plus me-2"></i>Add Images
                            </button>
                        </div>
                        <div class="row g-3">
                            @foreach($product->galleries as $gallery)
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="position-relative">
                                    <img src="{{ asset($gallery->image) }}" 
                                         alt="Gallery Image" 
                                         class="img-fluid rounded-3 border"
                                         style="width: 100%; height: 150px; object-fit: cover;">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <form action="{{ route('product.gallery.delete', $gallery->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            
                        </div>

                        <input type="file" id="galleryImageInput" class="d-none" accept="image/*">
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Quick Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="card border-0 bg-primary text-white">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-boxes fa-2x mb-2"></i>
                                <h4 class="fw-bold mb-1">245</h4>
                                <small>Total Stock</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-0 bg-success text-white">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-chart-line fa-2x mb-2"></i>
                                <h4 class="fw-bold mb-1">156</h4>
                                <small>Units Sold</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branch Stock Distribution -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Stock Distribution</h6>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium">Dhaka Main</span>
                                <span class="badge bg-primary">120 units</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: 49%"></div>
                            </div>
                            <small class="text-muted">Updated: 2024-07-01</small>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium">Chittagong</span>
                                <span class="badge bg-info">80 units</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: 33%"></div>
                            </div>
                            <small class="text-muted">Updated: 2024-07-01</small>
                        </div>

                        <div class="mb-0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium">Khulna</span>
                                <span class="badge bg-warning">45 units</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: 18%"></div>
                            </div>
                            <small class="text-muted">Updated: 2024-07-01</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Recent Activity</h6>
                        
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-success rounded-circle p-2 me-3">
                                <i class="fas fa-plus text-white small"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small">Stock added to Dhaka Main</p>
                                <small class="text-muted">+50 units • 2 hours ago</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-info rounded-circle p-2 me-3">
                                <i class="fas fa-edit text-white small"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small">Price updated</p>
                                <small class="text-muted">৳1,200 → ৳1,200 • 1 day ago</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-0">
                            <div class="bg-warning rounded-circle p-2 me-3">
                                <i class="fas fa-shopping-cart text-white small"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small">Order fulfilled</p>
                                <small class="text-muted">25 units sold • 2 days ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.progress-bar {
    transition: width 0.3s ease;
}

.badge {
    font-weight: 500;
}

.btn {
    transition: all 0.2s ease;
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const galleryInput = document.getElementById('galleryImageInput');
        const addGalleryBtn = document.getElementById('addGalleryBtn');
        const productId = {{ $product->id ?? 'null' }};

        addGalleryBtn.addEventListener('click', function() {
            galleryInput.click();
        });

        galleryInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('image', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                fetch('{{ route("product.gallery.add") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to upload image');
                    }
                })
                .catch(() => {
                    alert('An error occurred while uploading the image');
                });
            }
        });
    });
</script>
@endsection
