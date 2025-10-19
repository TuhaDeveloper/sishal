@extends('ecommerce.master')

@section('main-section')
    <section class="featured-categories pb-3 pt-5">
        <div class="container container-80 featured-plain">
            <h2 class="section-title text-start">Our Products</h2>
            
        </div>
    </section>

    <div class="container container-80 py-4">
        <div class="row">
            <!-- Sidebar Filters -->
            <div id="filterForm" class="col-md-3 mb-4">
                <div class="filter-card">
                    <div class="filter-header">
                        <h5 class="filter-title">
                            <i class="fas fa-filter me-2"></i>Filters
                        </h5>
                        <button type="button" class="btn-clear-filters" id="clearFilters">
                            <i class="fas fa-times"></i> Clear All
                        </button>
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="filter-section">
                        <div class="filter-section-header" data-bs-toggle="collapse" data-bs-target="#categoryFilter" aria-expanded="true">
                            <h6 class="filter-section-title">
                                <i class="fas fa-tags me-2"></i>Category
                            </h6>
                            <i class="fas fa-chevron-down filter-chevron"></i>
                        </div>
                        <div class="collapse show" id="categoryFilter">
                            <div class="filter-options">
                                <div class="filter-option">
                                    <input class="filter-checkbox" type="checkbox" name="categories[]" id="catAll" value="all" {{ empty($selectedCategories) ? 'checked' : '' }}>
                                    <label class="filter-label" for="catAll">
                                        <span class="checkmark"></span>
                                        <span class="label-text">All Categories</span>
                                    </label>
                                </div>
                                @foreach ($categories as $category)
                                    <div class="filter-option">
                                        <input class="filter-checkbox" type="checkbox" name="categories[]" id="{{ $category->slug }}"
                                            value="{{ $category->slug }}" {{ in_array($category->slug, $selectedCategories ?? []) ? 'checked' : '' }}>
                                        <label class="filter-label" for="{{ $category->slug }}">
                                            <span class="checkmark"></span>
                                            <span class="label-text">{{ $category->name }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-section">
                        <div class="filter-section-header" data-bs-toggle="collapse" data-bs-target="#priceFilter" aria-expanded="true">
                            <h6 class="filter-section-title">
                                <i class="fas fa-dollar-sign me-2"></i>Price Range
                            </h6>
                            <i class="fas fa-chevron-down filter-chevron"></i>
                        </div>
                        <div class="collapse show" id="priceFilter">
                            <div class="price-filter-container">
                                <div class="price-inputs">
                                    <div class="price-input-group">
                                        <label class="price-label">Min</label>
                                        <input type="number" class="price-input" id="priceMinInput" 
                                               value="{{ $priceMin }}" min="0" max="{{ $maxProductPrice }}">
                                    </div>
                                    <div class="price-separator">-</div>
                                    <div class="price-input-group">
                                        <label class="price-label">Max</label>
                                        <input type="number" class="price-input" id="priceMaxInput" 
                                               value="{{ $priceMax }}" min="0" max="{{ $maxProductPrice }}">
                                    </div>
                                </div>
                                <div id="price-slider" class="price-slider"></div>
                                <input type="hidden" name="price_min" id="price_min" value="{{ $priceMin }}">
                                <input type="hidden" name="price_max" id="price_max" value="{{ $priceMax }}">
                                <div class="price-display">
                                    <span id="priceMinValue">{{ number_format($priceMin) }}৳</span>
                                    <span class="price-separator">to</span>
                                    <span id="priceMaxValue">{{ number_format($priceMax) }}৳</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <div class="filter-section">
                        <div class="filter-section-header" data-bs-toggle="collapse" data-bs-target="#ratingFilter" aria-expanded="true">
                            <h6 class="filter-section-title">
                                <i class="fas fa-star me-2"></i>Customer Rating
                            </h6>
                            <i class="fas fa-chevron-down filter-chevron"></i>
                        </div>
                        <div class="collapse show" id="ratingFilter">
                            <div class="filter-options">
                                @for ($i = 5; $i >= 1; $i--)
                                    <div class="filter-option">
                                        <input class="filter-checkbox" type="checkbox" name="rating[]" id="rating{{ $i }}" value="{{ $i }}" {{ in_array($i, $selectedRatings ?? []) ? 'checked' : '' }}>
                                        <label class="filter-label rating-label" for="rating{{ $i }}">
                                            <span class="checkmark"></span>
                                            <div class="rating-stars">
                                                @for ($j = 1; $j <= 5; $j++)
                                                    <i class="fa{{ $j <= $i ? 's' : 'r' }} fa-star {{ $j <= $i ? 'text-warning' : 'text-muted' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="label-text">& up</span>
                                        </label>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Product Grid -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div id="product-count">Showing {{ $products->count() }} products</div>
                    <div>
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
                    </div>
                </div>
                <div id="products-container" class="row g-4 mt-4">
                    @include('ecommerce.partials.product-grid', ['products' => $products])
                </div>
            </div>
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

        // AJAX Filtering Function
        function applyFilters() {
            var formData = new FormData();
            
            // Get selected categories
            var selectedCategories = [];
            document.querySelectorAll('#filterForm input[type=checkbox][name="categories[]"]:checked').forEach(function(cb) {
                if (cb.value !== 'all') {
                    selectedCategories.push(cb.value);
                }
            });
            selectedCategories.forEach(function(cat) {
                formData.append('categories[]', cat);
            });
            
            // Get price range
            var priceMin = document.getElementById('price_min') ? document.getElementById('price_min').value : '';
            var priceMax = document.getElementById('price_max') ? document.getElementById('price_max').value : '';
            if (priceMin) formData.append('price_min', priceMin);
            if (priceMax) formData.append('price_max', priceMax);
            
            // Get selected ratings
            document.querySelectorAll('#filterForm input[type=checkbox][name="rating[]"]:checked').forEach(function(cb) {
                formData.append('rating[]', cb.value);
            });
            
            // Get sort option
            var sortSelect = document.getElementById('sortSelect');
            if (sortSelect && sortSelect.value) {
                formData.append('sort', sortSelect.value);
            }
            
            // Show loading state
            var container = document.getElementById('products-container');
            var countElement = document.getElementById('product-count');
            if (container) {
                container.innerHTML = '<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading products...</p></div>';
            }
            
            // Make AJAX request
            fetch('{{ route("products.filter") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (container) {
                        container.innerHTML = data.html;
                    }
                    if (countElement) {
                        if (data.count === 0) {
                            countElement.textContent = 'No products found';
                        } else {
                            countElement.textContent = 'Showing ' + data.count + ' products';
                        }
                    }
                } else {
                    if (container) {
                        container.innerHTML = '<div class="col-12"><div class="no-products-container"><div class="no-products-icon"><i class="fas fa-search"></i></div><h3 class="no-products-title">No Products Found</h3><p class="no-products-message">We couldn\'t find any products matching your current filters.</p><div class="no-products-suggestion"><i class="fas fa-lightbulb"></i><span>Try adjusting your filters to see more products</span></div></div></div>';
                    }
                }
            })
            .catch(error => {
                console.error('Filter error:', error);
                if (container) {
                    container.innerHTML = '<div class="col-12"><div class="no-products-container"><div class="no-products-icon"><i class="fas fa-search"></i></div><h3 class="no-products-title">No Products Found</h3><p class="no-products-message">We couldn\'t find any products matching your current filters.</p><div class="no-products-suggestion"><i class="fas fa-lightbulb"></i><span>Try adjusting your filters to see more products</span></div></div></div>';
                }
            });
        }

        // Enhanced filter functionality
        window.initProductsPage = function() {
            try {
                // Initialize price slider
                var priceSlider = document.getElementById('price-slider');
                if (priceSlider && !priceSlider.noUiSlider && window.noUiSlider) {
                    var minValue = document.getElementById('priceMinValue');
                    var maxValue = document.getElementById('priceMaxValue');
                    var priceMinInput = document.getElementById('price_min');
                    var priceMaxInput = document.getElementById('price_max');
                    var priceMinDirectInput = document.getElementById('priceMinInput');
                    var priceMaxDirectInput = document.getElementById('priceMaxInput');
                    var maxProductPrice = {{ $maxProductPrice }};
                    
                    window.noUiSlider.create(priceSlider, {
                        start: [parseInt(priceMinInput.value), parseInt(priceMaxInput.value)],
                        connect: true,
                        step: 1,
                        range: { 'min': 0, 'max': maxProductPrice },
                        format: { 
                            to: function(v){ return Math.round(v); }, 
                            from: function(v){ return Number(v); } 
                        }
                    });
                    
                    priceSlider.noUiSlider.on('update', function (values) {
                        var minVal = Math.round(values[0]);
                        var maxVal = Math.round(values[1]);
                        
                        if (minValue) minValue.textContent = `${minVal.toLocaleString()}৳`;
                        if (maxValue) maxValue.textContent = `${maxVal.toLocaleString()}৳`;
                        if (priceMinInput) priceMinInput.value = minVal;
                        if (priceMaxInput) priceMaxInput.value = maxVal;
                        if (priceMinDirectInput) priceMinDirectInput.value = minVal;
                        if (priceMaxDirectInput) priceMaxDirectInput.value = maxVal;
                    });
                    
                    priceSlider.noUiSlider.on('change', function () {
                        // Auto-apply filters on slider change
                        setTimeout(function() {
                            applyFilters();
                        }, 300);
                    });
                }

                // Price input synchronization
                var priceMinDirectInput = document.getElementById('priceMinInput');
                var priceMaxDirectInput = document.getElementById('priceMaxInput');
                
                if (priceMinDirectInput) {
                    priceMinDirectInput.addEventListener('change', function() {
                        var value = Math.max(0, Math.min(parseInt(this.value) || 0, {{ $maxProductPrice }}));
                        this.value = value;
                        if (priceSlider && priceSlider.noUiSlider) {
                            var currentValues = priceSlider.noUiSlider.get();
                            priceSlider.noUiSlider.set([value, currentValues[1]]);
                        }
                    });
                }
                
                if (priceMaxDirectInput) {
                    priceMaxDirectInput.addEventListener('change', function() {
                        var value = Math.max(0, Math.min(parseInt(this.value) || 0, {{ $maxProductPrice }}));
                        this.value = value;
                        if (priceSlider && priceSlider.noUiSlider) {
                            var currentValues = priceSlider.noUiSlider.get();
                            priceSlider.noUiSlider.set([currentValues[0], value]);
                        }
                    });
                }

                // Enhanced category checkboxes with "All" logic
                var categoryCheckboxes = document.querySelectorAll('#filterForm input[type=checkbox][name="categories[]"]');
                var allCategoryCheckbox = document.getElementById('catAll');
                
                categoryCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        if (this === allCategoryCheckbox && this.checked) {
                            // Uncheck all other category checkboxes
                            categoryCheckboxes.forEach(function(cb) {
                                if (cb !== allCategoryCheckbox) {
                                    cb.checked = false;
                                }
                            });
                        } else if (this !== allCategoryCheckbox && this.checked) {
                            // Uncheck "All" if a specific category is selected
                            if (allCategoryCheckbox) {
                                allCategoryCheckbox.checked = false;
                            }
                        }
                        
                        // Check if no categories are selected, then check "All"
                        var hasSelectedCategory = Array.from(categoryCheckboxes).some(function(cb) {
                            return cb !== allCategoryCheckbox && cb.checked;
                        });
                        
                        if (!hasSelectedCategory && allCategoryCheckbox) {
                            allCategoryCheckbox.checked = true;
                        }
                        
                        // Auto-apply filters on category change
                        setTimeout(function() {
                            applyFilters();
                        }, 300);
                    });
                });

                // Rating checkboxes
                var ratingCheckboxes = document.querySelectorAll('#filterForm input[type=checkbox][name="rating[]"]');
                ratingCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        // Auto-apply filters on rating change
                        setTimeout(function() {
                            applyFilters();
                        }, 300);
                    });
                });

                // Clear filters functionality
                var clearFiltersBtn = document.getElementById('clearFilters');
                if (clearFiltersBtn) {
                    clearFiltersBtn.addEventListener('click', function() {
                        // Uncheck all checkboxes
                        document.querySelectorAll('#filterForm input[type=checkbox]').forEach(function(cb) {
                            cb.checked = false;
                        });
                        
                        // Check "All" category
                        if (allCategoryCheckbox) {
                            allCategoryCheckbox.checked = true;
                        }
                        
                        // Reset price range
                        var maxProductPrice = {{ $maxProductPrice }};
                        if (priceSlider && priceSlider.noUiSlider) {
                            priceSlider.noUiSlider.set([0, maxProductPrice]);
                        }
                        
                        // Apply filters with cleared values
                        applyFilters();
                    });
                }

                // Sort select
                var sortSelect = document.getElementById('sortSelect');
                if (sortSelect) {
                    sortSelect.addEventListener('change', function () {
                        applyFilters();
                    });
                }

                // Collapsible filter sections
                var filterHeaders = document.querySelectorAll('.filter-section-header');
                filterHeaders.forEach(function(header) {
                    header.addEventListener('click', function() {
                        var chevron = this.querySelector('.filter-chevron');
                        if (chevron) {
                            // Use CSS classes instead of direct transform override
                            if (this.getAttribute('aria-expanded') === 'true') {
                                chevron.classList.remove('rotated');
                            } else {
                                chevron.classList.add('rotated');
                            }
                        }
                    });
                });

            } catch(error) {
                console.error('Error initializing products page:', error);
            }
        };

        // Initialize on first load and after AJAX injections
        document.addEventListener('DOMContentLoaded', function(){ if (typeof window.initProductsPage === 'function') window.initProductsPage(); });
        window.addEventListener('pageshow', function(){ if (typeof window.initProductsPage === 'function') window.initProductsPage(); });

        // Remove any existing cart event listeners to prevent duplicates
        if (window.__productsCartEventListener) {
            document.removeEventListener('click', window.__productsCartEventListener);
        }

        // Cart functionality is now handled by global cart handler in master.blade.php
        // No need for duplicate event listeners here
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

        /* Filter chevron rotation */
        .filter-chevron {
            transition: transform 0.3s ease;
        }
        .filter-chevron.rotated {
            transform: rotate(180deg);
        }

        /* No Products Found Styles */
        .no-products-container {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, #F3F0FF 0%, #E3F2FD 100%);
            border-radius: 16px;
            margin: 20px 0;
            border: 1px solid #E3F2FD;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.1);
        }

        .no-products-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #8B5CF6 0%, #00512C 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
        }

        .no-products-icon i {
            font-size: 32px;
            color: white;
        }

        .no-products-title {
            font-size: 28px;
            font-weight: 700;
            color: #00512C;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .no-products-message {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 24px;
            line-height: 1.5;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .no-products-suggestion {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 12px 20px;
            border-radius: 25px;
            border: 1px solid #E3F2FD;
            box-shadow: 0 2px 10px rgba(0, 81, 44, 0.1);
            font-size: 14px;
            color: #00512C;
            font-weight: 500;
        }

        .no-products-suggestion i {
            color: #FCD34D;
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .no-products-container {
                padding: 40px 15px;
                margin: 15px 0;
            }

            .no-products-icon {
                width: 60px;
                height: 60px;
                margin-bottom: 20px;
            }

            .no-products-icon i {
                font-size: 24px;
            }

            .no-products-title {
                font-size: 24px;
                margin-bottom: 12px;
            }

            .no-products-message {
                font-size: 14px;
                margin-bottom: 20px;
            }

            .no-products-suggestion {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
@endpush