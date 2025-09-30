@extends('ecommerce.master')

@section('main-section')
    <!-- Hero Section -->
    <div class="hero-section text-white py-5">
        <div class="container text-center">
            <h1 class="hero-title d-flex justify-content-center">About Sisal <br><span class="hero-second-title ms-2">Fashion</span></h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-5">

        <!-- Company Story -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h2 class="hero-title mb-3">Our Story</h2>
                    <hr class="border-primary border-3 mx-auto" style="width: 80px;">
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <p class="hero-description mb-4">
                            Sisal Fashion is a modern clothing ecommerce brand built for everyday style and comfort. We curate
                            quality pieces across essentials, workwear, and occasion outfits—so you can look good and feel confident,
                            every day.
                        </p>
                        <p class="hero-description mb-4">
                            Trusted by <strong class="stat-number fs-6">50,000+ fashion-forward customers</strong>, we focus on
                            premium fabrics, flattering fits, and timeless designs, with new drops inspired by global trends.
                        </p>
                        <p class="hero-description">
                            From discovery to doorstep, our experience is seamless—secure checkout, fast shipping, and easy returns.
                            Welcome to better basics and standout style, made simple.
                        </p>
                    </div>
                    <div class="col-lg-6">
                        <div class="card bg-light border-0 shadow">
                            <div class="card-body p-4">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <h3 class="stat-number">50K+</h3>
                                        <p class="stat-label">Happy Customers</p>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <h3 class="stat-number">500+</h3>
                                        <p class="stat-label">Style SKUs</p>
                                    </div>
                                    <div class="col-6">
                                        <h3 class="stat-number">2–5 Days</h3>
                                        <p class="stat-label">Fast Delivery</p>
                                    </div>
                                    <div class="col-6">
                                        <h3 class="stat-number">30 Days</h3>
                                        <p class="stat-label">Easy Returns</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mission & Vision -->
        <div class="bg-light py-5 my-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="text-center">
                            <div class="service-icon d-inline-flex align-items-center justify-content-center mb-4"
                                style="width: 80px; height: 80px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="hero-title mb-4">Our Mission</h3>
                            <p class="promo-description px-3">
                                Make great style accessible with thoughtfully designed clothing that fits beautifully,
                                lasts longer, and supports confident self-expression.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="text-center">
                            <div class="service-icon d-inline-flex align-items-center justify-content-center mb-4"
                                style="width: 80px; height: 80px;">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h3 class="hero-title mb-4">Our Vision</h3>
                            <p class="promo-description px-3">
                                Become the most loved everyday fashion destination—where quality, fit, and effortless
                                shopping come together for everyone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Why Choose Us -->
        <div class="py-5">
            <div class="text-center mb-5">
                <h2 class="hero-title mb-3">Why Choose Sisal Fashion?</h2>
                <hr class="border-primary border-3 mx-auto mb-4" style="width: 80px;">
                <p class="promo-description px-3" style="max-width: 600px; margin: 0 auto;">
                    Fashion made easy: quality fabrics, on-trend designs, fair pricing, and a delightful shopping experience.
                </p>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
                                style="width: 80px; height: 80px; color: var(--primary-blue);">
                                <i class="fas fa-shirt fa-2x"></i>
                            </div>
                            <h4 class="promo-title mb-3">Premium Fabrics</h4>
                            <p class="promo-description">Breathable cottons, soft knits, and durable blends designed for everyday wear.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
                                style="width: 80px; height: 80px; color: var(--primary-blue);">
                                <i class="fas fa-fire-flame-curved fa-2x"></i>
                            </div>
                            <h4 class="promo-title mb-3">Trend-led Collections</h4>
                            <p class="promo-description">New arrivals weekly—classic silhouettes with a modern twist.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-4">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
                                style="width: 80px; height: 80px; color: var(--primary-blue);">
                                <i class="fas fa-box-open fa-2x"></i>
                            </div>
                            <h4 class="promo-title mb-3">Easy Returns</h4>
                            <p class="promo-description">Hassle-free 30-day returns and responsive customer care.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

      
        <!-- Shop CTA -->
        <div class="text-center py-5">
            <h2 class="hero-title mb-4">Ready to Refresh Your Wardrobe?</h2>
            <p class="hero-description mb-4" style="max-width: 600px; margin: 0 auto;">
                Discover essentials and statement pieces made for real life. Fast delivery and easy returns.
            </p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="/categories" class="btn btn-primary-custom shadow-sm">
                    Shop the Collection
                </a>
                <a href="/contact" class="btn btn-outline-custom">
                    Contact Support
                </a>
            </div>
        </div>

    </div>

    <!-- Add Font Awesome for icons if not already included -->
    <script>
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
            document.head.appendChild(link);
        }
    </script>
@endsection