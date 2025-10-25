@if($products->count() > 0)
    @foreach($products as $product)
        <div class="col-lg-3 col-md-6 mt-0 mb-4">
            <div class="product-card position-relative mb-0 h-100" data-href="{{ route('product.details', $product->slug) }}">
                <!-- Top Wishlist Button -->
                <button class="product-wishlist-top {{$product->is_wishlisted ? ' active' : ''}}"
                    data-product-id="{{ $product->id }}"
                    onclick="event.stopPropagation(); toggleWishlist({{ $product->id }});"
                    title="Add to Wishlist">
                    <i class="{{ $product->is_wishlisted ? 'fas' : 'far' }} fa-heart"></i>
                </button>
                
                <!-- Original Wishlist Button (keeping for compatibility) -->
                <button class="wishlist-btn {{$product->is_wishlisted ? ' active' : ''}}"
                    data-product-id="{{ $product->id }}"
                    onclick="event.stopPropagation();">
                    <i class="{{ $product->is_wishlisted ? 'fas text-danger' : 'far' }} fa-heart"></i>
                </button>
                <div class="product-image-container">
                    <img src="{{$product->image ? $product->image : '/default-product.png'}}"
                        class="product-image"
                        alt="{{ $product->name }}"
                        loading="lazy"
                        onerror="this.src='/static/default-product.png'">
                </div>
                <div class="product-info">
                    <a href="{{ route('product.details', $product->slug) }}" class="product-title"
                        style="text-decoration: none;">{{ $product->name }}</a>
                    <p class="product-description">{{$product->short_desc ? $product->short_desc : ''}}</p>
                    <div class="product-meta" style="margin-top:6px;">
                        @php
                            $avgRating = $product->avg_rating ?? 0;
                            $totalReviews = $product->total_reviews ?? 0;
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
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        @php
                            $hasStock = $product->has_stock ?? false;
                        @endphp
                        <button class="btn-add-cart" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" data-has-stock="{{ $hasStock ? 'true' : 'false' }}"
                                {{ !$hasStock ? 'disabled' : '' }}><svg
                                xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff"
                                width="14" height="14">
                                <path
                                    d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z">
                                </path>
                                <circle cx="7" cy="22" r="2"></circle>
                                <circle cx="17" cy="22" r="2"></circle>
                            </svg> {{ $hasStock ? 'Add to Cart' : 'Out of Stock' }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="col-12">
        <div class="no-products-container">
            <div class="no-products-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="no-products-title">No Products Found</h3>
            <p class="no-products-message">We couldn't find any products matching your current filters.</p>
            <div class="no-products-suggestion">
                <i class="fas fa-lightbulb"></i>
                <span>Try adjusting your filters to see more products</span>
            </div>
        </div>
    </div>
@endif

@if($products->hasPages())
    <div class="col-12">
        <div class="d-flex justify-content-center mt-4">
            {{ $products->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
@endif
