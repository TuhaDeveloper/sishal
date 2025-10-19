<div id="offcanvasCart" class="offcanvas-cart-overlay">
    <div class="offcanvas-cart-panel">
        <div class="offcanvas-cart-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Your Cart <span id="cartCount" class="item-count">2</span></h5>
                <button type="button" class="btn-close-modern" onclick="closeOffcanvasCart()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="offcanvas-cart-body">
            <div id="cartItems">
                
            </div>
            
            <div id="emptyCart" class="empty-cart" style="display: none;">
                <i class="fas fa-shopping-cart"></i>
                <h6>Your cart is empty</h6>
                <p class="text-muted">Add some items to get started!</p>
            </div>
        </div>
        
        <div class="offcanvas-cart-footer">
            <div class="subtotal-row">
                <span class="subtotal-label">Subtotal</span>
                <span id="subtotalAmount" class="subtotal-amount">0.00৳</span>
            </div>
            <a href="{{ route('checkout') }}" class="checkout-btn">
                <i class="fas fa-lock me-2"></i>
                Secure Checkout
            </a>
        </div>
    </div>
</div>

<style>

    .offcanvas-cart-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        z-index: 16000; /* Above header */
        display: none;
        justify-content: flex-end;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .offcanvas-cart-panel {
        background: #f8f9fa;
        width: 420px;
        max-width: 100vw;
        height: 100vh;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 1px solid #e2e8f0;
    }

    .offcanvas-cart-overlay.show .offcanvas-cart-panel {
        transform: translateX(0);
    }

    .offcanvas-cart-header {
        background: #fff;
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .offcanvas-cart-header h5 {
        font-weight: 600;
        color: #000;
    }

    .btn-close-modern {
        background: none;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #000;
        transition: all 0.2s;
        cursor: pointer;
    }

    .btn-close-modern:hover {
        background:rgb(236, 234, 234);
        color: #000;
    }

    .item-count {
        background: var(--primary-blue);
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        margin-left: 0.5rem;
        font-weight: 500;
    }

    .offcanvas-cart-body {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        background: #f8f9fa;
    }

    .cart-item {
        background: #fff;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .cart-item:hover {
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .cart-item-image {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        object-fit: cover;
        margin-right: 1rem;
    }

    .cart-item-name {
        font-weight: 600;
        font-size: 0.95rem;
        color: #000;
        margin-bottom: 0.25rem;
    }

    .cart-item-price {
        font-weight: 600;
        font-size: 1rem;
        color: var(--primary-blue);
        margin-bottom: 0.75rem;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: 0 !important;
    }

    .quantity-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #000;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
    }

    .quantity-btn:hover:not(:disabled) {
        background: var(--primary-blue);
        color: white;
        border-color: var(--primary-blue);
    }

    .quantity-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .quantity-display {
        min-width: 40px;
        text-align: center;
        font-weight: 600;
        color: #000;
    }

    .delete-btn {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: all 0.2s;
        margin-left: 0.5rem;
    }

    .delete-btn:hover {
        background: rgba(239, 68, 68, 0.1);
    }

    .empty-cart {
        text-align: center;
        padding: 3rem 2rem;
        color: #000;
    }

    .empty-cart i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .offcanvas-cart-footer {
        background: #fff;
        padding: 1.5rem;
        border-top: 1px solid #e2e8f0;
    }

    .subtotal-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .subtotal-label {
        font-weight: 500;
        color: #000;
    }

    .subtotal-amount {
        font-weight: 700;
        font-size: 1.25rem;
        color: #000;
    }

    .checkout-btn {
        width: 100%;
        background: linear-gradient(135deg, hsl(199 89% 48%), hsl(199 89% 62%));
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.2s;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .checkout-btn:hover {
        background: linear-gradient(135deg, hsl(199 89% 31%), hsl(199 89% 52%));
        transform: translateY(-1px);
    }

    .checkout-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    @media (max-width: 768px) {
        .offcanvas-cart-panel {
            width: 100vw;
        }
        
        .cart-item {
            padding: 0.875rem;
        }
        
        .cart-item-image {
            width: 56px;
            height: 56px;
        }
    }

    .slide-out {
        animation: slideOut 0.3s ease-in-out forwards;
    }

    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100px); opacity: 0; }
    }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 1; }
        to { transform: translateX(0); opacity: 1; }
    }
    .slide-in {
        animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateX(0) !important;
    }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    var __cartBodyScrollY = 0;
    var __cartTouchMoveHandler = null;
    function renderCart(cartData) {
        console.log('[CART] renderCart called with data:', cartData);
        
        var cartItemsDiv = $('#cartItems');
        var emptyCartDiv = $('#emptyCart');
        var subtotalAmount = $('#subtotalAmount');
        var cartCount = $('#cartCount');

        cartItemsDiv.empty();
        if (!cartData.cart || cartData.cart.length === 0) {
            console.log('[CART] No cart items, showing empty cart');
            cartItemsDiv.hide();
            emptyCartDiv.show();
            subtotalAmount.text('0.00৳');
            cartCount.text('0');
            return;
        }
        
        console.log('[CART] Rendering ' + cartData.cart.length + ' cart items');
        cartItemsDiv.show();
        emptyCartDiv.hide();
        var totalCount = 0;
        
        $.each(cartData.cart, function(i, item) {
            console.log('[CART] Rendering item:', item);
            totalCount += item.qty;
            cartItemsDiv.append(`
                <div class="cart-item" data-cart-id="${item.cart_id}" data-product-id="${item.product_id}">
                    <div class="d-flex align-items-start">
                        <img src="/${item.image ? item.image : 'https://via.placeholder.com/64'}" class="cart-item-image" alt="${item.name}">
                        <div class="cart-item-details flex-grow-1">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">${(item.price * item.qty).toFixed(2)}৳</div>
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="decreaseQuantity(${item.cart_id})" ${item.qty <= 1 ? 'disabled' : ''}>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity-display">${item.qty}</span>
                                <button class="quantity-btn" onclick="increaseQuantity(${item.cart_id})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <button class="delete-btn" onclick="removeItem(${item.cart_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `);
        });
        
        subtotalAmount.text(`${cartData.cart_total.toFixed(2)}৳`);
        cartCount.text(totalCount);
        console.log('[CART] Cart rendered with total count:', totalCount);
    }

    function fetchCartData() {
        console.log('[CART] fetchCartData called');
        // Add cache-busting parameter to ensure fresh data
        var timestamp = new Date().getTime();
        $.get('/cart/list?t=' + timestamp, function(data) {
            console.log('[CART] Received data from /cart/list:', data);
            renderCart(data);
        }).fail(function(xhr, status, error) {
            console.error('[CART] Error fetching cart data:', error);
        });
    }

    function openOffcanvasCart() {
        console.log('[CART] openOffcanvasCart called');
        var overlay = document.getElementById('offcanvasCart');
        var panel = overlay.querySelector('.offcanvas-cart-panel');
        overlay.style.display = 'flex';
        overlay.classList.add('show');
        // Remove slide-out if present, add slide-in
        panel.classList.remove('slide-out');
        void panel.offsetWidth; // force reflow
        panel.classList.add('slide-in');
        // Calculate scrollbar width
        var scrollBarComp = window.innerWidth - document.documentElement.clientWidth;
        // Lock scroll cross-browser (including iOS)
        __cartBodyScrollY = window.scrollY || window.pageYOffset || 0;
        document.documentElement.style.overflow = 'hidden';
        document.documentElement.style.overscrollBehavior = 'contain';
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = '-' + __cartBodyScrollY + 'px';
        document.body.style.width = '100%';
        document.body.style.touchAction = 'none';
        if (scrollBarComp > 0) {
            document.body.style.paddingRight = scrollBarComp + 'px';
        }
        // Prevent background scroll on touch devices but allow inside panel
        __cartTouchMoveHandler = function(e){
            var withinPanel = e.target.closest && e.target.closest('.offcanvas-cart-panel');
            if (!withinPanel) {
                e.preventDefault();
            }
        };
        document.addEventListener('touchmove', __cartTouchMoveHandler, { passive: false });
        console.log('[CART] Fetching cart data on open');
        // Force refresh cart data when opening
        fetchCartData();
    }

    function closeOffcanvasCart() {
        const overlay = document.getElementById('offcanvasCart');
        const panel = overlay.querySelector('.offcanvas-cart-panel');
        // Remove slide-in, add slide-out
        panel.classList.remove('slide-in');
        panel.classList.add('slide-out');
        overlay.classList.remove('show');
        setTimeout(() => {
            overlay.style.display = 'none';
            // Restore scroll locking
            document.removeEventListener('touchmove', __cartTouchMoveHandler || function(){}, { passive: false });
            __cartTouchMoveHandler = null;
            document.documentElement.style.overflow = '';
            document.documentElement.style.overscrollBehavior = '';
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.style.touchAction = '';
            document.body.style.paddingRight = '';
            if (typeof __cartBodyScrollY === 'number') {
                window.scrollTo(0, __cartBodyScrollY);
            }
            panel.classList.remove('slide-out'); // clean up
        }, 300);
    }

    

    // Set CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    function increaseQuantity(cartId) {
        $.ajax({
            url: '/cart/increase/' + cartId,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    fetchCartData();
                    updateCartQtyBadge();
                }
            }
        });
    }

    function decreaseQuantity(cartId) {
        $.ajax({
            url: '/cart/decrease/' + cartId,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    fetchCartData();
                    updateCartQtyBadge();
                }
            }
        });
    }

    function removeItem(cartId) {
        $.ajax({
            url: '/cart/delete/' + cartId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    fetchCartData();
                    updateCartQtyBadge();
                }
            }
        });
    }

    function updateItemPrice(cartId, quantity) {
        const item = document.querySelector(`[data-cart-id="${cartId}"]`);
        if (item) {
            const priceElement = item.querySelector('.cart-item-price');
            const productId = item.getAttribute('data-product-id');
            
            // Sample prices - replace with your actual product prices
            const prices = { 1: 29.99, 2: 9.99 };
            const totalPrice = (prices[productId] * quantity).toFixed(2);
            priceElement.textContent = `$${totalPrice}`;
        }
    }

    function updateSubtotal() {
        const items = document.querySelectorAll('.cart-item');
        let total = 0;
        
        items.forEach(item => {
            const priceText = item.querySelector('.cart-item-price').textContent;
            const price = parseFloat(priceText.replace('$', ''));
            total += price;
        });
        
        document.getElementById('subtotalAmount').textContent = `$${total.toFixed(2)}`;
    }

    function updateCartCount() {
        // Fetch actual cart count from server
        $.get('/cart/qty-sum', function(data) {
            if (data && data.qty_sum !== undefined) {
                const count = data.qty_sum;
                
                // Update cart count in offcanvas cart
                const cartCountEl = document.getElementById('cartCount');
                if (cartCountEl) {
                    cartCountEl.textContent = count;
                }
                
                // Update cart count badges in navbar
                const navCartCounts = document.querySelectorAll('.nav-cart-count');
                navCartCounts.forEach(function(el) {
                    el.textContent = count;
                });
                
                // Update mobile cart count
                const mobileCartCounts = document.querySelectorAll('.qi-badge.nav-cart-count');
                mobileCartCounts.forEach(function(el) {
                    el.textContent = count;
                });
            } else {
                // Fallback to DOM count
                const itemCount = document.querySelectorAll('.cart-item').length;
                updateCartCountElements(itemCount);
            }
        }).fail(function() {
            // Fallback to DOM count if request fails
            const itemCount = document.querySelectorAll('.cart-item').length;
            updateCartCountElements(itemCount);
        });
    }
    
    function updateCartCountElements(count) {
        // Update cart count in offcanvas cart
        const cartCountEl = document.getElementById('cartCount');
        if (cartCountEl) {
            cartCountEl.textContent = count;
        }
        
        // Update cart count badges in navbar
        const navCartCounts = document.querySelectorAll('.nav-cart-count');
        navCartCounts.forEach(function(el) {
            el.textContent = count;
        });
        
        // Update mobile cart count
        const mobileCartCounts = document.querySelectorAll('.qi-badge.nav-cart-count');
        mobileCartCounts.forEach(function(el) {
            el.textContent = count;
        });
    }

    function checkEmptyCart() {
        const items = document.querySelectorAll('.cart-item');
        const emptyCart = document.getElementById('emptyCart');
        const cartItems = document.getElementById('cartItems');
        
        if (items.length === 0) {
            cartItems.style.display = 'none';
            emptyCart.style.display = 'block';
        }
    }

    // Close on overlay click
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[CART] DOMContentLoaded - initializing cart');
        
        // Ensure cart overlay is at top-level to avoid header stacking contexts
        var cartOverlay = document.getElementById('offcanvasCart');
        if (cartOverlay && cartOverlay.parentElement !== document.body) {
            document.body.appendChild(cartOverlay);
        }
        
        const overlay = document.getElementById('offcanvasCart');
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeOffcanvasCart();
            }
        });
        
        // Initialize cart data on page load
        console.log('[CART] Loading initial cart data');
        fetchCartData();
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeOffcanvasCart();
        }
    });
</script>