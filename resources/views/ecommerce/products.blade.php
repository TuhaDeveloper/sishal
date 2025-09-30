@extends('ecommerce.master')

@section('main-section')
    <style>
        .noUi-connect { background-color: var(--primary-blue) !important; }
        .wishlist-btn i.fa-heart.active { color: #e53935 !important; }
    </style>
    <section class="featured-categories pb-3 pt-5">
        <div class="container container-80 featured-plain">
            <h2 class="section-title text-start">Our Products</h2>
            
        </div>
    </section>

    <div class="container container-80 py-4">
        <div class="row">
            <!-- Sidebar Filters -->
            <form id="filterForm" method="GET" class="col-md-3 mb-4">
                <div class="card border-0 shadow-sm p-3">
                    <h5 class="fw-bold mb-3">Filters</h5>
                    <div class="mb-4">
                        <h6 class="fw-semibold">Category</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="categories[]" id="catAll" value="all" {{ empty($selectedCategories) ? 'checked' : '' }}>
                            <label class="form-check-label" for="catAll">All</label>
                        </div>
                        @foreach ($categories as $category)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="categories[]" id="{{ $category->slug }}"
                                    value="{{ $category->slug }}" {{ in_array($category->slug, $selectedCategories ?? []) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $category->slug }}">{{ $category->name }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-semibold">Price Range</h6>
                        <div id="price-slider" class="mx-3"></div>
                        <input type="hidden" name="price_min" id="price_min" value="{{ $priceMin }}">
                        <input type="hidden" name="price_max" id="price_max" value="{{ $priceMax }}">
                        <div class="d-flex justify-content-between mt-2">
                            <span id="priceMinValue">{{ $priceMin }}৳</span>
                            <span id="priceMaxValue">{{ $priceMax }}৳</span>
                        </div>
                        <div class="text-end small text-muted">Max: {{ $maxProductPrice }}৳</div>
                    </div>
                    <div>
                        <h6 class="fw-semibold">Rating</h6>
                        @for ($i = 4; $i >= 1; $i--)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rating{{ $i }}">
                                <label class="form-check-label" for="rating{{ $i }}">
                                    @for ($j = 1; $j <= 5; $j++)
                                        <i class="fa{{ $j <= $i ? 's' : 'r' }} fa-star text-warning"></i>
                                    @endfor
                                    &amp; up
                                </label>
                            </div>
                        @endfor
                    </div>
                </div>
            </form>

            <!-- Product Grid -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>Showing {{ $products->count() }} products</div>
                    <div>
                        <form id="sortForm" method="GET">
                            @foreach(request()->except('sort') as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <select class="form-select form-select-sm" style="width:auto;display:inline-block;" name="sort"
                                id="sortSelect">
                                <option value="">Sort By</option>
                                <option value="newest" {{ $selectedSort == 'newest' ? 'selected' : '' }}>Newest</option>
                                <option value="featured" {{ $selectedSort == 'featured' ? 'selected' : '' }}>Featured</option>
                                <option value="lowToHigh" {{ $selectedSort == 'lowToHigh' ? 'selected' : '' }}>Price: Low to
                                    High</option>
                                <option value="highToLow" {{ $selectedSort == 'highToLow' ? 'selected' : '' }}>Price: High to
                                    Low</option>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="row g-4 mt-4">
                    @foreach($products as $product)
                        <div class="col-lg-3 col-md-6 mt-0 mb-4">
                            <div class="product-card position-relative mb-0 h-100">
                                <button class="wishlist-btn {{$product->is_wishlisted ? ' active' : ''}}"
                                    data-product-id="{{ $product->id }}">
                                    <i class="{{ $product->is_wishlisted ? 'fas text-danger' : 'far' }} fa-heart"></i>
                                </button>
                                <div class="product-image-container">
                                    <img src="{{$product->image ? $product->image : '/default-product.png'}}"
                                        class="product-image" style="width:100%;height:200px;object-fit:cover;"
                                        alt="${product.name}">
                                </div>
                                <div class="product-info">
                                    <a href="{{ route('product.details', $product->slug) }}" class="product-title"
                                        style="text-decoration: none;">{{ $product->name }}</a>
                                    <p class="product-description">{{$product->short_desc ? $product->short_desc : ''}}</p>

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
                                        <button class="btn-add-cart" data-product-id="{{ $product->id }}"><svg
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
        // Reusable initializer so it runs after AJAX navigation
        window.initProductsPage = function() {
            try {
                var priceSlider = document.getElementById('price-slider');
                if (priceSlider && !priceSlider.noUiSlider && window.noUiSlider) {
                    var minValue = document.getElementById('priceMinValue');
                    var maxValue = document.getElementById('priceMaxValue');
                    var priceMinInput = document.getElementById('price_min');
                    var priceMaxInput = document.getElementById('price_max');
                    var maxProductPrice = {{ $maxProductPrice }};
                    window.noUiSlider.create(priceSlider, {
                        start: [parseInt(priceMinInput.value), parseInt(priceMaxInput.value)],
                        connect: true,
                        step: 1,
                        range: { 'min': 0, 'max': maxProductPrice },
                        format: { to: function(v){ return Math.round(v); }, from: function(v){ return Number(v); } }
                    });
                    priceSlider.noUiSlider.on('update', function (values) {
                        if (minValue) minValue.textContent = `${values[0]}৳`;
                        if (maxValue) maxValue.textContent = `${values[1]}৳`;
                        if (priceMinInput) priceMinInput.value = values[0];
                        if (priceMaxInput) priceMaxInput.value = values[1];
                    });
                    priceSlider.noUiSlider.on('change', function () {
                        var form = document.getElementById('filterForm');
                        if (form) form.submit();
                    });
                }

                // Category checkboxes
                document.querySelectorAll('#filterForm input[type=checkbox][name="categories[]"]').forEach(function (checkbox) {
                    checkbox.onchange = function () {
                        var form = document.getElementById('filterForm');
                        if (form) form.submit();
                    };
                });

                // Sort select
                var sortSelect = document.getElementById('sortSelect');
                if (sortSelect) {
                    sortSelect.onchange = function () {
                        var form = document.getElementById('sortForm');
                        if (form) form.submit();
                    };
                }
            } catch(_) {}
        };

        // Initialize on first load and after AJAX injections
        document.addEventListener('DOMContentLoaded', function(){ if (typeof window.initProductsPage === 'function') window.initProductsPage(); });
        window.addEventListener('pageshow', function(){ if (typeof window.initProductsPage === 'function') window.initProductsPage(); });

        // Delegated Add to Cart (works after AJAX)
        document.addEventListener('click', function(e){
            var btn = e.target && e.target.closest('.btn-add-cart');
            if (!btn) return;
            e.preventDefault();
            var productId = btn.getAttribute('data-product-id');
            btn.disabled = true;
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
                btn.disabled = false;
                if (data.success) {
                    showToast('Added to cart!');
                    if (typeof updateCartQtyBadge === 'function') updateCartQtyBadge();
                } else {
                    showToast('Could not add to cart.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                showToast('Could not add to cart.', 'error');
            });
        });
        document.addEventListener('click', function(e){
            var btn = e.target && e.target.closest('.wishlist-btn');
            if (!btn) return;
            e.preventDefault();
            var productId = btn.getAttribute('data-product-id');
            if (!window.jQuery) return;
            window.jQuery.ajax({
                url: '/add-remove-wishlist/' + productId,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                success: function (response) {
                    if (response.success) {
                        btn.classList.toggle('active');
                        showToast(response.message, 'success');
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
    </style>
@endpush