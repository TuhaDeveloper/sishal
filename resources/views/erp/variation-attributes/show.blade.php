@extends('erp.master')

@section('title', 'View Variation Attribute')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-eye me-2"></i>
                                View Variation Attribute
                            </h4>
                            <div class="d-flex gap-2">
                                <a href="{{ route('erp.variation-attributes.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Attributes
                                </a>
                                <a href="{{ route('erp.variation-attributes.edit', $attribute->id) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Edit Attribute
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Attribute Information
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-5">
                                                    <i class="fas fa-tag me-1"></i> Name
                                                </dt>
                                                <dd class="col-sm-7">
                                                    <strong>{{ $attribute->name }}</strong>
                                                </dd>

                                                <dt class="col-sm-5">
                                                    <i class="fas fa-link me-1"></i> Slug
                                                </dt>
                                                <dd class="col-sm-7">
                                                    <code>{{ $attribute->slug }}</code>
                                                </dd>

                                                <dt class="col-sm-5">
                                                    <i class="fas fa-info-circle me-1"></i> Description
                                                </dt>
                                                <dd class="col-sm-7">
                                                    {{ $attribute->description ? $attribute->description : '<span class="text-muted">No description</span>' }}
                                                </dd>

                                                <dt class="col-sm-5">
                                                    <i class="fas fa-palette me-1"></i> Type
                                                </dt>
                                                <dd class="col-sm-7">
                                                    @if($attribute->is_color)
                                                        <span class="badge bg-primary">
                                                            <i class="fas fa-palette me-1"></i> Color Attribute
                                                        </span>
                                                    @else
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-tag me-1"></i> Text Attribute
                                                        </span>
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Required
                                                </dt>
                                                <dd class="col-sm-7">
                                                    @if($attribute->is_required)
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-check-circle me-1"></i> Required
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">Optional</span>
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">
                                                    <i class="fas fa-sort me-1"></i> Sort Order
                                                </dt>
                                                <dd class="col-sm-7">
                                                    <span class="badge bg-light text-dark">{{ $attribute->sort_order }}</span>
                                                </dd>

                                                <dt class="col-sm-5">
                                                    <i class="fas fa-toggle-on me-1"></i> Status
                                                </dt>
                                                <dd class="col-sm-7">
                                                    <span class="badge bg-{{ $attribute->status === 'active' ? 'success' : 'secondary' }}">
                                                        {{ ucfirst($attribute->status) }}
                                                    </span>
                                                </dd>

                                                <dt class="col-sm-5">
                                                    <i class="fas fa-calendar me-1"></i> Created At
                                                </dt>
                                                <dd class="col-sm-7">
                                                    <small class="text-muted">{{ $attribute->created_at->format('M d, Y H:i') }}</small>
                                                </dd>

                                                <dt class="col-sm-5">
                                                    <i class="fas fa-edit me-1"></i> Updated At
                                                </dt>
                                                <dd class="col-sm-7">
                                                    <small class="text-muted">{{ $attribute->updated_at->format('M d, Y H:i') }}</small>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-list me-2"></i>
                                                Attribute Values
                                            </h5>
                                            <span class="badge bg-primary">{{ $attribute->values->count() }} values</span>
                                        </div>
                                        <div class="card-body">
                                            @if($attribute->values->count() > 0)
                                                <div class="list-group">
                                                    @foreach($attribute->values->sortBy('sort_order') as $value)
                                                        <div class="list-group-item">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div class="d-flex align-items-center gap-3">
                                                                    @if($attribute->is_color && $value->image)
                                                                        <img src="{{ asset($value->image) }}" 
                                                                             alt="{{ $value->value }}" 
                                                                             style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:2px solid #e2e8f0"/>
                                                                    @elseif($attribute->is_color && $value->color_code)
                                                                        <span class="color-indicator" 
                                                                              style="background-color: {{ $value->color_code }}; 
                                                                                     width: 40px; height: 40px; 
                                                                                     display: inline-block; 
                                                                                     border-radius: 8px;
                                                                                     border: 2px solid #e2e8f0;"></span>
                                                                    @else
                                                                        <span class="badge bg-secondary" style="width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;">
                                                                            <i class="fas fa-tag"></i>
                                                                        </span>
                                                                    @endif
                                                                    <div>
                                                                        <h6 class="mb-1">{{ $value->value }}</h6>
                                                                        @if($value->color_code)
                                                                            <small class="text-muted">
                                                                                Color: {{ $value->color_code }}
                                                                            </small>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="text-end">
                                                                    <span class="badge bg-light text-dark">
                                                                        Order: {{ $value->sort_order }}
                                                                    </span>
                                                                    @if($value->status === 'active')
                                                                        <span class="badge bg-success ms-1">Active</span>
                                                                    @else
                                                                        <span class="badge bg-secondary ms-1">Inactive</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No values found for this attribute</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger text-white">
                                            <h5 class="mb-0">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Danger Zone
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-3">These actions are permanent and cannot be undone. Be careful when performing these operations.</p>
                                            <div class="d-flex gap-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="toggleStatus({{ $attribute->id }})" 
                                                        title="{{ $attribute->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                    @if($attribute->status === 'active')
                                                        <i class="fas fa-pause me-1"></i> Deactivate Attribute
                                                    @else
                                                        <i class="fas fa-play me-1"></i> Activate Attribute
                                                    @endif
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteAttribute({{ $attribute->id }})" 
                                                        title="Delete">
                                                    <i class="fas fa-trash me-1"></i> Delete Attribute
                                                </button>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this attribute? This action cannot be undone and will affect all products using this attribute.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleStatus(attributeId) {
    if (confirm('Are you sure you want to toggle the status of this attribute?')) {
        fetch(`/erp/variation-attributes/${attributeId}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error toggling status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error toggling status');
        });
    }
}

function deleteAttribute(attributeId) {
    document.getElementById('deleteForm').action = `/erp/variation-attributes/${attributeId}`;
    const modalElement = document.getElementById('deleteModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        console.error('Delete modal element not found');
    }
}
</script>
@endpush
