@extends('erp.master')

@section('title', 'Subcategory List')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')

        <div class="container">
            <div class="row">
                <div class="col-12 my-4">
                    <div class="card border w-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-list-ul text-primary me-2"></i>Sub Categories
                                </h5>
                                <div class="d-flex gap-2">
                                    <form action="" method="GET" class="d-flex align-items-center gap-2">
                                        <select name="parent_id" class="form-select form-select-sm" style="width: 220px;">
                                            <option value="">All Categories</option>
                                            @foreach($parentCategories as $pc)
                                                <option value="{{ $pc->id }}" @if(request('parent_id')==$pc->id) selected @endif>{{ $pc->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="search" name="search" class="form-control form-control-sm" placeholder="Search subcategories..." value="{{ request('search') }}" style="width: 220px;">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
                                        @if(request('search') || request('parent_id'))
                                            <a href="{{ route('subcategory.list') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                                        @endif
                                    </form>
                                    <button class="btn btn-sm btn-outline-primary" style="height: max-content;" data-bs-toggle="modal" data-bs-target="#addSubcategoryModal">
                                        <i class="fas fa-plus me-1"></i>Create New
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 fw-semibold" style="width: 70px;">SL</th>
                                            <th class="border-0 fw-semibold" style="width: 80px;">Thumbnail</th>
                                            <th class="border-0 fw-semibold">Category</th>
                                            <th class="border-0 fw-semibold">Name</th>
                                            <th class="border-0 fw-semibold">Status</th>
                                            <th class="border-0 fw-semibold" style="width: 90px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($subcategories as $idx => $subcategory)
                                            <tr>
                                                <td class="border-0 py-3">{{ $subcategories->firstItem() + $idx }}</td>
                                                <td class="border-0 py-3">
                                                    @if($subcategory->image)
                                                        <img src="{{ asset($subcategory->image) }}" width="48" class="rounded" />
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="border-0 py-3">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $subcategory->parent?->name }}</span>
                                                </td>
                                                <td class="border-0 py-3">{{ $subcategory->name }}</td>
                                                <td class="border-0 py-3">
                                                    <div class="form-check form-switch">
                                                        <input 
                                                            class="form-check-input" 
                                                            type="checkbox"
                                                            data-update-url="{{ route('subcategory.update', $subcategory->id) }}"
                                                            {{ $subcategory->status == 'active' ? 'checked' : '' }} 
                                                            onchange="toggleSubStatus(this, '{{ route('subcategory.update', $subcategory->id) }}')">
                                                    </div>
                                                </td>
                                                <td class="border-0 py-3">
                                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editSubcategoryModal{{ $subcategory->id }}" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('subcategory.delete', $subcategory->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Delete this subcategory?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <div class="modal fade" id="editSubcategoryModal{{ $subcategory->id }}" tabindex="-1" aria-labelledby="editSubcategoryModalLabel{{ $subcategory->id }}" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title" id="editSubcategoryModalLabel{{ $subcategory->id }}"><i class="fas fa-layer-group me-2"></i>Edit Subcategory</h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="{{ route('subcategory.update', $subcategory->id) }}" method="POST" enctype="multipart/form-data">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label for="edit_subcategory_name_{{ $subcategory->id }}" class="form-label">Name <span class="text-danger">*</span></label>
                                                                            <input type="text" class="form-control" id="edit_subcategory_name_{{ $subcategory->id }}" name="name" value="{{ $subcategory->name }}" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="edit_subcategory_parent_{{ $subcategory->id }}" class="form-label">Category <span class="text-danger">*</span></label>
                                                                            <select class="form-select" id="edit_subcategory_parent_{{ $subcategory->id }}" name="parent_id" required>
                                                                                @foreach($parentCategories as $pc)
                                                                                    <option value="{{ $pc->id }}" @if($subcategory->parent_id == $pc->id) selected @endif>{{ $pc->name }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="edit_subcategory_slug_{{ $subcategory->id }}" class="form-label">Slug <span class="text-danger">*</span></label>
                                                                            <input type="text" class="form-control" id="edit_subcategory_slug_{{ $subcategory->id }}" name="slug" value="{{ $subcategory->slug ?? '' }}" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="edit_subcategory_description_{{ $subcategory->id }}" class="form-label">Description</label>
                                                                            <textarea class="form-control" id="edit_subcategory_description_{{ $subcategory->id }}" name="description" rows="2">{{ $subcategory->description }}</textarea>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="edit_subcategory_image_{{ $subcategory->id }}" class="form-label">Image</label>
                                                                            <input class="form-control" type="file" id="edit_subcategory_image_{{ $subcategory->id }}" name="image" accept="image/*">
                                                                            @if($subcategory->image)
                                                                                <img src="{{ asset($subcategory->image) }}" width="100" class="mt-2 rounded" />
                                                                            @endif
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="edit_subcategory_status_{{ $subcategory->id }}" class="form-label">Status</label>
                                                                            <select class="form-select" id="edit_subcategory_status_{{ $subcategory->id }}" name="status">
                                                                                <option value="active" @if($subcategory->status == 'active') selected @endif>Active</option>
                                                                                <option value="inactive" @if($subcategory->status == 'inactive') selected @endif>Inactive</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No Subcategory Found...</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <nav class="d-flex justify-content-start mt-4">
                                {{ $subcategories->onEachSide(1)->links('vendor.pagination.bootstrap-5') }}
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addSubcategoryModal" tabindex="-1" aria-labelledby="addSubcategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addSubcategoryModalLabel"><i class="fas fa-layer-group me-2"></i>Create Subcategory</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('subcategory.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="subcategory_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subcategory_name" name="name" required placeholder="Enter subcategory name">
                        </div>
                        <div class="mb-3">
                            <label for="subcategory_parent_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="subcategory_parent_id" name="parent_id" required>
                                @foreach($parentCategories as $pc)
                                    <option value="{{ $pc->id }}">{{ $pc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subcategory_slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subcategory_slug" name="slug" required placeholder="Auto-generated from name">
                        </div>
                        <div class="mb-3">
                            <label for="subcategory_description" class="form-label">Description</label>
                            <textarea class="form-control" id="subcategory_description" name="description" rows="2" placeholder="Enter description (optional)"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="subcategory_image" class="form-label">Image</label>
                            <input class="form-control" type="file" id="subcategory_image" name="image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="subcategory_status" class="form-label">Status</label>
                            <select class="form-select" id="subcategory_status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<style>
.form-check-input[type="checkbox"] { cursor: pointer; }
.form-check-input:checked { background-color: #20c997 !important; border-color: #20c997 !important; }
.form-check-input:not(:checked) { background-color: #d6d6d6 !important; border-color: #d6d6d6 !important; }
</style>
<script>
function toggleSubStatus(checkboxEl, urlFromBlade) {
    const isActive = checkboxEl.checked;
    const status = isActive ? 'active' : 'inactive';
    const updateUrl = urlFromBlade || checkboxEl.getAttribute('data-update-url');

    fetch(updateUrl, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status })
    })
    .then(async response => {
        const contentType = response.headers.get('Content-Type') || '';
        if (!response.ok || !contentType.includes('application/json')) {
            throw new Error('Non-JSON response or network error');
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            checkboxEl.checked = !isActive;
            alert('Failed to update status');
        }
    })
    .catch(() => {
        checkboxEl.checked = !isActive;
        alert('Failed to update status');
    });
}
function slugify(text) {
    return text.toString().toLowerCase().trim().replace(/[\s\W-]+/g, '-').replace(/^-+|-+$/g, '');
}
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('subcategory_name');
    const slugInput = document.getElementById('subcategory_slug');
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            slugInput.value = slugify(nameInput.value);
        });
    }
    @foreach ($subcategories as $subcategory)
        (function() {
            const editName = document.getElementById('edit_subcategory_name_{{ $subcategory->id }}');
            const editSlug = document.getElementById('edit_subcategory_slug_{{ $subcategory->id }}');
            if (editName && editSlug) {
                editName.addEventListener('input', function() {
                    editSlug.value = slugify(editName.value);
                });
            }
        })();
    @endforeach
});
</script>
@endpush


