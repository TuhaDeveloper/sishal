@extends('erp.master')

@section('title', 'Variation Attributes')

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
                        <i class="fas fa-tags me-2"></i>
                        Variation Attributes
                    </h4>
                    <a href="{{ route('erp.variation-attributes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Attribute
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($attributes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Type</th>
                                        <th>Values</th>
                                        <th>Required</th>
                                        <th>Status</th>
                                        <th>Sort Order</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attributes as $attribute)
                                        <tr>
                                            <td>
                                                <strong>{{ $attribute->name }}</strong>
                                                @if($attribute->description)
                                                    <br><small class="text-muted">{{ $attribute->description }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <code>{{ $attribute->slug }}</code>
                                            </td>
                                            <td>
                                                @if($attribute->is_color)
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-palette me-1"></i> Color
                                                    </span>
                                                @else
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-tag me-1"></i> Text
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                                    @foreach($attribute->values->take(3) as $value)
                                                        <span class="badge bg-light text-dark d-flex align-items-center" style="gap:6px;">
                                                            @if($attribute->is_color && $value->image)
                                                                <img src="{{ asset($value->image) }}" alt="{{ $value->value }}" style="width:18px;height:18px;object-fit:cover;border-radius:4px;border:1px solid #e2e8f0"/>
                                                            @elseif($attribute->is_color && $value->color_code)
                                                                <span class="color-indicator" 
                                                                      style="background-color: {{ $value->color_code }}; 
                                                                             width: 14px; height: 14px; 
                                                                             display: inline-block; 
                                                                             border-radius: 3px;"></span>
                                                            @endif
                                                            <span>{{ $value->value }}</span>
                                                        </span>
                                                    @endforeach
                                                    @if($attribute->values->count() > 3)
                                                        <span class="badge bg-secondary">
                                                            +{{ $attribute->values->count() - 3 }} more
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($attribute->is_required)
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation-circle me-1"></i> Required
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">Optional</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $attribute->status === 'active' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($attribute->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $attribute->sort_order }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('erp.variation-attributes.show', $attribute->id) }}" 
                                                       class="btn btn-sm btn-outline-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('erp.variation-attributes.edit', $attribute->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-{{ $attribute->status === 'active' ? 'secondary' : 'success' }}"
                                                            onclick="toggleStatus({{ $attribute->id }})" 
                                                            title="{{ $attribute->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                        <i class="fas fa-{{ $attribute->status === 'active' ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteAttribute({{ $attribute->id }})" 
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No attributes found</h5>
                            <p class="text-muted">Create your first variation attribute to get started.</p>
                            <a href="{{ route('erp.variation-attributes.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Create First Attribute
                            </a>
                        </div>
                    @endif
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
