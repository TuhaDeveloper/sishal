@extends('ecommerce.master')

@section('main-section')
    <!-- Home: Left Category + Right Banner Slider -->
    <section class="home-hero py-4">
        <div class="container">
            <div class="row g-3 align-items-stretch">
                <!-- Left Category Menu -->
                <div class="col-lg-3">
                    <div class="category-menu">
                        <div class="menu-header">Category Menu</div>
                        <ul class="menu-list">
                            @foreach(($categories ?? []) as $category)
                            <li class="menu-item">
                                <a href="{{ route('product.archive') }}?category={{ $category->slug }}" class="menu-link">
                                    <span class="menu-icon menu-thumb">
                                        @if(!empty($category->image))
                                            <img src="{{ asset($category->image) }}" alt="{{ $category->name }}">
                                        @else
                                            <img src="https://via.placeholder.com/36x36?text=\u00A0" alt="placeholder">
                                        @endif
                                    </span>
                                    <span class="menu-text">{{ $category->name }}</span>
                                    @php $children = $category->children ?? ($category->subcategories ?? collect()); @endphp
                                    @if(!empty($children) && count($children))
                                        <span class="arrow">›</span>
                                    @endif
                                </a>
                                @if(!empty($children) && count($children))
                                <div class="submenu">
                                    @foreach($children as $child)
                                    <a href="{{ route('product.archive') }}?category={{ $child->slug }}" class="submenu-link">
                                        <span class="submenu-thumb">
                                            @if(!empty($child->image))
                                                <img src="{{ asset($child->image) }}" alt="{{ $child->name }}">
                                            @else
                                                <img src="https://via.placeholder.com/28x28?text=\u00A0" alt="placeholder">
                                            @endif
                                        </span>
                                        <span class="submenu-text">{{ $child->name }}</span>
                                    </a>
                                    @endforeach
                                </div>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- Right Banner Slider (admin managed) -->
                <div class="col-lg-9">
                    <div id="homeHeroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="5000">
                        <div class="carousel-inner">
                            @if(!empty($banners) && count($banners) > 0)
                                @foreach($banners as $index => $banner)
                                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                    @if($banner->link_url)
                                        <a href="{{ $banner->link_url }}" target="_blank" class="d-block">
                                            <img src="{{ $banner->image_url }}" class="d-block w-100 rounded-4 hero-slide-img" alt="{{ $banner->title }}">
                                        </a>
                                    @else
                                        <img src="{{ $banner->image_url }}" class="d-block w-100 rounded-4 hero-slide-img" alt="{{ $banner->title }}">
                                    @endif
                                    @if($banner->title || $banner->description)
                                        <div class="carousel-caption d-none d-md-block">
                                            @if($banner->title)
                                                <h5 class="text-white">{{ $banner->title }}</h5>
                                            @endif
                                            @if($banner->description)
                                                <p class="text-white">{{ $banner->description }}</p>
                                            @endif
                                            @if($banner->link_url && $banner->link_text)
                                                <a href="{{ $banner->link_url }}" target="_blank" class="btn btn-primary">{{ $banner->link_text }}</a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <div class="carousel-item active">
                                    <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=1800&auto=format&fit=crop" class="d-block w-100 rounded-4 hero-slide-img" alt="fallback-1">
                                </div>
                                <div class="carousel-item">
                                    <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?q=80&w=1800&auto=format&fit=crop" class="d-block w-100 rounded-4 hero-slide-img" alt="fallback-2">
                                </div>
                            @endif
                        </div>
                        
                        @if(!empty($banners) && count($banners) > 1)
                        <!-- Carousel Indicators -->
                        <div class="carousel-indicators">
                            @foreach($banners as $index => $banner)
                            <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="{{ $index }}" 
                                    class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" 
                                    aria-label="Slide {{ $index + 1 }}"></button>
                            @endforeach
                        </div>
                        @endif
                        
                        <!-- Carousel Controls -->
                        @if(!empty($banners) && count($banners) > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Categories (compact scroller) -->
    <section class="popular-categories">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Categories</h2>
                <a href="{{ route('categories') }}" class="view-more-btn">View More</a>
            </div>

            <div class="category-scroller">
                @forelse ($featuredCategories as $category)
                    <a href="{{ route('product.archive') }}?category={{ $category->slug }}" class="category-chip">
                        <div class="chip-thumb">
                            <img src="{{ asset($category->image) }}" alt="{{ $category->name }}">
                        </div>
                        <div class="chip-title">{{ $category->name }}</div>
                    </a>
                @empty
                    @foreach([['Electronics'],['Appliances'],['Fashion'],['Beauty'],['Sports']] as $fallback)
                        <a href="#" class="category-chip">
                            <div class="chip-thumb placeholder"></div>
                            <div class="chip-title">{{ $fallback[0] }}</div>
                        </a>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    <!-- Top Selling Products -->
    <section class="top-products">
        <div class="container">
            <div class="d-flex justify-content-between mb-5">
                <div class="">
                    <h2 class="section-title mb-0 text-start mb-2">Top Selling Products</h2>
                    
                </div>
                <a href="{{ route('product.archive') }}" class="btn btn-outline-custom">View All Products</a>
            </div>

            <div class="row product-grid" id="mostSoldProductsContainer">
                <!-- Products will be loaded here by jQuery -->
            </div>
        </div>
    </section>


    

    <!-- Vlogs -->
    <!-- <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between mb-5">
                <div class="">
                    <h2 class="section-title mb-0 text-start mb-2">Latest Vlogs</h2>
                    <p class="section-subtitle text-start mb-0">Watch our latest tips and installs</p>
                </div>
            </div>
            <div class="row">
                @forelse($vlogs as $vlog)
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="ratio ratio-16x9">
                                {!! $vlog->frame_code !!}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted">No vlogs available.</div>
                @endforelse
            </div>
        </div>
    </section> -->


    <div id="toast-container"
        style="position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;">
    </div>

    <!-- Video Modal -->
    <!-- <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" style="max-width:90vw;">
            <div class="modal-content" style="height:90vh;">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 d-flex justify-content-center align-items-center"
                    style="height:calc(90vh - 56px);">
                    <div class="ratio ratio-16x9 w-100 h-100">
                        <iframe id="youtubeVideo" width="100%" height="100%" src="" title="YouTube video player"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

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
            z-index: 9999;
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

        .wishlist-btn i.fa-heart.active {
            color: #e53935 !important;
        }
    </style>
@endsection

@push('scripts')
    <script>
        window.showToast = function (message, type = 'success') {
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
        $(function () {
            $.get('/api/products/most-sold', function (products) {
                var container = $('#mostSoldProductsContainer');
                container.empty();
                if (!products.length) {
                    container.append('<div class="col-12 text-center text-muted">No products found.</div>');
                    return;
                }
                products.forEach(function (product) {
                    const rating = product.avg_rating ?? product.rating ?? 0;
                    const price = parseFloat(product.price || 0).toFixed(2);
                    const image = product.image ? product.image : '/default-product.png';
                    container.append(`
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="product-card position-relative" data-href="/product/${product.slug}">
                                    <button class="wishlist-btn${product.is_wishlisted ? ' active' : ''}" data-product-id="${product.id}">
                                        <i class="${product.is_wishlisted ? 'fas text-danger' : 'far'} fa-heart"></i>
                                    </button>
                                    <div class="product-image-container">
                                        <img src="${image}" class="product-image" alt="${product.name}">
                                    </div>
                                    <div class="product-info">
                                        <a href="/product/${product.slug}" style="text-decoration: none" class="product-title">${product.name}</a>
                                        <div class=\"product-meta\">
                                            <div class=\"stars\" aria-label=\"${rating} out of 5\">${Array.from({length:5}).map((_,i)=>`<i class=\\\"fa${i < Math.round(rating) ? 's' : 'r'} fa-star\\\"></i>`).join('')}</div>
                                        </div>
                                        <div class="price">${price}৳</div>
                                        <div class="d-flex justify-content-between align-items-center gap-2 product-actions">
                                            <button class="btn-add-cart" data-product-id="${product.id}" data-product-name="${product.name}"><svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff" width="14" height="14">
                                <path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z"></path>
                                <circle cx="7" cy="22" r="2"></circle>
                                <circle cx="17" cy="22" r="2"></circle>
                            </svg> Add to Cart</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);
                });
            }).fail(function () {
                $('#mostSoldProductsContainer').html('<div class="col-12 text-center text-danger">Failed to load products.</div>');
            });

            // Make product-card clickable but ignore clicks from interactive children
            $(document).on('click', '.product-card', function (e) {
                const interactive = ['A', 'BUTTON', 'SVG', 'PATH', 'FORM', 'INPUT', 'SELECT', 'TEXTAREA', 'LABEL'];
                if (interactive.includes(e.target.tagName)) return;
                const href = $(this).data('href');
                if (href) window.location.href = href;
            });

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
                            showToast(response.message, 'success');
                        }
                    }
                });
            });
        });

        // Safe modal initialization utility
        function safeModalInit(modalId, options = {}) {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.warn(`Modal element with id '${modalId}' not found`);
                return null;
            }
            
            try {
                return new bootstrap.Modal(modalElement, options);
            } catch (error) {
                console.error(`Failed to initialize modal '${modalId}':`, error);
                return null;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var playBtn = document.getElementById('playVideoBtn');
            var videoModalElement = document.getElementById('videoModal');
            var youtubeVideo = document.getElementById('youtubeVideo');
            var YOUTUBE_URL = 'https://www.youtube.com/embed/np0FD080?autoplay=1'; // Replace YOUR_VIDEO_ID

            // Only initialize modal if the elements exist
            if (playBtn && videoModalElement && youtubeVideo) {
                var videoModal = safeModalInit('videoModal');

                if (videoModal) {
                    playBtn.addEventListener('click', function () {
                        youtubeVideo.src = YOUTUBE_URL;
                        videoModal.show();
                    });
                    
                    videoModalElement.addEventListener('hidden.bs.modal', function () {
                        youtubeVideo.src = '';
                    });
                }
            } else {
                console.log('Video modal elements not found - modal functionality disabled');
            }
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
            z-index: 9999;
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

        .wishlist-btn i.fa-heart.active {
            color: #e53935 !important;
        }
    </style>
@endpush