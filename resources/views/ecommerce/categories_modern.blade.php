@extends('ecommerce.master')

@section('title', $pageTitle)

@section('main-section')
<!-- Modern Hero Banner -->
<section class="modern-hero-banner">
    <div class="container-fluid">
        <div class="row">
            <!-- Category Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="category-sidebar">
                    <div class="sidebar-header">
                        <h3 class="sidebar-title">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z"/>
                            </svg>
                            Category Menu
                        </h3>
                    </div>
                    <div class="category-list">
                        <a href="#" class="category-item">
                            <div class="category-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                    <path d="M12,2A3,3 0 0,1 15,5V7A3,3 0 0,1 12,10A3,3 0 0,1 9,7V5A3,3 0 0,1 12,2M19,10C19,11.38 18.44,12.63 17.54,13.54L16.13,12.13C16.63,11.63 17,10.85 17,10C17,8.9 16.1,8 15,8C13.9,8 13,8.9 13,10C13,10.85 13.37,11.63 13.87,12.13L12.46,13.54C11.56,12.63 11,11.38 11,10A3,3 0 0,1 14,7A3,3 0 0,1 17,10H19M12,12A5,5 0 0,0 7,17V19H9V17A3,3 0 0,1 12,14A3,3 0 0,1 15,17V19H17V17A5,5 0 0,0 12,12Z"/>
                                </svg>
                            </div>
                            <span class="category-text">Headphones & Earbuds</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="arrow-icon">
                                <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                            </svg>
                        </a>
                        <a href="#" class="category-item">
                            <div class="category-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                    <path d="M7,2H17A2,2 0 0,1 19,4V20A2,2 0 0,1 17,22H7A2,2 0 0,1 5,20V4A2,2 0 0,1 7,2M7,4V8H17V4H7M7,10V12H9V10H7M11,10V12H13V10H11M15,10V12H17V10H15M7,14V16H9V14H7M11,14V16H13V14H11M15,14V16H17V14H15M7,18V20H9V18H7M11,18V20H13V18H11M15,18V20H17V18H15Z"/>
                                </svg>
                            </div>
                            <span class="category-text">Gaming Consoles & Accessories</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="arrow-icon">
                                <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                            </svg>
                        </a>
                        <a href="#" class="category-item">
                            <div class="category-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                    <path d="M4,6H20V8H4V6M4,11H20V13H4V11M4,16H20V18H4V16Z"/>
                                </svg>
                            </div>
                            <span class="category-text">Computer Peripherals</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="arrow-icon">
                                <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                            </svg>
                        </a>
                        <a href="#" class="category-item">
                            <div class="category-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                    <path d="M4,4H7L9,2H15L17,4H20A2,2 0 0,1 22,6V18A2,2 0 0,1 20,20H4A2,2 0 0,1 2,18V6A2,2 0 0,1 4,4M12,7A5,5 0 0,0 7,12A5,5 0 0,0 12,17A5,5 0 0,0 17,12A5,5 0 0,0 12,7M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9Z"/>
                                </svg>
                            </div>
                            <span class="category-text">Cameras & Photography Equipment</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="arrow-icon">
                                <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                            </svg>
                        </a>
                        <a href="#" class="category-item">
                            <div class="category-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                    <path d="M12,2A3,3 0 0,1 15,5V7A3,3 0 0,1 12,10A3,3 0 0,1 9,7V5A3,3 0 0,1 12,2M19,10C19,11.38 18.44,12.63 17.54,13.54L16.13,12.13C16.63,11.63 17,10.85 17,10C17,8.9 16.1,8 15,8C13.9,8 13,8.9 13,10C13,10.85 13.37,11.63 13.87,12.13L12.46,13.54C11.56,12.63 11,11.38 11,10A3,3 0 0,1 14,7A3,3 0 0,1 17,10H19M12,12A5,5 0 0,0 7,17V19H9V17A3,3 0 0,1 12,14A3,3 0 0,1 15,17V19H17V17A5,5 0 0,0 12,12Z"/>
                                </svg>
                            </div>
                            <span class="category-text">Speakers & Audio Systems</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="arrow-icon">
                                <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                            </svg>
                        </a>
                        <a href="#" class="category-item">
                            <div class="category-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                    <path d="M6,18A6,6 0 0,0 12,24A6,6 0 0,0 18,18V16H6M6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12V10H6M6,6A6,6 0 0,0 12,12A6,6 0 0,0 18,6V4H6M12,0A6,6 0 0,0 6,6H18A6,6 0 0,0 12,0Z"/>
                                </svg>
                            </div>
                            <span class="category-text">Smartwatches & Wearables</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="arrow-icon">
                                <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                            </svg>
                        </a>
                        <a href="#" class="category-item">
                            <div class="category-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                    <path d="M17,19H7V5H17M17,1H7C5.89,1 5,1.89 5,3V21C5,22.11 5.89,23 7,23H17C18.11,23 19,22.11 19,21V3C19,1.89 18.11,1 17,1Z"/>
                                </svg>
                            </div>
                            <span class="category-text">Smartphones & Accessories</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="arrow-icon">
                                <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Hero Content -->
            <div class="col-lg-9 col-md-8">
                <div class="hero-banner">
                    <div class="banner-content">
                        <div class="banner-badge">
                            <span class="badge-text">New Arrival</span>
                        </div>
                        <h1 class="banner-title">MENS WATCH</h1>
                        <div class="price-circle">
                            <div class="price-text">
                                <span class="price-label">STARTING FROM</span>
                                <span class="price-value">$99</span>
                            </div>
                        </div>
                        <div class="banner-actions">
                            <button class="btn-shop-now">SHOP NOW</button>
                        </div>
                        <div class="banner-footer">
                            <div class="website-info">
                                <span class="website-url">alicom.com</span>
                                <div class="social-links">
                                    <a href="#" class="social-link">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                            <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                                        </svg>
                                    </a>
                                    <a href="#" class="social-link">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                            <path d="M22.46,6C21.69,6.35 20.86,6.58 20,6.69C20.88,6.16 21.56,5.32 21.88,4.31C21.05,4.81 20.13,5.16 19.16,5.36C18.37,4.5 17.26,4 16,4C13.65,4 11.73,5.92 11.73,8.29C11.73,8.63 11.77,8.96 11.84,9.27C8.28,9.09 5.11,7.38 3,4.79C2.63,5.42 2.42,6.16 2.42,6.94C2.42,8.43 3.17,9.75 4.33,10.5C3.62,10.5 2.96,10.3 2.38,10C2.38,10 2.38,10 2.38,10.03C2.38,12.11 3.86,13.85 5.82,14.24C5.46,14.34 5.08,14.39 4.69,14.39C4.42,14.39 4.15,14.36 3.89,14.31C4.43,16 6,17.26 7.89,17.29C6.43,18.45 4.58,19.13 2.56,19.13C2.22,19.13 1.88,19.11 1.54,19.07C3.44,20.29 5.7,21 8.12,21C16,21 20.33,14.46 20.33,8.79C20.33,8.6 20.33,8.42 20.32,8.23C21.16,7.63 21.88,6.87 22.46,6Z"/>
                                        </svg>
                                    </a>
                                    <a href="#" class="social-link">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                            <path d="M12,2.163c3.204,0 3.584,0.012 4.85,0.07 3.252,0.148 4.771,1.691 4.919,4.919 0.058,1.265 0.069,1.645 0.069,4.849 0,3.205 -0.012,3.584 -0.069,4.849 -0.149,3.225 -1.664,4.771 -4.919,4.919 -1.266,0.058 -1.644,0.07 -4.85,0.07 -3.204,0 -3.584,-0.012 -4.849,-0.07 -3.26,-0.149 -4.771,-1.699 -4.919,-4.92 -0.058,-1.265 -0.07,-1.644 -0.07,-4.849 0,-3.204 0.013,-3.583 0.07,-4.849 0.149,-3.227 1.664,-4.771 4.919,-4.919 1.266,-0.058 1.645,-0.07 4.849,-0.07zm0,-2.163c-3.259,0 -3.667,0.014 -4.947,0.072 -4.358,0.2 -6.78,2.618 -6.98,6.98 -0.059,1.281 -0.073,1.689 -0.073,4.948 0,3.259 0.014,3.668 0.072,4.948 0.2,4.358 2.618,6.78 6.98,6.98 1.281,0.058 1.689,0.072 4.948,0.072 3.259,0 3.668,-0.014 4.948,-0.072 4.354,-0.2 6.782,-2.618 6.979,-6.98 0.059,-1.28 0.073,-1.689 0.073,-4.948 0,-3.259 -0.014,-3.667 -0.072,-4.947 -0.196,-4.354 -2.617,-6.78 -6.979,-6.98 -1.281,-0.059 -1.69,-0.073 -4.949,-0.073zm0,5.838c-3.403,0 -6.162,2.759 -6.162,6.162s2.759,6.163 6.162,6.163 6.162,-2.759 6.162,-6.163c0,-3.403 -2.759,-6.162 -6.162,-6.162zm0,10.162c-2.209,0 -4,-1.79 -4,-4 0,-2.209 1.791,-4 4,-4s4,1.791 4,4c0,2.21 -1.791,4 -4,4zm6.406,-11.845c-0.796,0 -1.441,-0.645 -1.441,-1.44s0.645,-1.44 1.441,-1.44c0.795,0 1.439,0.645 1.439,1.44s-0.644,1.44 -1.439,1.44z"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="savings-circle">
                                <span class="savings-text">SAVE UP TO 70%</span>
                            </div>
                        </div>
                    </div>
                    <div class="banner-image">
                        <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&h=600&fit=crop&crop=center" alt="Men's Watch" class="product-image">
                    </div>
                    <div class="banner-navigation">
                        <div class="nav-dots">
                            <span class="dot active"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                        <div class="nav-arrows">
                            <button class="nav-arrow prev">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M15.41,16.58L10.83,12L15.41,7.41L14,6L8,12L14,18L15.41,16.58Z"/>
                                </svg>
                            </button>
                            <button class="nav-arrow next">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Grid Section -->
<section class="categories-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="categories-grid">
                    @forelse($categories as $category)
                    <a href="{{ route('product.archive') }}?category={{ $category->slug }}" class="category-card">
                        <div class="category-image">
                            @if($category->image)
                                <img src="{{ asset($category->image) }}" alt="{{ $category->name }}" class="img-fluid">
                            @else
                                <div class="placeholder-image">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="60" height="60" fill="currentColor">
                                        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z"/>
                                    </svg>
                                </div>
                            @endif
                        <div class="category-overlay">
                            <h3 class="category-name">{{ $category->name }}</h3>
                        </div>
                        </div>
                    </a>
                    @empty
                    <div class="col-12 text-center">
                        <div class="no-categories">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="80" height="80" fill="currentColor">
                                <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z"/>
                            </svg>
                            <h3>No Categories Available</h3>
                            <p>Check back later for new categories!</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
