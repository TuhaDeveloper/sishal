@extends('ecommerce.master')

@section('main-section')
    <style></style>
    <section class="featured-categories featured-plain pb-3 pt-5">
        <div class="container container-80">
            <h2 class="section-title text-start">Best Deal</h2>
        </div>
    </section>

    <div class="container container-80 py-4">
        <div class="row g-4 grid-5">
            @foreach($products as $product)
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card position-relative h-100">
                        <button class="wishlist-btn {{$product->is_wishlisted ? ' active' : ''}}" data-product-id="{{ $product->id }}">
                            <i class="{{ $product->is_wishlisted ? 'fas text-danger' : 'far' }} fa-heart"></i>
                        </button>
                        <div class="product-image-container">
                            <img src="{{ $product->image ?: '/default-product.png' }}" class="product-image" alt="{{ $product->name }}">
                        </div>
                        <div class="product-info">
                            <a href="{{ route('product.details', $product->slug) }}" class="product-title" style="text-decoration: none;">{{ $product->name }}</a>
                            <div class="price">
                                @if(isset($product->discount) && $product->discount > 0)
                                    <span class="fw-bold text-primary">{{ number_format($product->discount, 2) }}৳</span>
                                    <span class="text-muted text-decoration-line-through ms-2">{{ number_format($product->price, 2) }}৳</span>
                                @else
                                    <span class="fw-bold text-primary">{{ number_format($product->price, 2) }}৳</span>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 product-actions">
                                <button class="btn-add-cart" data-product-id="{{ $product->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff" width="14" height="14"><path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z"></path><circle cx="7" cy="22" r="2"></circle><circle cx="17" cy="22" r="2"></circle></svg>
                                    Buy Now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $products->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).on('click', '.btn-add-cart', function (e) {
            e.preventDefault();
            var btn = $(this);
            var productId = btn.data('product-id');
            btn.prop('disabled', true);
            fetch("{{ url('cart/add') }}/" + productId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
                .then(response => response.json())
                .then(data => {
                    btn.prop('disabled', false);
                    if (data.success) {
                        if (typeof showToast === 'function') showToast('Added to cart!');
                        if (typeof updateCartQtyBadge === 'function') updateCartQtyBadge();
                    } else {
                        if (typeof showToast === 'function') showToast('Could not add to cart.', 'error');
                    }
                })
                .catch(() => {
                    btn.prop('disabled', false);
                    if (typeof showToast === 'function') showToast('Could not add to cart.', 'error');
                });
        });

        $(document).on('click', '.wishlist-btn', function (e) {
            e.preventDefault();
            var btn = $(this);
            var icon = btn.find('i.fa-heart');
            var productId = btn.data('product-id');
            $.ajax({
                url: '/add-remove-wishlist/' + productId,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    if (response.success) {
                        icon.toggleClass('active');
                        icon.toggleClass('fas far');
                        if (typeof showToast === 'function') showToast(response.message, 'success');
                    }
                }
            });
        });
    </script>
@endpush


