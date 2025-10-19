@extends('ecommerce.master')

@php
    use Illuminate\Support\Str;
@endphp

@section('main-section')
    <div class="container-fluid py-3 wishlist-page" style="background-color: #f8f9fa;">
        <div class="container">
            <!-- Page Header -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-2" style="color: #333; font-weight: 600;">My Wishlist</h2>
                            <p class="text-muted mb-0">Save your favorite water filtration products</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="wishlist-count-badge me-3">
                                <i class="fas fa-heart me-1"></i>
                                {{ $wishlists->count() }} {{ $wishlists->count() == 1 ? 'Item' : 'Items' }}
                            </span>
                            @if($wishlists->count() > 0)
                                <form action="{{ route('wishlist.removeAll') }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-clear-all">
                                        <i class="fas fa-trash me-1"></i> Clear All
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wishlist Items -->
            <div class="row g-3 g-md-4">
                @forelse ($wishlists as $wishlist)

                    <div class="col-lg-3 col-md-6 mt-0 mb-4">
                        <div class="product-card position-relative mb-0 h-100" data-href="{{ route('product.details', $wishlist->product->slug) }}">
                            <!-- Top Wishlist Button -->
                            <button class="product-wishlist-top active"
                                data-product-id="{{ $wishlist->product->id }}"
                                onclick="event.stopPropagation(); toggleWishlist({{ $wishlist->product->id }});"
                                title="Remove from Wishlist">
                                <i class="fas fa-heart"></i>
                            </button>
                            
                            <!-- Original Wishlist Button (keeping for compatibility) -->
                            <button class="wishlist-btn active"
                                data-product-id="{{ $wishlist->product->id }}"
                                onclick="event.stopPropagation();">
                                <i class="fas text-danger fa-heart"></i>
                            </button>
                            <div class="product-image-container">
                                <img src="{{$wishlist->product->image ? $wishlist->product->image : '/default-product.png'}}"
                                    class="product-image"
                                    alt="{{ $wishlist->product->name }}">
                            </div>
                            <div class="product-info">
                                <a href="{{ route('product.details', $wishlist->product->slug) }}" class="product-title"
                                    style="text-decoration: none;">{{ $wishlist->product->name }}</a>
                                <p class="product-description">
                                    {{$wishlist->product->short_desc ? $wishlist->product->short_desc : ($wishlist->product->description ? Str::limit($wishlist->product->description, 80) : '')}}</p>
                                <div class="product-meta" style="margin-top:6px;">
                                    @php
                                        $avgRating = $wishlist->product->averageRating();
                                        $totalReviews = $wishlist->product->totalReviews();
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
                                    @if(isset($wishlist->product->discount) && $wishlist->product->discount > 0)
                                        <span class="fw-bold text-primary">
                                            {{ number_format($wishlist->product->discount, 2) }}৳
                                        </span>
                                        <span class="text-muted text-decoration-line-through ms-2">
                                            {{ number_format($wishlist->product->price, 2) }}৳
                                        </span>
                                    @else
                                        <span class="fw-bold text-primary">
                                            {{ number_format($wishlist->product->price, 2) }}৳
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    @php
                                        $hasStock = $wishlist->product->hasStock();
                                    @endphp
                                    <button class="btn-add-cart" data-product-id="{{ $wishlist->product->id }}" data-product-name="{{ $wishlist->product->name }}" data-has-stock="{{ $hasStock ? 'true' : 'false' }}"
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
                @empty
                    <div class="col-12">
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-heart" style="font-size: 4rem; color: #e9ecef;"></i>
                            </div>
                            <h4 class="mb-3" style="color: #6c757d;">Your Wishlist is Empty</h4>
                            <p class="text-muted mb-4">Discover amazing water filtration products and add them to your wishlist</p>
                            <a href="{{ route('product.archive') }}" class="btn btn-primary btn-lg"
                                style="background: #20c997; border: none; padding: 12px 30px;">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Action Buttons -->
            <div class="row mt-3 mb-0">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('product.archive') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"
        style="position: fixed; top: 24px; right: 24px; z-index: 16000; display: flex; flex-direction: column; gap: 10px;">
    </div>

    <!-- Custom CSS -->
    <style>
        /* Layout tweaks */
        .wishlist-page { padding-bottom: 12px; }
        .product-card { background:#fff; border:1px solid #eef0f2; border-radius:14px; overflow:hidden; box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
        .card:hover {
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .badge {
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #1ea085 !important;
            transform: translateY(-1px);
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .card-img-top {
            transition: transform 0.3s ease;
        }

        .card:hover .card-img-top {
            opacity: 0.9;
        }

        /* Product card image fixes to avoid odd padding/whitespace */
        .product-image-container { position: relative; overflow: hidden; border-radius: 12px; background: transparent; }
        .product-image { width: 100%; height: 260px; object-fit: cover; display: block; }
        .product-info { padding: 14px 16px 16px; }
        .product-title { display:block; margin-top: 6px; color:#222; font-weight:600; }
        .product-wishlist-top { position:absolute; top:12px; right:12px; }

        /* Badge + Clear button styling */
        .wishlist-count-badge {
            font-size: 13px;
            padding: 6px 12px;
            background: #e7f6ff;
            color: #0d6efd;
            border-radius: 999px;
            line-height: 1;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        .btn-clear-all {
            background: rgba(231, 13, 13, 0.08);
            color: #e20d0d;
            border: 1px solid rgba(226, 13, 13, 0.18);
        }
        .btn-clear-all:hover { background: rgba(231, 13, 13, 0.14); color: #b10a0a; }

        /* Theme colored outline button (Continue Shopping) */
        .wishlist-page .btn.btn-outline-primary {
            border-color: var(--primary-blue) !important;
            color: var(--primary-blue) !important;
        }
        .wishlist-page .btn.btn-outline-primary:hover,
        .wishlist-page .btn.btn-outline-primary:focus {
            background-color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            color: #fff !important;
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }

            .d-flex.justify-content-between .d-flex {
                justify-content: center;
            }
        }
    </style>
    @push('scripts')
        <script>
            // Toast function
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

            // Remove from wishlist functionality (client-side only UI update)
            document.querySelectorAll('.wishlist-btn, .product-wishlist-top').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    this.closest('.col-lg-3').remove();

                    // Update item count
                    const itemCount = document.querySelectorAll('.col-lg-3').length;
                    const badge = document.querySelector('.badge');
                    if (badge) {
                        badge.innerHTML = `<i class="fas fa-heart me-1"></i> ${itemCount} ${itemCount === 1 ? 'Item' : 'Items'}`;
                    }

                    // Show empty state if no items left
                    if (itemCount === 0) window.location.reload();
                });
            });

            // Product card click functionality
            document.querySelectorAll('.product-card[data-href]').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't navigate if clicking on buttons or links
                    if (e.target.closest('button') || e.target.closest('a')) {
                        return;
                    }
                    window.location.href = this.getAttribute('data-href');
                });
            });

            // No client-side clear-all binding; handled by form submit
        </script>
        <style>
            .custom-toast {
                min-width: 220px;
                max-width: 340px;
                background: #fff;
                color: #222;
                padding: 0;
                border-radius: 10px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.18);
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
            .custom-toast.error { border-left-color: #e53935; }
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
            .custom-toast .toast-close:hover { color: #e53935; }
            .custom-toast .toast-progress {
                position: absolute;
                left: 0; bottom: 0;
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
@endsection