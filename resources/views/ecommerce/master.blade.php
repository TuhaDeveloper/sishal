@include('ecommerce.components.header')
@include('ecommerce.components.navbar')

<!-- Main Content Container for AJAX Loading -->
<div id="main-content-container">
@yield('main-section')
@stack('scripts')
</div>

@include('ecommerce.components.footer')

<!-- Page loading optimization -->
<div id="page-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.3s ease-out;">
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

/* Minimal button interactions - barely noticeable */
.btn, .nav-link, .tab-btn, .action-btn, .header-link {
    transition: all 0.02s ease;
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
    will-change: transform;
}

.btn:hover, .nav-link:hover, .tab-btn:hover, .action-btn:hover, .header-link:hover {
    transform: translateY(-0.1px) translateZ(0);
    -webkit-transform: translateY(-0.1px) translateZ(0);
}

.btn:active, .nav-link:active, .tab-btn:active, .action-btn:active, .header-link:active {
    transform: translateY(0) translateZ(0);
    -webkit-transform: translateY(0) translateZ(0);
    transition: all 0.005s ease;
}

/* Specific navigation link fixes - minimal */
.nav-links .nav-link {
    position: relative;
    display: inline-block;
    padding: 12px 16px;
    text-decoration: none;
    transition: color 0.03s ease, background-color 0.03s ease;
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
}

.nav-links .nav-link:hover {
    transform: translateY(-0.05px) translateZ(0);
    -webkit-transform: translateY(-0.05px) translateZ(0);
}

.nav-links .nav-link:active {
    transform: translateY(0) translateZ(0);
    -webkit-transform: translateY(0) translateZ(0);
    transition: all 0.005s ease;
}

/* Prevent layout shifts on navigation */
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

/* Action buttons stability */
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
    transition: all 0.02s ease;
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
}

.action-btn:hover {
    transform: translateY(-0.1px) translateZ(0);
    -webkit-transform: translateY(-0.1px) translateZ(0);
}

.action-btn:active {
    transform: translateY(0) translateZ(0);
    -webkit-transform: translateY(0) translateZ(0);
    transition: all 0.005s ease;
}
</style>

<script>
// Enhanced smooth navigation without Turbo CDN
document.addEventListener('DOMContentLoaded', function() {
    // compute header heights and expose as CSS variables for layout
    function setHeaderVars(){
        var topBar = document.querySelector('.top-bar');
        var header = document.querySelector('header.modern-header');
        var nav = document.querySelector('nav.main-nav');
        var r = document.documentElement;
        if(topBar){ r.style.setProperty('--tb', topBar.offsetHeight + 'px'); }
        if(header){ r.style.setProperty('--hd', header.offsetHeight + 'px'); }
        if(nav){ r.style.setProperty('--nv', nav.offsetHeight + 'px'); }
    }
    setHeaderVars();
    window.addEventListener('load', setHeaderVars);
    window.addEventListener('resize', setHeaderVars);
    // CSS handles fixed layout using variables; just ensure vars stay updated
    // Hide page loader with subtle transition
    const pageLoader = document.getElementById('page-loader');
    if (pageLoader) {
        pageLoader.style.transition = 'opacity 0.2s ease-out';
        pageLoader.style.opacity = '0';
        setTimeout(() => {
            pageLoader.style.display = 'none';
        }, 200);
    }
    
    // AJAX Navigation System - Load only page content
    function initializeAjaxNavigation() {
        const navLinks = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="javascript:"]):not([target="_blank"])');
        if (navLinks.length === 0) {
            console.log('No navigation links found for AJAX');
            return;
        }
        
    navLinks.forEach(link => {
            if (link) {
                link.removeEventListener('click', handleAjaxNavigation);
                link.addEventListener('click', handleAjaxNavigation);
            }
        });
    }
    
    function handleAjaxNavigation(e) {
        // Only handle internal links
        if (this.hostname === window.location.hostname) {
            e.preventDefault();
            
            const url = this.href;
            const title = this.textContent.trim();
            
            // Minimal transition before loading
            // Avoid body transforms that can break position: fixed
            document.body.style.transition = 'opacity 0.005s ease-out';
            document.body.style.opacity = '1';
            
            // Load page content via AJAX
            loadPageContent(url, title);
        }
    }
    
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
    
    function loadPageContent(url, title) {
        console.log('Loading page content for:', url); // Debug log
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            console.log('Server response length:', html.length); // Debug log
            console.log('Server response preview:', html.substring(0, 200)); // Debug log
            
            // Parse the HTML response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Extract only the main content from the response
            // The server returns the full page, so we need to find the main content area
            let mainContent = doc.querySelector('#main-content-container');
            
            // If not found, try to find the main content area
            if (!mainContent) {
                mainContent = doc.querySelector('main') || 
                             doc.querySelector('.container') ||
                             doc.body;
            }
            
            console.log('Found main content:', mainContent); // Debug log
            
            if (mainContent) {
                // Update the main content container
                const container = document.getElementById('main-content-container');
                if (container) {
                    // If we found the #main-content-container, use its innerHTML directly
                    if (mainContent.id === 'main-content-container') {
                        container.innerHTML = mainContent.innerHTML;
                    } else {
                        // Otherwise, try to find the content inside
                        const contentToUpdate = mainContent.querySelector('#main-content-container') || mainContent;
                        container.innerHTML = contentToUpdate.innerHTML;
                    }

                        // Execute any inline scripts within the newly injected content
                        // This ensures per-page scripts (e.g., home product loader) run after AJAX navigation
                        (function executeScriptsInElement(rootEl){
                            if (!rootEl) return;
                            const scripts = Array.from(rootEl.querySelectorAll('script'));
                            scripts.forEach(oldScript => {
                                const newScript = document.createElement('script');
                                // Copy attributes
                                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                                if (oldScript.src) {
                                    newScript.src = oldScript.src;
                                } else {
                                    newScript.text = oldScript.textContent;
                                }
                                // Replace in DOM to trigger execution
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            });
                        })(container);
                }
                
                // Update page title
                const newTitle = doc.querySelector('title');
                if (newTitle) {
                    document.title = newTitle.textContent;
                }
                
                // Update URL without page reload
                history.pushState({}, title, url);
                
                // Update active navigation state
                updateActiveNavigation(url);
                
                // Re-initialize any page-specific scripts
                reinitializePageScripts();
                
                // Hide loader
                hidePageLoader();
            } else {
                // Fallback: redirect to the page
                window.location.href = url;
            }
        })
        .catch(error => {
            console.error('Error loading page:', error);
            // Fallback: redirect to the page
            window.location.href = url;
        });
    }
    
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
    
    function reinitializePageScripts() {
        // Re-initialize any scripts that need to run on new content
        initializeAjaxNavigation();
        ensureNavLinksWork();
        ensureContactAboutWork();
        
        // Re-initialize tab functionality for product pages
        initializeTabFunctionality();
        
        // Re-initialize any page-specific functionality
        if (typeof initializePageSpecificScripts === 'function') {
            initializePageSpecificScripts();
        }

        // Ensure Home page product grid loads after AJAX navigation
        if (typeof loadMostSoldProducts === 'function') {
            loadMostSoldProducts();
        }
    }
    
    // Initialize AJAX navigation
    initializeAjaxNavigation();
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state) {
            loadPageContent(window.location.href, document.title);
        }
    });
    
    // Handle initial page load
    if (window.history.state === null) {
        // This is the initial page load, no need to do anything special
        console.log('Initial page load - master layout loaded');
    }
    
    // Specific handling for navigation links to ensure consistency
    function ensureNavLinksWork() {
        const navLinks = document.querySelectorAll('.nav-links .nav-link');
        if (navLinks.length === 0) {
            console.log('No nav links found');
            return;
        }
        
        navLinks.forEach(link => {
            if (link) {
                // Remove any existing listeners to prevent duplicates
                link.removeEventListener('click', handleNavLinkClick);
                // Ensure each nav link has the transition effect
                link.addEventListener('click', handleNavLinkClick);
            }
        });
    }
    
    function handleNavLinkClick(e) {
        // Minimal transition - barely visible
        setTimeout(() => {
            document.body.style.transition = 'opacity 0.01s ease-out';
            document.body.style.opacity = '0.999';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
                document.body.style.transition = 'opacity 0.01s ease-in';
            }, 3);
        }, 2);
    }
    
    // Apply specific nav link handling
    ensureNavLinksWork();
    
    // Re-apply transitions if DOM changes (for dynamic content)
    const navObserver = new MutationObserver(function(mutations) {
        let shouldReapply = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // Check if navigation elements were added/modified
                const navElements = document.querySelectorAll('.nav-links, .nav-link');
                if (navElements.length > 0) {
                    shouldReapply = true;
                }
            }
        });
        if (shouldReapply) {
            initializeAjaxNavigation();
            ensureNavLinksWork();
        }
    });
    
    navObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Also re-apply on window load to catch any late-loading content
    window.addEventListener('load', function() {
        initializeAjaxNavigation();
        ensureNavLinksWork();
    });
    
    // Fallback: Re-apply every 2 seconds to ensure consistency
    setInterval(function() {
        ensureNavLinksWork();
    }, 2000);
    
    // Additional fallback for Contact and About buttons specifically
    function ensureContactAboutWork() {
        const contactLink = document.querySelector('a[href*="contact"]');
        const aboutLink = document.querySelector('a[href*="about"]');
        
        [contactLink, aboutLink].forEach(link => {
            if (link) {
                link.removeEventListener('click', handleNavLinkClick);
                link.addEventListener('click', handleNavLinkClick);
            }
        });
    }
    
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
    
    // Apply specific handling for Contact and About
    ensureContactAboutWork();
    
    // Initialize tab functionality on page load
    initializeTabFunctionality();

    // Define and run loader for Most Sold Products (Home)
    window.loadMostSoldProducts = function() {
        var container = window.jQuery ? window.jQuery('#mostSoldProductsContainer') : null;
        if (!container || container.length === 0) return; // Not on home section

        // If already populated, do nothing
        if (container.children().length > 0) return;

        if (window.jQuery) {
            window.jQuery.get('/api/products/most-sold', function (products) {
                container.empty();
                if (!products || !products.length) {
                    container.append('<div class="col-12 text-center text-muted">No products found.</div>');
                    return;
                }
                products.forEach(function (product) {
                    var rating = product.avg_rating ?? product.rating ?? 0;
                    var sold = product.sold_count ?? product.total_sold ?? product.sold ?? 0;
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
                                ' + ((rating > 0 || sold > 0) ? '\
                                <div class="product-meta">\
                                    <div class="stars" aria-label="' + rating + ' out of 5">' + Array.from({length:5}).map(function(_,i){return '<i class="fa' + (i < Math.round(rating) ? 's' : 'r') + ' fa-star"></i>';}).join('') + '</div>\
                                    <div class="sold">' + sold + ' Sold</div>\
                                </div>' : '') + '\
                                <div class="price">' + price + '৳</div>\
                                <div class="d-flex justify-content-between align-items-center gap-2 product-actions">\
                                    <button class="btn-add-cart" data-product-id="' + product.id + '"><svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff" width="14" height="14"><path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z"></path><circle cx="7" cy="22" r="2"></circle><circle cx="17" cy="22" r="2"></circle></svg> Add to Cart</button>\
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
    
    // Re-apply Contact and About handling periodically
    setInterval(ensureContactAboutWork, 1000);
    
    // Re-apply tab functionality periodically
    setInterval(initializeTabFunctionality, 2000);
    
    // Minimal image loading
    const images = document.querySelectorAll('img');
    if (images.length > 0) {
        images.forEach(img => {
            if (img) {
                if (img.complete) {
                    img.style.opacity = '1';
                } else {
                    img.style.opacity = '0.995';
                    img.style.transition = 'opacity 0.03s ease-in-out';
                    img.addEventListener('load', function() {
                        this.style.opacity = '1';
                    });
                }
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
    
    // Prevent any layout shifts that could cause vibration
    const preventVibration = () => {
        // Do not apply transforms to body; this breaks position: fixed
        document.body.style.transform = '';
        document.body.style.willChange = '';
    };
    
    // Apply vibration prevention on page load and navigation
    preventVibration();
    
    // Re-apply on window resize to prevent layout shifts
    window.addEventListener('resize', preventVibration);
    
    // Prevent vibration on scroll
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(function() {
                preventVibration();
                ticking = false;
            });
            ticking = true;
        }
    });
});
</script>

</body>
</html>