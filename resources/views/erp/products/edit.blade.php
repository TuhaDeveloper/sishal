@extends('erp.master')

@section('title', 'Edit Product')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                        </div>
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            <form action="{{ route('product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $product->name) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="slug" name="slug" required value="{{ old('slug', $product->slug) }}" placeholder="Auto-generated from name">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="sku" name="sku" required value="{{ old('sku', $product->sku) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="type" name="type" required>
                                            <option value="product" {{ old('type', $product->type) == 'product' ? 'selected' : '' }}>Product</option>
                                            <option value="service" {{ old('type', $product->type) == 'service' ? 'selected' : '' }}>Service</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select" id="category_id" name="category_id" required style="width: 100%">
                                            @if($product->category)
                                                <option value="{{ $product->category->id }}" selected>{{ $product->category->name }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="short_desc" class="form-label">Short Description</label>
                                        <textarea class="form-control" id="short_desc" name="short_desc" rows="3">{{ old('short_desc', $product->short_desc) }}</textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="description" class="form-label">Description</label>
                                        <input type="hidden" name="description" id="description_input" value="{{ old('description', $product->description) }}">
                                        <div id="quill_description_edit" style="height: 220px; background: #fff;" class="border"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" required value="{{ old('price', $product->price) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="discount" class="form-label">Discount</label>
                                        <input type="number" step="0.01" class="form-control" id="discount" name="discount" value="{{ old('discount', $product->discount) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cost" class="form-label">Cost <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" id="cost" name="cost" required value="{{ old('cost', $product->cost) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="image" class="form-label">Main Image</label>
                                        @if($product->image)
                                            <div class="mb-2">
                                                <img src="{{ asset($product->image) }}" alt="Current Image" style="max-width: 120px; max-height: 120px;">
                                            </div>
                                        @endif
                                        <input class="form-control" type="file" id="image" name="image" accept="image/*">
                                        <small class="form-text text-muted">Supported: jpeg, png, jpg, gif, svg. Max size: 2MB.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gallery" class="form-label">Gallery Images</label>
                                        <div class="mb-2 d-flex flex-wrap gap-2">
                                            @foreach($product->galleries as $gallery)
                                                <div class="position-relative" style="display: inline-block;">
                                                    <img src="{{ asset($gallery->image) }}" alt="Gallery Image" style="max-width: 80px; max-height: 80px;">
                                                    <button type="button" class="btn btn-sm btn-danger p-1 gallery-delete-btn" data-action="{{ route('product.gallery.delete', $gallery->id) }}" style="position: absolute; top: 0; right: 0;" title="Remove image"><i class="fas fa-times"></i></button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <input class="form-control" type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
                                        <small class="form-text text-muted">You can select multiple images. Uploading new images will add to the gallery.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <h3>Meta Information</h3>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="meta_title" class="form-label">Meta Title</label>
                                        <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="meta_description" class="form-label">Meta Description</label>
                                        <textarea class="form-control" id="meta_description" name="meta_description" rows="3">{{ old('meta_description', $product->meta_description) }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                        <div id="keywords-container">
                                            @php
                                                $existingKeywords = [];
                                                if ($product->meta_keywords) {
                                                    if (is_array($product->meta_keywords)) {
                                                        $existingKeywords = $product->meta_keywords;
                                                    } else {
                                                        $decoded = json_decode($product->meta_keywords, true);
                                                        if (is_array($decoded)) { $existingKeywords = $decoded; }
                                                    }
                                                }
                                            @endphp
                                            @if(count($existingKeywords) > 0)
                                                @foreach($existingKeywords as $index => $keyword)
                                                    <div class="input-group mb-2">
                                                        <input type="text" class="form-control keyword-input" name="meta_keywords[]" placeholder="Enter keyword" value="{{ $keyword }}">
                                                        <button type="button" class="btn btn-outline-danger remove-keyword">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="input-group mb-2">
                                                    <input type="text" class="form-control keyword-input" name="meta_keywords[]" placeholder="Enter keyword">
                                                    <button type="button" class="btn btn-outline-danger remove-keyword" style="display: none;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-keyword">
                                            <i class="fas fa-plus me-1"></i>Add Keyword
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-4 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Update Product</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="galleryDeleteForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <style>
        .select2-selection{
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
        }
    </style>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
function slugify(text) {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[\s\W-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
$(document).ready(function() {
    $('#category_id').select2({
        placeholder: 'Search for a category',
        ajax: {
            url: '/erp/categories/search',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data.map(function(cat){ return { id: cat.id, text: cat.name }; }) }; },
            cache: true
        },
        minimumInputLength: 1
    });
    $('#name').on('input', function(){ $('#slug').val(slugify($(this).val())); });

    // Handle gallery delete without nested forms
    $(document).on('click', '.gallery-delete-btn', function(){
        var action = $(this).data('action');
        var form = document.getElementById('galleryDeleteForm');
        form.setAttribute('action', action);
        form.submit();
    });

    // Quill init for Description only
    var form = document.getElementById('quill_description_edit') ? document.getElementById('quill_description_edit').closest('form') : null;
    var quill = new Quill('#quill_description_edit', {
        theme: 'snow',
        modules: { toolbar: [[{ header: [1,2,3,false] }], ['bold','italic','underline','strike'], [{ list:'ordered' }, { list:'bullet' }], [{ align: [] }], ['link','blockquote','code-block','image'], ['clean']] }
    });
    var initial = document.getElementById('description_input').value || '';
    if (initial) { document.querySelector('#quill_description_edit .ql-editor').innerHTML = initial; }
    
    // Keep hidden input in sync on every change
    quill.on('text-change', function(){
        document.getElementById('description_input').value = quill.root.innerHTML;
    });
    // Also sync on submit as a final safety (bind only if form exists)
    if (form) {
        form.addEventListener('submit', function(){
            document.getElementById('description_input').value = quill.root.innerHTML;
        });
    }

    // Keywords add/remove
    let keywordCount = $('.keyword-input').length;
    $('#add-keyword').on('click', function(){
        $('#keywords-container').append(`
            <div class="input-group mb-2">
                <input type="text" class="form-control keyword-input" name="meta_keywords[]" placeholder="Enter keyword">
                <button type="button" class="btn btn-outline-danger remove-keyword">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`);
        keywordCount++; updateRemoveButtons();
    });
    $(document).on('click', '.remove-keyword', function(){ if (keywordCount>1) { $(this).closest('.input-group').remove(); keywordCount--; updateRemoveButtons(); } });
    function updateRemoveButtons(){ const btns=$('.remove-keyword'); if (keywordCount<=1) btns.hide(); else btns.show(); }
    updateRemoveButtons();
});
</script>
@endpush
