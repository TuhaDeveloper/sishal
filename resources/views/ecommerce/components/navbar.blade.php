<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                {{ $general_settings->top_text }}
            </div>
        </div>
    </div>
</div>

<!-- Header Section -->
<header class="modern-header">
    <div class="container">
        <div class="row align-items-center py-3">
            <!-- Logo -->
            <div class="col-lg-3 col-md-4">
                <a class="navbar-brand d-flex align-items-center" href="/">
                    <img src="{{ $general_settings && $general_settings->site_logo ? asset($general_settings->site_logo) : asset('static/default-logo.webp') }}" alt="{{ $general_settings->site_title ?? 'alicom' }}" class="logo-img">
                </a>
            </div>

            <!-- Search Bar -->
            <div class="col-lg-6 col-md-8">
                <form class="search-form" action="{{ route('search') }}" method="get">
                    <div class="search-container">
                        <input type="text" class="search-input" placeholder="Search product" name="search">
                        <button class="search-btn" type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                                <path d="M23.707,22.293l-5.969-5.969a10.016,10.016,0,1,0-1.414,1.414l5.969,5.969a1,1,0,0,0,1.414-1.414ZM10,18a8,8,0,1,1,8-8A8.009,8.009,0,0,1,10,18Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Side Action Buttons -->
            <div class="col-lg-3 col-md-12">
                <div class="header-actions">
                    <!-- Mobile search toggle -->
                    <button class="action-btn mobile-search-toggle d-lg-none me-1" id="mobileSearchToggle" aria-controls="mobileSearchBar" aria-expanded="false" aria-label="Toggle search">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                            <path d="M23.707,22.293l-5.969-5.969a10.016,10.016,0,1,0-1.414,1.414l5.969,5.969a1,1,0,0,0,1.414-1.414ZM10,18a8,8,0,1,1,8-8A8.009,8.009,0,0,1,10,18Z" fill="currentColor"/>
                        </svg>
                    </button>
                    <!-- Mobile menu toggle -->
                    <button class="action-btn mobile-menu-toggle d-lg-none" id="mobileMenuToggle" aria-controls="mobileNav" aria-expanded="false" aria-label="Toggle navigation">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                            <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <a href="{{ route('wishlist.index') }}" class="action-btn" title="Wishlist">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z" fill="currentColor"/>
                        </svg>
                        <span class="badge nav-wishlist-count">0</span>
                    </a>
                    
                    <a href="#" class="action-btn" onclick="openOffcanvasCart(); return false;" title="Cart">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z" fill="currentColor"/>
                            <circle cx="7" cy="22" r="2" fill="currentColor"/>
                            <circle cx="17" cy="22" r="2" fill="currentColor"/>
                        </svg>
                        <span class="badge nav-cart-count">0</span>
                    </a>
                    
                    <div class="dropdown">
                        <button class="action-btn login-btn" id="accountDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Account">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                                <path d="M12.006,12.309c3.611-.021,5.555-1.971,5.622-5.671-.062-3.56-2.111-5.614-5.634-5.637-3.561,.022-5.622,2.17-5.622,5.637,0,3.571,2.062,5.651,5.634,5.672Zm-.012-9.309c2.437,.016,3.591,1.183,3.634,3.636-.047,2.559-1.133,3.657-3.622,3.672-2.495-.015-3.582-1.108-3.634-3.654,.05-2.511,1.171-3.639,3.622-3.654Z" fill="currentColor"/>
                                <path d="M11.994,13.661c-5.328,.034-8.195,2.911-8.291,8.322-.01,.552,.43,1.008,.982,1.018,.516-.019,1.007-.43,1.018-.982,.076-4.311,2.08-6.331,6.291-6.357,4.168,.027,6.23,2.106,6.304,6.356,.01,.546,.456,.983,1,.983h.018c.552-.01,.992-.465,.983-1.017-.092-5.333-3.036-8.288-8.304-8.322Z" fill="currentColor"/>
                            </svg>
                            <span class="login-text">Login</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                            @guest
                                <li><a class="dropdown-item" href="{{ route('login') }}">Login</a></li>
                                <li><a class="dropdown-item" href="{{ route('register') }}">Register</a></li>
                            @else
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">Logout</button>
                                    </form>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Navigation -->
<nav class="main-nav">
    <div class="container">
        <div class="row align-items-center">
            <!-- Category label aligned with left sidebar (desktop only) -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="nav-category-label">Category Menu</div>
            </div>
            <!-- Navigation Links -->
            <div class="col-lg-6 col-md-7">
                <ul class="nav-links">
                    <li class="nav-item {{ request()->is('/') ? 'active' : '' }}"><a href="/" class="nav-link">Home</a></li>
                    
                    <li class="nav-item {{ request()->is('products*') ? 'active' : '' }}"><a href="{{ route('product.archive') }}" class="nav-link">Products</a></li>
                    <li class="nav-item {{ request()->is('best-deal') ? 'active' : '' }}"><a href="{{ route('best.deal') }}" class="nav-link">Best Deal</a></li>
                    <li class="nav-item {{ request()->is('contact*') ? 'active' : '' }}"><a href="{{ route('contact') }}" class="nav-link">Contact</a></li>
                    @foreach($additional_pages as $page)
                    @if($page->positioned_at == 'navbar')
                    <li class="nav-item {{ request()->is('page/' . $page->slug) ? 'active' : '' }}"><a href="{{ route('additionalPage.show', $page->slug) }}" class="nav-link">{{ $page->title }}</a></li>
                    @endif
                    @endforeach
                </ul>
            </div>
            <!-- Right actions align to the end -->
            <div class="col-lg-3 d-none d-lg-block">
            </div>
        </div>
        <!-- Mobile overlay & side drawer -->
        <div class="mobile-overlay d-lg-none" id="mobileOverlay" hidden></div>
        <aside class="mobile-drawer d-lg-none" id="mobileNav" aria-hidden="true" hidden>
            <div class="drawer-header">
                <span>Menu</span>
                <button class="drawer-close" id="mobileMenuClose" aria-label="Close menu">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="drawer-content">
                <a href="{{ auth()->check() ? route('profile.edit') : route('login') }}" class="drawer-login">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5Z" fill="currentColor"/></svg>
                    </span>
                    <span class="text">{{ auth()->check() ? 'Profile' : 'Login' }}</span>
                    <span class="chev">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </a>

                <div class="drawer-quick">
                    <a href="{{ route('wishlist.index') }}" class="quick-item">
                        <span class="qi-left">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Z" fill="currentColor"/></svg>
                            <span>Wishlist</span>
                        </span>
                        <span class="qi-badge nav-wishlist-count">0</span>
                    </a>
                    <a href="#" onclick="openOffcanvasCart(); return false;" class="quick-item">
                        <span class="qi-left">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077Z" fill="currentColor"/></svg>
                            <span>My Cart</span>
                        </span>
                        <span class="qi-badge nav-cart-count">0</span>
                    </a>
                </div>

                <nav class="drawer-links">
                    <a href="/" class="drawer-link {{ request()->is('/') ? 'active' : '' }}">Home</a>
                    
                    <a href="{{ route('product.archive') }}" class="drawer-link {{ request()->is('products*') ? 'active' : '' }}">Products</a>
                    <a href="{{ route('best.deal') }}" class="drawer-link {{ request()->is('best-deal') ? 'active' : '' }}">Best Deal</a>
                    <a href="{{ route('contact') }}" class="drawer-link {{ request()->is('contact*') ? 'active' : '' }}">Contact</a>
                    @foreach($additional_pages as $page)
                        @if($page->positioned_at == 'navbar')
                        <a href="{{ route('additionalPage.show', $page->slug) }}" class="drawer-link {{ request()->is('page/' . $page->slug) ? 'active' : '' }}">{{ $page->title }}</a>
                        @endif
                    @endforeach
                </nav>
            </div>
        </aside>
    </div>
</nav>
<!-- end header-stack -->
<!-- Mobile search bar dropdown -->
<div class="mobile-searchbar d-lg-none" id="mobileSearchBar" hidden>
    <div class="container">
        <form class="mobile-search-form" action="{{ route('search') }}" method="get">
            <input type="text" class="mobile-search-input" placeholder="Search product" name="search">
            <button class="mobile-search-btn" type="submit" aria-label="Search">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path d="M23.707,22.293l-5.969-5.969a10.016,10.016,0,1,0-1.414,1.414l5.969,5.969a1,1,0,0,0,1.414-1.414ZM10,18a8,8,0,1,1,8-8A8.009,8.009,0,0,1,10,18Z" fill="currentColor"/></svg>
            </button>
        </form>
    </div>
    </div>
<script>
    (function(){
        var toggle = document.getElementById('mobileMenuToggle');
        var drawer = document.getElementById('mobileNav');
        var overlay = document.getElementById('mobileOverlay');
        var closeBtn = document.getElementById('mobileMenuClose');
        var searchToggle = document.getElementById('mobileSearchToggle');
        var searchBar = document.getElementById('mobileSearchBar');
        // Ensure overlay and drawer are top-level so they cover the whole site
        if(overlay && overlay.parentElement !== document.body){
            document.body.appendChild(overlay);
        }
        if(drawer && drawer.parentElement !== document.body){
            document.body.appendChild(drawer);
        }

        function openDrawer(){
            if(!drawer || !overlay) return;
            drawer.removeAttribute('hidden');
            overlay.removeAttribute('hidden');
            // Delay class application to next frame so transition plays smoothly
            requestAnimationFrame(function(){
                drawer.classList.add('open');
                overlay.classList.add('open');
                toggle && toggle.setAttribute('aria-expanded','true');
                document.documentElement.classList.add('mobile-lock');
            });
        }
        function closeDrawer(){
            if(!drawer || !overlay) return;
            drawer.classList.remove('open');
            overlay.classList.remove('open');
            toggle && toggle.setAttribute('aria-expanded','false');
            document.documentElement.classList.remove('mobile-lock');
            // hide after transition
            setTimeout(function(){
                drawer.setAttribute('hidden','');
                overlay.setAttribute('hidden','');
            }, 420);
        }
        if(toggle && drawer){
            toggle.addEventListener('click', function(){ openDrawer(); });
        }
        if(overlay){ overlay.addEventListener('click', function(){ closeDrawer(); }); }
        if(closeBtn){ closeBtn.addEventListener('click', function(){ closeDrawer(); }); }
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closeDrawer(); } });

        // Close drawer slowly when any link or button inside is clicked
        if(drawer){
            drawer.addEventListener('click', function(e){
                var interactive = e.target.closest('a, button');
                if(interactive){ closeDrawer(); }
            });
        }

        // Mobile search toggle
        function toggleSearchBar(){
            if(!searchBar) return;
            var open = searchBar.classList.toggle('open');
            if(open){ searchBar.removeAttribute('hidden'); } else { setTimeout(function(){ searchBar.setAttribute('hidden',''); }, 150); }
            if(searchToggle){ searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false'); }
        }
        if(searchToggle && searchBar){
            searchToggle.addEventListener('click', function(){ toggleSearchBar(); });
        }
    })();
</script>
<style>
@media (max-width: 992px) {
    .main-nav .nav-links, .main-nav .nav-category-label { display: none !important; }
}
</style>
@include('ecommerce.components.offcanvas-cart')