@extends('erp.master')

@section('title', 'Category List')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">

        @include('erp.components.header')

        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card border w-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-box text-primary me-2"></i>Category List
                                </h5>
                                <div class="d-flex flex-column flex-sm-row gap-2">
                                    <form action="" method="GET" class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
                                        <input type="search" name="search" class="form-control form-control-sm"
                                            placeholder="Search categories..." value="{{ request('search') }}"
                                            style="width: 220px;">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><i
                                                class="fas fa-search"></i></button>
                                        @if(request('search'))
                                            <a href="{{ route('category.list') }}"
                                                class="btn btn-sm btn-outline-secondary">Clear</a>
                                        @endif
                                    </form>
                                    <button class="btn btn-sm" style="height: max-content; background-color: #20c997; color: white; border: none;" data-bs-toggle="modal"
                                        data-bs-target="#addCategoryModal">
                                        <i class="fas fa-plus me-1"></i>Create New
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Mobile list -->
                            <div class="d-md-none">
                                <div class="list-group list-group-flush">
                                    @forelse ($categories as $idx => $category)
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                @if($category->image)
                                                    <img src="{{ asset($category->image) }}" width="44" height="44" class="rounded me-2" style="object-fit:cover" />
                                                @else
                                                    <div class="rounded bg-light me-2" style="width:44px;height:44px;"></div>
                                                @endif
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">{{ $category->name }}</div>
                                                    <div class="small text-muted">#{{ $categories->firstItem() + $idx }}</div>
                                                </div>
                                                <div>
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" data-update-url="{{ route('category.update', $category->id) }}" {{ $category->status == 'active' ? 'checked' : '' }} onchange="toggleStatus(this, '{{ route('category.update', $category->id) }}')">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-2 d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category->id }}">Edit</button>
                                                <form action="{{ route('category.delete', $category->id) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="list-group-item text-center text-muted">No Category Found...</div>
                                    @endforelse
                                </div>
                                {{-- Edit modals are rendered globally at the bottom to avoid layout issues --}}
                            </div>
                            <!-- Desktop table -->
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 fw-semibold" style="width: 70px;">SL</th>
                                            <th class="border-0 fw-semibold" style="width: 80px;">Thumbnail</th>
                                            <th class="border-0 fw-semibold">Name</th>
                                            <th class="border-0 fw-semibold">Status</th>
                                            <th class="border-0 fw-semibold" style="width: 90px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($categories as $idx => $category)
                                            <tr>
                                                <td class="border-0 py-3">{{ $categories->firstItem() + $idx }}</td>
                                                <td class="border-0 py-3">
                                                    @if($category->image)
                                                        <img src="{{ asset($category->image) }}" width="48" class="rounded" />
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="border-0 py-3">{{ $category->name }}</td>
                                                <td class="border-0 py-3">
                                                    <div class="form-check form-switch">
                                                        <input 
                                                            class="form-check-input" 
                                                            type="checkbox"
                                                            data-update-url="{{ route('category.update', $category->id) }}"
                                                            {{ $category->status == 'active' ? 'checked' : '' }} 
                                                            onchange="toggleStatus(this, '{{ route('category.update', $category->id) }}')">
                                                    </div>
                                                </td>
                                                <td class="border-0 py-3">
                                                    <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="modal"
                                                        data-bs-target="#editCategoryModal{{ $category->id }}"
                                                        title="Edit Category" style="width: 32px; height: 32px; padding: 0;">
                                                        <i class="fas fa-edit text-primary"></i>
                                                    </button>
                                                    <form action="{{ route('category.delete', $category->id) }}" method="POST"
                                                        style="display:inline-block"
                                                        onsubmit="return confirm('Delete this category?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-light btn-sm rounded-circle"
                                                            title="Delete Category" style="width: 32px; height: 32px; padding: 0; margin-left: 5px;">
                                                            <i class="fas fa-trash text-danger"></i>
                                                        </button>
                                                    </form>
                                                    {{-- Edit modals are rendered globally at the bottom to avoid layout issues --}}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    No Category Found...
                                                </td>
                                            </tr>
                                        @endforelse

                                    </tbody>
                                </table>
                            </div>

                            <nav class="d-flex justify-content-start mt-4">
                                {{ $categories->onEachSide(1)->links('vendor.pagination.bootstrap-5') }}
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Edit Modals (outside tables/lists to avoid overflow/transform issues) --}}
    @foreach ($categories as $category)
        <div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1" aria-labelledby="editCategoryModalLabel{{ $category->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editCategoryModalLabel{{ $category->id }}"><i class="fas fa-layer-group me-2"></i>Edit Category</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('category.update', $category->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_category_name_{{ $category->id }}" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_category_name_{{ $category->id }}" name="name" value="{{ $category->name }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category_slug_{{ $category->id }}" class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_category_slug_{{ $category->id }}" name="slug" value="{{ $category->slug ?? '' }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category_description_{{ $category->id }}" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_category_description_{{ $category->id }}" name="description" rows="2">{{ $category->description }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category_image_{{ $category->id }}" class="form-label">Category Image</label>
                                <input class="form-control" type="file" id="edit_category_image_{{ $category->id }}" name="image" accept="image/*">
                                @if($category->image)
                                    <img src="{{ asset($category->image) }}" width="100px" class="mt-2 rounded" />
                                @endif
                                <small class="form-text text-muted">Supported: jpeg, png, jpg, gif, svg. Max size: 2MB.</small>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category_status_{{ $category->id }}" class="form-label">Status</label>
                                <select class="form-select" id="edit_category_status_{{ $category->id }}" name="status">
                                    <option value="active" @if($category->status == 'active') selected @endif>Active</option>
                                    <option value="inactive" @if($category->status == 'inactive') selected @endif>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addCategoryModalLabel"><i class="fas fa-layer-group me-2"></i>Add New
                        Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('category.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_name" name="name" required
                                placeholder="Enter category name">
                        </div>
                        <div class="mb-3">
                            <label for="category_slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_slug" name="slug" required placeholder="Auto-generated from name">
                        </div>
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Description</label>
                            <textarea class="form-control" id="category_description" name="description" rows="2"
                                placeholder="Enter description (optional)"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category_image" class="form-label">Category Image</label>
                            <input class="form-control" type="file" id="category_image" name="image" accept="image/*">
                            <small class="form-text text-muted">Supported: jpeg, png, jpg, gif, svg. Max size: 2MB.</small>
                        </div>
                        <div class="mb-3">
                            <label for="category_status" class="form-label">Status</label>
                            <select class="form-select" id="category_status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<style>
/* Toggle colors: grey when off, teal when on */
.form-check-input[type="checkbox"] {
    cursor: pointer;
}
.form-check-input:checked {
    background-color: #20c997 !important;
    border-color: #20c997 !important;
}
.form-check-input:not(:checked) {
    background-color: #d6d6d6 !important;
    border-color: #d6d6d6 !important;
}
</style>
<script>
function slugify(text) {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[\s\W-]+/g, '-') // Replace spaces and non-word chars with -
        .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
}

function toggleStatus(checkboxEl, urlFromBlade) {
    const isActive = checkboxEl.checked;
    const status = isActive ? 'active' : 'inactive';
    // Ensure full ERP path; fallback to provided URL
    const updateUrl = urlFromBlade || checkboxEl.getAttribute('data-update-url');

    fetch(updateUrl, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            status: status
        })
    })
    .then(async response => {
        // If route returns redirect/html (not ajax), treat as failure
        const contentType = response.headers.get('Content-Type') || '';
        if (!response.ok || !contentType.includes('application/json')) {
            throw new Error('Non-JSON response or network error');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // success - no console noise
        } else {
            // Revert the toggle if update failed
            checkboxEl.checked = !isActive;
            alert('Failed to update status');
        }
    })
    .catch(error => {
        // Revert the toggle if update failed
        checkboxEl.checked = !isActive;
        console.error('Error:', error);
        alert('Failed to update status');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // For Add Category Modal
    const nameInput = document.getElementById('category_name');
    const slugInput = document.getElementById('category_slug');
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            slugInput.value = slugify(nameInput.value);
        });
    }

    // For Edit Category Modals
    @foreach ($categories as $category)
        (function() {
            const editName = document.getElementById('edit_category_name_{{ $category->id }}');
            const editSlug = document.getElementById('edit_category_slug_{{ $category->id }}');
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