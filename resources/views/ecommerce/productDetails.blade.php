@extends('ecommerce.master')

@push('head')
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
@endpush

@section('main-section')
    <!-- Product Details Content Section -->
    <style>
        .product-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 40px 0;
            min-height: auto;
        }

        .product-main {
            display: flex;
            margin-bottom: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
        }

        .product-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00512C, #10B981, #3B82F6);
        }

        /* Product Gallery */
        .product-gallery {
            display: flex;
            flex-direction: column;
            position: relative;
            background: #fafbfc;
        }

        /* Variation Image Styles */
        .variation-image-slide,
        .variation-gallery-slide,
        .variation-thumb-slide,
        .variation-gallery-thumb-slide {
            transition: opacity 0.3s ease;
        }

        .variation-image-slide img,
        .variation-gallery-slide img {
            width: 100%;
            height: auto;
            object-fit: cover;
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
            padding: 20px;
            background: white;
        }

        .thumb-swiper .swiper-slide {
            width: max-content !important;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .thumb-swiper .swiper-slide:hover {
            transform: translateY(-2px);
        }

        .thumb-swiper .swiper-slide img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .thumb-swiper .swiper-slide:hover img {
            border-color: #00512C;
            box-shadow: 0 4px 12px rgba(0, 81, 44, 0.15);
        }

        .thumb-swiper .swiper-slide-thumb-active img {
            border-color: #00512C;
            box-shadow: 0 4px 12px rgba(0, 81, 44, 0.25);
        }

        .main-image {
            width: 100%;
            height: 500px;
            border: none;
            border-radius: 0;
            overflow: hidden;
            background: #fafbfc;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin: 0;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .main-image:hover img {
            transform: scale(1.05);
        }

        .swiper-button-next,
        .swiper-button-prev {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: #00512C;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: #00512C;
            color: white;
            transform: scale(1.1);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px;
            font-weight: bold;
        }

        /* Removed unused zoom overlay and legacy thumbnail grid/styles */

        /* Product Info */
        .product-info {
            padding: 40px;
            background: white;
            position: relative;
        }

        .product-info h1 {
            font-size: 32px;
            margin-bottom: 16px;
            color: #1a202c;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .stars {
            color: #fbbf24;
            font-size: 20px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .rating-text {
            color: #64748b;
            font-size: 15px;
            font-weight: 500;
        }

        .product-price {
            margin-bottom: 24px;
        }

        .product-price .fw-bold {
            font-size: 36px;
            font-weight: 800;
            color: #00512C;
            text-shadow: 0 2px 4px rgba(0, 81, 44, 0.1);
        }

        .product-price .text-decoration-line-through {
            font-size: 20px;
            color: #94a3b8;
            margin-left: 12px;
        }

        .product-description {
            color: #64748b;
            margin-bottom: 32px;
            line-height: 1.7;
            font-size: 16px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #00512C;
        }

        .product-options {
            margin-bottom: 32px;
        }

        .option-group {
            margin-bottom: 24px;
        }

        .option-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 15px;
        }

        .option-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .option-group select:focus {
            border-color: #00512C;
            box-shadow: 0 0 0 3px rgba(0, 81, 44, 0.1);
            outline: none;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 32px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .quantity-selector label {
            font-weight: 600;
            color: #374151;
            font-size: 16px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .quantity-btn {
            background: #f8fafc;
            border: none;
            width: 48px;
            height: 48px;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #00512C;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover {
            background: #00512C;
            color: white;
            transform: scale(1.05);
        }

        .quantity-input {
            width: 80px;
            height: 48px;
            text-align: center;
            border: none;
            border-left: 2px solid #e2e8f0;
            border-right: 2px solid #e2e8f0;
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            background: white;
        }

        .quantity-input:focus {
            outline: none;
            background: #f8fafc;
        }

        .action-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #00512C 0%, #10B981 100%);
            color: white;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 4px 12px rgba(0, 81, 44, 0.3);
        }

        .btn-add-cart:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 81, 44, 0.4);
        }

        .btn-add-cart:disabled {
            background: #e5e7eb !important;
            color: #9ca3af !important;
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Wishlist button states */
        .btn-outline-custom.added-to-wishlist {
            background: #10B981 !important;
            color: white !important;
            border-color: #10B981 !important;
        }

        .btn-outline-custom:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-outline-custom i.fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-outline-custom {
            background: white;
            color: #00512C;
            border: 2px solid #00512C;
            padding: 14px 30px;
            min-width: 120px;
        }

        .btn-outline-custom:hover {
            background: #00512C;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 81, 44, 0.3);
        }

        .btn-outline-custom.border {
            border: 2px solid #e2e8f0;
            color: #64748b;
            background: white;
        }

        .btn-outline-custom.border:hover {
            border-color: #00512C;
            color: #00512C;
            background: #f8fafc;
        }


        /* Product Tabs */
        .product-tabs {
            margin-bottom: 30px;
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

        /* Reviews Section Styles */
        .reviews-section {
            padding: 20px 0;
        }

        .reviews-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reviews-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .reviews-subtitle {
            color: #64748b;
            font-size: 16px;
        }

        .reviews-summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .rating-overview {
            padding: 20px;
        }

        .overall-rating {
            font-size: 48px;
            font-weight: 800;
            color: #00512C;
            margin-bottom: 10px;
        }

        .rating-stars {
            color: #fbbf24;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .rating-count {
            color: #64748b;
            font-size: 16px;
            font-weight: 500;
        }

        .rating-breakdown h6 {
            color: #1a202c;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .rating-bar-label {
            min-width: 60px;
            font-size: 14px;
            color: #374151;
        }

        .rating-bar-fill {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            margin: 0 10px;
            overflow: hidden;
        }

        .rating-bar-progress {
            height: 100%;
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        .rating-bar-count {
            min-width: 30px;
            text-align: right;
            font-size: 12px;
            color: #64748b;
        }

        .review-form-section {
            margin-bottom: 40px;
        }

        .review-form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .form-title {
            color: #1a202c;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 16px;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            gap: 8px;
            justify-content: flex-start;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .star-label {
            font-size: 32px;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 4px;
        }

        .star-label:hover,
        .star-label:hover ~ .star-label,
        .rating-input input[type="radio"]:checked ~ .star-label {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .rating-text {
            color: #64748b;
            font-size: 14px;
            margin-top: 8px;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #00512C;
            box-shadow: 0 0 0 3px rgba(0, 81, 44, 0.1);
            outline: none;
        }

        .form-actions {
            display: flex;
            gap: 16px;
            margin-top: 25px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00512C 0%, #10B981 100%);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 81, 44, 0.3);
        }

        .review-login-prompt {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .login-prompt-content h5 {
            color: #1a202c;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .login-prompt-content p {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 24px;
        }

        .reviews-list-section {
            margin-top: 40px;
        }

        .reviews-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .reviews-list-header h4 {
            color: #1a202c;
            font-weight: 700;
            margin: 0;
        }

        .reviews-filter select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .reviews-list {
            space-y: 20px;
        }

        .review-item {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .review-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .reviewer-info {
            flex: 1;
        }

        .reviewer-name {
            font-weight: 600;
            color: #1a202c;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .review-rating {
            color: #fbbf24;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .review-date {
            color: #64748b;
            font-size: 14px;
        }

        .review-title {
            font-weight: 600;
            color: #1a202c;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .review-comment {
            color: #374151;
            line-height: 1.6;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .review-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .helpful-btn {
            background: none;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 12px;
            color: #64748b;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .helpful-btn:hover {
            border-color: #00512C;
            color: #00512C;
        }

        .helpful-btn.active {
            background: #00512C;
            border-color: #00512C;
            color: white;
        }

        .no-reviews {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        .no-reviews i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .reviews-summary-card .row {
                flex-direction: column;
            }

            .rating-breakdown {
                margin-top: 20px;
            }

            .reviews-list-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .form-actions {
                flex-direction: column;
            }

            .review-header {
                flex-direction: column;
                gap: 10px;
            }

            .review-actions {
                flex-wrap: wrap;
            }
        }


        /* Related Products - Enhanced Design */
        .related-products {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 50px 0 10px 0;
            position: relative;
            overflow: hidden;
        }

        .related-products::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00512C, #10B981, #3B82F6);
        }

        .related-products-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .related-products h2 {
            font-size: 36px;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 16px;
            position: relative;
            display: inline-block;
        }

        .related-products h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #00512C, #10B981);
            border-radius: 2px;
        }

        .related-products-subtitle {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 0;
        }

        .related-products-container {
            position: relative;
            padding: 0;
        }

        .related-swiper {
            padding: 20px 10px 5px 10px;
            overflow: visible;
            height: auto !important;
        }

        .related-swiper .swiper-slide {
            width: auto;
            height: auto;
            margin-right: 0;
        }

        .related-swiper .swiper-wrapper {
            height: auto !important;
        }

        .related-product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            height: 420px;
            max-height: 420px;
            width: 280px;
            min-width: 280px;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255, 255, 255, 0.2);
            flex-shrink: 0;
            margin: 0 5px;
        }

        .related-product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00512C, #10B981);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .related-product-card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .related-product-card:hover::before {
            opacity: 1;
        }

        .related-product-image-container {
            position: relative;
            overflow: hidden;
            background: #f8fafc;
            padding: 16px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .related-product-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .related-product-card:hover .related-product-image {
            transform: none;
        }

        .related-product-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 2;
        }

        .related-product-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .related-product-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .related-product-title:hover {
            color: #00512C;
        }

        .related-product-description {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-product-rating {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            gap: 6px;
        }

        .related-product-stars {
            color: #fbbf24;
            font-size: 14px;
        }

        .related-product-rating-text {
            font-size: 12px;
            color: #64748b;
        }

        .related-product-price {
            margin-bottom: 16px;
        }

        .related-product-current-price {
            font-size: 20px;
            font-weight: 800;
            color: #00512C;
            margin-right: 8px;
        }

        .related-product-original-price {
            font-size: 16px;
            color: #94a3b8;
            text-decoration: line-through;
        }

        .related-product-actions {
            margin-top: auto;
            display: flex;
            gap: 12px;
        }

        .related-product-btn {
            flex: 1;
            padding: 10px 14px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
        }

        .related-product-btn-primary {
            background: linear-gradient(135deg, #00512C, #10B981);
            color: white;
        }

        .related-product-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 81, 44, 0.3);
            color: white;
        }

        .related-product-btn-secondary {
            background: white;
            color: #00512C;
            border: 2px solid #e2e8f0;
        }

        .related-product-btn-secondary:hover {
            border-color: #00512C;
            background: #f0fdf4;
            color: #00512C;
        }

        .related-product-wishlist {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .related-product-wishlist:hover {
            border-color: #ef4444;
            color: #ef4444;
            background: #fef2f2;
        }

        .related-product-wishlist.active {
            border-color: #ef4444;
            color: #ef4444;
            background: #fef2f2;
        }

        /* Top Wishlist Button for All Product Cards */
        .product-wishlist-top {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.9);
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .product-wishlist-top:hover {
            border-color: #ef4444;
            color: #ef4444;
            background: rgba(254, 242, 242, 0.95);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .product-wishlist-top.active {
            border-color: #ef4444;
            color: #ef4444;
            background: rgba(254, 242, 242, 0.95);
        }

        .product-wishlist-top i {
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .product-wishlist-top:hover i {
            transform: scale(1.1);
        }

        /* Swiper Navigation for Related Products */
        .related-swiper .swiper-button-next,
        .related-swiper .swiper-button-prev {
            background: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            color: #00512C;
            transition: all 0.3s ease;
            top: 50%;
            transform: translateY(-50%);
        }

        .related-swiper .swiper-button-next:hover,
        .related-swiper .swiper-button-prev:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .related-swiper .swiper-button-next:after,
        .related-swiper .swiper-button-prev:after {
            font-size: 18px;
            font-weight: 700;
        }

        .related-swiper .swiper-button-next {
            right: -30px;
        }

        .related-swiper .swiper-button-prev {
            left: -30px;
        }

        .related-swiper .swiper-pagination {
            bottom: 0px;
        }

        .related-swiper .swiper-pagination-bullet {
            background: #cbd5e1;
            opacity: 1;
            width: 12px;
            height: 12px;
            margin: 0 6px;
        }

        .related-swiper .swiper-pagination-bullet-active {
            background: #00512C;
        }

        /* Additional Animations and Effects */
        .related-products {
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .related-product-card {
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Staggered animation for cards */
        .related-product-card:nth-child(1) { animation-delay: 0.1s; }
        .related-product-card:nth-child(2) { animation-delay: 0.2s; }
        .related-product-card:nth-child(3) { animation-delay: 0.3s; }
        .related-product-card:nth-child(4) { animation-delay: 0.4s; }
        .related-product-card:nth-child(5) { animation-delay: 0.5s; }
        .related-product-card:nth-child(6) { animation-delay: 0.6s; }
        .related-product-card:nth-child(7) { animation-delay: 0.7s; }
        .related-product-card:nth-child(8) { animation-delay: 0.8s; }

        /* Loading skeleton animation */
        .related-product-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        /* Hover effects for interactive elements */
        .related-product-title {
            position: relative;
            overflow: hidden;
        }

        .related-product-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #00512C, #10B981);
            transition: left 0.3s ease;
        }

        .related-product-title:hover::after {
            left: 0;
        }

        /* Pulse animation for featured badge */
        .related-product-badge {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        /* Smooth transitions for all interactive elements */
        .related-product-btn,
        .related-product-wishlist,
        .related-product-title,
        .related-product-image {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Mobile Responsiveness */
        @media (max-width: 1200px) {
            
            .related-products {
                padding: 40px 0 5px 0;
            }
            
            .related-products h2 {
                font-size: 32px;
            }
            
            .related-swiper .swiper-button-next {
                right: -25px;
            }
            
            .related-swiper .swiper-button-prev {
                left: -25px;
            }
            
            .related-products-container {
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .related-products {
                padding: 30px 0 5px 0;
            }
            
            .related-products h2 {
                font-size: 28px;
            }
            
            .related-products-subtitle {
                font-size: 16px;
            }
            
            .related-product-card {
                border-radius: 16px;
                height: 380px;
                max-height: 380px;
                width: 260px;
                min-width: 260px;
            }
            
            .related-product-image-container {
                height: 140px;
                padding: 12px;
            }
            
            .related-product-content {
                padding: 16px;
            }
            
            .related-product-title {
                font-size: 15px;
            }
            
            .related-product-description {
                font-size: 12px;
                margin-bottom: 10px;
            }
            
            .related-product-current-price {
                font-size: 18px;
            }
            
            .related-product-original-price {
                font-size: 14px;
            }
            
            .related-product-btn {
                padding: 8px 12px;
                font-size: 12px;
            }
            
            .related-product-wishlist {
                width: 36px;
                height: 36px;
            }
            
            .related-swiper .swiper-button-next,
            .related-swiper .swiper-button-prev {
                width: 40px;
                height: 40px;
                display: none; /* Hide on mobile for cleaner look */
            }
            
            .related-products-container {
                padding: 0;
            }
        }

        @media (max-width: 480px) {
            .related-products {
                padding: 25px 0 5px 0;
            }
            
            .related-products h2 {
                font-size: 24px;
            }
            
            .related-products-subtitle {
                font-size: 14px;
            }
            
            .related-product-card {
                height: 360px;
                max-height: 360px;
                width: 240px;
                min-width: 240px;
            }
            
            .related-product-image-container {
                height: 120px;
                padding: 10px;
            }
            
            .related-product-content {
                padding: 14px;
            }
            
            .related-products-container {
                padding: 0;
            }
            
            .related-product-actions {
                flex-direction: column;
                gap: 8px;
            }
            
            .related-product-btn {
                width: 100%;
                padding: 8px 10px;
                font-size: 11px;
            }
            
            .related-product-wishlist {
                width: 100%;
                height: 36px;
            }
        }

        @media (max-width: 768px) {
            .product-section {
                padding: 15px 0;
            }

            .product-main {
                flex-direction: column;
                margin-bottom: 25px;
                border-radius: 16px;
            }

            .product-info {
                padding: 24px;
            }

            .product-info h1 {
                font-size: 24px;
            }

            .product-price .fw-bold {
                font-size: 28px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 12px;
            }

            .btn-add-cart,
            .btn-outline-custom {
                width: 100%;
                justify-content: center;
            }

            .tab-nav {
                flex-wrap: wrap;
                gap: 8px;
            }

            .tab-btn {
                padding: 12px 16px;
                font-size: 14px;
                border-radius: 8px;
                margin-bottom: 8px;
            }

            .rating-breakdown {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }

            .main-image {
                height: 300px;
            }

            .thumb-swiper .swiper-slide img {
                width: 60px;
                height: 60px;
            }

            .thumb-swiper {
                padding: 16px;
            }



            .swiper-button-next,
            .swiper-button-prev {
                width: 40px;
                height: 40px;
            }

            .swiper-button-next:after,
            .swiper-button-prev:after {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .product-info {
                padding: 16px;
            }

            .product-info h1 {
                font-size: 20px;
            }

            .product-price .fw-bold {
                font-size: 24px;
            }

            .main-image {
                height: 250px;
            }

            .thumb-swiper .swiper-slide img {
                width: 50px;
                height: 50px;
            }

            .quantity-selector {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .quantity-controls {
                justify-content: center;
            }

            .rating-number {
                font-size: 40px;
            }

            .rating-stars {
                font-size: 20px;
            }
        }

        /* Product Variations */
        .variation-group {
            margin-bottom: 20px;
        }

        .color-option, .size-option {
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0 !important;
            box-shadow: none;
			cursor: pointer;
			position: relative;
        }

        .color-image-btn {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .color-image-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .color-image-btn .color-label {
            font-size: 12px;
            font-weight: 500;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Color Image Selection Styles */

        .color-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .color-label {
            font-size: 10px;
            font-weight: 600;
            text-align: center;
            line-height: 1;
            padding: 2px;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
            display: block;
        }

        .color-image-btn[style*="background-color: #ffffff"] .color-label,
        .color-image-btn[style*="background-color: #f9fafb"] .color-label {
            color: #333;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.7);
        }

        .color-image-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .color-image-btn.active {
            transform: scale(1.15);
            box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.5);
            border: 2px solid #ff6a00 !important;
        }

        .color-option:hover, .size-option:hover {
            border-color: #ff6a00 !important;
        }

        .color-option.active, .size-option.active {
            border-color: #ff6a00 !important; /* orange */
            box-shadow: 0 0 0 2px rgba(255,106,0,0.15);
            background-color: #fff7f0 !important;
            color: #222 !important;
        }

        .size-option {
            min-width: 56px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            pointer-events: auto;
        }

        .variation-info {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
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

        /* Image Upload Styles */
        .image-upload-container {
            position: relative;
        }

        .image-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 32px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .image-upload-area:hover {
            border-color: #00512C;
            background: #f0fdf4;
        }

        .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .upload-text {
            margin: 8px 0 4px 0;
            color: #374151;
            font-weight: 500;
        }

        .image-preview {
            position: relative;
            margin-top: 16px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .preview-image {
            width: 100%;
            max-width: 200px;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        .remove-image-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .remove-image-btn:hover {
            background: rgba(220, 38, 38, 1);
            transform: scale(1.1);
        }


        /* Image Modal Styles */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            cursor: pointer;
        }

        .image-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
        }

        .image-modal-close:hover {
            color: #bbb;
        }
    </style>

    <div class="container">
        <div class="product-main row" data-has-variations="{{ $product->has_variations ? 'true' : 'false' }}">
            <!-- Product Gallery -->
            <div class="col-lg-6 col-md-7 col-12" style="padding: 20px;">
                <div class="product-gallery">
                    <div class="swiper main-swiper">
                        <div class="swiper-wrapper" id="main-swiper-wrapper">
                            <!-- Main product image -->
                            <div class="swiper-slide" data-image-type="product">
                                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="main-image" id="main-product-image">
                            </div>
                            <!-- Product galleries -->
                            @foreach($product->galleries as $gallery)
                                <div class="swiper-slide" data-image-type="gallery">
                                    <img src="{{ asset($gallery->image) }}" alt="{{ $product->name }}" class="main-image">
                                </div>
                            @endforeach
                            <!-- Variation images (hidden by default) -->
                            @if($product->has_variations)
                                @foreach($product->variations as $variation)
                                    @if($variation->image)
                                        <div class="swiper-slide variation-image-slide" data-variation-id="{{ $variation->id }}" data-image-type="variation" style="display: none;">
                                            <img src="{{ asset($variation->image) }}" alt="{{ $variation->name }}" class="main-image">
                                        </div>
                                    @endif
                                    @foreach($variation->galleries as $gallery)
                                        <div class="swiper-slide variation-gallery-slide" data-variation-id="{{ $variation->id }}" data-image-type="variation-gallery" style="display: none;">
                                            <img src="{{ asset($gallery->image) }}" alt="{{ $variation->name }}" class="main-image">
                                        </div>
                                    @endforeach
                                @endforeach
                            @endif
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                    <div class="swiper thumb-swiper mt-2">
                        <div class="swiper-wrapper" id="thumb-swiper-wrapper">
                            <!-- Main product image thumbnail -->
                            <div class="swiper-slide" data-image-type="product">
                                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}">
                            </div>
                            <!-- Product gallery thumbnails -->
                            @foreach($product->galleries as $gallery)
                                <div class="swiper-slide" data-image-type="gallery">
                                    <img src="{{ asset($gallery->image) }}" alt="{{ $product->name }}">
                                </div>
                            @endforeach
                            <!-- Variation image thumbnails (hidden by default) -->
                            @if($product->has_variations)
                                @foreach($product->variations as $variation)
                                    @if($variation->image)
                                        <div class="swiper-slide variation-thumb-slide" data-variation-id="{{ $variation->id }}" data-image-type="variation" style="display: none;">
                                            <img src="{{ asset($variation->image) }}" alt="{{ $variation->name }}">
                                        </div>
                                    @endif
                                    @foreach($variation->galleries as $gallery)
                                        <div class="swiper-slide variation-gallery-thumb-slide" data-variation-id="{{ $variation->id }}" data-image-type="variation-gallery" style="display: none;">
                                            <img src="{{ asset($gallery->image) }}" alt="{{ $variation->name }}">
                                        </div>
                                    @endforeach
                                @endforeach
                            @endif
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

                    <div class="product-rating" id="product-rating">
                        <div class="stars" id="rating-stars">
                            @php
                                $avgRating = $product->averageRating() ?? 0;
                                $fullStars = floor($avgRating);
                                $hasHalfStar = $avgRating - $fullStars >= 0.5;
                            @endphp
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $fullStars)
                                    ★
                                @elseif($i == $fullStars + 1 && $hasHalfStar)
                                    ☆
                                @else
                                    ☆
                                @endif
                            @endfor
                        </div>
                    </div>

                    @if ($product->short_desc)
                    <div class="product-description">
                        <p>{{ $product->short_desc }}</p>
                    </div>
                    @endif

                    @if($product->has_variations)
                    @php
                    $attributeGroups = [];
                    $variationImages = []; // Map attribute value IDs to variation images
                    
                    foreach (($product->variations ?? []) as $variation) {
                        foreach (($variation->combinations ?? []) as $comb) {
                            if (!$comb->attribute || !$comb->attributeValue) { continue; }
                            $attrId = $comb->attribute->id;
                            $valId = $comb->attributeValue->id;
                            $attributeGroups[$attrId]['name'] = $comb->attribute->name;
                            
                            // Use variation image if available, otherwise use attribute value image
                            $imageToUse = $variation->image ? asset($variation->image) : ($comb->attributeValue->image ?? null);
                            
                            // Persist value label + optional image and color_code from backend
                            $attributeGroups[$attrId]['values'][$valId] = [
                                'label' => $comb->attributeValue->value,
                                'image' => $imageToUse,
                                'color_code' => $comb->attributeValue->color_code ?? null,
                            ];
                            
                            // Store variation image for this attribute value
                            if ($variation->image) {
                                $variationImages[$valId] = asset($variation->image);
                            }
                        }
                    }
                    @endphp

                    <div class="variation-section mt-3">
                        <div class="alert alert-info mb-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Please select your preferred options below to add this item to cart.
                        </div>
                        @foreach($attributeGroups as $attrId => $group)
                            <div class="variation-group">
                                <div class="d-flex align-items-center mb-2">
                                    <strong class="me-2">{{ $group['name'] }}:</strong>
                                    <span class="text-muted small" data-selected-label="attr-{{ $attrId }}">Choose {{ $group['name'] }}</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2" data-attribute-id="{{ $attrId }}">
                                    @foreach($group['values'] as $valId => $val)
                                        @php
                                            $isColor = strtolower($group['name']) === 'color';
                                            $buttonClass = $isColor ? 'btn btn-sm color-option' : 'btn btn-sm size-option';
                                            $label = is_array($val) ? ($val['label'] ?? (string)$val) : (string)$val;
                                            $imgPath = is_array($val) ? ($val['image'] ?? null) : null;
                                            $colorCode = is_array($val) ? ($val['color_code'] ?? null) : null;
                                        @endphp
                                        @if($isColor)
                                            @if(!empty($imgPath))
                                                <button type="button" class="{{ $buttonClass }} color-image-btn" data-attr-id="{{ $attrId }}" data-value-id="{{ $valId }}" data-label="{{ $label }}" title="{{ $label }}">
                                                    <img src="{{ asset($imgPath) }}" alt="{{ $label }}" class="color-image">
                                                </button>
                                            @elseif(!empty($colorCode))
                                                <button type="button" class="{{ $buttonClass }} color-image-btn" data-attr-id="{{ $attrId }}" data-value-id="{{ $valId }}" data-label="{{ $label }}" title="{{ $label }}" style="background-color: {{ $colorCode }};">
                                                    <span class="color-label">{{ $label }}</span>
                                                </button>
                                            @else
                                                <button type="button" class="{{ $buttonClass }} color-image-btn" data-attr-id="{{ $attrId }}" data-value-id="{{ $valId }}" data-label="{{ $label }}" title="{{ $label }}">
                                                    <span class="color-label">{{ $label }}</span>
                                                </button>
                                            @endif
                                        @else
                                            <button type="button" class="{{ $buttonClass }}" data-attr-id="{{ $attrId }}" data-value-id="{{ $valId }}" data-label="{{ $label }}">{{ $label }}</button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <input type="hidden" id="selected-variation-id" value="">
                        <div id="selected-attribute-values" style="display:none;"></div>

                        <div class="variation-info p-3 mt-3 bg-light border rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="small text-muted">Selected Variation</div>
                                    <div id="selected-variation-name" class="fw-semibold text-muted">Please select options above</div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-muted">Price</div>
                                    <div id="selected-variation-price" class="fw-semibold text-muted">—</div>
                                </div>
                            </div>
                            <div class="mt-2 small text-muted" id="selected-variation-stock">Select your preferences to see availability</div>
                        </div>
                    </div>
                    @endif

                    <!-- Variation Scripts - Moved here for AJAX compatibility -->
                        @if($product->has_variations)
                        <script>
                            console.log('[VARIATION] Variation script section reached');
                            
                            // Global error handler for querySelector errors
                            window.addEventListener('error', function(e) {
                                if (e.message && e.message.includes('querySelector') && e.message.includes('not a valid selector')) {
                                    console.error('[VARIATION] querySelector Error Caught:', e.message);
                                    console.error('[VARIATION] Error at:', e.filename, 'line:', e.lineno);
                                    console.error('[VARIATION] Stack trace:', e.error ? e.error.stack : 'No stack trace');
                                    
                                    // Try to identify the problematic selector
                                    var errorText = e.message;
                                    var selectorMatch = errorText.match(/'([^']+)'/);
                                    if (selectorMatch) {
                                        console.error('[VARIATION] Problematic selector:', selectorMatch[1]);
                                    }
                                    
                                    // Prevent the error from propagating
                                    e.preventDefault();
                                    e.stopPropagation();
                                    return true;
                                }
                            }, true);
                            
                            // Global querySelector wrapper to prevent invalid selector errors
                            (function() {
                                var originalQuerySelector = Document.prototype.querySelector;
                                var originalQuerySelectorAll = Document.prototype.querySelectorAll;
                                
                                Document.prototype.querySelector = function(selector) {
                                    if (!selector || selector === '' || selector === '#' || selector === 'undefined' || selector === 'null') {
                                        console.warn('[VARIATION] Invalid selector prevented:', selector);
                                        return null;
                                    }
                                    try {
                                        return originalQuerySelector.call(this, selector);
                                    } catch (e) {
                                        console.error('[VARIATION] querySelector error caught:', e.message, 'selector:', selector);
                                        return null;
                                    }
                                };
                                
                                Document.prototype.querySelectorAll = function(selector) {
                                    if (!selector || selector === '' || selector === '#' || selector === 'undefined' || selector === 'null') {
                                        console.warn('[VARIATION] Invalid selector prevented:', selector);
                                        return [];
                                    }
                                    try {
                                        return originalQuerySelectorAll.call(this, selector);
                                    } catch (e) {
                                        console.error('[VARIATION] querySelectorAll error caught:', e.message, 'selector:', selector);
                                        return [];
                                    }
                                };
                                
                                // Also wrap Element.prototype methods
                                var originalElementQuerySelector = Element.prototype.querySelector;
                                var originalElementQuerySelectorAll = Element.prototype.querySelectorAll;
                                
                                Element.prototype.querySelector = function(selector) {
                                    if (!selector || selector === '' || selector === '#' || selector === 'undefined' || selector === 'null') {
                                        console.warn('[VARIATION] Invalid selector prevented:', selector);
                                        return null;
                                    }
                                    try {
                                        return originalElementQuerySelector.call(this, selector);
                                    } catch (e) {
                                        console.error('[VARIATION] Element querySelector error caught:', e.message, 'selector:', selector);
                                        return null;
                                    }
                                };
                                
                                Element.prototype.querySelectorAll = function(selector) {
                                    if (!selector || selector === '' || selector === '#' || selector === 'undefined' || selector === 'null') {
                                        console.warn('[VARIATION] Invalid selector prevented:', selector);
                                        return [];
                                    }
                                    try {
                                        return originalElementQuerySelectorAll.call(this, selector);
                                    } catch (e) {
                                        console.error('[VARIATION] Element querySelectorAll error caught:', e.message, 'selector:', selector);
                                        return [];
                                    }
                                };
                            })();
                        
                        // IMMEDIATE TEST - Run variation logic right away
                        console.log('[VARIATION] Running immediate test...');
                        var testBtns = document.querySelectorAll('.color-option, .size-option, .color-image-btn');
                        console.log('[VARIATION] Found ' + testBtns.length + ' variation buttons immediately');
                        
                        // Add immediate click listener
                        document.addEventListener('click', function(e) {
                            console.log('[VARIATION] IMMEDIATE CLICK DETECTED on:', e.target);
                            if (e.target.classList.contains('color-option') || e.target.classList.contains('size-option') || e.target.classList.contains('color-image-btn')) {
                                console.log('[VARIATION] IMMEDIATE: Variation button clicked!');
                                alert('Variation button clicked: ' + e.target.textContent);
                            }
                        });
                        
                        // Button state will be managed by the global cart handler
                        
                        function initializeVariationSelection() {
                            var hasVariations = @json($product->has_variations);
                            if (!hasVariations) {
                                return;
                            }

                            @php
                                $__variationPayload = ($product->variations ?? collect())->map(function($v) use ($product) {
                                    return [
                                        'id' => $v->id,
                                        'name' => $v->name,
                                        'price' => (float) ($v->final_price ?? $v->price ?? $product->price),
                                        'image' => $v->image ? asset($v->image) : null,
                                        'galleries' => $v->galleries->map(function($g) {
                                            return asset($g->image);
                                        })->values()->all(),
                                        'available_stock' => (int) ($v->available_stock ?? 0),
                                        'attribute_value_ids' => $v->combinations->pluck('attribute_value_id')->values()->all(),
                                    ];
                                })->values()->all();
                            @endphp
                            var productVariations = @json($__variationPayload);
                            console.log('[VARIATION] variations payload size =', Array.isArray(productVariations) ? productVariations.length : 'n/a');

                            // Apply variation logic only if product has variations
                            var hasVariations = @json($product->has_variations);
                            if (hasVariations) {
                                var initialAddBtn = document.querySelector('.btn-add-cart');
                                if (initialAddBtn) {
                                    initialAddBtn.disabled = true;
                                }
                            }

                            function updateHiddenSelectedValues(selectedMap) {
                                var container = document.getElementById('selected-attribute-values');
                                if (!container) return;
                                container.innerHTML = '';
                                Object.keys(selectedMap).forEach(function(attrId) {
                                    // Validate attrId
                                    if (!attrId || attrId === 'undefined' || attrId === 'null' || attrId === '' || attrId === '#') {
                                        console.warn('[VARIATION] Invalid attrId in updateHiddenSelectedValues:', attrId);
                                        return;
                                    }
                                    
                                    var input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'attribute_value_ids[]';
                                    input.value = selectedMap[attrId];
                                    container.appendChild(input);
                                });
                            }

                            function renderSelectionLabels(selectedMap) {
                                Object.keys(selectedMap).forEach(function(attrId) {
                                    // More robust validation
                                    if (!attrId || attrId === 'undefined' || attrId === 'null' || attrId === '' || attrId === '#') {
                                        console.warn('[VARIATION] Invalid attrId:', attrId);
                                        return;
                                    }
                                    
                                    // Additional validation - ensure attrId is a valid string/number
                                    if (typeof attrId !== 'string' && typeof attrId !== 'number') {
                                        console.warn('[VARIATION] attrId is not string/number:', typeof attrId, attrId);
                                        return;
                                    }
                                    
                                    // Sanitize attrId to prevent XSS and invalid selectors
                                    var sanitizedAttrId = String(attrId).replace(/[^a-zA-Z0-9_-]/g, '');
                                    if (!sanitizedAttrId) {
                                        console.warn('[VARIATION] attrId became empty after sanitization:', attrId);
                                        return;
                                    }
                                    
                                    try {
                                        var labelEl = document.querySelector('[data-selected-label="attr-' + sanitizedAttrId + '"]');
                                        if (labelEl) {
                                            var btn = document.querySelector('.size-option.active[data-attr-id="' + sanitizedAttrId + '"], .color-option.active[data-attr-id="' + sanitizedAttrId + '"], .color-image-btn.active[data-attr-id="' + sanitizedAttrId + '"]');
                                            var label = btn ? (btn.getAttribute('data-label') || btn.textContent) : '';
                                            if (label) { labelEl.textContent = label; }
                                        }
                                    } catch (e) {
                                        console.error('[VARIATION] Error in renderSelectionLabels:', e, 'attrId:', attrId, 'sanitized:', sanitizedAttrId);
                                    }
                                });
                            }

                            function tryResolveVariation(selectedMap) {
                                var selectedIds = Object.values(selectedMap).filter(Boolean).map(function(v){ return parseInt(v, 10); }).sort(function(a,b){return a-b;});
                                console.log('[VARIATION] Trying to resolve with selected IDs:', selectedIds);
                                var resolved = null;
                                
                                // First try exact match (all attributes selected)
                                productVariations.forEach(function(v){
                                    var ids = (v.attribute_value_ids || []).map(function(x){ return parseInt(x, 10); }).sort(function(a,b){return a-b;});
                                    console.log('[VARIATION] Checking variation', v.id, 'with IDs:', ids);
                                    if (ids.length && ids.length === selectedIds.length) {
                                        var same = ids.length === selectedIds.length && ids.every(function(x, i){ return x === selectedIds[i]; });
                                        if (same) { 
                                            resolved = v; 
                                            console.log('[VARIATION] Found exact matching variation:', v);
                                        }
                                    }
                                });
                                
                                // If no exact match and we have selections, try partial match for image display
                                if (!resolved && selectedIds.length > 0) {
                                    console.log('[VARIATION] No exact match, trying partial match for image display...');
                                    productVariations.forEach(function(v){
                                        var ids = (v.attribute_value_ids || []).map(function(x){ return parseInt(x, 10); });
                                        console.log('[VARIATION] Checking partial match for variation', v.id, 'with IDs:', ids);
                                        
                                        // Check if all selected IDs are present in this variation
                                        var allSelectedMatch = selectedIds.every(function(selectedId) {
                                            return ids.includes(selectedId);
                                        });
                                        
                                        if (allSelectedMatch && ids.length > 0) {
                                            resolved = v;
                                            console.log('[VARIATION] Found partial matching variation for image display:', v);
                                        }
                                    });
                                }
                                
                                console.log('[VARIATION] Resolution result:', resolved);
                                return resolved;
                            }

                            function switchToVariationImages(variation) {
                                console.log('[VARIATION] Switching to variation images for variation:', variation);
                                
                                // Hide all variation images first
                                var allVariationSlides = document.querySelectorAll('.variation-image-slide, .variation-gallery-slide, .variation-thumb-slide, .variation-gallery-thumb-slide');
                                allVariationSlides.forEach(function(slide) {
                                    slide.style.display = 'none';
                                });
                                
                                // Show product and gallery images by default
                                var productSlides = document.querySelectorAll('[data-image-type="product"], [data-image-type="gallery"]');
                                productSlides.forEach(function(slide) {
                                    slide.style.display = 'block';
                                });
                                
                                if (variation && variation.image) {
                                    // Hide product images and show variation images
                                    productSlides.forEach(function(slide) {
                                        slide.style.display = 'none';
                                    });
                                    
                                    // Show variation main image
                                    var variationImageSlides = document.querySelectorAll('.variation-image-slide[data-variation-id="' + variation.id + '"]');
                                    variationImageSlides.forEach(function(slide) {
                                        slide.style.display = 'block';
                                    });
                                    
                                    // Show variation gallery images
                                    var variationGallerySlides = document.querySelectorAll('.variation-gallery-slide[data-variation-id="' + variation.id + '"]');
                                    variationGallerySlides.forEach(function(slide) {
                                        slide.style.display = 'block';
                                    });
                                    
                                    // Show variation thumbnails
                                    var variationThumbSlides = document.querySelectorAll('.variation-thumb-slide[data-variation-id="' + variation.id + '"]');
                                    variationThumbSlides.forEach(function(slide) {
                                        slide.style.display = 'block';
                                    });
                                    
                                    var variationGalleryThumbSlides = document.querySelectorAll('.variation-gallery-thumb-slide[data-variation-id="' + variation.id + '"]');
                                    variationGalleryThumbSlides.forEach(function(slide) {
                                        slide.style.display = 'block';
                                    });
                                    
                                    // Update Swiper if it exists
                                    if (window.mainSwiper) {
                                        window.mainSwiper.update();
                                    }
                                    if (window.thumbSwiper) {
                                        window.thumbSwiper.update();
                                    }
                                    
                                    console.log('[VARIATION] Switched to variation images for variation ID:', variation.id);
                                } else {
                                    // Show product images if no variation or no variation image
                                    productSlides.forEach(function(slide) {
                                        slide.style.display = 'block';
                                    });
                                    
                                    // Update Swiper if it exists
                                    if (window.mainSwiper) {
                                        window.mainSwiper.update();
                                    }
                                    if (window.thumbSwiper) {
                                        window.thumbSwiper.update();
                                    }
                                    
                                    console.log('[VARIATION] Showing product images (no variation image)');
                                }
                            }


                            var selectedMap = {};
                            
                            document.addEventListener('click', function(e){
                                console.log('[VARIATION] Click event triggered on:', e.target);
                                
                                // More robust button detection
                                var btn = null;
                                var target = e.target;
                                
                                // Check if the clicked element itself is a variation button
                                if (target.classList.contains('size-option') || target.classList.contains('color-option') || target.classList.contains('color-image-btn')) {
                                    btn = target;
                                } else {
                                    // Check if the clicked element is inside a variation button (including color image buttons)
                                    var parent = target.parentElement;
                                    while (parent && parent !== document.body) {
                                        if (parent.classList.contains('size-option') || parent.classList.contains('color-option') || parent.classList.contains('color-image-btn')) {
                                            btn = parent;
                                            break;
                                        }
                                        parent = parent.parentElement;
                                    }
                                }
                                
                                console.log('[VARIATION] Found button:', btn);
                                if (!btn) return;
                                e.preventDefault();
                                e.stopPropagation();
                                var attrId = btn.getAttribute('data-attr-id');
                                var valId = btn.getAttribute('data-value-id');
                                
                                // Validate attrId and valId
                                if (!attrId || !valId || attrId === 'undefined' || attrId === 'null' || attrId === '' || attrId === '#') { 
                                    console.warn('[VARIATION] click missing or invalid attrId/valId', {attrId: attrId, valId: valId}); 
                                    return; 
                                }
                                
                                // Additional validation for attrId
                                if (typeof attrId !== 'string' && typeof attrId !== 'number') {
                                    console.warn('[VARIATION] attrId is not string/number:', typeof attrId, attrId);
                                    return;
                                }

                                console.log('[VARIATION] option clicked', {attrId: attrId, valId: valId, text: btn.textContent});

                                // toggle selection per attribute
                                var container = btn.closest('[data-attribute-id]');
                                if (container) {
                                    container.querySelectorAll('.size-option, .color-option, .color-image-btn').forEach(function(b){ b.classList.remove('active'); });
                                }
                                btn.classList.add('active');
                                // Force visible active style in case of CSS overrides
                                try {
                                    btn.style.borderColor = '#ff6a00';
                                    btn.style.boxShadow = '0 0 0 2px rgba(255,106,0,0.15)';
                                    btn.style.backgroundColor = '#fff7f0';
                                    btn.style.color = '#222';
                                } catch(_) {}
                                selectedMap[String(attrId)] = String(valId);
                                console.log('[VARIATION] Updated selectedMap:', selectedMap);
                                updateHiddenSelectedValues(selectedMap);
                                renderSelectionLabels(selectedMap);

                                var resolved = tryResolveVariation(selectedMap);
                                console.log('[VARIATION] resolved =', resolved);
                                var varIdEl = document.getElementById('selected-variation-id');
                                var nameEl = document.getElementById('selected-variation-name');
                                var priceEl = document.getElementById('selected-variation-price');
                                var stockEl = document.getElementById('selected-variation-stock');
                                var addBtn = document.querySelector('.btn-add-cart');
                                if (resolved) {
                                    if (varIdEl) {
                                        varIdEl.value = resolved.id;
                                        console.log('[VARIATION] Set variation ID to:', resolved.id);
                                    }
                                    if (nameEl) nameEl.textContent = resolved.name || 'Selected';
                                    if (priceEl) priceEl.textContent = (resolved.price != null ? Number(resolved.price).toFixed(2) : '') + '৳';
                                    if (stockEl) stockEl.textContent = resolved.available_stock > 0 ? ('In stock: ' + resolved.available_stock) : 'Out of stock';
                                    if (addBtn) { addBtn.disabled = resolved.available_stock <= 0; console.log('[VARIATION] add-to-cart disabled =', addBtn.disabled); }
                                    
                                    // Switch to variation images
                                    switchToVariationImages(resolved);
                                    
                                } else {
                                    if (varIdEl) {
                                        varIdEl.value = '';
                                        console.log('[VARIATION] Cleared variation ID');
                                    }
                                    if (nameEl) nameEl.textContent = 'Please select options above';
                                    if (priceEl) priceEl.textContent = '—';
                                    if (stockEl) stockEl.textContent = 'Select your preferences to see availability';
                                    if (addBtn && hasVariations) { addBtn.disabled = true; }
                                    
                                    // Show product images when no variation is selected
                                    switchToVariationImages(null);
                                }
                            });

                            // No auto-selection - customers must manually choose their variations
                            
                            // Debug: Check if variation buttons exist and add direct event listeners
                            setTimeout(function() {
                                var colorBtns = document.querySelectorAll('.color-option');
                                var sizeBtns = document.querySelectorAll('.size-option');
                                console.log('[VARIATION] Found ' + colorBtns.length + ' color buttons and ' + sizeBtns.length + ' size buttons');
                                
                                if (colorBtns.length === 0 && sizeBtns.length === 0) {
                                    console.warn('[VARIATION] No variation buttons found in DOM!');
                                    return;
                                }
                                
                                // Add direct click event listeners to each button
                                var allBtns = document.querySelectorAll('.color-option, .size-option, .color-image-btn');
                                allBtns.forEach(function(btn, index) {
                                    console.log('[VARIATION] Adding direct listener to button ' + index + ': ' + btn.textContent);
                                    btn.addEventListener('click', function(e) {
                                        console.log('[VARIATION] Direct click on button: ' + btn.textContent);
                                        handleVariationClick(btn, e);
                                    });
                                });
                            }, 1000);
                            
                            // Function to handle variation button clicks
                            function handleVariationClick(btn, e) {
                                e.preventDefault();
                                e.stopPropagation();
                                
                                var attrId = btn.getAttribute('data-attr-id');
                                var valId = btn.getAttribute('data-value-id');
                                console.log('[VARIATION] Button clicked - attrId: ' + attrId + ', valId: ' + valId + ', text: ' + btn.textContent);
                                
                                // Validate attrId and valId
                                if (!attrId || !valId || attrId === 'undefined' || attrId === 'null' || attrId === '' || attrId === '#') {
                                    console.warn('[VARIATION] Missing or invalid attrId or valId', {attrId: attrId, valId: valId});
                                    return;
                                }
                                
                                // Additional validation for attrId
                                if (typeof attrId !== 'string' && typeof attrId !== 'number') {
                                    console.warn('[VARIATION] attrId is not string/number:', typeof attrId, attrId);
                                    return;
                                }
                                
                                // Toggle selection per attribute
                                var container = btn.closest('[data-attribute-id]');
                                if (container) {
                                    container.querySelectorAll('.size-option, .color-option, .color-image-btn').forEach(function(b){ 
                                        b.classList.remove('active'); 
                                    });
                                }
                                btn.classList.add('active');
                                
                                // Force visible active style
                                btn.style.borderColor = '#ff6a00';
                                btn.style.boxShadow = '0 0 0 2px rgba(255,106,0,0.15)';
                                btn.style.backgroundColor = '#fff7f0';
                                btn.style.color = '#222';
                                
                                selectedMap[String(attrId)] = String(valId);
                                console.log('[VARIATION] Updated selectedMap:', selectedMap);
                                updateHiddenSelectedValues(selectedMap);
                                renderSelectionLabels(selectedMap);
                                
                                var resolved = tryResolveVariation(selectedMap);
                                console.log('[VARIATION] resolved =', resolved);
                                var varIdEl = document.getElementById('selected-variation-id');
                                var nameEl = document.getElementById('selected-variation-name');
                                var priceEl = document.getElementById('selected-variation-price');
                                var stockEl = document.getElementById('selected-variation-stock');
                                var addBtn = document.querySelector('.btn-add-cart');
                                
                                // Check if this is a complete match (all attributes selected)
                                var allAttributeIds = Object.keys(selectedMap).length;
                                var totalAttributes = document.querySelectorAll('[data-attribute-id]').length;
                                var isCompleteMatch = allAttributeIds === totalAttributes;
                                
                                if (resolved) {
                                    if (isCompleteMatch) {
                                        // Complete match - show full variation details and enable add to cart
                                        if (varIdEl) {
                                            varIdEl.value = resolved.id;
                                            console.log('[VARIATION] Set variation ID to:', resolved.id);
                                        }
                                        if (nameEl) nameEl.textContent = resolved.name || 'Selected';
                                        if (priceEl) priceEl.textContent = (resolved.price != null ? Number(resolved.price).toFixed(2) : '') + '৳';
                                        if (stockEl) stockEl.textContent = resolved.available_stock > 0 ? ('In stock: ' + resolved.available_stock) : 'Out of stock';
                                        if (addBtn) { addBtn.disabled = resolved.available_stock <= 0; console.log('[VARIATION] add-to-cart disabled =', addBtn.disabled); }
                                    } else {
                                        // Partial match - show images but keep add to cart disabled
                                        if (varIdEl) {
                                            varIdEl.value = '';
                                            console.log('[VARIATION] Partial match - cleared variation ID');
                                        }
                                        if (nameEl) nameEl.textContent = 'Please select all options';
                                        if (priceEl) priceEl.textContent = '—';
                                        if (stockEl) stockEl.textContent = 'Select all options to see price and availability';
                                        if (addBtn && hasVariations) { addBtn.disabled = true; }
                                    }
                                    
                                    // Switch to variation images for both complete and partial matches
                                    switchToVariationImages(resolved);
                                    
                                } else {
                                    if (varIdEl) {
                                        varIdEl.value = '';
                                        console.log('[VARIATION] Cleared variation ID');
                                    }
                                    if (nameEl) nameEl.textContent = 'Please select options above';
                                    if (priceEl) priceEl.textContent = '—';
                                    if (stockEl) stockEl.textContent = 'Select your preferences to see availability';
                                    if (addBtn && hasVariations) { addBtn.disabled = true; }
                                    
                                    // Show product images when no variation is selected
                                    switchToVariationImages(null);
                                }
                            }
                        }
                        
                        // Initialize immediately
                        initializeVariationSelection();
                    </script>
                    @endif

                    <div class="quantity-selector align-items-center mt-4">
                        <label for="quantity">Quantity:</label>
                        <div class="input-group" style="width: 135px; flex-wrap: nowrap;">
                            <button class="btn nav-button border py-1 px-3 quantity-btn" type="button"
                                onclick="changeQuantity(-1)">-</button>
                            <input type="number" class="form-control text-center ps-3 pe-0 py-0 w-100" id="quantityInput"
                                name="quantity" value="1" min="1" max="10">
                            <button class="btn nav-button border py-1 px-3 quantity-btn" type="button"
                                onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button class="btn-add-cart" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}">
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
                <div class="reviews-section">
                    <div class="reviews-header">
                        <h3 class="reviews-title">
                            <i class="fas fa-star text-warning me-2"></i>
                            Customer Reviews
                        </h3>
                        <p class="reviews-subtitle">Share your experience and read what others think</p>
                    </div>
                    
                    <!-- Reviews Summary -->
                    <div class="reviews-summary-card">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="rating-overview text-center">
                                    <div class="overall-rating" id="overall-rating">0.0</div>
                                    <div class="rating-stars" id="rating-stars">
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                    <div class="rating-count" id="rating-count">0 reviews</div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="rating-breakdown">
                                    <h6>Rating Breakdown</h6>
                                    <div id="rating-bars">
                                        <!-- Dynamic rating bars will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Review Form -->
                    <div class="review-form-section">
                        @auth
                            <div class="review-form-card">
                                <h4 class="form-title">
                                    <i class="fas fa-edit me-2"></i>
                                    Write a Review
                                </h4>
                                <form id="review-form" class="review-form">
                                    @csrf
                                    <div class="form-group">
                                        <label for="rating" class="form-label">Rating *</label>
                                        <div class="rating-input">
                                            <input type="radio" name="rating" value="5" id="star5">
                                            <label for="star5" class="star-label" data-rating="5">
                                                <i class="far fa-star"></i>
                                            </label>
                                            <input type="radio" name="rating" value="4" id="star4">
                                            <label for="star4" class="star-label" data-rating="4">
                                                <i class="far fa-star"></i>
                                            </label>
                                            <input type="radio" name="rating" value="3" id="star3">
                                            <label for="star3" class="star-label" data-rating="3">
                                                <i class="far fa-star"></i>
                                            </label>
                                            <input type="radio" name="rating" value="2" id="star2">
                                            <label for="star2" class="star-label" data-rating="2">
                                                <i class="far fa-star"></i>
                                            </label>
                                            <input type="radio" name="rating" value="1" id="star1">
                                            <label for="star1" class="star-label" data-rating="1">
                                                <i class="far fa-star"></i>
                                            </label>
                                        </div>
                                        <div class="rating-text" id="rating-text">Select a rating</div>
                                    </div>
                                    <div class="form-group">
                                        <label for="comment" class="form-label">Your Review *</label>
                                        <textarea class="form-control" name="comment" id="comment" rows="4" 
                                                  placeholder="Tell us about your experience with this product" required></textarea>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            Submit Review
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="cancel-review" style="display: none;">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="review-login-prompt">
                                <div class="login-prompt-content">
                                    <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                                    <h5>Login Required</h5>
                                    <p>Please log in to write a review and share your experience with others.</p>
                                    <a href="{{ route('login') }}" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Login to Write Review
                                    </a>
                                </div>
                            </div>
                        @endauth
                    </div>

                    <!-- Reviews List -->
                    <div class="reviews-list-section">
                        <div class="reviews-list-header">
                            <h4>Customer Reviews</h4>
                            <div class="reviews-filter">
                                <select id="reviews-filter" class="form-select">
                                    <option value="">All Reviews</option>
                                    <option value="5">5 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="2">2 Stars</option>
                                    <option value="1">1 Star</option>
                                </select>
                            </div>
                        </div>
                        <div id="reviews-list" class="reviews-list" data-product-id="{{ $product->id }}" data-product-slug="{{ $product->slug }}">
                            <!-- Reviews will be loaded here -->
                        </div>
                        <div id="load-more-container" class="text-center mt-4" style="display: none;">
                            <button class="btn btn-outline-primary" id="load-more-reviews">Load More Reviews</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Related Products Section -->
    @if(isset($relatedProducts) && count($relatedProducts))
        <div class="related-products">
            <div class="container">
                <div class="related-products-header">
                    <h2>You Might Also Like</h2>
                    <p class="related-products-subtitle">Discover more amazing products</p>
                </div>
                
                <div class="related-products-container">
                    <div class="swiper related-swiper">
                        <div class="swiper-wrapper">
                            @foreach($relatedProducts as $product)
                                <div class="swiper-slide">
                                    <div class="related-product-card">
                                        <!-- Wishlist Button -->
                                        <button class="product-wishlist-top {{$product->is_wishlisted ? ' active' : ''}}" 
                                                onclick="event.stopPropagation(); toggleWishlist({{ $product->id }});"
                                                data-product-id="{{ $product->id }}"
                                                title="{{$product->is_wishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'}}">
                                            <i class="{{ $product->is_wishlisted ? 'fas' : 'far' }} fa-heart"></i>
                                        </button>
                                        
                                        <div class="related-product-image-container">
                                            <img src="{{ asset($product->image) }}" 
                                                 class="related-product-image" 
                                                 alt="{{ $product->name }}"
                                                 loading="lazy">
                                        </div>
                                        
                                        <div class="related-product-content">
                                            <a href="{{ route('product.details', $product->slug) }}" 
                                               class="related-product-title">
                                                {{ $product->name }}
                                            </a>
                                            
                                            <p class="related-product-description">
                                                {{ Str::limit($product->short_desc ?? $product->description, 100) }}
                                            </p>
                                            
                                            <!-- Rating Display -->
                                            <div class="related-product-rating">
                                                <div class="related-product-stars">
                                                    @php
                                                        $avgRating = $product->averageRating() ?? 0;
                                                        $fullStars = floor($avgRating);
                                                        $hasHalfStar = $avgRating - $fullStars >= 0.5;
                                                    @endphp
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= $fullStars)
                                                            <i class="fas fa-star"></i>
                                                        @elseif($i == $fullStars + 1 && $hasHalfStar)
                                                            <i class="fas fa-star-half-alt"></i>
                                                        @else
                                                            <i class="far fa-star"></i>
                                                        @endif
                                                    @endfor
                                                </div>
                                            </div>
                                            
                                            <!-- Price Display -->
                                            <div class="related-product-price">
                                                @if(isset($product->discount) && $product->discount > 0)
                                                    <span class="related-product-current-price">
                                                        {{ number_format($product->discount, 2) }}৳
                                                    </span>
                                                    <span class="related-product-original-price">
                                                        {{ number_format($product->price, 2) }}৳
                                                    </span>
                                                @else
                                                    <span class="related-product-current-price">
                                                        {{ number_format($product->price, 2) }}৳
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <!-- Action Buttons -->
                                            <div class="related-product-actions">
                                                <a href="{{ route('product.details', $product->slug) }}" 
                                                   class="related-product-btn related-product-btn-primary">
                                                    <i class="fas fa-eye me-1"></i>
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Navigation Buttons -->
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                        
                        <!-- Pagination -->
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
        <img class="image-modal-content" id="modalImage">
    </div>

    <script>
        console.log('[PD] Product Details page script running');
        
        // Global quantity control function
        window.changeQuantity = function(delta) {
            console.log('[QTY] changeQuantity called with delta:', delta);
            var input = document.getElementById('quantityInput');
            if (!input) {
                console.error('[QTY] Quantity input not found');
                return;
            }
            var value = parseInt(input.value) || 1;
            value += delta;
            if (value < 1) value = 1;
            if (value > 10) value = 10;
            input.value = value;
            console.log('[QTY] Quantity updated to:', value);
        }

        // Fallback quantity control with event delegation
        document.addEventListener('click', function(e) {
            if (e.target && e.target.matches('.quantity-btn')) {
                e.preventDefault();
                var delta = e.target.textContent === '+' ? 1 : -1;
                console.log('[QTY] Quantity button clicked via delegation:', delta);
                window.changeQuantity(delta);
            }
        });

        // Wishlist functionality
        function addToWishlist() {
            console.log('[WISHLIST] Adding to wishlist...');
            
            // Get product ID from the button or page
            var productId = {{ $product->id }};
            var button = event.target.closest('button');
            
            // Disable button to prevent multiple clicks
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            }
            
            // Make AJAX request to add to wishlist
            fetch('/add-remove-wishlist/' + productId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    if (typeof showToast === 'function') {
                        showToast('Added to wishlist!', 'success');
                    } else {
                        alert('Added to wishlist!');
                    }
                    
                    // Update wishlist count
                    if (typeof updateWishlistCount === 'function') {
                        updateWishlistCount();
                    }
                    
                    // Update button state
                    if (button) {
                        button.innerHTML = '<i class="fas fa-heart"></i> Added!';
                        button.classList.add('added-to-wishlist');
                        setTimeout(function() {
                            button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="var(--primary-blue)" id="Outline" viewBox="0 0 24 24" width="20" height="20"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z" /></svg>';
                            button.disabled = false;
                        }, 2000);
                    }
                } else {
                    // Show error message
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Failed to add to wishlist', 'error');
                    } else {
                        alert(data.message || 'Failed to add to wishlist');
                    }
                    
                    // Reset button state
                    if (button) {
                        button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="var(--primary-blue)" id="Outline" viewBox="0 0 24 24" width="20" height="20"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z" /></svg>';
                        button.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Wishlist error:', error);
                if (typeof showToast === 'function') {
                    showToast('Error adding to wishlist', 'error');
                } else {
                    alert('Error adding to wishlist');
                }
                
                // Reset button state
                if (button) {
                    button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="var(--primary-blue)" id="Outline" viewBox="0 0 24 24" width="20" height="20"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z" /></svg>';
                    button.disabled = false;
                }
            });
        }

        // Simple and robust image gallery initialization
        function initImageGallery() {
            console.log('[GALLERY] Starting initialization...');
            
            var gallery = document.querySelector('.product-gallery');
            if (!gallery) {
                console.log('[GALLERY] Gallery not found');
                return;
            }

            if (typeof Swiper === 'undefined') {
                console.error('[GALLERY] Swiper not loaded');
                return;
            }

            var thumbContainer = gallery.querySelector('.thumb-swiper');
            var mainContainer = gallery.querySelector('.main-swiper');
            
            if (!thumbContainer || !mainContainer) {
                console.error('[GALLERY] Containers not found');
                return;
            }

            // Destroy existing swipers
            if (window.thumbSwiper) {
                try { window.thumbSwiper.destroy(true, true); } catch(e) {}
            }
            if (window.mainSwiper) {
                try { window.mainSwiper.destroy(true, true); } catch(e) {}
            }

            // Create thumb swiper
            window.thumbSwiper = new Swiper(thumbContainer, {
                spaceBetween: 10,
                slidesPerView: 'auto',
                freeMode: true,
                watchSlidesProgress: true
            });

            // Create main swiper
            window.mainSwiper = new Swiper(mainContainer, {
                spaceBetween: 10,
                navigation: {
                    nextEl: '.main-swiper .swiper-button-next',
                    prevEl: '.main-swiper .swiper-button-prev',
                },
                thumbs: { 
                    swiper: window.thumbSwiper 
                }
            });

            // Add click handlers for thumbnails
            var thumbSlides = document.querySelectorAll('.thumb-swiper .swiper-slide');
            console.log('[GALLERY] Adding click handlers to', thumbSlides.length, 'thumbnails');
            
            thumbSlides.forEach(function(slide, index) {
                slide.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('[GALLERY] Thumbnail clicked, index:', index);
                    if (window.mainSwiper) {
                        window.mainSwiper.slideTo(index);
                    }
                });
            });

            console.log('[GALLERY] Initialization complete');
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[PD] DOM ready - initializing...');
            initImageGallery();
        });

        // Toast notification system
        function showToast(message, type = 'success') {
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
                container.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;';
                document.body.appendChild(container);
            }
            
            container.appendChild(toast);
            
            // Animate progress bar - start at 100% and animate to 0%
            var progressBar = toast.querySelector('.toast-progress');
            progressBar.style.width = '100%';
            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 10);
            
            // Auto remove after 2.5 seconds
            setTimeout(() => {
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 400);
            }, 2500);
        }

        // Make showToast globally available
        window.showToast = showToast;

        // Toggle wishlist function
        function toggleWishlist(productId) {
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
        }

        // Make toggleWishlist globally available
        window.toggleWishlist = toggleWishlist;

        // Test elements on page load
        setTimeout(function() {
            var qtyInput = document.getElementById('quantityInput');
            var qtyButtons = document.querySelectorAll('.quantity-btn');
            var gallery = document.querySelector('.product-gallery');
            var thumbSlides = document.querySelectorAll('.thumb-swiper .swiper-slide');
            
            console.log('[PD] Elements found:');
            console.log('[PD] - Quantity input:', !!qtyInput);
            console.log('[PD] - Quantity buttons:', qtyButtons.length);
            console.log('[PD] - Gallery:', !!gallery);
            console.log('[PD] - Thumb slides:', thumbSlides.length);
            console.log('[PD] - Swiper loaded:', typeof Swiper !== 'undefined');
            console.log('[PD] - showToast function:', typeof showToast === 'function');
            
            // Test quantity functionality
            if (qtyInput && qtyButtons.length > 0) {
                console.log('[PD] Testing quantity controls...');
                var originalValue = qtyInput.value;
                window.changeQuantity(1);
                if (qtyInput.value != originalValue) {
                    console.log('[PD] ✓ Quantity controls working');
                    qtyInput.value = originalValue; // Reset
                } else {
                    console.log('[PD] ✗ Quantity controls not working');
                }
            }
            
            // Test image gallery
            if (gallery && thumbSlides.length > 0) {
                console.log('[PD] Testing image gallery...');
                if (window.mainSwiper) {
                    console.log('[PD] ✓ Image gallery initialized');
                } else {
                    console.log('[PD] ✗ Image gallery not initialized');
                }
            }
            
            // Test wishlist functionality
            var wishlistBtn = document.querySelector('button[onclick="addToWishlist()"]');
            if (wishlistBtn) {
                console.log('[PD] ✓ Wishlist button found');
                console.log('[PD] ✓ addToWishlist function available:', typeof addToWishlist === 'function');
            } else {
                console.log('[PD] ✗ Wishlist button not found');
            }
            
            // Test toast functionality
            console.log('[PD] Testing toast notifications...');
            if (typeof showToast === 'function') {
                console.log('[PD] ✓ Toast system ready');
            } else {
                console.log('[PD] ✗ Toast system not available');
            }
        }, 1000);
        
        (function retryInit(attempts){
            if (typeof window.initializePageSpecificScripts === 'function') {
                try {
                    window.initializePageSpecificScripts();
                    console.log('[PD] initializePageSpecificScripts invoked from section');
                } catch(e) {
                    console.error('[PD] init error', e);
                }
            } else if (attempts > 0) {
                setTimeout(function(){ retryInit(attempts - 1); }, 150);
            } else {
                console.warn('[PD] initializer not found after retries');
            }
        })(30);
    </script>

    <!-- Toast Container -->
    <div id="toast-container"
        style="position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;">
    </div>

@endsection

@push('scripts')
    <script>
        console.log('[PD] productDetails scripts executing');
        console.log('[PD] Script section loaded successfully');
        // Image modal removed per requirements; keep a no-op cleaner in case of legacy backdrops
        function removeStuckBackdrops(){
            try {
                document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
            } catch(_) {}
        }

        // Enhanced Related products slider
        if (document.querySelector('.related-swiper') && typeof Swiper !== 'undefined') {
            new Swiper('.related-swiper', {
                slidesPerView: 4,
                spaceBetween: 30,
                centeredSlides: false,
                loop: false,
                freeMode: false,
                autoHeight: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                speed: 600,
                navigation: {
                    nextEl: '.related-swiper .swiper-button-next',
                    prevEl: '.related-swiper .swiper-button-prev',
                },
                pagination: {
                    el: '.related-swiper .swiper-pagination',
                    clickable: true,
                    dynamicBullets: true
                },
                breakpoints: {
                    0: {
                        slidesPerView: 1.2,
                        spaceBetween: 20
                    },
                    480: {
                        slidesPerView: 1.5,
                        spaceBetween: 20
                    },
                    640: { 
                        slidesPerView: 2.2,
                        spaceBetween: 25
                    },
                    768: { 
                        slidesPerView: 2.5,
                        spaceBetween: 25
                    },
                    1024: { 
                        slidesPerView: 3.2,
                        spaceBetween: 30
                    },
                    1200: { 
                        slidesPerView: 4,
                        spaceBetween: 35
                    }
                },
                on: {
                    init: function() {
                        // Add fade-in animation to slides
                        this.slides.forEach((slide, index) => {
                            slide.style.opacity = '0';
                            slide.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                slide.style.transition = 'all 0.6s ease';
                                slide.style.opacity = '1';
                                slide.style.transform = 'translateY(0)';
                            }, index * 100);
                        });
                    }
                }
            });
        }


        // Image preview functionality
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                    document.querySelector('.image-upload-area').style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeImage() {
            document.getElementById('image').value = '';
            document.getElementById('image-preview').style.display = 'none';
            document.querySelector('.image-upload-area').style.display = 'block';
        }

		// Add event listener for image input (guard element existence on this page)
		var imageInputEl = document.getElementById('image');
		if (imageInputEl) {
			imageInputEl.addEventListener('change', function() {
				previewImage(this);
			});
		}

        // Image modal functionality
        function openImageModal(src) {
            var modal = document.getElementById('imageModal');
            var modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = src;
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Close modal when clicking outside the image
        window.onclick = function(event) {
            var modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }


        // Expose page-specific initializer so master layout can re-run after AJAX navigation
        window.initializePageSpecificScripts = function(){
            // Reset the flag to allow re-initialization after AJAX navigation
            if (window.__productPageInitApplied && !window.__allowReinit) { 
                console.log('[PD] Page already initialized, skipping');
                return; 
            }
            window.__productPageInitApplied = true;
            console.log('[PD] Initializing page-specific scripts');
            
            // Initialize variation selection after AJAX navigation
            if (typeof window.initializeVariationSelection === 'function') {
                console.log('[VARIATION] Re-initializing variation selection after AJAX navigation');
                window.initializeVariationSelection();
            }

            // Cart functionality is now handled by global cart handler in master.blade.php
            // No need for duplicate event listeners here
            
            // Keep variation-specific logic for product details page
            // DISABLED: Using global cart handler instead
            window.__cartEventListener = function(e){
                return; // Function disabled - using global cart handler
                var btnEl = e.target.closest ? e.target.closest('.btn-add-cart') : null;
                if (!btnEl) return;
                e.preventDefault();
                
                // Prevent multiple simultaneous requests
                if (btnEl.disabled || btnEl.getAttribute('data-processing') === 'true') {
                    console.log('[CART] Request already in progress, ignoring click');
                    return;
                }
                
                // Mark button as processing
                btnEl.setAttribute('data-processing', 'true');
                
                // Get product ID from data attribute or fallback to PHP variable
                var productId = btnEl.getAttribute('data-product-id') || {{ $product->id ?? 'null' }};
                var productName = btnEl.getAttribute('data-product-name') || '{{ $product->name ?? "Unknown Product" }}';
                
                // Validate product ID
                if (!productId || productId === 'null' || productId === '') {
                    console.error('[CART] Invalid product ID:', productId);
                    alert('Error: Invalid product ID. Please refresh the page and try again.');
                    btnEl.removeAttribute('data-processing');
                    return;
                }
                
                console.log('[CART] Adding product to cart:');
                console.log('[CART] Product ID:', productId);
                console.log('[CART] Product Name:', productName);
                
                var qtyInput = document.getElementById('quantityInput');
                var qty = parseInt(qtyInput && qtyInput.value ? qtyInput.value : 1, 10) || 1;
                btnEl.disabled = true;
                var data = new URLSearchParams();
                data.append('qty', qty.toString());

                // Variation payload - use server flag as primary source, DOM as secondary check
                var hasVariations = @json($product->has_variations);
                console.log('[CART] Server has_variations flag:', hasVariations);
                
                // Only override server flag if DOM clearly shows variation elements exist
                var hasVariationsDOM = document.querySelectorAll('.color-option, .size-option, .color-image-btn, .variation-option').length > 0;
                console.log('[CART] DOM variation elements found:', hasVariationsDOM);
                
                // Trust the server flag primarily, but if DOM shows variations and server says no, use DOM
                if (hasVariationsDOM && !hasVariations) {
                    console.log('[CART] DOM shows variations but server says no - using DOM detection');
                    hasVariations = true;
                }

                var variationIdEl = document.getElementById('selected-variation-id');
                var variationId = variationIdEl ? variationIdEl.value : '';
                console.log('[CART] variationIdEl:', variationIdEl);
                console.log('[CART] variationId:', variationId);

                if (variationId) {
                    // Always send variation_id if present (even if server flag says no variations)
                    data.append('variation_id', variationId);
                    console.log('[CART] Using variation_id:', variationId);
                } else if (hasVariations) {
                    // Require explicit selection
                    showToast('Please select Color and Size before adding to cart.', 'error');
                    btnEl.disabled = false;
                    btnEl.removeAttribute('data-processing');
                    return;
                } else {
                    console.log('[CART] Product has no variations, skipping variation data');
                }
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
                console.log('[CART] POST /cart/add-page/' + productId, Object.fromEntries(data));
                fetch('/cart/add-page/' + productId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: data.toString()
                }).then(function(res){
                    console.log('[CART] Response status:', res.status);
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status + ': ' + res.statusText);
                    }
                    return res.json().catch(function(){
                        return { success:false, message:'Invalid JSON from server', status: res.status };
                    });
                }).then(function(response){
                    console.log('[CART] response', response);
                    if (response && response.success) {
                        showToast((response.message || 'Product added to cart successfully!'), 'success');
                        if (typeof updateCartCount === 'function') { updateCartCount(); }
                        if (typeof updateCartQtyBadge === 'function') { updateCartQtyBadge(); }
                    } else {
                        showToast((response && response.message) || 'Failed to add product to cart', 'error');
                    }
                }).catch(function(error){
                    console.error('[CART] network/error:', error);
                    showToast('Failed to add product to cart: ' + error.message, 'error');
                }).finally(function(){
                    btnEl.disabled = false;
                    btnEl.removeAttribute('data-processing');
                });
            };
            
            // Register the event listener - DISABLED: Using global cart handler instead
            // document.addEventListener('click', window.__cartEventListener);
            
            // Reset the re-init flag after initialization
            window.__allowReinit = false;
        };

        // Variation scripts moved to main content section for AJAX compatibility
        // Run initializer immediately for normal loads
        if (typeof window.initializePageSpecificScripts === 'function') {
            try { window.initializePageSpecificScripts(); } catch (e) { console.error('Init error', e); }
        }
    </script>

    <!-- NEW CLEAN REVIEW SYSTEM -->
    <script>
        $(document).ready(function() {
            // Function to get current product info dynamically
            function getCurrentProductInfo() {
                var productId = {{ $product->id }};
                var productName = '{{ $product->name }}';
                var productSlug = '{{ $product->slug }}';

                return {
                    id: productId,
                    name: productName,
                    slug: productSlug
                };
            }

        });

        // Review System JavaScript
        $(document).ready(function() {
            // Check if reviews container exists
            const reviewsContainer = $('#reviews-list');
            if (reviewsContainer.length === 0) {
                console.error('❌ Reviews container not found!');
                return;
            }
            
            // Get product ID from the container data attribute to ensure we have the correct product
            const reviewProductId = parseInt(reviewsContainer.data('product-id'));
            const productSlug = reviewsContainer.data('product-slug');
            let currentPage = 1;
            let currentFilter = '';
            let isLoading = false;
            
            // Debug: Show product information
            console.log('=== REVIEW SYSTEM INITIALIZATION ===');
            console.log('Product ID from container:', reviewProductId);
            console.log('Product Slug from container:', productSlug);
            console.log('Product ID from PHP:', @json($product->id));
            console.log('Product Name from PHP:', @json($product->name));
            console.log('Product Slug from PHP:', @json($product->slug));
            console.log('Current URL:', window.location.href);
            console.log('Page Title:', document.title);
            console.log('Timestamp:', new Date().toISOString());
            
            // Additional debugging
            console.log('=== PHP DEBUG INFO ===');
            console.log('PHP Product ID:', {{ $product->id }});
            console.log('PHP Product Name:', '{{ $product->name }}');
            console.log('PHP Product Slug:', '{{ $product->slug }}');
            console.log('Container Product ID:', $('#reviews-list').data('product-id'));
            console.log('Container Product Slug:', $('#reviews-list').data('product-slug'));
            
            // Verify the product ID is correct
            if (reviewProductId !== @json($product->id)) {
                console.warn('⚠️ Product ID mismatch detected, using container product ID');
                // Don't return, just use the container's product ID
            }

            // Load reviews on page load
            loadReviews();

            // Initialize star rating interaction
            initializeStarRating();

            // Handle review form submission
            const reviewForm = $('#review-form');
            console.log('Review form found:', reviewForm.length > 0);
            if (reviewForm.length > 0) {
                reviewForm.on('submit', function(e) {
                    console.log('Form submit event triggered!');
                    e.preventDefault();
                    console.log('Prevented default, calling submitReview()');
                    submitReview();
                });
            } else {
                console.error('Review form not found!');
            }

            // Backup: Handle submit button click
            $('#review-form button[type="submit"]').on('click', function(e) {
                console.log('Submit button clicked!');
                e.preventDefault();
                submitReview();
            });

            // Handle review filter
            $('#reviews-filter').on('change', function() {
                currentFilter = $(this).val();
                currentPage = 1;
                loadReviews();
            });

            // Handle load more button
            $('#load-more-reviews').on('click', function() {
                loadMoreReviews();
            });

            // Load reviews function
            function loadReviews() {
                if (isLoading) return;
                isLoading = true;

                const url = `/api/products/${reviewProductId}/reviews?page=${currentPage}&rating=${currentFilter}&_t=${Date.now()}`;
                console.log('=== LOAD REVIEWS DEBUG ===');
                console.log('Current reviewProductId:', reviewProductId);
                console.log('Type of reviewProductId:', typeof reviewProductId);
                console.log('Product ID from PHP:', @json($product->id));
                console.log('Are they equal?', reviewProductId === @json($product->id));
                console.log('API URL:', url);
                console.log('Current page:', currentPage);
                console.log('Current filter:', currentFilter);
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        console.log('=== API RESPONSE DEBUG ===');
                        console.log('API Response:', response);
                        if (response.success) {
                            console.log('Reviews loaded for product ID:', reviewProductId);
                            console.log('Number of reviews:', response.reviews.data.length);
                            console.log('Reviews data:', response.reviews.data);
                            
                            // Check if reviews belong to the correct product
                            response.reviews.data.forEach((review, index) => {
                                console.log(`Review ${index + 1}: Product ID ${review.product_id}, Expected: ${reviewProductId}`);
                                if (review.product_id != reviewProductId) {
                                    console.error(`❌ MISMATCH: Review ${review.id} belongs to product ${review.product_id} but we're on product ${reviewProductId}`);
                                }
                            });
                            
                            displayReviews(response.reviews.data);
                            updateRatingSummary(response.average_rating, response.total_reviews, response.rating_distribution);
                            updatePagination(response.pagination);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading reviews:', xhr);
                        showError('Failed to load reviews');
                    },
                    complete: function() {
                        isLoading = false;
                    }
                });
            }

            // Load more reviews
            function loadMoreReviews() {
                currentPage++;
                loadReviews();
            }

            // Display reviews
            function displayReviews(reviews) {
                const reviewsList = $('#reviews-list');
                const containerProductId = reviewsList.data('product-id');
                
                console.log('=== DISPLAY REVIEWS DEBUG ===');
                console.log('Container product ID:', containerProductId);
                console.log('Current reviewProductId:', reviewProductId);
                console.log('Reviews to display:', reviews.length);
                
                // Verify we're displaying reviews for the correct product
                if (containerProductId != reviewProductId) {
                    console.error('❌ CONTAINER MISMATCH: Container is for product', containerProductId, 'but we have reviews for product', reviewProductId);
                    console.error('❌ This should not happen with the new implementation!');
                    return; // Don't display reviews if there's a mismatch
                }
                
                if (currentPage === 1) {
                    reviewsList.empty();
                }

                if (reviews.length === 0 && currentPage === 1) {
                    reviewsList.html(`
                        <div class="no-reviews">
                            <i class="fas fa-comments"></i>
                            <p>No reviews yet. Be the first to review this product!</p>
                        </div>
                    `);
                    return;
                }

                reviews.forEach(function(review) {
                    const reviewHtml = createReviewHtml(review);
                    reviewsList.append(reviewHtml);
                });
            }

            // Create review HTML
            function createReviewHtml(review) {
                console.log('Creating HTML for review:', {
                    id: review.id,
                    product_id: review.product_id,
                    expected_product_id: reviewProductId,
                    rating: review.rating,
                    comment: review.comment.substring(0, 50) + '...'
                });
                
                const stars = generateStars(review.rating);
                const date = new Date(review.created_at).toLocaleDateString();
                
                return `
                    <div class="review-item" data-review-id="${review.id}">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-name">${review.user_name}</div>
                                <div class="review-rating">${stars}</div>
                                <div class="review-date">${date}</div>
                            </div>
                        </div>
                        <div class="review-comment">${review.comment}</div>
                    </div>
                `;
            }

            // Update rating summary
            function updateRatingSummary(averageRating, totalReviews, ratingDistribution) {
                $('#overall-rating').text(averageRating.toFixed(1));
                $('#rating-count').text(`${totalReviews} review${totalReviews !== 1 ? 's' : ''}`);
                
                // Update stars
                const stars = generateStars(Math.round(averageRating));
                $('#rating-stars').html(stars);

                // Update rating bars
                updateRatingBars(ratingDistribution, totalReviews);
            }

            // Update rating bars
            function updateRatingBars(distribution, totalReviews) {
                const barsContainer = $('#rating-bars');
                barsContainer.empty();

                for (let i = 5; i >= 1; i--) {
                    const count = distribution[i] || 0;
                    const percentage = totalReviews > 0 ? (count / totalReviews) * 100 : 0;
                    
                    barsContainer.append(`
                        <div class="rating-bar">
                            <div class="rating-bar-label">${i} star${i !== 1 ? 's' : ''}</div>
                            <div class="rating-bar-fill">
                                <div class="rating-bar-progress" style="width: ${percentage}%"></div>
                            </div>
                            <div class="rating-bar-count">${count}</div>
                        </div>
                    `);
                }
            }

            // Update pagination
            function updatePagination(pagination) {
                const loadMoreContainer = $('#load-more-container');
                if (pagination.current_page < pagination.last_page) {
                    loadMoreContainer.show();
                } else {
                    loadMoreContainer.hide();
                }
            }

            // Initialize star rating
            function initializeStarRating() {
                $('.star-label').on('click', function() {
                    const rating = $(this).data('rating');
                    updateStarDisplay(rating);
                });

                $('.star-label').on('mouseenter', function() {
                    const rating = $(this).data('rating');
                    updateStarDisplay(rating, true);
                });

                $('.rating-input').on('mouseleave', function() {
                    const selectedRating = $('input[name="rating"]:checked').val();
                    if (selectedRating) {
                        updateStarDisplay(selectedRating);
                    } else {
                        updateStarDisplay(0);
                    }
                });
            }

            // Update star display
            function updateStarDisplay(rating, isHover) {
                $('.star-label').each(function() {
                    const starRating = $(this).data('rating');
                    const starIcon = $(this).find('i');
                    
                    if (starRating <= rating) {
                        starIcon.removeClass('far').addClass('fas');
                        $(this).addClass('selected');
                    } else {
                        starIcon.removeClass('fas').addClass('far');
                        $(this).removeClass('selected');
                    }
                });

                // Update rating text
                const ratingTexts = {
                    1: 'Poor',
                    2: 'Fair',
                    3: 'Good',
                    4: 'Very Good',
                    5: 'Excellent'
                };
                $('#rating-text').text(ratingTexts[rating] || 'Select a rating');
            }

            // Submit review
            function submitReview() {
                console.log('submitReview() function called!');
                const rating = $('input[name="rating"]:checked').val();
                const comment = $('#comment').val();

                console.log('Review data:', { rating, comment });

                if (!rating) {
                    console.log('No rating selected');
                    alert('Please select a rating');
                    return;
                }

                if (!comment.trim() || comment.trim().length < 5) {
                    console.log('Comment too short or empty:', comment);
                    alert('Please write a review comment (at least 5 characters)');
                    return;
                }

                console.log('Validation passed, proceeding with submission');

                const formData = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    rating: rating,
                    comment: comment
                };
                
                console.log('Submitting review for product ID:', reviewProductId);

                $.ajax({
                    url: `/api/products/${reviewProductId}/reviews`,
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        console.log('Sending review submission...');
                    },
                    success: function(response) {
                        console.log('Success response:', response);
                        if (response.success) {
                            alert('Review submitted successfully!');
                            $('#review-form')[0].reset();
                            updateStarDisplay(0);
                            currentPage = 1;
                            loadReviews();
                        } else {
                            alert(response.message || 'Error submitting review');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Review submission failed:', {status, error, response: xhr.responseText});
                        
                        const response = xhr.responseJSON;
                        if (response && response.errors) {
                            // Show validation errors
                            let errorMessage = 'Validation failed:\n';
                            for (const field in response.errors) {
                                errorMessage += `• ${response.errors[field][0]}\n`;
                            }
                            alert(errorMessage);
                        } else {
                            alert(response ? response.message : 'Error submitting review: ' + error);
                        }
                    }
                });
            }

            // Generate stars HTML
            function generateStars(rating) {
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<i class="fa${i <= rating ? 's' : 'r'} fa-star"></i>`;
                }
                return stars;
            }

            // Show error message
            function showError(message) {
                alert(message);
            }
        });

    </script>
@endpush



