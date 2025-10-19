@extends('erp.master')

@section('title', 'Point of Sale')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0 text-primary fw-bold">
                        <i class="fas fa-cash-register me-2"></i>Point of Sale
                    </h4>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-outline-primary me-2" onclick="clearCart()">
                        <i class="fas fa-trash-alt me-1"></i>Clear Cart
                    </button>
                    <button class="btn btn-success" onclick="processPayment()">
                        <i class="fas fa-credit-card me-1"></i>Process Payment
                    </button>
                </div>
            </div>
        </div>

        <!-- Main POS Content -->
        <div class="container-fluid px-4 py-4">
            <div class="row">
                <!-- Products Section -->
                <div class="col-lg-8">
                    <!-- Search and Filter -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body py-3">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white" style="padding: 10px;">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0"
                                            placeholder="Search products..." id="searchInput">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select class="form-select" id="categoryFilter">
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="form-select" id="sortBy">
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="row" id="productsGrid">
                        <!-- Product Cards will be populated here -->
                    </div>
                </div>

                <!-- Cart Section -->
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 120px;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="cart-items" id="cartItems" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-center py-5 text-muted" id="emptyCart">
                                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                    <p>Your cart is empty</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold">Total:</span>
                                <span class="fw-bold text-primary h5 mb-0" id="cartTotal">0.00৳</span>
                            </div>
                            <button class="btn btn-success w-100" id="openCheckoutDrawer">
                                <i class="fas fa-credit-card me-2"></i>Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img src="" alt="" class="img-fluid rounded" id="modalProductImage">
                        </div>
                        <div class="col-md-6">
                            <h4 id="modalProductName"></h4>
                            <p class="text-muted" id="modalProductCategory"></p>
                            <p id="modalProductDescription"></p>
                            <h5 class="text-primary">
                                <span id="modalDiscountPrice"></span>
                                <span style="font-weight: 400; color: #6c757d">
                                    <del id="modalProductPrice"></del>
                                </span>
                            </h5>
                            <div class="input-group mb-3" style="width: 150px;">
                                <button class="btn btn-outline-secondary" onclick="decrementQuantity()">-</button>
                                <input type="number" class="form-control text-center" value="1" min="1" id="modalQuantity">
                                <button class="btn btn-outline-secondary" onclick="incrementQuantity()">+</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addToCartFromModal()">
                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('erp.pos.components.checkout-drawer')

    <style>
        .product-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #007bff;
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }

        .product-category {
            background: #f8f9fa;
            color: #6c757d;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quantity-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quantity-btn:hover {
            background: #e9ecef;
        }

        .search-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .select2-container--open {
            z-index: 3000 !important;
        }

        .select2-selection {
            border: 2px solid #e5e7eb;
            padding: 16px 16px 16px 48px;
            height: 100% !important;
            border-radius: 12px;
            font-size: 14px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Global variables
        let products = [];
        let cart = [];
        let selectedProduct = null;
        let currentBranchId = null;

        // Initialize the page
        $(document).ready(function () {
            // Clear cartItems from sessionStorage after successful POS sale (do this FIRST)
            if ($('.alert-success').length > 0) {
                sessionStorage.setItem('cartItems', '[]');
            }
            $('#drawerBranchSelect').val(currentBranchId);
            
            setupEventListeners();
            // Set initial branch ID from the first option
            const branchSelect = $('#sortBy');
            if (branchSelect.val()) {
                currentBranchId = branchSelect.val();
                loadProductsFromAPI();
                $('#drawerBranchSelect').val(currentBranchId);
            }
            const storedCart = sessionStorage.getItem('cartItems');
            if (storedCart) {
                try {
                    cart = JSON.parse(storedCart);
                    updateCartDisplay();
                } catch (e) {
                    cart = [];
                }
            }
            if ($('#productPagination').length === 0) {
                $('#productsGrid').after('<div id="productPagination"></div>');
            }
        });

        function setupEventListeners() {
            $('#searchInput').on('input', debounce(filterProducts, 300));
            $('#categoryFilter').on('change', filterProducts);
            $('#sortBy').on('change', function () {
                // Set cartItems in sessionStorage to an empty array when branch is changed
                sessionStorage.clear();
                currentBranchId = $(this).val();
                loadProductsFromAPI();
                clearCart()
            });
        }

        // Load products from API based on selected branch
        function loadProductsFromAPI(page = 1) {
            if (!currentBranchId) return;

            const searchTerm = $('#searchInput').val();
            const categoryId = $('#categoryFilter').val();

            // Show loading state
            const $grid = $('#productsGrid');
            $grid.html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading products...</p></div>');

            // Build query parameters
            const params = new URLSearchParams();
            if (searchTerm) params.append('search', searchTerm);
            if (categoryId) params.append('category_id', categoryId);
            if (page) params.append('page', page);

            // Make API call using jQuery AJAX
            $.ajax({
                url: `/erp/products/search-with-filters/${currentBranchId}?${params.toString()}`,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    // If paginated (search), data will have .data and meta
                    if (data.data && data.current_page) {
                        products = data.data;
                        renderProducts(products, {
                            current_page: data.current_page,
                            last_page: data.last_page
                        });
                    } else {
                        products = data;
                        renderProducts(products);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading products:', error);
                    $grid.html('<div class="col-12 text-center py-5 text-danger"><i class="fas fa-exclamation-triangle fa-2x"></i><p class="mt-2">Error loading products</p></div>');
                }
            });
        }

        // Debounce function to limit API calls
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function renderProducts(productsToRender = products, pagination = null) {
            const $grid = $('#productsGrid');
            $grid.empty();

            productsToRender.forEach(product => {
                const $productCard = createProductCard(product);
                $grid.append($productCard);
            });

            // Render pagination controls if pagination meta is present
            $('#productPagination').remove();
            if (pagination) {
                const $pagination = $('<div id="productPagination" class="d-flex justify-content-center my-3"></div>');
                const prevDisabled = pagination.current_page === 1 ? 'disabled' : '';
                const nextDisabled = pagination.current_page === pagination.last_page ? 'disabled' : '';
                $pagination.append(`<button class="btn btn-outline-primary mx-1" id="prevPageBtn" ${prevDisabled}>Prev</button>`);
                $pagination.append(`<span class="mx-2 align-self-center">Page ${pagination.current_page} of ${pagination.last_page}</span>`);
                $pagination.append(`<button class="btn btn-outline-primary mx-1" id="nextPageBtn" ${nextDisabled}>Next</button>`);
                $grid.after($pagination);

                // Pagination button handlers
                $('#prevPageBtn').off('click').on('click', function () {
                    if (pagination.current_page > 1) {
                        loadProductsFromAPI(pagination.current_page - 1);
                    }
                });
                $('#nextPageBtn').off('click').on('click', function () {
                    if (pagination.current_page < pagination.last_page) {
                        loadProductsFromAPI(pagination.current_page + 1);
                    }
                });
            }
        }

        function createProductCard(product) {
            const $col = $('<div>').addClass('col-md-6 col-lg-4 mb-4');

            // Handle image path
            const imageSrc = product.image ? `/${product.image}` : 'https://via.placeholder.com/300x200?text=No+Image';

            // Handle category name
            const categoryName = product.category ? product.category.name : 'Uncategorized';

            // Handle stock information
            const stockInfo = product.branch_stock ?
                `<small class="text-success">Stock: ${product.branch_stock.quantity}</small>` :
                '<small class="text-danger">Out of Stock</small>';

            const cardHtml = `
                            <div class="card product-card fade-in" data-product-id="${product.id}">
                                <img src="${imageSrc}" class="card-img-top product-image" alt="${product.name}" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">${product.name}</h6>
                                        <span class="product-category">${categoryName}</span>
                                    </div>
                                    <p class="card-text text-muted small" style="height: 48px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">${product.description || 'No description available'}</p>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="product-price">
                                            ${parseFloat(product.discount ?? product.price).toFixed(2)}৳
                                            ${product.discount && product.discount < product.price ? `<del style="color:rgb(179, 172, 172)">${parseFloat(product.price).toFixed(2)}৳</del>` : ''}
                                        </span>
                                        ${stockInfo}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">SKU: ${product.sku}</small>
                                        <button class="btn btn-primary btn-sm add-to-cart-btn" data-product-id="${product.id}" ${(!product.branch_stock || product.branch_stock.quantity <= 0) ? 'disabled' : ''}>
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;

            $col.html(cardHtml);

            // Add click event for product modal
            $col.find('.product-card').on('click', function () {
                const productId = $(this).data('product-id');
                showProductModal(productId);
            });

            // Add click event for add to cart button
            $col.find('.add-to-cart-btn').on('click', function (e) {
                e.stopPropagation();
                const productId = $(this).data('product-id');
                addToCart(productId);
            });

            return $col;
        }

        function showProductModal(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;

            // Store in session storage
            sessionStorage.setItem('lastViewedProduct', JSON.stringify(product));

            selectedProduct = product;

            // Handle image path
            const imageSrc = product.image ? `/${product.image}` : 'https://via.placeholder.com/300x200?text=No+Image';
            $('#modalProductImage').attr('src', imageSrc).on('error', function () {
                $(this).attr('src', 'https://via.placeholder.com/300x200?text=No+Image');
            });

            $('#modalProductName').text(product.name);
            $('#modalProductCategory').text(product.category ? product.category.name : 'Uncategorized');
            $('#modalProductDescription').text(product.description || 'No description available');
            if (product.discount && product.discount < product.price) {
                $('#modalDiscountPrice').text(`${parseFloat(product.discount).toFixed(2)}৳`);
                $('#modalProductPrice').text(`${parseFloat(product.price).toFixed(2)}৳`).show();
            } else {
                $('#modalDiscountPrice').text(`${parseFloat(product.price).toFixed(2)}৳`);
                $('#modalProductPrice').hide();
            }
            $('#modalQuantity').val(1);

            // Add stock information to modal
            const stockInfo = product.branch_stock ?
                `<p class="text-success"><i class="fas fa-box me-1"></i>Available Stock: ${product.branch_stock.quantity}</p>` :
                '<p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Out of Stock</p>';

            // Add SKU information
            const skuInfo = `<p class="text-muted"><i class="fas fa-barcode me-1"></i>SKU: ${product.sku}</p>`;

            // Insert stock and SKU info after description
            $('#modalProductDescription').after(stockInfo + skuInfo);

            const $modal = $('#productModal');
            $modal.modal('show');

            // Clear additional info when modal is hidden
            $modal.off('hidden.bs.modal').on('hidden.bs.modal', function () {
                // Remove stock and SKU info
                $modal.find('p.text-success, p.text-danger, p.text-muted').each(function () {
                    const text = $(this).text();
                    if (text.includes('Available Stock') || text.includes('Out of Stock') || text.includes('SKU:')) {
                        $(this).remove();
                    }
                });
            });
        }

        function addToCart(productId, quantity = 1) {
            const product = products.find(p => p.id === productId);
            if (!product) return;

            // Store in session storage
            sessionStorage.setItem('lastAddedProduct', JSON.stringify(product));

            // Check stock availability
            if (!product.branch_stock || product.branch_stock.quantity <= 0) {
                showToast('Product is out of stock!', 'warning');
                return;
            }

            const existingItem = cart.find(item => item.id === productId);

            if (existingItem) {
                const newQuantity = existingItem.quantity + quantity;
                // Check if new quantity exceeds available stock
                if (newQuantity > product.branch_stock.quantity) {
                    showToast(`Only ${product.branch_stock.quantity} items available in stock!`, 'warning');
                    return;
                }
                existingItem.quantity = newQuantity;
            } else {
                // Check if quantity exceeds available stock
                if (quantity > product.branch_stock.quantity) {
                    showToast(`Only ${product.branch_stock.quantity} items available in stock!`, 'warning');
                    return;
                }
                cart.push({
                    ...product,
                    quantity: quantity
                });
            }

            updateCartDisplay();

            // Show success message
            showToast('Product added to cart!', 'success');
        }

        function addToCartFromModal() {
            if (!selectedProduct) return;

            const quantity = parseInt($('#modalQuantity').val());

            // Validate quantity
            if (quantity <= 0) {
                showToast('Please enter a valid quantity!', 'warning');
                return;
            }

            addToCart(selectedProduct.id, quantity);

            $('#productModal').modal('hide');
        }

        function updateCartDisplay() {
            const $cartItems = $('#cartItems');
            const $emptyCart = $('#emptyCart');
            const $cartTotal = $('#cartTotal');
            const $checkoutBtn = $('#checkoutBtn');

            if (cart.length === 0) {
                $emptyCart.show();
                $cartItems.html('<div class="text-center py-5 text-muted" id="emptyCart"><i class="fas fa-shopping-cart fa-3x mb-3"></i><p>Your cart is empty</p></div>');
                $cartTotal.text('0.00৳');
                $checkoutBtn.prop('disabled', true);
                return;
            }

            $emptyCart.hide();
            $checkoutBtn.prop('disabled', false);

            let total = 0;
            $cartItems.empty();

            cart.forEach(item => {
                // Use discount price if available and less than original price
                const useDiscount = item.discount && Number(item.discount) < Number(item.price);
                const displayPrice = useDiscount ? Number(item.discount) : Number(item.price);
                const originalPrice = Number(item.price);
                const itemTotal = displayPrice * item.quantity;
                total += itemTotal;

                const $cartItem = $(`
                                <div class="cart-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">${item.name}</h6>
                                            <small class="text-muted">
                                                ${displayPrice.toFixed(2)}৳ each
                                                ${useDiscount ? `<del style='color:rgb(179, 172, 172); margin-left:4px;'>$${originalPrice.toFixed(2)}</del>` : ''}
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="quantity-controls me-2">
                                                <button class="quantity-btn decrease-qty" data-product-id="${item.id}">-</button>
                                                <span class="mx-2">${item.quantity}</span>
                                                <button class="quantity-btn increase-qty" data-product-id="${item.id}">+</button>
                                            </div>
                                            <button class="btn btn-outline-danger btn-sm remove-item" data-product-id="${item.id}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="text-end mt-2">
                                        <strong>${itemTotal.toFixed(2)}৳</strong>
                                    </div>
                                </div>
                            `);

                // Add event listeners for quantity controls
                $cartItem.find('.decrease-qty').on('click', function () {
                    const productId = $(this).data('product-id');
                    updateQuantity(productId, -1);
                });

                $cartItem.find('.increase-qty').on('click', function () {
                    const productId = $(this).data('product-id');
                    updateQuantity(productId, 1);
                });

                $cartItem.find('.remove-item').on('click', function () {
                    const productId = $(this).data('product-id');
                    removeFromCart(productId);
                });

                $cartItems.append($cartItem);
            });

            $cartTotal.text(`${total.toFixed(2)}৳`);
            sessionStorage.setItem('cartItems', JSON.stringify(cart));
        }

        function updateQuantity(productId, change) {
            const item = cart.find(item => item.id === productId);
            if (!item) return;

            const newQuantity = item.quantity + change;

            if (newQuantity <= 0) {
                removeFromCart(productId);
            } else {
                // Check if new quantity exceeds available stock
                const product = products.find(p => p.id === productId);
                if (product && product.branch_stock && newQuantity > product.branch_stock.quantity) {
                    showToast(`Only ${product.branch_stock.quantity} items available in stock!`, 'warning');
                    return;
                }

                item.quantity = newQuantity;
                updateCartDisplay();
            }
        }

        function removeFromCart(productId) {
            cart = cart.filter(item => item.id !== productId);
            updateCartDisplay();
            showToast('Product removed from cart', 'info');
        }

        function clearCart() {
            cart = [];
            updateCartDisplay();
            showToast('Cart cleared', 'info');
        }

        function processPayment() {
            if (cart.length === 0) return;

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

            // Here you would integrate with your payment processing logic
            alert(`Processing payment for ${total.toFixed(2)}৳\n\nItems:\n${cart.map(item => `${item.name} x${item.quantity}`).join('\n')}`);

            clearCart();
            showToast('Payment processed successfully!', 'success');
        }

        function filterProducts() {
            // Reload products from API with current filters
            loadProductsFromAPI();
        }

        function sortProducts() {
            const sortBy = $('#sortBy').val();
            const $grid = $('#productsGrid');
            const $productCards = $grid.children();

            const sortedCards = $productCards.sort((a, b) => {
                const productAId = $(a).find('.product-card').data('product-id');
                const productBId = $(b).find('.product-card').data('product-id');

                const productA = products.find(p => p.id === productAId);
                const productB = products.find(p => p.id === productBId);

                switch (sortBy) {
                    case 'price':
                        return productA.price - productB.price;
                    case 'category':
                        return productA.category.localeCompare(productB.category);
                    default:
                        return productA.name.localeCompare(productB.name);
                }
            });

            $grid.empty().append(sortedCards);
        }

        function incrementQuantity() {
            const $input = $('#modalQuantity');
            $input.val(parseInt($input.val()) + 1);
        }

        function decrementQuantity() {
            const $input = $('#modalQuantity');
            const currentVal = parseInt($input.val());
            if (currentVal > 1) {
                $input.val(currentVal - 1);
            }
        }

        function showToast(message, type = 'info') {
            // Create toast notification
            const $toast = $(`
                            <div class="alert alert-${type} position-fixed" style="top: 20px; right: 20px; z-index: 16000; min-width: 250px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-${getToastIcon(type)} me-2"></i>
                                    ${message}
                                </div>
                            </div>
                        `);

            $('body').append($toast);

            // Remove toast after 3 seconds
            setTimeout(() => {
                $toast.remove();
            }, 3000);
        }

        function getToastIcon(type) {
            switch (type) {
                case 'success': return 'check-circle';
                case 'warning': return 'exclamation-triangle';
                case 'danger': return 'times-circle';
                default: return 'info-circle';
            }
        }

        // Additional jQuery event handlers for modal buttons
        $(document).on('click', '#addToCartFromModal', addToCartFromModal);
        $(document).on('click', '#incrementQuantity', incrementQuantity);
        $(document).on('click', '#decrementQuantity', decrementQuantity);
        $(document).on('click', '#clearCart', clearCart);
        $(document).on('click', '#checkoutBtn', processPayment);

        // Drawer open/close logic
        $(document).on('click', '#openCheckoutDrawer', function () {
            renderDrawerCart();
            $('#checkoutDrawer').addClass('open').show();

            // Initialize or re-initialize Select2 for customer search
            if ($('#drawerCustomerSelect').length) {
                if ($('#drawerCustomerSelect').hasClass('select2-hidden-accessible')) {
                    $('#drawerCustomerSelect').select2('destroy');
                }
                $('#drawerCustomerSelect').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Search or select customer',
                    minimumInputLength: 1,
                    dropdownParent: $('#checkoutDrawer'),
                    ajax: {
                        url: '/erp/customers/search',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(function (customer) {
                                    return {
                                        id: customer.id,
                                        text: customer.name + (customer.email ? ' (' + customer.email + (customer.phone ? ', ' + customer.phone : '') + ')' : (customer.phone ? ' (' + customer.phone + ')' : ''))
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    width: '100%'
                });
            }
            // Initialize or re-initialize Select2 for employee search
            if ($('#drawerEmployee').length) {
                if ($('#drawerEmployee').hasClass('select2-hidden-accessible')) {
                    $('#drawerEmployee').select2('destroy');
                }
                $('#drawerEmployee').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Search or select employee',
                    minimumInputLength: 1,
                    dropdownParent: $('#checkoutDrawer'),
                    ajax: {
                        url: '/erp/employees/search',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(function (employee) {
                                    return {
                                        id: employee.id,
                                        text: employee.name + (employee.email ? ' (' + employee.email + (employee.phone ? ', ' + employee.phone : '') + ')' : (employee.phone ? ' (' + employee.phone + ')' : ''))
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    width: '100%'
                });
            }

            updateDrawerTotals();
        });
        $(document).on('click', '#closeCheckoutDrawer', function () {
            $('#checkoutDrawer').removeClass('open').hide();
        });

        function renderDrawerCart() {
            const $drawerCartItems = $('#drawerCartItems');
            $drawerCartItems.empty();
            let subtotal = 0;
            if (cart.length === 0) {
                $drawerCartItems.html('<div class="text-center text-muted py-5">No items in cart</div>');
            } else {
                cart.forEach(item => {
                    const useDiscount = item.discount && Number(item.discount) < Number(item.price);
                    const displayPrice = useDiscount ? Number(item.discount) : Number(item.price);
                    const originalPrice = Number(item.price);
                    const itemTotal = displayPrice * item.quantity;
                    subtotal += itemTotal;
                    $drawerCartItems.append(`
                                    <div class='d-flex justify-content-between align-items-center mb-2'>
                                        <div>
                                            <div class='fw-bold'>${item.name}</div>
                                            <div class='small text-muted'>
                                                ${displayPrice.toFixed(2)}৳ x ${item.quantity}
                                                ${useDiscount ? `<del style='color:rgb(179, 172, 172); margin-left:4px;'>$${originalPrice.toFixed(2)}</del>` : ''}
                                            </div>
                                        </div>
                                        <div class='fw-bold'>${itemTotal.toFixed(2)}৳</div>
                                    </div>
                                `);
                });
            }
            $('#drawerCartSubtotal').text(`${subtotal.toFixed(2)}৳`);
        }

        function updateDrawerTotals() {
            // Get subtotal from cart
            let subtotal = 0;
            cart.forEach(item => {
                const useDiscount = item.discount && Number(item.discount) < Number(item.price);
                const displayPrice = useDiscount ? Number(item.discount) : Number(item.price);
                subtotal += displayPrice * item.quantity;
            });

            // Get values from input fields
            const shipping = parseFloat($('#drawerShippingCharge').val()) || 0;
            const discount = parseFloat($('#drawerDiscountInput').val()) || 0;
            const paid = parseFloat($('#drawerPaidAmountInput').val()) || 0;

            // Calculate totals
            const total = subtotal + shipping - discount;
            const due = total - paid;

            // Update UI
            $('#drawerCartSubtotal').text(subtotal.toFixed(2) + '৳');
            $('#drawerShippingTotal').text(shipping.toFixed(2) + '৳');
            $('#drawerDiscountTotal').text(discount.toFixed(2) + '৳');
            $('#drawerCartTotal').text(total.toFixed(2) + '৳');
            $('#drawerPaidAmountTotal').text(paid.toFixed(2) + '৳');
            $('#drawerDueAmountTotal').text(due.toFixed(2) + '৳');
        }

        // Attach event listeners to trigger calculation
        $(document).on('input', '#drawerShippingCharge, #drawerDiscountInput, #drawerPaidAmountInput', updateDrawerTotals);
    </script>
@endsection

@push('scripts')
<script>
    // Set branch id in hidden input when opening the checkout drawer
    $(document).on('click', '#openCheckoutDrawer', function() {
        if (typeof currentBranchId !== 'undefined' && currentBranchId) {
            $('#hiddenBranchId').val(currentBranchId);
        }
    });
</script>
@endpush