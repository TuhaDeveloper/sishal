@extends('erp.master')

@section('title', 'Banner Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Banner Management</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Banner Management</h2>
                    <p class="text-muted mb-0">Manage banners, promotional content, and advertisements.</p>
                </div>
                <div class="col-md-4 text-end">
                    @can('create banners')
                    <a href="{{ route('banners.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Banner
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" placeholder="Search by title..." value="{{ request('search') }}">
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
                                <a href="{{ route('banners.index') }}" class="btn btn-outline-secondary">
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

            <!-- Banners Table -->
            <div class="card">
                <div class="card-body">
                    @if($banners->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Sort Order</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($banners as $banner)
                                        <tr>
                                            <td>
                                                @if($banner->image)
                                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-thumbnail" style="width: 60px; height: 40px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 40px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $banner->title }}</strong>
                                                    @if($banner->description)
                                                        <br><small class="text-muted">{{ Str::limit($banner->description, 50) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <button class="btn btn-sm status-toggle {{ $banner->status == 'active' ? 'btn-success' : 'btn-secondary' }}" 
                                                        data-banner-id="{{ $banner->id }}" 
                                                        data-current-status="{{ $banner->status }}">
                                                    {{ ucfirst($banner->status) }}
                                                </button>
                                            </td>
                                            <td>
                                                @if($banner->start_date)
                                                    {{ $banner->start_date->format('M d, Y') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($banner->end_date)
                                                    {{ $banner->end_date->format('M d, Y') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $banner->sort_order }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('banners.show', $banner) }}" class="btn btn-sm btn-outline-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @can('edit banners')
                                                    <a href="{{ route('banners.edit', $banner) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endcan
                                                    @can('delete banners')
                                                    <form action="{{ route('banners.destroy', $banner) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this banner?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    @endcan
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
                                Showing {{ $banners->firstItem() }} to {{ $banners->lastItem() }} of {{ $banners->total() }} results
                            </div>
                            <div>
                                {{ $banners->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No banners found</h5>
                            <p class="text-muted">Get started by creating your first banner.</p>
                            @can('create banners')
                            <a href="{{ route('banners.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Banner
                            </a>
                            @endcan
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
                const bannerId = this.dataset.bannerId;
                const currentStatus = this.dataset.currentStatus;
                
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
                        // Update button appearance
                        this.className = `btn btn-sm ${data.status === 'active' ? 'btn-success' : 'btn-secondary'} status-toggle`;
                        this.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                        this.dataset.currentStatus = data.status;
                        
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
                    alert('An error occurred while updating the banner status.');
                });
            });
        });
    </script>
@endsection
