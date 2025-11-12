@extends('erp.master')

@section('title', 'Product List')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid py-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-2 mb-4">
                <form class="d-flex flex-column flex-sm-row gap-2" method="GET" action="">
                    <input type="search" name="search" class="form-control" placeholder="Search products..." value="{{ request('search') }}">
                    <select class="form-select" id="category_id" name="category_id" style="max-width: 180px; width: 100%; height: 38px;">
                        <option value="">All Categories</option>
                    </select>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                        <a href="{{ route('product.list') }}" class="btn btn-outline-primary" style="display: flex; white-space: nowrap; align-items: center;"><i class="fas fa-sync me-1"></i>Reset Filter</a>
                    </div>
                </form>
                <a href="{{ route('product.create') }}" class="btn btn-primary align-self-md-center"><i class="fas fa-plus me-1"></i>Add Product</a>
            </div>

            <div class="row g-4">

                @forelse ($products as $product)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="position-relative">
                            <img src="{{ asset($product->image) }}" class="card-img-top" alt="Product " style="height: 200px; object-fit: cover;">
                            @if($product->free_delivery)
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-info"><i class="fas fa-truck me-1"></i>Free Delivery</span>
                            </div>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title fw-bold mb-1">{{ $product->name ?? 'N/A' }} </h6>
                            <div class="mb-2 text-muted small">Category: {{ $product->category->name ?? 'N/A' }}</div>
                            <div class="mb-2 text-muted small">SKU: {{ $product->sku ?? 'N/A' }}</div>
                            <div class="mb-2 text-muted small">Total Quantity: {{ $product->total_variation_stock }}</div>
                            <div class="mb-2">
                                <span class="fw-semibold text-success">{{ $product->discount ? $product->discount : $product->price }}৳</span>
                                <span class="text-decoration-line-through text-muted ms-2">{{ $product->price ?? 'N/A' }}৳</span>
                            </div>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="badge bg-success">{{ $product->status == 'active' ? 'Active' : 'Inactive' }}</span>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('product.show', $product->id) }}" class="btn btn-sm btn-outline-success" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('product.edit', $product->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="{{ route('erp.products.variations.index', $product->id) }}" class="btn btn-sm btn-outline-info" title="Manage Variations"><i class="fas fa-layer-group"></i></a>
                                    <form action="{{ route('product.delete', $product->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                    
                @endforelse
            </div>

            <nav class="d-flex justify-content-center mt-4">
                {{ $products->onEachSide(1)->links('vendor.pagination.bootstrap-5') }}
            </nav>
        </div>
    </div>

    <style>
        .select2-selection{
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-selection__arrow{
            height: 100% !important;
        }
    </style>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#category_id').select2({
        placeholder: 'Search or select a category',
        allowClear: true,
        width: 'resolve',
        ajax: {
            url: '/erp/categories/search',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(cat) {
                        return { id: cat.id, text: cat.display_name || cat.name };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });

    // Set the selected category if present in the query string
    var selectedCategory = '{{ request('category_id') }}';
    if(selectedCategory) {
        $.ajax({
            url: '/erp/categories/search',
            data: { q: '' },
            dataType: 'json'
        }).then(function(data) {
            var option = data.find(function(cat) { return cat.id == selectedCategory; });
            if(option) {
                var newOption = new Option(option.display_name || option.name, option.id, true, true);
                $('#category_id').append(newOption).trigger('change');
            }
        });
    }
});
</script>
@endpush