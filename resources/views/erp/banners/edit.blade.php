@extends('erp.master')

@section('title', 'Edit Banner')

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
                            <li class="breadcrumb-item active" aria-current="page">Edit Banner</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Edit Banner</h2>
                    <p class="text-muted mb-0">Update banner information and settings.</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('banners.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Banners
                    </a>
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
                            <form action="{{ route('banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                                   id="title" name="title" value="{{ old('title', $banner->title) }}" required>
                                            @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3" 
                                              placeholder="Enter banner description...">{{ old('description', $banner->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="image" class="form-label">Banner Image</label>
                                    @if($banner->image)
                                        <div class="mb-2">
                                            <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-thumbnail" style="max-height: 150px;">
                                            <p class="text-muted small mt-1">Current image</p>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                           id="image" name="image" accept="image/*">
                                    <div class="form-text">Supported formats: JPEG, PNG, JPG, GIF, WebP. Max size: 2MB. Leave empty to keep current image.</div>
                                    @error('image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="link_url" class="form-label">Link URL</label>
                                            <input type="url" class="form-control @error('link_url') is-invalid @enderror" 
                                                   id="link_url" name="link_url" value="{{ old('link_url', $banner->link_url) }}" 
                                                   placeholder="https://example.com">
                                            @error('link_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="link_text" class="form-label">Link Text</label>
                                            <input type="text" class="form-control @error('link_text') is-invalid @enderror" 
                                                   id="link_text" name="link_text" value="{{ old('link_text', $banner->link_text) }}" 
                                                   placeholder="Click here">
                                            @error('link_text')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select @error('status') is-invalid @enderror" 
                                                    id="status" name="status" required>
                                                <option value="active" {{ old('status', $banner->status) == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', $banner->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="sort_order" class="form-label">Sort Order</label>
                                            <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                                   id="sort_order" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}" 
                                                   min="0" placeholder="0">
                                            @error('sort_order')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            @php
                                                $inputTz = env('APP_INPUT_TZ', config('app.timezone', 'UTC'));
                                                $startLocal = $banner->start_date ? $banner->start_date->copy()->setTimezone($inputTz) : null;
                                            @endphp
                                            <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" 
                                                   id="start_date" name="start_date" 
                                                   value="{{ old('start_date', $startLocal ? $startLocal->format('Y-m-d\TH:i') : '') }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            @php
                                                $endLocal = $banner->end_date ? $banner->end_date->copy()->setTimezone($inputTz) : null;
                                            @endphp
                                            <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" 
                                                   id="end_date" name="end_date" 
                                                   value="{{ old('end_date', $endLocal ? $endLocal->format('Y-m-d\TH:i') : '') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('banners.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Banner
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Preview</h5>
                        </div>
                        <div class="card-body">
                            <div id="banner-preview" class="border rounded p-3 text-center" style="min-height: 200px;">
                                @if($banner->image)
                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-fluid rounded" style="max-height: 200px;">
                                @else
                                    <div class="text-muted">
                                        <i class="fas fa-image fa-2x mb-2"></i>
                                        <p>No image uploaded</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('banner-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Banner Preview" class="img-fluid rounded" style="max-height: 200px;">
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                // Revert to original image if no new file selected
                @if($banner->image)
                    preview.innerHTML = `
                        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-fluid rounded" style="max-height: 200px;">
                    `;
                @else
                    preview.innerHTML = `
                        <div class="text-muted">
                            <i class="fas fa-image fa-2x mb-2"></i>
                            <p>No image uploaded</p>
                        </div>
                    `;
                @endif
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate && new Date(startDate) >= new Date(endDate)) {
                e.preventDefault();
                alert('End date must be after start date.');
                return false;
            }
        });
    </script>
@endsection
