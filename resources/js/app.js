import './bootstrap';

import Alpine from 'alpinejs';
import Splide from '@splidejs/splide';
import '@splidejs/splide/css';

// App.js loaded successfully

window.Alpine = Alpine;

Alpine.start();

// Delegated wishlist toggle handler (use capture so inline stopPropagation doesn't block)
document.addEventListener('click', async (event) => {
    const button = event.target.closest('.wishlist-btn');
    if (!button) return;
    event.preventDefault();
    event.stopPropagation();

    const productId = button.getAttribute('data-product-id');
    if (!productId) return;

    const icon = button.querySelector('i');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Prevent double clicks during request
    if (button.dataset.loading === 'true') return;
    button.dataset.loading = 'true';
    button.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch(`/add-remove-wishlist/${productId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({}),
        });

        let result = null;
        try { result = await response.json(); } catch (_) { /* ignore */ }

        if (!response.ok) {
            throw new Error(result?.message || `Wishlist update failed (${response.status})`);
        }

        // Decide whether this action resulted in an add or a removal
        const wasActive = button.classList.contains('active') || (icon && icon.classList.contains('fas'));
        let added;
        if (typeof result?.added !== 'undefined') {
            added = !!result.added;
        } else if (typeof result?.message === 'string') {
            const msg = result.message.toLowerCase();
            if (msg.includes('remove')) {
                added = false;
            } else if (msg.includes('added')) {
                added = true;
            } else {
                added = !wasActive;
            }
        } else if (typeof result?.success !== 'undefined') {
            // Backend returns success for both add/remove; infer from current UI state
            added = !wasActive;
        } else {
            added = !wasActive;
        }

        // Toggle UI state
        if (icon) {
            if (added) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                icon.classList.add('text-danger');
                icon.classList.add('active');
                button.classList.add('active');
            } else {
                icon.classList.remove('fas');
                icon.classList.remove('text-danger');
                icon.classList.add('far');
                icon.classList.remove('active');
                button.classList.remove('active');
            }
        }

        // Show toast if available
        if (typeof window.showToast === 'function') {
            window.showToast(added ? 'Added to wishlist' : 'Removed from wishlist');
        }
        // Update wishlist count if helper exists
        if (typeof window.updateWishlistCount === 'function') {
            window.updateWishlistCount();
        }
    } catch (err) {
        console.error(err);
        if (typeof window.showToast === 'function') {
            window.showToast('Could not update wishlist', 'error');
        }
    } finally {
        delete button.dataset.loading;
        button.removeAttribute('disabled');
    }
}, true);

// Custom Banner Carousel (works without Bootstrap)
function initCategorySplide() {
    try {
        const categoryEl = document.getElementById('categorySplide');
        if (categoryEl && !categoryEl.__splideMounted) {
            const splide = new Splide(categoryEl, {
                type: 'loop',
                perPage: 4,
                perMove: 1,
                gap: '16px',
                pagination: false,
                arrows: true,
                drag: true,
                flickPower: 300,
                releaseWheel: true,
                keyboard: 'focused',
                autoplay: false,
                interval: 2000,
                pauseOnHover: true,
                rewind: true,
                breakpoints: {
                    1199: { perPage: 3 },
                    767: { perPage: 2 },
                    575: { perPage: 2 }
                }
            });
            splide.mount();
            categoryEl.__splideMounted = true;
        }
    } catch (e) {
        console.error('Failed to mount category Splide:', e);
    }
}

// Initialize only once to prevent layout shifts
document.addEventListener('DOMContentLoaded', initCategorySplide);

document.addEventListener('DOMContentLoaded', function() {
    // Vlog Splide
    (function initVlogSplide(){
        const vlogEl = document.getElementById('vlogSplide');
        if (!vlogEl || vlogEl.__splideMounted) return;
        try {
            const vlogSplide = new Splide(vlogEl, {
                perPage: 2,
                gap: '24px',
                pagination: false,
                arrows: true,
                drag: false, // disable mouse/touch drag to avoid YouTube iframe conflicts
                keyboard: 'focused',
                breakpoints: {
                    991: { perPage: 1, gap: '16px' }
                }
            });
            vlogSplide.mount();
            vlogEl.__splideMounted = true;
        } catch (e) {
            console.error('Failed to mount vlog Splide:', e);
        }
    })();

    // Build Top Selling Splide from API
    (function initMostSoldSplide(){
        const wrapper = document.getElementById('mostSoldSplide');
        const listEl = document.getElementById('mostSoldSplideList');
        const fallback = document.getElementById('mostSoldFallback');
        
        if (!wrapper || !listEl) return;
        
        // Add loading state
        if (fallback) {
            fallback.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Loading top selling products...</p></div>';
            fallback.style.display = 'block';
        }
        
        const startTime = performance.now();
        
        fetch('/api/products/most-sold', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            const endTime = performance.now();
            const loadTime = Math.round(endTime - startTime);
            
            // Handle new API response format
            const products = data.success ? data.data : data;
            
            if (!Array.isArray(products) || products.length === 0) {
                if (fallback) {
                    fallback.innerHTML = '<div class="col-12 text-center text-muted"><i class="fas fa-box-open fa-2x mb-3"></i><p>No top selling products found.</p></div>';
                    fallback.style.display = 'block';
                }
                if (wrapper) wrapper.style.display = 'none';
                return;
            }
            
            if (fallback) fallback.style.display = 'none';
            
            // Render products with improved error handling
            listEl.innerHTML = products.map(product => {
                try {
                    const rating = product.avg_rating ?? 0;
                    const reviews = product.total_reviews ?? 0;
                    const price = Number(product.price || 0);
                    const discount = Number(product.discount || 0);
                    const finalPrice = discount > 0 ? discount : price;
                    const image = product.image || '/static/default-product.png';
                    const hasStock = product.has_stock !== false;
                    const isWishlisted = product.is_wishlisted === true;
                    
                    return `
                        <li class="splide__slide">
                            <div class="product-card position-relative no-hover-border" data-href="/product/${product.slug}">
                                <button class="wishlist-btn${isWishlisted ? ' active' : ''}" data-product-id="${product.id}" title="${isWishlisted ? 'Remove from wishlist' : 'Add to wishlist'}" type="button" onclick="event.stopPropagation();">
                                    <i class="${isWishlisted ? 'fas text-danger' : 'far'} fa-heart"></i>
                                </button>
                                <div class="product-image-container">
                                    <img src="${image}" class="product-image" alt="${product.name}" loading="lazy" onerror="this.src='/static/default-product.png'">
                                    ${rating > 0 ? `<div class="rating-badge">
                                        <span>${rating.toFixed(1)}</span>
                                        <i class="fas fa-star star"></i>
                                        <span>| ${reviews}</span>
                                    </div>` : ''}
                                </div>
                                <div class="product-info">
                                    <a href="/product/${product.slug}" style="text-decoration: none" class="product-title" title="${product.name}">${product.name}</a>
                                    <div class="price">
                                        ${discount > 0 && discount < price ? 
                                            `<span class="fw-bold text-primary">${finalPrice.toFixed(2)}৳</span><span class="old">${price.toFixed(2)}৳</span>` : 
                                            `<span class="fw-bold text-primary">${finalPrice.toFixed(2)}৳</span>`
                                        }
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center gap-2 product-actions">
                                        <button class="btn-add-cart ${!hasStock ? 'disabled' : ''}" 
                                                data-product-id="${product.id}" 
                                                data-product-name="${product.name}" 
                                                data-has-stock="${hasStock}" 
                                                ${!hasStock ? 'disabled title="Out of stock"' : 'title="Add to cart"'}>
                                            <svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff" width="14" height="14">
                                                <path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z"></path>
                                                <circle cx="7" cy="22" r="2"></circle>
                                                <circle cx="17" cy="22" r="2"></circle>
                                            </svg> 
                                            ${hasStock ? 'Add to Cart' : 'Out of Stock'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>`;
                } catch (error) {
                    console.error('Error rendering product:', product, error);
                    return ''; // Skip this product if there's an error
                }
            }).filter(html => html).join('');
            
            // Initialize Splide carousel - optimized to prevent shake
            wrapper.style.visibility = 'visible';
            const topSplide = new Splide(wrapper, {
                type: 'loop',
                perPage: 4,
                gap: '16px',
                pagination: false,
                arrows: true,
                lazyLoad: 'nearby',
                autoplay: false,
                interval: 3000,
                pauseOnHover: true,
                rewind: true,
                // Optimize for stability
                speed: 400,
                easing: 'ease',
                breakpoints: { 
                    1199: { perPage: 3 }, 
                    991: { perPage: 2 }, 
                    575: { perPage: 2 } 
                }
            });
            topSplide.mount();
            
            // Log performance metrics
            console.log(`Top selling products loaded in ${loadTime}ms`, {
                productsCount: products.length,
                loadTime: loadTime,
                cached: data.meta?.cached || false
            });
            
        })
        .catch(error => {
            console.error('Failed to load top selling products:', error);
            
            if (fallback) {
                fallback.innerHTML = `
                    <div class="col-12 text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <p>Failed to load top selling products.</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                            <i class="fas fa-refresh me-1"></i>Retry
                        </button>
                    </div>
                `;
                fallback.style.display = 'block';
            }
            if (wrapper) wrapper.style.display = 'none';
        });
    })();

    // Build New Arrivals Splide from API
    (function initNewArrivalsSplide(){
        const wrapper = document.getElementById('newArrivalsSplide');
        const listEl = document.getElementById('newArrivalsSplideList');
        const fallback = document.getElementById('newArrivalsFallback');

        if (!wrapper || !listEl) return;

        if (fallback) {
            fallback.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Loading new arrivals...</p></div>';
            fallback.style.display = 'block';
        }

        const startTime = performance.now();

        fetch('/api/products/new-arrivals', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            const endTime = performance.now();
            const loadTime = Math.round(endTime - startTime);

            const products = data.success ? data.data : data;

            if (!Array.isArray(products) || products.length === 0) {
                if (fallback) {
                    fallback.innerHTML = '<div class="col-12 text-center text-muted"><i class="fas fa-box-open fa-2x mb-3"></i><p>No new arrival products found.</p></div>';
                    fallback.style.display = 'block';
                }
                if (wrapper) wrapper.style.display = 'none';
                return;
            }

            if (fallback) fallback.style.display = 'none';

            listEl.innerHTML = products.map(product => {
                try {
                    const rating = product.avg_rating ?? product.rating ?? 0;
                    const reviews = product.total_reviews ?? 0;
                    const price = Number(product.price || 0);
                    const discount = Number(product.discount || 0);
                    const finalPrice = discount > 0 ? discount : price;
                    const image = product.image || '/static/default-product.png';
                    const hasStock = product.has_stock !== false;
                    const isWishlisted = product.is_wishlisted === true;

                    return `
                        <li class=\"splide__slide\">
                            <div class=\"product-card position-relative no-hover-border\" data-href=\"/product/${product.slug}\">
                                <button class=\"wishlist-btn${isWishlisted ? ' active' : ''}\" data-product-id=\"${product.id}\" title=\"${isWishlisted ? 'Remove from wishlist' : 'Add to wishlist'}\" type=\"button\" onclick=\"event.stopPropagation();\">
                                    <i class=\"${isWishlisted ? 'fas text-danger' : 'far'} fa-heart\"></i>
                                </button>
                                <div class=\"product-image-container\">
                                    <img src=\"${image}\" class=\"product-image\" alt=\"${product.name}\" loading=\"lazy\" onerror=\"this.src='/static/default-product.png'\">
                                    ${rating > 0 ? `<div class=\"rating-badge\"><span>${rating.toFixed(1)}</span><i class=\"fas fa-star star\"></i><span>| ${reviews}</span></div>` : ''}
                                </div>
                                <div class=\"product-info\">
                                    <a href=\"/product/${product.slug}\" style=\"text-decoration: none\" class=\"product-title\" title=\"${product.name}\">${product.name}</a>
                                    <div class="price">
                                        ${discount > 0 && discount < price ? 
                                            `<span class="fw-bold text-primary">${finalPrice.toFixed(2)}৳</span><span class="old">${price.toFixed(2)}৳</span>` : 
                                            `<span class="fw-bold text-primary">${finalPrice.toFixed(2)}৳</span>`
                                        }
                                    </div>
                                    <div class=\"d-flex justify-content-between align-items-center gap-2 product-actions\">
                                        <button class=\"btn-add-cart ${!hasStock ? 'disabled' : ''}\" 
                                                data-product-id=\"${product.id}\" 
                                                data-product-name=\"${product.name}\" 
                                                data-has-stock=\"${hasStock}\" 
                                                ${!hasStock ? 'disabled title="Out of stock"' : 'title="Add to cart"'}>
                                            <svg xmlns=\"http://www.w3.org/2000/svg\" id=\"Outline\" viewBox=\"0 0 24 24\" fill=\"#fff\" width=\"14\" height=\"14\">
                                                <path d=\"M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z\"></path>
                                                <circle cx=\"7\" cy=\"22\" r=\"2\"></circle>
                                                <circle cx=\"17\" cy=\"22\" r=\"2\"></circle>
                                            </svg> 
                                            ${hasStock ? 'Add to Cart' : 'Out of Stock'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>`;
                } catch (error) {
                    console.error('Error rendering new arrival product:', product, error);
                    return '';
                }
            }).filter(html => html).join('');

            wrapper.style.visibility = 'visible';
            const naSplide = new Splide(wrapper, {
                type: 'loop',
                perPage: 4,
                gap: '16px',
                pagination: false,
                arrows: true,
                lazyLoad: 'nearby',
                autoplay: false,
                interval: 2500,
                pauseOnHover: true,
                rewind: true,
                breakpoints: { 
                    1199: { perPage: 3 }, 
                    991: { perPage: 2 }, 
                    575: { perPage: 2 } 
                }
            });
            naSplide.mount();

            console.log(`New arrivals loaded in ${loadTime}ms`, { count: products.length });
        })
        .catch(error => {
            console.error('Failed to load new arrivals:', error);
            if (fallback) {
                fallback.innerHTML = `
                    <div class=\"col-12 text-center text-danger\">
                        <i class=\"fas fa-exclamation-triangle fa-2x mb-3\"></i>
                        <p>Failed to load new arrival products.</p>
                        <button class=\"btn btn-outline-primary btn-sm\" onclick=\"location.reload()\">
                            <i class=\"fas fa-refresh me-1\"></i>Retry
                        </button>
                    </div>
                `;
                fallback.style.display = 'block';
            }
            if (wrapper) wrapper.style.display = 'none';
        });
    })();

    // Build Best Deals Splide from API (like Top Selling)
    (function initBestDealsSplide(){
        const wrapper = document.getElementById('bestDealsSplide');
        const listEl = document.getElementById('bestDealsSplideList');
        const fallback = document.getElementById('bestDealsFallback');
        
        if (!wrapper || !listEl) return;
        
        // Add loading state
        if (fallback) {
            fallback.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Loading best deals...</p></div>';
            fallback.style.display = 'block';
        }
        
        const startTime = performance.now();
        
        fetch('/api/products/best-deals', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            const endTime = performance.now();
            const loadTime = Math.round(endTime - startTime);
            
            // Handle API response format
            const products = data.success ? data.data : data;
            
            if (!Array.isArray(products) || products.length === 0) {
                if (fallback) {
                    fallback.innerHTML = '<div class="col-12 text-center text-muted"><i class="fas fa-box-open fa-2x mb-3"></i><p>No best deals found.</p></div>';
                    fallback.style.display = 'block';
                }
                if (wrapper) wrapper.style.display = 'none';
                return;
            }
            
            if (fallback) fallback.style.display = 'none';
            
            // Render products
            listEl.innerHTML = products.map(product => {
                try {
                    const rating = product.avg_rating ?? 0;
                    const reviews = product.total_reviews ?? 0;
                    const price = Number(product.price || 0);
                    const discount = Number(product.discount || 0);
                    const finalPrice = discount > 0 ? discount : price;
                    const image = product.image || '/static/default-product.png';
                    const hasStock = product.has_stock !== false;
                    const isWishlisted = product.is_wishlisted === true;
                    
                    return `
                        <li class="splide__slide">
                            <div class="product-card position-relative no-hover-border" data-href="/product/${product.slug}">
                                <button class="wishlist-btn${isWishlisted ? ' active' : ''}" data-product-id="${product.id}" title="${isWishlisted ? 'Remove from wishlist' : 'Add to wishlist'}" type="button" onclick="event.stopPropagation();">
                                    <i class="${isWishlisted ? 'fas text-danger' : 'far'} fa-heart"></i>
                                </button>
                                <div class="product-image-container">
                                    <img src="${image}" class="product-image" alt="${product.name}" loading="lazy" onerror="this.src='/static/default-product.png'">
                                    ${rating > 0 ? `<div class="rating-badge">
                                        <span>${rating.toFixed(1)}</span>
                                        <i class="fas fa-star star"></i>
                                        <span>| ${reviews}</span>
                                    </div>` : ''}
                                </div>
                                <div class="product-info">
                                    <a href="/product/${product.slug}" style="text-decoration: none" class="product-title" title="${product.name}">${product.name}</a>
                                    <div class="price">
                                        ${discount > 0 && discount < price ? 
                                            `<span class="fw-bold text-primary">${finalPrice.toFixed(2)}৳</span><span class="old">${price.toFixed(2)}৳</span>` : 
                                            `<span class="fw-bold text-primary">${finalPrice.toFixed(2)}৳</span>`
                                        }
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center gap-2 product-actions">
                                        <button class="btn-add-cart ${!hasStock ? 'disabled' : ''}" 
                                                data-product-id="${product.id}" 
                                                data-product-name="${product.name}" 
                                                data-has-stock="${hasStock}" 
                                                ${!hasStock ? 'disabled title="Out of stock"' : 'title="Add to cart"'}>
                                            <svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff" width="14" height="14">
                                                <path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z"></path>
                                                <circle cx="7" cy="22" r="2"></circle>
                                                <circle cx="17" cy="22" r="2"></circle>
                                            </svg> 
                                            ${hasStock ? 'Add to Cart' : 'Out of Stock'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>`;
                } catch (error) {
                    console.error('Error rendering best deal product:', product, error);
                    return '';
                }
            }).filter(html => html).join('');
            
            // Initialize Splide carousel
            wrapper.style.visibility = 'visible';
            const dealsSplide = new Splide(wrapper, {
                type: 'loop',
                perPage: 4,
                gap: '16px',
                pagination: false,
                arrows: true,
                drag: true,
                flickPower: 300,
                releaseWheel: true,
                keyboard: 'focused',
                autoplay: false,
                interval: 4500,
                pauseOnHover: true,
                rewind: true,
                breakpoints: { 
                    1199: { perPage: 3 }, 
                    991: { perPage: 2 }, 
                    575: { perPage: 2 } 
                }
            });
            dealsSplide.mount();
            
            console.log(`Best deals loaded in ${loadTime}ms`, { count: products.length });
            
        })
        .catch(error => {
            console.error('Failed to load best deals:', error);
            if (fallback) {
                fallback.innerHTML = `
                    <div class="col-12 text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <p>Failed to load best deals.</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                            <i class="fas fa-refresh me-1"></i>Retry
                        </button>
                    </div>
                `;
                fallback.style.display = 'block';
            }
            if (wrapper) wrapper.style.display = 'none';
        });
    })();

    // Init Hero Splide - optimized to prevent shake
    try {
        const heroEl = document.getElementById('heroSplide');
        if (heroEl && !heroEl.__splideMounted) {
            // Pre-calculate arrows to prevent layout shift
            const shouldShowArrows = window.innerWidth > 768;
            
            const hero = new Splide(heroEl, {
                type: 'loop',
                autoplay: true,
                interval: 5000,
                pauseOnHover: true,
                arrows: shouldShowArrows,
                pagination: true,
                drag: true,
                rewind: true,
                // Disable transitions that could cause shake
                speed: 400,
                easing: 'ease',
                breakpoints: {
                    768: {
                        arrows: false
                    }
                }
            });
            hero.mount();
            heroEl.__splideMounted = true;
        }
    } catch (e) { console.error('Failed to mount hero Splide:', e); }
    
    const carousel = document.getElementById('homeHeroCarousel');
    // old markup removed; keep code for backward compatibility on other pages
    
    if (!carousel) return; // Exit if carousel element doesn't exist

    const items = carousel.querySelectorAll('.carousel-item');
    const indicators = carousel.querySelectorAll('.carousel-indicators button');
    const prevBtn = carousel.querySelector('.carousel-control-prev');
    const nextBtn = carousel.querySelector('.carousel-control-next');
    
    if (items.length === 0) return;

    let currentIndex = 0;
    let autoPlayInterval = null;
    let isHovering = false;

    // Show specific slide
    function showSlide(index) {
        items.forEach(item => item.classList.remove('active'));
        indicators.forEach(ind => ind.classList.remove('active'));
        
        currentIndex = (index + items.length) % items.length;
        items[currentIndex].classList.add('active');
        if (indicators[currentIndex]) {
            indicators[currentIndex].classList.add('active');
        }
    }

    // Next slide
    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    // Previous slide
    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    // Auto-play
    function startAutoPlay() {
        autoPlayInterval = setInterval(() => {
            if (!isHovering) nextSlide();
        }, 5000);
    }

    function stopAutoPlay() {
        if (autoPlayInterval) {
            clearInterval(autoPlayInterval);
            autoPlayInterval = null;
        }
    }

    // Event listeners for controls
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            prevSlide();
            stopAutoPlay();
            startAutoPlay();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            nextSlide();
            stopAutoPlay();
            startAutoPlay();
        });
    }

    // Indicator clicks
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', (e) => {
            e.preventDefault();
            showSlide(index);
            stopAutoPlay();
            startAutoPlay();
        });
    });

    // Hover pause
    carousel.addEventListener('mouseenter', () => {
        isHovering = true;
    });

    carousel.addEventListener('mouseleave', () => {
        isHovering = false;
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') prevSlide();
        if (e.key === 'ArrowRight') nextSlide();
    });

    // Mouse drag support with better detection (supports dragging on links)
    let isDown = false;
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    let didDrag = false;

    carousel.addEventListener('mousedown', (e) => {
        // Allow drag on everything, including anchors; we'll suppress click later if it was a drag
        isDown = true;
        startX = e.pageX - carousel.offsetLeft;
        carousel.style.cursor = 'grabbing';
        isDragging = false;
        didDrag = false;
    });

    carousel.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        currentX = e.pageX - carousel.offsetLeft;
        const walk = currentX - startX;
        
        if (Math.abs(walk) > 5) {
            isDragging = true;
            didDrag = true;
        }
    });

    carousel.addEventListener('mouseup', (e) => {
        if (!isDown) return;
        
        const deltaX = currentX - startX;
        isDown = false;
        carousel.style.cursor = 'grab';
        
        // Only change slide if dragged enough distance
        if (isDragging && Math.abs(deltaX) > 50) {
            if (deltaX < 0) {
                nextSlide();
            } else {
                prevSlide();
            }
            stopAutoPlay();
            startAutoPlay();
        }
        
        isDragging = false;
    });

    carousel.addEventListener('mouseleave', () => {
        if (isDown) {
            isDown = false;
            carousel.style.cursor = 'grab';
        }
    });

    // Prevent text selection while dragging
    carousel.addEventListener('dragstart', (e) => {
        e.preventDefault();
    });

    // Suppress anchor navigation if a drag happened, but ignore clicks on controls
    carousel.addEventListener('click', (e) => {
        if (!didDrag) return;
        if (e.target.closest('.carousel-control-prev') || e.target.closest('.carousel-control-next')) {
            didDrag = false; return;
        }
        const anchor = e.target.closest('a');
        if (anchor) {
            e.preventDefault();
            e.stopPropagation();
        }
        didDrag = false;
    }, true);

    // Touch support for mobile
    let touchStartX = 0;
    let touchEndX = 0;

    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });

    carousel.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    function handleSwipe() {
        if (touchEndX < touchStartX - 50) {
            nextSlide();
            stopAutoPlay();
            startAutoPlay();
        }
        if (touchEndX > touchStartX + 50) {
            prevSlide();
            stopAutoPlay();
            startAutoPlay();
        }
    }

    // Initialize safely (avoid duplicate mounts)
    if (!carousel.dataset.initialized) {
        carousel.dataset.initialized = 'true';
        showSlide(0);
        startAutoPlay();
        carousel.style.cursor = 'grab';
    }
});
