@extends('ecommerce.master')

@section('main-section')
    <section class="featured-categories pb-3 pt-5">
        <div class="container">
            <h2 class="section-title text-start">Search Result</h2>
            <p class="section-subtitle text-start">Search result for {{ $search }}</p>
        </div>
    </section>

    <div class="container py-4">
        <div class="row">

            <!-- Product Grid -->
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>Showing {{ $products->count() }} products</div>
                </div>
                <div class="row g-4 mt-4">
                    @foreach($products as $product)
                        <div class="col-lg-3 col-md-6 mt-0 mb-4">
                            <div class="product-card position-relative mb-0 h-100" data-href="{{ route('product.details', $product->slug) }}">
                                <button class="wishlist-btn {{$product->is_wishlisted ? ' active' : ''}}"
                                    data-product-id="{{ $product->id }}">
                                    <i class="{{ $product->is_wishlisted ? 'fas text-danger' : 'far' }} fa-heart"></i>
                                </button>
                                <div class="product-image-container">
                                    <img src="{{$product->image ? $product->image : '/default-product.png'}}"
                                        class="product-image"
                                        alt="{{ $product->name }}">
                                </div>
                                <div class="product-info">
                                    <a href="{{ route('product.details', $product->slug) }}" class="product-title"
                                        style="text-decoration: none;">{{ $product->name }}</a>
                                    <p class="product-description">{{$product->description ? $product->description : ''}}</p>
                                    <div class="product-meta" style="margin-top:6px;">
                                        @php
                                            $avgRating = $product->averageRating();
                                            $totalReviews = $product->totalReviews();
                                        @endphp
                                        <div class="stars" aria-label="{{ $avgRating }} out of 5">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="fa{{ $i <= $avgRating ? 's' : 'r' }} fa-star"></i>
                                            @endfor
                                        </div>
                                        <div class="rating-text" style="font-size: 12px; color: #666; margin-top: 2px;">
                                            ({{ $totalReviews }} review{{ $totalReviews !== 1 ? 's' : '' }})
                                        </div>
                                    </div>

                                    <div class="price">
                                        @if(isset($product->discount) && $product->discount > 0)
                                            <span class="fw-bold text-primary">
                                                {{ number_format($product->discount, 2) }}৳
                                            </span>
                                            <span class="text-muted text-decoration-line-through ms-2">
                                                {{ number_format($product->price, 2) }}৳
                                            </span>
                                        @else
                                            <span class="fw-bold text-primary">
                                                {{ number_format($product->price, 2) }}৳
                                            </span>
                                        @endif
                                    </div>
                                    <button class="btn-add-cart" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}"><svg
                                            xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff"
                                            width="14" height="14">
                                            <path
                                                d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z">
                                            </path>
                                            <circle cx="7" cy="22" r="2"></circle>
                                            <circle cx="17" cy="22" r="2"></circle>
                                        </svg> Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-center mt-4">
                    {{ $products->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
    <div id="toast-container"
        style="position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;">
    </div>
@endsection