@extends('erp.master')

@section('title', 'Banner Details')

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
                            <li class="breadcrumb-item"><a href="{{ route('banners.index') }}" class="text-decoration-none">Banner Management</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Banner Details</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">{{ $banner->title }}</h2>
                    <p class="text-muted mb-0">View banner details and information.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group">
                        @can('edit banners')
                        <a href="{{ route('banners.edit', $banner) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Banner
                        </a>
                        @endcan
                        <a href="{{ route('banners.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Banners
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Banner Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Title</label>
                                        <p class="form-control-plaintext">{{ $banner->title }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Position</label>
                                        <p class="form-control-plaintext">
                                            <span class="badge bg-info">{{ ucfirst($banner->position) }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if($banner->description)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description</label>
                                    <p class="form-control-plaintext">{{ $banner->description }}</p>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status</label>
                                        <p class="form-control-plaintext">
                                            <span class="badge {{ $banner->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ ucfirst($banner->status) }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Sort Order</label>
                                        <p class="form-control-plaintext">{{ $banner->sort_order }}</p>
                                    </div>
                                </div>
                            </div>

                            @if($banner->link_url)
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Link URL</label>
                                            <p class="form-control-plaintext">
                                                <a href="{{ $banner->link_url }}" target="_blank" class="text-decoration-none">
                                                    {{ $banner->link_url }} <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Link Text</label>
                                            <p class="form-control-plaintext">{{ $banner->link_text }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Start Date</label>
                                        <p class="form-control-plaintext">
                                            @if($banner->start_date)
                                                {{ $banner->start_date->format('M d, Y H:i') }}
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">End Date</label>
                                        <p class="form-control-plaintext">
                                            @if($banner->end_date)
                                                {{ $banner->end_date->format('M d, Y H:i') }}
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Created At</label>
                                        <p class="form-control-plaintext">{{ $banner->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Updated At</label>
                                        <p class="form-control-plaintext">{{ $banner->updated_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Banner Preview</h5>
                        </div>
                        <div class="card-body">
                            @if($banner->image)
                                <div class="text-center">
                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-fluid rounded mb-3" style="max-height: 300px;">
                                    @if($banner->link_url)
                                        <a href="{{ $banner->link_url }}" target="_blank" class="btn btn-primary">
                                            {{ $banner->link_text ?: 'Visit Link' }} <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-image fa-3x mb-3"></i>
                                    <p>No image uploaded</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Banner Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle {{ $banner->isCurrentlyActive() ? 'bg-success' : 'bg-secondary' }}" 
                                         style="width: 12px; height: 12px;"></div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Currently Active</h6>
                                    <small class="text-muted">
                                        {{ $banner->isCurrentlyActive() ? 'This banner is currently being displayed' : 'This banner is not currently active' }}
                                    </small>
                                </div>
                            </div>

                            @if($banner->start_date && $banner->start_date > now())
                                <div class="alert alert-info">
                                    <i class="fas fa-clock me-2"></i>
                                    This banner will become active on {{ $banner->start_date->format('M d, Y H:i') }}
                                </div>
                            @endif

                            @if($banner->end_date && $banner->end_date < now())
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    This banner expired on {{ $banner->end_date->format('M d, Y H:i') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @can('edit banners')
                                <a href="{{ route('banners.edit', $banner) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Banner
                                </a>
                                <button class="btn btn-outline-{{ $banner->status == 'active' ? 'warning' : 'success' }}" 
                                        onclick="toggleStatus({{ $banner->id }})">
                                    <i class="fas fa-toggle-{{ $banner->status == 'active' ? 'off' : 'on' }} me-2"></i>
                                    {{ $banner->status == 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                                @endcan
                                @can('delete banners')
                                <form action="{{ route('banners.destroy', $banner) }}" method="POST" class="d-inline" 
                                      onsubmit="return confirm('Are you sure you want to delete this banner? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Banner
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleStatus(bannerId) {
            fetch(`/erp/banners/${bannerId}/toggle-status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show updated status
                    location.reload();
                } else {
                    alert('An error occurred while updating the banner status.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the banner status.');
            });
        }
    </script>
@endsection
