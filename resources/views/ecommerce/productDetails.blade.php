@extends('ecommerce.master')

@section('main-section')
    <!-- Product Details Content Section -->
    <style>
        .product-section {
            background: white;
            padding: 40px 0;
        }

        .product-main {
            display: flex;
            margin-bottom: 60px;
        }

        /* Product Gallery */
        .product-gallery {
            display: flex;
            flex-direction: column;
        }

        .product-gallery {
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
        }

        .swiper-container,
        .swiper-wrapper,
        .main-swiper .swiper-slide {
            width: 100% !important;
            max-width: 100%;
            box-sizing: border-box;
        }

        .main-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .thumb-swiper {
            width: 100% !important;
        }

        .thumb-swiper .swiper-slide {
            width: max-content !important;
        }

        .thumb-swiper .swiper-slide img {
            width: 100%;
            max-height: 150px;
            height: 100%;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        .main-image {
            width: 100%;
            height: 400px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        /* Removed unused zoom overlay and legacy thumbnail grid/styles */

        /* Product Info */
        .product-info h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stars {
            color: #ffc107;
            font-size: 18px;
        }

        .rating-text {
            color: #666;
            font-size: 14px;
        }

        /* Removed unused legacy price styles (replaced by .product-price usage) */

        .product-description {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .product-options {
            margin-bottom: 30px;
        }

        .option-group {
            margin-bottom: 20px;
        }

        .option-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .option-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            background: white;
            cursor: pointer;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .quantity-selector label {
            font-weight: 500;
            color: #333;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }

        .quantity-btn {
            background: #f8f9fa;
            border: none;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.3s;
            font-weight: bold;
        }

        .quantity-btn:hover {
            background: #e9ecef;
        }

        .quantity-input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: none;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
            font-size: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        .btn-primary {
            background: #2196F3;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #1976D2;
        }

        .btn-secondary {
            background: white;
            color: #2196F3;
            border: 1px solid #2196F3;
            padding: 11px 23px;
        }

        .btn-secondary:hover {
            background: #2196F3;
            color: white;
        }

        .product-features {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .features-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .features-list li {
            padding: 8px 0;
            color: #666;
            position: relative;
            padding-left: 25px;
        }

        .features-list li::before {
            content: "✓";
            color: #2196F3;
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        /* Product Tabs */
        .product-tabs {
            margin-bottom: 60px;
        }

        .tab-nav {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }

        .tab-btn {
            padding: 15px 30px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }

        .tab-btn.active {
            color: #2196F3;
            border-bottom-color: #2196F3;
        }

        .tab-btn:hover {
            color: #2196F3;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
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

        .tab-content h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
        }

        .tab-content p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .specifications-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .specifications-table th,
        .specifications-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .specifications-table th {
            background: #f8f9fa;
            font-weight: 500;
            color: #333;
        }

        .specifications-table td {
            color: #666;
        }

        /* Reviews */
        .reviews-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .reviews-summary h3 {
            margin-bottom: 15px;
        }

        .rating-breakdown {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .overall-rating {
            text-align: center;
        }

        .rating-number {
            font-size: 48px;
            font-weight: bold;
            color: #2196F3;
        }

        .rating-bars {
            flex: 1;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .bar-fill {
            flex: 1;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }

        .bar-fill-inner {
            height: 100%;
            background: #ffc107;
            transition: width 0.3s ease;
        }

        .review-item {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: 500;
            color: #333;
        }

        .review-date {
            color: #666;
            font-size: 14px;
        }

        .review-text {
            color: #666;
            line-height: 1.6;
        }

        /* Related Products */
        .related-products {
            background: #f8f9fa;
            padding: 60px 0;
        }

        .related-products h2 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 28px;
            color: #333;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .product-card-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .product-card-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .product-card-info {
            padding: 20px;
        }

        .product-card-title {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
        }

        .product-card-price {
            font-size: 20px;
            font-weight: bold;
            color: #2196F3;
            margin-bottom: 15px;
        }

        .product-card-btn {
            width: 100%;
            padding: 10px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: 500;
        }

        .product-card-btn:hover {
            background: #1976D2;
        }

        /* Responsive */
        @media (max-width: 768px) {

            .action-buttons {
                flex-direction: column;
            }

            .tab-nav {
                flex-wrap: wrap;
            }

            .tab-btn {
                padding: 10px 20px;
                font-size: 14px;
            }

            .rating-breakdown {
                flex-direction: column;
                align-items: stretch;
            }

            .main-image {
                max-height: 250px;
            }

            .thumb-swiper .swiper-slide img {
                height: 40px;
            }
        }

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
            z-index: 9999;
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
    </style>

    <div class="container">
        <div class="product-main row">
            <!-- Product Gallery -->
            <div class="col-lg-6 col-md-7 col-12" style="padding: 20px;">
                <div class="product-gallery">
                    <div class="swiper main-swiper">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="main-image">
                            </div>
                            @foreach($product->galleries as $gallery)
                                <div class="swiper-slide">
                                    <img src="{{ asset($gallery->image) }}" alt="{{ $product->name }}" class="main-image">
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                    <div class="swiper thumb-swiper mt-2">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}">
                            </div>
                            @foreach($product->galleries as $gallery)
                                <div class="swiper-slide">
                                    <img src="{{ asset($gallery->image) }}" alt="{{ $product->name }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                </div>
            </div>
            <!-- Product Info -->
            <div class="col-lg-6 col-md-5 col-12">
                <div class="product-info">
                    <h1>{{ $product->name }}</h1>
                    <div class="product-price mb-3">
                        @if(isset($product->discount) && $product->discount > 0)
                            <span class="fw-bold text-primary fs-4">
                                {{ number_format($product->discount, 2) }}৳
                            </span>
                            <span class="text-muted text-decoration-line-through ms-2">
                                {{ number_format($product->price, 2) }}৳
                            </span>
                        @else
                            <span class="fw-bold text-primary fs-4">
                                {{ number_format($product->price, 2) }}৳
                            </span>
                        @endif
                    </div>

                    <div class="product-rating">
                        <div class="stars">★★★★☆</div>
                        <span class="rating-text">(24 reviews)</span>
                    </div>

                    @if ($product->short_desc)
                    <div class="product-description">
                        <p>{{ $product->short_desc }}</p>
                    </div>
                    @endif

                    <div class="quantity-selector align-items-center mt-4">
                        <label for="quantity">Quantity:</label>
                        <div class="input-group" style="width: 135px; flex-wrap: nowrap;">
                            <button class="btn nav-button border py-1 px-3" type="button"
                                onclick="changeQuantity(-1)">-</button>
                            <input type="number" class="form-control text-center ps-3 pe-0 py-0 w-100" id="quantityInput"
                                name="quantity" value="1" min="1" max="10">
                            <button class="btn nav-button border py-1 px-3" type="button"
                                onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button class="btn-add-cart">
                            <svg xmlns="http://www.w3.org/2000/svg" id="Outline" viewBox="0 0 24 24" fill="#fff" width="14"
                                height="14">
                                <path
                                    d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z">
                                </path>
                                <circle cx="7" cy="22" r="2"></circle>
                                <circle cx="17" cy="22" r="2"></circle>
                            </svg> Add to Cart
                        </button>

                        <form action="{{ url('/buy-now') }}/{{ $product->id }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-custom" style="white-space: nowrap; font-size: 14px;">
                                Buy Now
                            </button>
                        </form>

                        <button class="btn btn-outline-custom border d-flex" onclick="addToWishlist()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="var(--primary-blue)" id="Outline"
                                viewBox="0 0 24 24" width="20" height="20">
                                <path
                                    d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z" />
                            </svg>
                        </button>
                        <a href="https://wa.me/8801823064555?text={{ route('product.details', $product->slug) }}
    " target="_blank" class="btn btn-outline-custom border d-flex">
                            <svg stroke="currentColor" fill="green" stroke-width="0" viewBox="0 0 448 512" height="20"
                                width="20" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z">
                                </path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Tabs -->
        <div class="product-tabs">
            <div class="tab-nav">
                <button class="tab-btn active" data-tab="description" type="button">Description</button>
                <button class="tab-btn" data-tab="specs" type="button">Specifications</button>
                <button class="tab-btn" data-tab="reviews" type="button">Reviews</button>
            </div>

            <div id="description" class="tab-content active">
                <h3>Product Description</h3>
                {!! $product->description !!}
            </div>

            <div id="specs" class="tab-content" style="display:none;">
                <h3>Technical Specifications</h3>
                <table class="specifications-table">
                    <tr>
                        <th>Specification</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Flow Rate</td>
                        <td>75 GPD (Gallons Per Day)</td>
                    </tr>
                    <tr>
                        <td>Voltage</td>
                        <td>220V AC, 50Hz</td>
                    </tr>
                    <tr>
                        <td>Power Consumption</td>
                        <td>36W</td>
                    </tr>
                    <tr>
                        <td>Operating Pressure</td>
                        <td>80-100 PSI</td>
                    </tr>
                    <tr>
                        <td>Inlet/Outlet Size</td>
                        <td>1/4" Quick Connect</td>
                    </tr>
                    <tr>
                        <td>Dimensions</td>
                        <td>6.5" x 4.2" x 3.8"</td>
                    </tr>
                    <tr>
                        <td>Weight</td>
                        <td>2.1 lbs</td>
                    </tr>
                    <tr>
                        <td>Material</td>
                        <td>High-grade plastic housing with stainless steel components</td>
                    </tr>
                    <tr>
                        <td>Operating Temperature</td>
                        <td>5°C to 40°C</td>
                    </tr>
                    <tr>
                        <td>Warranty</td>
                        <td>1 Year Manufacturer Warranty</td>
                    </tr>
                </table>
            </div>

            <div id="reviews" class="tab-content">
                <div class="reviews-summary">
                    <h3>Customer Reviews</h3>
                    <div class="rating-breakdown">
                        <div class="overall-rating">
                            <div class="rating-number">4.2</div>
                            <div class="stars">★★★★☆</div>
                            <div>24 reviews</div>
                        </div>
                        <div class="rating-bars">
                            <div class="rating-bar">
                                <span>5★</span>
                                <div class="bar-fill">
                                    <div class="bar-fill-inner" style="width: 65%"></div>
                                </div>
                                <span>65%</span>
                            </div>
                            <div class="rating-bar">
                                <span>4★</span>
                                <div class="bar-fill">
                                    <div class="bar-fill-inner" style="width: 20%"></div>
                                </div>
                                <span>20%</span>
                            </div>
                            <div class="rating-bar">
                                <span>3★</span>
                                <div class="bar-fill">
                                    <div class="bar-fill-inner" style="width: 10%"></div>
                                </div>
                                <span>10%</span>
                            </div>
                            <div class="rating-bar">
                                <span>2★</span>
                                <div class="bar-fill">
                                    <div class="bar-fill-inner" style="width: 3%"></div>
                                </div>
                                <span>3%</span>
                            </div>
                            <div class="rating-bar">
                                <span>1★</span>
                                <div class="bar-fill">
                                    <div class="bar-fill-inner" style="width: 2%"></div>
                                </div>
                                <span>2%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="review-item">
                    <div class="review-header">
                        <div>
                            <div class="reviewer-name">Ahmed Rahman</div>
                            <div class="stars">★★★★★</div>
                        </div>
                        <div class="review-date">March 15, 2024</div>
                    </div>
                    <div class="review-text">
                        Excellent pump! Installation was straightforward and the performance is exactly as described. Water
                        pressure has improved significantly throughout my home. Very quiet operation - you can barely hear
                        it running.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products Section -->
    @if(isset($relatedProducts) && count($relatedProducts))
        <div class="related-products mt-5">
            <div class="container">
                <h2>Related Products</h2>
                <div class="swiper related-swiper mt-4" style="height: max-content;">
                    <div class="swiper-wrapper">
                        @foreach($relatedProducts as $product)
                            <div class="swiper-slide">
                                <div class="product-card h-100 shadow-sm position-relative">
                                    <img src="{{ asset($product->image) }}" class="card-img-top p-3" alt="{{ $product->name }}"
                                        style="height:180px;object-fit:contain;">
                                    <div class="card-body d-flex flex-column p-3">
                                        <a href="{{ route('product.details', $product->slug) }}" class="fw-bold text-black"
                                            style="text-decoration: none;">{{ $product->name }}</a>
                                        <p class="text-muted small mb-2"
                                            style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            {{ $product->description }}
                                        </p>
                                        <div class="mb-2">
                                            @if(isset($product->discount) && $product->discount > 0)
                                                <span class="fw-bold text-primary">{{ number_format($product->discount, 2) }}৳</span>
                                                <span
                                                    class="text-decoration-line-through text-muted ms-2">{{ number_format($product->price, 2) }}৳</span>
                                            @else
                                                <span class="fw-bold text-primary">{{ number_format($product->price, 2) }}৳</span>
                                            @endif
                                        </div>
                                        <div class="mt-auto">
                                            <button class="btn-add-cart"><svg xmlns="http://www.w3.org/2000/svg" id="Outline"
                                                    viewBox="0 0 24 24" fill="#fff" width="14" height="14">
                                                    <path
                                                        d="M22.713,4.077A2.993,2.993,0,0,0,20.41,3H4.242L4.2,2.649A3,3,0,0,0,1.222,0H1A1,1,0,0,0,1,2h.222a1,1,0,0,1,.993.883l1.376,11.7A5,5,0,0,0,8.557,19H19a1,1,0,0,0,0-2H8.557a3,3,0,0,1-2.82-2h11.92a5,5,0,0,0,4.921-4.113l.785-4.354A2.994,2.994,0,0,0,22.713,4.077ZM21.4,6.178l-.786,4.354A3,3,0,0,1,17.657,13H5.419L4.478,5H20.41A1,1,0,0,1,21.4,6.178Z">
                                                    </path>
                                                    <circle cx="7" cy="22" r="2"></circle>
                                                    <circle cx="17" cy="22" r="2"></circle>
                                                </svg> Add to Cart</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </div>
    @endif

@endsection

<div id="toast-container"
    style="position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;">
</div>

@push('scripts')
    
    <script>
        // Image modal removed per requirements; keep a no-op cleaner in case of legacy backdrops
        function removeStuckBackdrops(){
            try {
                document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
            } catch(_) {}
        }
        // Swiper init that can be safely called multiple times
        (function(){
            var thumbSwiperInstance = null;
            var mainSwiperInstance = null;

            function imagesLoaded(container, callback){
                var imgs = Array.from(container.querySelectorAll('img'));
                var remaining = imgs.length;
                if(remaining === 0){ callback(); return; }
                imgs.forEach(function(img){
                    if (img.complete) { if(--remaining === 0) callback(); }
                    else { img.addEventListener('load', function(){ if(--remaining === 0) callback(); });
                           img.addEventListener('error', function(){ if(--remaining === 0) callback(); }); }
                });
            }

            function initProductGallery(){
                var gallery = document.querySelector('.product-gallery');
                if(!gallery) return;
                if (gallery.dataset.initialized === '1') {
                    // Still ensure updates if already initialized
                    try { window.__productThumbSwiper && window.__productThumbSwiper.update(); } catch(_){}
                    try { window.__productMainSwiper && window.__productMainSwiper.update(); } catch(_){}
                    return;
                }

                // Destroy if already exists
                try { if(thumbSwiperInstance){ thumbSwiperInstance.destroy(true, true); thumbSwiperInstance = null; } } catch(_){}
                try { if(mainSwiperInstance){ mainSwiperInstance.destroy(true, true); mainSwiperInstance = null; } } catch(_){ }

                var thumbContainer = gallery.querySelector('.thumb-swiper');
                var mainContainer = gallery.querySelector('.main-swiper');
                var nextEl = gallery.querySelector('.main-swiper .swiper-button-next');
                var prevEl = gallery.querySelector('.main-swiper .swiper-button-prev');

                thumbSwiperInstance = new Swiper(thumbContainer, {
                    spaceBetween: 10,
                    slidesPerView: 4,
                    freeMode: true,
                    watchSlidesProgress: true,
                    observer: true,
                    observeParents: true
                });
                mainSwiperInstance = new Swiper(mainContainer, {
                    spaceBetween: 10,
                    navigation: {
                        nextEl: nextEl,
                        prevEl: prevEl,
                    },
                    thumbs: { swiper: thumbSwiperInstance },
                    zoom: true,
                    observer: true,
                    observeParents: true,
                    // Improve interaction reliability
                    preloadImages: false,
                    lazy: true
                });

                // Expose globally for navigation/rehydration scenarios
                window.__productThumbSwiper = thumbSwiperInstance;
                window.__productMainSwiper = mainSwiperInstance;

                // Native swiper tap sync for extra reliability
                try {
                    thumbSwiperInstance.on('tap', function(){
                        var idx = thumbSwiperInstance.clickedIndex;
                        if (typeof idx === 'number' && idx >= 0) {
                            mainSwiperInstance.slideTo(idx);
                        }
                    });
                } catch(_){}

                // Click thumb to slide explicitly (extra reliability)
                var thumbSlides = document.querySelectorAll('.thumb-swiper .swiper-slide');
                thumbSlides.forEach(function(slide, index){
                    slide.addEventListener('click', function(){ if(mainSwiperInstance){ mainSwiperInstance.slideTo(index); } });
                });

                // Ensure update after images load and after a short tick (ajax)
                imagesLoaded(gallery, function(){
                    try { thumbSwiperInstance && thumbSwiperInstance.update(); } catch(_){ }
                    try { mainSwiperInstance && mainSwiperInstance.update(); } catch(_){ }
                });
                setTimeout(function(){
                    try { thumbSwiperInstance && thumbSwiperInstance.update(); } catch(_){ }
                    try { mainSwiperInstance && mainSwiperInstance.update(); } catch(_){ }
                }, 50);

                // Mark as initialized to avoid duplicate setups
                gallery.dataset.initialized = '1';
            }

            // Initialize on DOM ready and on window load
            document.addEventListener('DOMContentLoaded', initProductGallery);
            window.addEventListener('load', initProductGallery);
            // Handle bfcache (back/forward) restore where DOM is persisted but scripts may not run
            window.addEventListener('pageshow', function(e){
                if (e.persisted) { initProductGallery(); }
                else { // even without persisted, ensure update when returning via history
                    if (typeof mainSwiperInstance !== 'undefined' && mainSwiperInstance) {
                        try { mainSwiperInstance.update(); } catch(_) {}
                    } else { initProductGallery(); }
                }
            });
            document.addEventListener('visibilitychange', function(){
                if (document.visibilityState === 'visible') {
                    if (typeof mainSwiperInstance !== 'undefined' && mainSwiperInstance) {
                        try { mainSwiperInstance.update(); } catch(_) {}
                    }
                }
            });

            // Observe DOM for product gallery being (re)inserted via AJAX/PJAX
            try {
                var ob = new MutationObserver(function(muts){
                    muts.forEach(function(m){
                        if ([].some.call(m.addedNodes || [], function(n){ return n.nodeType===1 && (n.matches && n.matches('.product-gallery') || (n.querySelector && n.querySelector('.product-gallery'))); })) {
                            // Reset flag to allow fresh init
                            var g = document.querySelector('.product-gallery');
                            if (g) { delete g.dataset.initialized; }
                            initProductGallery();
                        }
                    });
                });
                ob.observe(document.body, { childList: true, subtree: true });
            } catch(_) {}

            // Delegated click fallback: if swiper not active, still swap the main image
            document.addEventListener('click', function(e){
                var slide = e.target && e.target.closest('.thumb-swiper .swiper-slide');
                if(!slide) return;
                try {
                    var index = Array.prototype.indexOf.call(slide.parentNode.children, slide);
                    if (window.__productMainSwiper && typeof window.__productMainSwiper.slideTo === 'function') {
                        window.__productMainSwiper.slideTo(index);
                    } else {
                        // Hard fallback: replace the first main image src
                        var main = document.querySelector('.main-swiper .swiper-slide img');
                        var img = slide.querySelector('img');
                        if (main && img) { main.src = img.src; }
                    }
                } catch(_) {}
            });

            // Expose for manual re-init if content is injected via AJAX
            window.initProductGallery = initProductGallery;
        })();

        // Ensure initialization after AJAX injection
        if (typeof window.initProductGallery === 'function') {
            try { window.initProductGallery(); } catch(_) {}
        }

        // Tab functionality is now handled by the master layout
        // This ensures it works properly after AJAX navigation

        // Safety cleanup: if a backdrop ever sticks around, remove it on modal hide
        // No modal now; keep failsafe for any stray backdrops
        // Failsafe: any click on the page will clear a stray backdrop if no modals are visible
        document.addEventListener('click', function(){
            var anyOpen = document.querySelector('.modal.show');
            if(!anyOpen){ removeStuckBackdrops(); }
        });

        function changeQuantity(delta) {
            var input = document.getElementById('quantityInput');
            var value = parseInt(input.value) || 1;
            value += delta;
            if (value < 1) value = 1;
            if (value > 10) value = 10;
            input.value = value;
        }
        $(function () {
            $(document).on('click', '.btn-add-cart', function (e) {
                e.preventDefault();
                var btn = $(this);
                var productId = {{ $product->id }};
                var qty = parseInt($('#quantityInput').val()) || 1;
                btn.prop('disabled', true);
                $.ajax({
                    url: '/cart/add-page/' + productId,
                    type: 'POST',
                    data: { qty: qty },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        btn.prop('disabled', false);
                        if (data.success) {
                            if (typeof showToast === 'function') showToast('Added to cart!');
                            if (typeof updateCartQtyBadge === 'function') updateCartQtyBadge();
                        } else {
                            if (typeof showToast === 'function') showToast('Could not add to cart.', 'error');
                        }
                    },
                    error: function () {
                        btn.prop('disabled', false);
                        if (typeof showToast === 'function') showToast('Could not add to cart.', 'error');
                    }
                });
            });
        });

        // Related products slider
        if (document.querySelector('.related-swiper')) {
            new Swiper('.related-swiper', {
                slidesPerView: 4,
                spaceBetween: 24,
                navigation: {
                    nextEl: '.related-swiper .swiper-button-next',
                    prevEl: '.related-swiper .swiper-button-prev',
                },
                breakpoints: {
                    0: { slidesPerView: 1 },
                    576: { slidesPerView: 2 },
                    992: { slidesPerView: 3 },
                    1200: { slidesPerView: 4 }
                }
            });
        }
    </script>
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
    </script>
@endpush