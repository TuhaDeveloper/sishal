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
                    <div class="product-card position-relative h-100" data-href="{{ route('product.details', $product->slug) }}">
                        <button class="wishlist-btn {{$product->is_wishlisted ? ' active' : ''}}" data-product-id="{{ $product->id }}">
                            <i class="{{ $product->is_wishlisted ? 'fas text-danger' : 'far' }} fa-heart"></i>
                        </button>
                        <div class="product-image-container">
                            <img src="{{ $product->image ?: '/default-product.png' }}" class="product-image" alt="{{ $product->name }}">
                        </div>
                        <div class="product-info">
                            <a href="{{ route('product.details', $product->slug) }}" class="product-title" style="text-decoration: none;">{{ $product->name }}</a>
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
                                    <span class="fw-bold text-primary">{{ number_format($product->discount, 2) }}৳</span>
                                    <span class="text-muted text-decoration-line-through ms-2">{{ number_format($product->price, 2) }}৳</span>
                                @else
                                    <span class="fw-bold text-primary">{{ number_format($product->price, 2) }}৳</span>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 product-actions">
                                @php
                                    $hasStock = $product->hasStock();
                                @endphp
                                <button class="btn-add-cart" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" data-has-stock="{{ $hasStock ? 'true' : 'false' }}"
                                        {{ !$hasStock ? 'disabled' : '' }}>
                                    <svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff" width="14" height="14"><path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z"></path><circle cx="7" cy="22" r="2"></circle><circle cx="17" cy="22" r="2"></circle></svg>
                                    {{ $hasStock ? 'Add to Cart' : 'Out of Stock' }}
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

    <div id="toast-container"
        style="position: fixed; top: 24px; right: 24px; z-index: 16000; display: flex; flex-direction: column; gap: 10px;">
    </div>
@endsection

@push('scripts')
    <script>
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'custom-toast ' + type;
            toast.innerHTML = `
                <div class="toast-content">
                    <span class="toast-icon">${type === 'error' ? '❌' : ''}</span>
                    <span class="toast-message">${message}</span>
                    <button class="toast-close" onclick="this.parentElement.parentElement.classList.add('hide'); setTimeout(()=>this.parentElement.parentElement.remove(), 400);">&times;</button>
                </div>
                <div class="toast-progress"></div>
            `;
            document.getElementById('toast-container').appendChild(toast);
            // Animate progress bar
            setTimeout(() => {
                toast.querySelector('.toast-progress').style.width = '0%';
            }, 10);
            setTimeout(() => {
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 400);
            }, 2500);
        }

        // Cart functionality is now handled by global cart handler in master.blade.php
        // No need for duplicate event listeners here

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
    <style>
        .custom-toast {
            min-width: 220px;
            max-width: 340px;
            background: #fff;
            color: #222;
            padding: 0;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            font-size: 16px;
            opacity: 1;
            transition: opacity 0.4s, transform 0.4s;
            margin-left: auto;
            margin-right: 0;
            pointer-events: auto;
            z-index: 16000;
            overflow: hidden;
            border-left: 5px solid #2196F3;
            position: relative;
        }

        .custom-toast.error {
            border-left-color: #e53935;
        }

        .custom-toast .toast-content {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 18px 14px 16px;
        }

        .custom-toast .toast-icon {
            font-size: 22px;
            flex-shrink: 0;
        }

        .custom-toast .toast-message {
            flex: 1;
            font-weight: 500;
        }

        .custom-toast .toast-close {
            background: none;
            border: none;
            color: #888;
            font-size: 22px;
            cursor: pointer;
            margin-left: 8px;
            transition: color 0.2s;
        }

        .custom-toast .toast-close:hover {
            color: #e53935;
        }

        .custom-toast .toast-progress {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 100%;
            background: linear-gradient(90deg, #2196F3, #21cbf3);
            transition: width 2.3s linear;
        }

        .custom-toast.error .toast-progress {
            background: linear-gradient(90deg, #e53935, #ffb199);
        }

        .custom-toast.hide {
            opacity: 0;
            transform: translateY(-20px) scale(0.98);
        }
    </style>
@endpush


