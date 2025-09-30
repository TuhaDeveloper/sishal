<!-- Footer -->
<footer class="footer">
    <div class="container-fluid px-3">
        <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <a href="/" class="footer-logo">
                    <img src="{{ $general_settings && $general_settings->site_logo ? asset($general_settings->site_logo) : asset('static/default-logo.webp') }}" alt="" class="img-fluid">
                </a>
                <p class="footer-description">
                    {{ $general_settings->footer_text }}
                </p>
                <div class="social-links">
                    @if($general_settings->facebook_url)
                    <a href="{{ $general_settings->facebook_url }}" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    @endif
                    @if($general_settings->x_url)
                    <a href="{{ $general_settings->x_url }}" target="_blank"><i class="fab fa-twitter"></i></a>
                    @endif
                    @if($general_settings->instagram_url)
                    <a href="{{ $general_settings->instagram_url }}" target="_blank"><i class="fab fa-instagram"></i></a>
                    @endif
                    @if($general_settings->youtube_url)
                    <a href="{{ $general_settings->youtube_url }}" target="_blank"><i class="fab fa-youtube"></i></a>
                    @endif
                </div>
            </div>
            <div class="col-lg-3">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('product.archive') }}">All Products</a></li>
                    
                    <li><a href="{{ route('vlogs') }}">Vlogs</a></li>
                    <li><a href="{{ route('about') }}">About Us</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h4 class="footer-title">Customer Service</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('contact') }}">Contact Us</a></li>
                    @foreach($additional_pages as $page)
                    @if($page->positioned_at == 'footer')
                    <li><a href="{{ route('additionalPage.show', $page->slug) }}">{{ $page->title }}</a></li>
                    @endif
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-3">
                <h4 class="footer-title">Contact Info</h4>
                <p><i class="fas fa-phone"></i> {{$general_settings->contact_phone}}</p>
                <p><i class="fas fa-envelope"></i> {{$general_settings->contact_email}}</p>
                <p><i class="fas fa-map-marker-alt"></i> {{$general_settings->contact_address}}</p>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0">&copy; {{ Date('Y') }} {{ $general_settings->site_title }}. All rights reserved.</p>
        </div>
        </div>
    </div>
</footer>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.js"></script>
<script>

    // Wishlist functionality
    document.querySelectorAll('.wishlist-btn').forEach(button => {
        button.addEventListener('click', function () {
            const icon = this.querySelector('i');
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                icon.style.color = '#e74c3c';
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                icon.style.color = '';
            }
        });
    });

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all product cards and category items
    document.querySelectorAll('.product-card, .category-item').forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(item);
    });

    // Newsletter signup (placeholder)
    function subscribeNewsletter() {
        const email = prompt('Enter your email address:');
        if (email) {
            alert('Thank you for subscribing! You will receive updates about our latest products and offers.');
        }
    }

    // Mobile menu toggle enhancement
    // const navbarToggler = document.querySelector('.navbar-toggler');
    // const navbarCollapse = document.querySelector('.navbar-collapse');

    // navbarToggler.addEventListener('click', function () {
    //     if (navbarCollapse.classList.contains('show')) {
    //         navbarCollapse.classList.remove('show');
    //     } else {
    //         navbarCollapse.classList.add('show');
    //     }
    // });

    // Close mobile menu when clicking outside
    // document.addEventListener('click', function (event) {
    //     if (!navbarToggler.contains(event.target) && !navbarCollapse.contains(event.target)) {
    //         navbarCollapse.classList.remove('show');
    //     }
    // });

    window.updateCartQtyBadge = function() {
        $.get("{{ route('cart.qtySum') }}")
            .done(function(data) {
                var qty = data.qty_sum || 0;
                
                var badge = $(".nav-cart-count");

                badge.text(qty);
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to fetch cart qty sum:', error);
            });
    };
    $(function() {
        updateCartQtyBadge();
    });

    window.updateWishlistCount = function() {
        $.get('/wishlist/count')
            .done(function(count) {
                // If response is {count: 3}, use count.count; if just a number, use count
                var wishlistCount = (typeof count === 'object' && count !== null && 'count' in count) ? count.count : count;
                $(".nav-wishlist-count").text(wishlistCount);
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to fetch wishlist count:', error);
            });
    };

    $(function() {
        updateWishlistCount();
        // Also update wishlist count after wishlist button is clicked
        $(document).on('click', '.wishlist-btn', function() {
            setTimeout(updateWishlistCount, 200); // slight delay to allow DB update
        });
    });
</script>
</body>