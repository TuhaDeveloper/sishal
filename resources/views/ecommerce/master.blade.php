@include('ecommerce.components.header')
@include('ecommerce.components.navbar')

<!-- Main Content Container for AJAX Loading -->
<div id="main-content-container">
@yield('main-section')
@stack('scripts')
</div>

@include('ecommerce.components.footer')

<!-- Scroll to Top Button -->
<button id="scrollToTopBtn" class="scroll-to-top-btn" title="Scroll to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Page loading optimization -->
<div id="page-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 20000; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s ease-out; pointer-events: none;">
    <div style="text-align: center;">
        <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #2196F3; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <p style="color: #666; font-size: 14px;">Loading...</p>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Prevent layout shifts and vibration */
body {
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

/* Sticky footer layout so pages with little content (e.g., empty wishlist)
   keep the footer at the bottom without large blank space */
html, body { height: 100%; }
body { display: flex; flex-direction: column; min-height: 100vh; }
#main-content-container { flex: 1 0 auto; }
.footer { margin-top: auto; }

/* Smooth transitions without vibration */
* {
    -webkit-tap-highlight-color: transparent;
}

/* Prevent text selection vibration on mobile */
a, button {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Global button and navigation stability - minimal interactions */
.nav-link, .tab-btn, .action-btn, .header-link {
    transition: color 0.2s ease, background-color 0.2s ease;
    transform: none;
    -webkit-transform: none;
    will-change: auto;
}

.nav-link:hover, .tab-btn:hover, .action-btn:hover, .header-link:hover {
    transform: none;
    -webkit-transform: none;
}

.nav-link:active, .tab-btn:active, .action-btn:active, .header-link:active {
    transform: none;
    -webkit-transform: none;
    transition: all 0.1s ease;
}

/* Navigation layout stability */
.nav-links {
    display: flex;
    align-items: center;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-links .nav-item {
    margin: 0;
    padding: 0;
}

.nav-links .nav-link {
    position: relative;
    display: inline-block;
    padding: 12px 16px;
    text-decoration: none;
    transition: color 0.2s ease, background-color 0.2s ease;
    transform: none;
    -webkit-transform: none;
}

.nav-links .nav-link:hover {
    transform: none;
    -webkit-transform: none;
}

.nav-links .nav-link:active {
    transform: none;
    -webkit-transform: none;
    transition: all 0.1s ease;
}

/* Action buttons layout */
.action-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    text-decoration: none;
    transition: color 0.2s ease, background-color 0.2s ease;
    transform: none;
    -webkit-transform: none;
}

.action-btn:hover {
    transform: none;
    -webkit-transform: none;
}

.action-btn:active {
    transform: none;
    -webkit-transform: none;
    transition: all 0.1s ease;
}

/* Categories Page Styles */
.categories-section {
    min-height: 60vh;
    padding-top: 2rem !important;
    padding-bottom: 3rem !important;
}

/* Section title styles are defined in individual page files to avoid conflicts */

.category-tile {
    transition: all 0.3s ease;
    border-radius: 12px;
    overflow: hidden;
}

.category-tile:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    text-decoration: none;
}

.tile-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.tile-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.tile-img {
    width: 100%;
    height: 120px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
}

.tile-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.category-tile:hover .tile-img img {
    opacity: 0.9;
}

.placeholder-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e2e8f0;
    color: #64748b;
}

.tile-title {
    padding: 1rem;
    font-weight: 600;
    color: #1a202c;
    text-align: center;
    font-size: 0.9rem;
    flex-grow: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-categories {
    padding: 3rem 1rem;
    color: #64748b;
}

.no-categories svg {
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-categories h3 {
    color: #374151;
    margin-bottom: 0.5rem;
}

/* Footer Spacing */
.footer {
    margin-top: 2rem;
    background: #1a202c;
    color: white;
    padding: 3rem 0 1rem;
}

.footer-logo img {
    max-height: 50px;
    margin-bottom: 1rem;
}

.footer-description {
    color: #a0aec0;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.footer-title {
    color: white;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: #a0aec0;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: white;
}

.social-links {
    display: flex;
    gap: 0.75rem;
}

.social-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #2d3748;
    color: white;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-links a:hover {
    background: #4a5568;
    transform: translateY(-2px);
}

.footer-bottom {
    border-top: 1px solid #2d3748;
    padding-top: 1.5rem;
    margin-top: 2rem;
    color: #a0aec0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .categories-section {
        padding-top: 1.5rem !important;
        padding-bottom: 2rem !important;
    }
    
    /* Section title responsive styles handled in individual page files */
    
    .tile-img {
        height: 100px;
    }
    
    .footer {
        padding: 2rem 0 1rem;
    }
}
</style>

<script>
// Enhanced smooth navigation without Turbo CDN
document.addEventListener('DOMContentLoaded', function() {
    // Optimized header management - prevent inconsistent behavior
    let headerElements = null;
    let headerVarsSet = false;
    
    function initializeHeaderElements() {
        if (!headerElements) {
            headerElements = {
                topBar: document.querySelector('.top-bar'),
                header: document.querySelector('header.modern-header'),
                nav: document.querySelector('nav.main-nav'),
                root: document.documentElement
            };
        }
        return headerElements;
    }
    
    function setHeaderVars(force = false) {
        // Completely disable dynamic header variable calculation
        // Use only CSS-defined values to prevent any shake
        return;
    }
    
    // Set initial values immediately
    setHeaderVars();
    
    // Completely disable resize-based header recalculation to prevent shake
    // let resizeTimeout;
    // window.addEventListener('resize', function() {
    //     // Disabled to prevent any dynamic calculations
    // });
    // CSS handles fixed layout using variables; just ensure vars stay updated
    // Hide page loader with subtle transition
    const pageLoader = document.getElementById('page-loader');
    if (pageLoader) {
        // Ensure loader is hidden immediately on page load
        pageLoader.style.opacity = '0';
        pageLoader.style.display = 'none';
    }
    
    // AJAX Navigation System Removed - Using standard page navigation for better SEO
    
    function showPageLoader() {
        const loader = document.getElementById('page-loader');
        if (loader) {
            loader.style.display = 'flex';
            loader.style.opacity = '1';
        }
    }
    
    function hidePageLoader() {
        const loader = document.getElementById('page-loader');
        if (loader) {
            loader.style.transition = 'opacity 0.05s ease-out';
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 50);
        }
    }
    
    // loadPageContent function removed - using standard page navigation
    
    function updateActiveNavigation(url) {
        // Remove active class from all nav items
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.classList.remove('active');
        });
        
        // Add active class to current page nav item
        const currentUrl = new URL(url);
        const currentPath = currentUrl.pathname;
        const currentSearch = currentUrl.search;
        
        console.log('Updating navigation for:', currentPath, currentSearch); // Debug log
        
        // Check for exact matches first
        navItems.forEach(item => {
            const link = item.querySelector('a');
            if (link) {
                const linkUrl = new URL(link.href);
                const linkPath = linkUrl.pathname;
                const linkSearch = linkUrl.search;
                
                console.log('Checking link:', linkPath, linkSearch); // Debug log
                
                // Exact match (path and query parameters)
                if (linkPath === currentPath && linkSearch === currentSearch) {
                    item.classList.add('active');
                    console.log('Exact match found:', link.textContent.trim());
                }
                // Home page match
                else if (currentPath === '/' && linkPath === '/') {
                    item.classList.add('active');
                    console.log('Home match found');
                }
                // Products page - check for specific view parameter
                else if (currentPath === '/products') {
                    // If current page has view=categories, only activate Category link
                    if (currentSearch.includes('view=categories')) {
                        if (linkSearch.includes('view=categories')) {
                            item.classList.add('active');
                            console.log('Category match found');
                        }
                    }
                    // If current page doesn't have view=categories, only activate Products link
                    else {
                        if (!linkSearch.includes('view=categories') && linkPath === '/products') {
                            item.classList.add('active');
                            console.log('Products match found');
                        }
                    }
                }
                // Contact page match
                else if (currentPath === '/contact' && linkPath === '/contact') {
                    item.classList.add('active');
                    console.log('Contact match found');
                }
                // About page match
                else if (currentPath === '/about' && linkPath === '/about') {
                    item.classList.add('active');
                    console.log('About match found');
                }
                // Additional pages match
                else if (currentPath.startsWith('/pages/') && linkPath === currentPath) {
                    item.classList.add('active');
                    console.log('Additional page match found');
                }
            }
        });
    }
    
    // reinitializePageScripts function removed - no longer needed
    
    function ensureStylesLoaded() {
        // Gentle style recalculation without forcing reflow
        const container = document.getElementById('main-content-container');
        if (container) {
            // Only trigger reflow if necessary
            container.offsetHeight;
        }
    }
    
    function scrollToTop() {
        // Simple, consistent scroll to top without multiple methods
        window.scrollTo(0, 0);
    }
    
    // AJAX navigation removed - using standard page navigation
    
    // Handle initial page load - only scroll if not already at top
    if (window.history.state === null && window.pageYOffset > 0) {
        scrollToTop();
    }
    
    // ensureNavLinksWork function removed - no longer needed
    
    // handleNavLinkClick function removed - no longer needed
    
    // AJAX navigation calls removed - using standard page navigation
    
    // ensureContactAboutWork function removed - no longer needed
    
    function initializeTabFunctionality() {
        // Initialize tab functionality for product pages
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        if (tabButtons.length > 0 && tabContents.length > 0) {
            console.log('Initializing tab functionality for', tabButtons.length, 'tabs');
            
            tabButtons.forEach(function (btn) {
                // Remove any existing event listeners
                btn.removeEventListener('click', handleTabClick);
                // Add new event listener
                btn.addEventListener('click', handleTabClick);
            });
            
            function handleTabClick(event) {
                event.preventDefault();
                event.stopPropagation();
                
                console.log('Tab clicked:', this.getAttribute('data-tab'));
                
                // Remove active from all buttons
                tabButtons.forEach(b => b.classList.remove('active'));
                // Hide all tab contents
                tabContents.forEach(tc => {
                    tc.classList.remove('active');
                    tc.style.display = 'none';
                });
                
                // Activate clicked button
                this.classList.add('active');
                
                // Show corresponding tab
                const tabId = this.getAttribute('data-tab');
                const tabContent = document.getElementById(tabId);
                if (tabContent) {
                    tabContent.classList.add('active');
                    tabContent.style.display = 'block';
                    console.log('Showing tab:', tabId);
                } else {
                    console.log('Tab content not found:', tabId);
                }
            }
        }
    }
    
    // Contact and About handling removed - using standard navigation
    
    // Initialize tab functionality on page load
    initializeTabFunctionality();

    // Define and run loader for Most Sold Products (Home)
    window.loadMostSoldProducts = function() {
        var container = window.jQuery ? window.jQuery('#mostSoldProductsContainer') : null;
        if (!container || container.length === 0) return; // Not on home section

        // If already populated, do nothing
        if (container.children().length > 0) return;
        
        // Check if home page script has already loaded products with ratings
        if (container.find('.product-meta .stars').length > 0) return;
        
        // Disable delayed product loading to prevent layout shifts
        // if (window.location.pathname === '/' && container.length > 0) {
        //     // Disabled to prevent shake
        //     return;
        // }

        // Load products from master layout
        loadProductsFromMaster();
    };
    
    // Separate function to load products from master layout
    function loadProductsFromMaster() {
        var container = window.jQuery ? window.jQuery('#mostSoldProductsContainer') : null;
        if (!container || container.length === 0) return;
        
        if (window.jQuery) {
            window.jQuery.get('/api/products/most-sold', function (products) {
                container.empty();
                if (!products || !products.length) {
                    container.append('<div class="col-12 text-center text-muted">No products found.</div>');
                    return;
                }
                products.forEach(function (product) {
                    var rating = product.avg_rating ?? product.rating ?? 0;
                    var price = parseFloat(product.price || 0).toFixed(2);
                    var image = product.image ? product.image : '/default-product.png';
                    container.append('\
                    <div class="col-lg-3 col-md-6 mb-4">\
                        <div class="product-card position-relative" data-href="/product/' + product.slug + '">\
                            <button class="wishlist-btn' + (product.is_wishlisted ? ' active' : '') + '" data-product-id="' + product.id + '">\
                                <i class="' + (product.is_wishlisted ? 'fas text-danger' : 'far') + ' fa-heart"></i>\
                            </button>\
                            <div class="product-image-container">\
                                <img src="' + image + '" class="product-image" alt="' + product.name + '">\
                            </div>\
                            <div class="product-info">\
                                <a href="/product/' + product.slug + '" style="text-decoration: none" class="product-title">' + product.name + '</a>\
                                <div class="product-meta">\
                                    <div class="stars" aria-label="' + rating + ' out of 5">' + Array.from({length:5}).map(function(_,i){return '<i class="fa' + (i < Math.round(rating) ? 's' : 'r') + ' fa-star"></i>';}).join('') + '</div>\
                                </div>\
                                <div class="price">' + price + '৳</div>\
                                <div class="d-flex justify-content-between align-items-center gap-2 product-actions">\
                                    <button class="btn-add-cart" data-product-id="' + product.id + '" data-has-stock="' + (product.has_stock ? 'true' : 'false') + '"' + (!product.has_stock ? ' disabled' : '') + '><svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff" width="14" height="14"><path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z"></path><circle cx="7" cy="22" r="2"></circle><circle cx="17" cy="22" r="2"></circle></svg> ' + (product.has_stock ? 'Add to Cart' : 'Out of Stock') + '</button>\
                                </div>\
                            </div>\
                        </div>\
                    </div>');
                });
            }).fail(function () {
                container.html('<div class="col-12 text-center text-danger">Failed to load products.</div>');
            });
        }
    };

    // Attempt to load products on initial page load as well
    loadMostSoldProducts();
    
    // Contact and About periodic handling removed
    
    // Initialize tab functionality once on page load
    initializeTabFunctionality();
    
    // Minimal image loading
    const images = document.querySelectorAll('img');
    if (images.length > 0) {
        images.forEach(img => {
            if (img) {
                // Disable image loading animations to prevent layout shifts
                img.style.opacity = '1';
                // if (img.complete) {
                //     img.style.opacity = '1';
                // } else {
                //     img.style.opacity = '0.995';
                //     img.style.transition = 'opacity 0.03s ease-in-out';
                //     img.addEventListener('load', function() {
                //         this.style.opacity = '1';
                //     });
                // }
            }
        });
    }
    
    // Smooth scroll for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    if (anchorLinks.length > 0) {
        anchorLinks.forEach(link => {
            if (link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            }
        });
    }
    
    // Add loading state for forms
    const forms = document.querySelectorAll('form');
    if (forms.length > 0) {
        forms.forEach(form => {
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.style.opacity = '0.7';
                        submitBtn.disabled = true;
                    }
                });
            }
        });
    }
    
    // Global cart count update function
    window.updateCartQtyBadge = function() {
        if (typeof updateCartCount === 'function') {
            updateCartCount();
        } else {
            // Fallback: fetch cart count directly
            fetch('/cart/qty-sum')
                .then(response => response.json())
                .then(data => {
                    if (data && data.qty_sum !== undefined) {
                        const count = data.qty_sum;
                        
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
                })
                .catch(function() {
                    // Silent fail
                });
        }
    };

    // Add toast CSS if not already present
    if (!document.getElementById('toast-css')) {
        const style = document.createElement('style');
        style.id = 'toast-css';
        style.textContent = `
            .custom-toast {
                min-width: 220px;
                max-width: 340px;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
                border-left: 4px solid #10B981;
                overflow: hidden;
                transform: translateX(0);
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
                font-size: 18px;
                cursor: pointer;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
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
        `;
        document.head.appendChild(style);
    }

    // Global toast notification function
    window.showToast = function(message, type = 'success') {
        console.log('[TOAST] Showing toast:', message, type);
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
        
        // Ensure toast container exists
        var container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 16000; display: flex; flex-direction: column; gap: 10px;';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Animate progress bar
        setTimeout(() => {
            toast.querySelector('.toast-progress').style.width = '0%';
        }, 10);
        
        // Auto remove after 2.5 seconds
        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 400);
        }, 2500);
    };

    // Global wishlist toggle function
    window.toggleWishlist = function(productId) {
        console.log('[WISHLIST] Toggling wishlist for product:', productId);
        
        const button = document.querySelector(`[data-product-id="${productId}"].product-wishlist-top`);
        if (!button) {
            console.error('[WISHLIST] Button not found for product:', productId);
            return;
        }

        const isActive = button.classList.contains('active');
        const icon = button.querySelector('i');
        
        // Show loading state
        button.disabled = true;
        icon.className = 'fas fa-spinner fa-spin';

        fetch(`/add-remove-wishlist/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Toggle button state
                button.classList.toggle('active');
                icon.className = isActive ? 'far fa-heart' : 'fas fa-heart';
                
                // Show success message
                if (typeof showToast === 'function') {
                    showToast(isActive ? 'Removed from wishlist!' : 'Added to wishlist!', 'success');
                } else {
                    alert(isActive ? 'Removed from wishlist!' : 'Added to wishlist!');
                }
                
                // Update wishlist count
                if (typeof updateWishlistCount === 'function') {
                    updateWishlistCount();
                }
            } else {
                // Show error message
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Failed to update wishlist', 'error');
                } else {
                    alert(data.message || 'Failed to update wishlist');
                }
                
                // Reset button state
                icon.className = isActive ? 'fas fa-heart' : 'far fa-heart';
            }
        })
        .catch(error => {
            console.error('Wishlist error:', error);
            if (typeof showToast === 'function') {
                showToast('Error updating wishlist', 'error');
            } else {
                alert('Error updating wishlist');
            }
            
            // Reset button state
            icon.className = isActive ? 'fas fa-heart' : 'far fa-heart';
        })
        .finally(() => {
            button.disabled = false;
        });
    };
    
    // Prevent layout shifts without excessive transforms
    const preventLayoutShifts = () => {
        // Ensure body doesn't have problematic transforms
        document.body.style.transform = 'none';
        document.body.style.willChange = 'auto';
        // Remove any existing transform classes that might conflict
        document.body.classList.remove('transform-gpu', 'will-change-transform');
    };
    
    // Apply layout shift prevention on page load
    preventLayoutShifts();
    
    // Re-apply on window resize to prevent layout shifts
    window.addEventListener('resize', preventLayoutShifts);
    
    // Ensure header stability without scroll interference
    function stabilizeHeader() {
        // Directly target elements without dynamic calculation
        const topBar = document.querySelector('.top-bar');
        const header = document.querySelector('header.modern-header');
        const nav = document.querySelector('nav.main-nav');
        
        // Remove conflicting transforms that cause shake
        if (topBar) topBar.style.transform = 'none';
        if (header) header.style.transform = 'none';
        if (nav) nav.style.transform = 'none';
    }
    
    // Stabilize header once on load
    stabilizeHeader();
    
    // Disable visibility change header recalculation to prevent shake
    // document.addEventListener('visibilitychange', function() {
    //     if (!document.hidden) {
    //         stabilizeHeader();
    //     }
    // });
    
    // Force header stability on window focus
    window.addEventListener('focus', function() {
        stabilizeHeader();
    });
    
    // Scroll to Top Button functionality
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');
    
    // Show/hide scroll to top button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollToTopBtn.classList.add('show');
        } else {
            scrollToTopBtn.classList.remove('show');
        }
    });
    
    // Scroll to top when button is clicked
    scrollToTopBtn.addEventListener('click', function() {
        scrollToTop();
    });
    
    // Global cart event handler to prevent duplicate listeners
    window.globalCartHandler = function(e) {
        var btn = e.target.closest('.btn-add-cart');
        if (!btn) return;
        
        // Check stock for products without variations
        var hasStock = btn.getAttribute('data-has-stock');
        if (hasStock === 'false') {
            e.preventDefault();
            e.stopPropagation();
            showToast('This product is out of stock!', 'warning');
            return;
        }
        
        // For dynamically generated buttons without stock info, we'll let the server handle the check
        // The server-side cart handler will check stock and return appropriate response
        
        e.preventDefault();
        e.stopPropagation();
        
        // Prevent multiple simultaneous requests
        if (btn.disabled || btn.getAttribute('data-processing') === 'true') {
            return;
        }
        
        // Mark button as processing
        btn.setAttribute('data-processing', 'true');
        btn.disabled = true;
        
        var productId = btn.getAttribute('data-product-id');
        var productName = btn.getAttribute('data-product-name') || 'Product';
        
        if (!productId) {
            btn.disabled = false;
            btn.removeAttribute('data-processing');
            if (typeof showToast === 'function') showToast('Error: Product ID not found', 'error');
            return;
        }
        
        // Get quantity if available
        var qtyInput = document.getElementById('quantityInput');
        var qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
        
        // Prepare data
        var data = new URLSearchParams();
        data.append('qty', qty.toString());
        
        // Get CSRF token
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        // Determine the correct endpoint based on current page
        var endpoint = window.location.pathname.includes('/product/') ? 
            '/cart/add-page/' + productId : 
            '/cart/add/' + productId;
            
        // Handle variations for product details page
        if (window.location.pathname.includes('/product/')) {
            var hasVariations = document.querySelector('[data-has-variations="true"]') !== null;
            if (hasVariations) {
                var variationIdEl = document.getElementById('selected-variation-id');
                var selectedVariationId = variationIdEl ? variationIdEl.value : null;
                
                if (!selectedVariationId) {
                    if (typeof showToast === 'function') showToast('Please select product options before adding to cart', 'error');
                    btn.disabled = false;
                    btn.removeAttribute('data-processing');
                    return;
                }
                
                data.append('variation_id', selectedVariationId);
            }
        }
        
        // Function to re-enable button
        var reEnableButton = function() {
            btn.disabled = false;
            btn.removeAttribute('data-processing');
        };
        
        // Backup timeout to ensure button gets re-enabled even if everything fails
        var backupTimeout = setTimeout(function() {
            reEnableButton();
        }, 5000); // 5 second backup timeout
        
        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-TOKEN': csrfToken
            },
            body: data.toString()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message || 'Product added to cart successfully!', 'success');
                if (typeof updateCartCount === 'function') updateCartCount();
                if (typeof updateCartQtyBadge === 'function') updateCartQtyBadge();
            } else {
                if (typeof showToast === 'function') showToast(data.message || 'Failed to add product to cart', 'error');
            }
        })
        .catch(error => {
            if (typeof showToast === 'function') showToast('Failed to add product to cart', 'error');
        })
        .finally(() => {
            clearTimeout(backupTimeout);
            reEnableButton();
        });
    };
    
    // Remove any existing global cart listeners and add the new one
    document.removeEventListener('click', window.globalCartHandler);
    document.addEventListener('click', window.globalCartHandler);
    
    // Clean up any stuck buttons on page load (but preserve variation logic)
    document.querySelectorAll('.btn-add-cart[data-processing="true"]').forEach(function(btn) {
        btn.disabled = false;
        btn.removeAttribute('data-processing');
    });
    
    // Disable delayed variation logic to prevent layout shifts
    // setTimeout(function() {
    //     // Disabled to prevent shake
    // }, 100);
    
    // Clean up stuck buttons on page visibility change instead of interval
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            var stuckButtons = document.querySelectorAll('.btn-add-cart[data-processing="true"]');
            if (stuckButtons.length > 0) {
                stuckButtons.forEach(function(btn) {
                    btn.disabled = false;
                    btn.removeAttribute('data-processing');
                });
            }
        }
    });
    
    // Reset button states when page becomes visible (navigation between pages)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            document.querySelectorAll('.btn-add-cart').forEach(function(btn) {
                btn.disabled = false;
                btn.removeAttribute('data-processing');
            });
            
            // Disable delayed variation logic to prevent layout shifts
            // setTimeout(function() {
            //     // Disabled to prevent shake
            // }, 50);
        }
    });
    
    // Global product card click handler - make entire product card clickable
    document.addEventListener('click', function(e) {
        var productCard = e.target.closest('.product-card');
        if (!productCard) return;

        // Prevent navigation when clicking on wishlist/cart or other interactive UI inside the card
        if (
            e.target.closest('.wishlist-btn') ||
            e.target.closest('.product-wishlist-top') ||
            e.target.closest('.btn-add-cart') ||
            e.target.closest('button') ||
            e.target.closest('a')
        ) {
            return;
        }

        // Don't trigger if clicking on generic interactive elements
        var interactiveElements = ['A', 'BUTTON', 'SVG', 'PATH', 'FORM', 'INPUT', 'SELECT', 'TEXTAREA', 'LABEL'];
        if (interactiveElements.includes(e.target.tagName)) return;

        // Check if the card has a data-href attribute (for dynamically loaded products)
        var href = productCard.getAttribute('data-href');
        if (href) {
            window.location.href = href;
            return;
        }

        // For static product cards, find the product title link
        var titleLink = productCard.querySelector('.product-title');
        if (titleLink && titleLink.href) {
            window.location.href = titleLink.href;
        }
    });
});
</script>

</body>
</html>