@extends('ecommerce.master')

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

        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 81, 44, 0.4);
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

        /* Reviews */
        .reviews-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 20px;
        }

        .reviews-left-column,
        .reviews-right-column {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .reviews-summary {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
        }

        .reviews-summary h3 {
            margin-bottom: 16px;
            color: #1a202c;
            font-size: 22px;
            font-weight: 700;
        }

        .rating-breakdown {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .overall-rating {
            text-align: center;
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .rating-number {
            font-size: 56px;
            font-weight: 800;
            color: #00512C;
            text-shadow: 0 2px 4px rgba(0, 81, 44, 0.1);
        }

        .rating-stars {
            color: #fbbf24;
            font-size: 24px;
            margin: 8px 0;
        }

        .rating-count {
            color: #64748b;
            font-size: 16px;
            font-weight: 500;
        }

        .rating-bars {
            flex: 1;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .bar-fill {
            flex: 1;
            height: 12px;
            background: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }

        .bar-fill-inner {
            height: 100%;
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
            transition: width 0.6s ease;
            border-radius: 6px;
        }

        .review-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 0;
            background: white;
            margin-bottom: 12px;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .review-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .reviewer-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .reviewer-name {
            font-weight: 600;
            color: #1a202c;
            font-size: 16px;
        }

        .review-stars {
            color: #fbbf24;
            font-size: 16px;
        }

        .review-date {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .review-text {
            color: #374151;
            line-height: 1.6;
            font-size: 15px;
        }

        /* Review Form Styles */
        .review-form-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .review-form-header {
            margin-bottom: 20px;
            text-align: center;
        }

        .review-form-title {
            color: #1a202c;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .review-form-subtitle {
            color: #64748b;
            font-size: 16px;
        }

        .review-form {
            background: transparent;
            padding: 0;
            border-radius: 0;
            margin-bottom: 0;
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

        .form-label.required::after {
            content: " *";
            color: #ef4444;
        }

        .rating-input-container {
            display: flex;
            justify-content: center;
            margin: 16px 0;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            gap: 8px;
            justify-content: center;
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

        .star-label.selected {
            color: #fbbf24;
        }

        .star-label.hovered {
            color: #fbbf24;
        }

        .review-textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
        }

        .review-textarea:focus {
            border-color: #00512C;
            box-shadow: 0 0 0 3px rgba(0, 81, 44, 0.1);
            outline: none;
        }

        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 24px;
        }

        .btn-submit-review {
            background: linear-gradient(135deg, #00512C 0%, #10B981 100%);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 81, 44, 0.3);
        }

        .btn-submit-review:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 81, 44, 0.4);
        }

        .btn-cancel-review {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel-review:hover {
            border-color: #00512C;
            color: #00512C;
            background: #f8fafc;
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

        .review-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: bold;
            color: #333;
        }

        .review-date {
            color: #666;
            font-size: 14px;
        }

        .rating-bars {
            margin-top: 15px;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 10px;
        }

        .rating-bar span:first-child {
            min-width: 30px;
            font-size: 14px;
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

        .rating-bar span:last-child {
            min-width: 35px;
            text-align: right;
            font-size: 12px;
            color: #666;
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
            padding: 20px 0 5px 0;
            overflow: visible;
            height: auto !important;
        }

        .related-swiper .swiper-slide {
            width: auto;
            height: auto;
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
            .reviews-container {
                grid-template-columns: 1fr;
                gap: 20px;
                margin-bottom: 15px;
            }
            
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


            .reviews-left-column,
            .reviews-right-column {
                padding: 16px;
            }

            .review-form-card {
                padding: 16px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit-review,
            .btn-cancel-review {
                width: 100%;
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

        /* Review Image Styles */
        .review-image-container {
            margin-top: 12px;
        }

        .review-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .review-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

                    <div class="product-rating" id="product-rating">
                        <div class="stars" id="rating-stars">★★★★☆</div>
                        <span class="rating-text" id="rating-text">(0 reviews)</span>
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
                <div class="reviews-header">
                    <h3 class="reviews-title">
                        <i class="fas fa-star text-warning me-2"></i>
                        Customer Reviews
                    </h3>
                    <p class="reviews-subtitle">Share your experience and read what others think</p>
                </div>
                
                <!-- Two Column Layout for Reviews -->
                <div class="reviews-container">
                    <!-- Left Column: Review Form -->
                    <div class="reviews-left-column">
                        @auth
                        <div class="review-form-card">
                            <div class="review-form-header">
                                <h4 class="review-form-title">
                                    <i class="fas fa-edit me-2"></i>
                                    Write a Review
                                </h4>
                                <p class="review-form-subtitle">Help others by sharing your experience</p>
                            </div>
                            <form id="review-form" class="review-form" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                
                                <div class="form-group">
                                    <label class="form-label required">Rating *</label>
                                    <div class="rating-input-container">
                                        <div class="rating-input">
                                            <input type="radio" name="rating" value="5" id="star5">
                                            <label for="star5" class="star-label" data-rating="5">
                                                <i class="fas fa-star"></i>
                                            </label>
                                            <input type="radio" name="rating" value="4" id="star4">
                                            <label for="star4" class="star-label" data-rating="4">
                                                <i class="fas fa-star"></i>
                                            </label>
                                            <input type="radio" name="rating" value="3" id="star3">
                                            <label for="star3" class="star-label" data-rating="3">
                                                <i class="fas fa-star"></i>
                                            </label>
                                            <input type="radio" name="rating" value="2" id="star2">
                                            <label for="star2" class="star-label" data-rating="2">
                                                <i class="fas fa-star"></i>
                                            </label>
                                            <input type="radio" name="rating" value="1" id="star1">
                                            <label for="star1" class="star-label" data-rating="1">
                                                <i class="fas fa-star"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comment" class="form-label">Your Review *</label>
                                    <textarea class="form-control review-textarea" name="comment" id="comment" rows="4" 
                                              placeholder="Tell us about your experience with this product"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="image" class="form-label">Add Photo (Optional)</label>
                                    <div class="image-upload-container">
                                        <input type="file" class="form-control" name="image" id="image" accept="image/*" style="display: none;">
                                        <div class="image-upload-area" onclick="document.getElementById('image').click()">
                                            <div class="upload-content">
                                                <i class="fas fa-camera fa-2x text-muted mb-2"></i>
                                                <p class="upload-text">Click to add a photo</p>
                                                <small class="text-muted">JPG, PNG, GIF, WebP (Max 2MB)</small>
                                            </div>
                                        </div>
                                        <div class="image-preview" id="image-preview" style="display: none;">
                                            <img id="preview-img" src="" alt="Preview" class="preview-image">
                                            <button type="button" class="remove-image-btn" onclick="removeImage()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-submit-review">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Submit Review
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-cancel-review" style="display: none;">
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

                    <!-- Right Column: Reviews Summary -->
                    <div class="reviews-right-column">
                        <div class="reviews-summary-card" id="reviews-summary">
                            <div class="rating-breakdown">
                                <div class="overall-rating-section">
                                    <div class="rating-display">
                                        <div class="rating-number" id="overall-rating">0.0</div>
                                        <div class="rating-stars" id="overall-stars">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="rating-count" id="rating-count">0 reviews</div>
                                    </div>
                                </div>
                                
                                <div class="rating-distribution">
                                    <h6 class="distribution-title">Rating Distribution</h6>
                                    <div class="rating-bars" id="rating-bars">
                                        <!-- Dynamic rating breakdown will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Reviews Section -->
                        <div class="customer-reviews-section">
                            <div class="customer-reviews-header">
                                <h4 class="customer-reviews-title">Customer Reviews</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="reviews-list" id="reviews-list">
                    <!-- Dynamic reviews will be loaded here -->
                </div>

        <!-- Load More Button -->
        <div class="text-center mt-3" id="load-more-container" style="display: none;">
            <button class="btn btn-outline-primary" id="load-more-reviews">Load More Reviews</button>
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
                                        @if($product->discount > 0)
                                            <div class="related-product-badge">On Sale</div>
                                        @endif
                                        
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
                                                <span class="related-product-rating-text">
                                                    ({{ $product->totalReviews() }})
                                                </span>
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
                                                
                                                <button class="related-product-wishlist" 
                                                        onclick="toggleWishlist({{ $product->id }})"
                                                        data-product-id="{{ $product->id }}">
                                                    <i class="fas fa-heart"></i>
                                                </button>
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

        // Enhanced Related products slider
        if (document.querySelector('.related-swiper') && typeof Swiper !== 'undefined') {
            new Swiper('.related-swiper', {
                slidesPerView: 4,
                spaceBetween: 20,
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
                        spaceBetween: 15
                    },
                    480: {
                        slidesPerView: 1.5,
                        spaceBetween: 15
                    },
                    640: { 
                        slidesPerView: 2.2,
                        spaceBetween: 20
                    },
                    768: { 
                        slidesPerView: 2.5,
                        spaceBetween: 20
                    },
                    1024: { 
                        slidesPerView: 3.2,
                        spaceBetween: 25
                    },
                    1200: { 
                        slidesPerView: 4,
                        spaceBetween: 30
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

        // Review functionality - wrapped in IIFE to prevent conflicts
        (function() {
            var currentPage = 1;
            var isLoading = false;

            // Initialize page
            $(document).ready(function() {
                // Clear any existing reviews first
                $('#reviews-list').empty();
                $('#overall-rating').text('0.0');
                $('#rating-count').text('0 reviews');
                $('#overall-stars').text('☆☆☆☆☆');
                $('#rating-stars').text('☆☆☆☆☆');
                $('#rating-text').text('(0 reviews)');
                
                // Load reviews immediately when page loads
                loadReviews();
            });

            // Review form submission
            $('#review-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                var rating = $('input[name="rating"]:checked').val();
                
                if (!rating) {
                    alert('Please select a rating');
                    return;
                }

                $.ajax({
                    url: '{{ route("reviews.store") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            $('#review-form')[0].reset();
                            // Always reload reviews after submission
                            loadReviews();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;
                        alert(response.message || 'Error submitting review');
                    }
                });
            });

            // Load reviews function
            function loadReviews(page) {
                page = page || 1;
                if (isLoading) return;
                isLoading = true;

                var productId = {{ $product->id }};
                var productName = '{{ $product->name }}';
                var apiUrl = '{{ route("reviews.product", $product->id) }}';
                
                console.log('=== REVIEW DEBUG ===');
                console.log('Product ID:', productId);
                console.log('Product Name:', productName);
                console.log('API URL:', apiUrl);
                console.log('==================');

                $.ajax({
                    url: apiUrl,
                    type: 'GET',
                    data: { 
                        page: page,
                        _t: new Date().getTime() // Cache busting
                    },
                    cache: false, // Disable caching
                    success: function(response) {
                        console.log('API Response for', productName, ':', response);
                        
                        // Verify the response is for the correct product
                        if (response.product_id && response.product_id != productId) {
                            console.error('CRITICAL ERROR: API returned data for different product!');
                            console.error('Expected product ID:', productId, 'Got:', response.product_id);
                            return;
                        }
                        
                        if (response.success) {
                            console.log('Reviews found:', response.reviews.data.length);
                            console.log('Average rating:', response.average_rating);
                            console.log('Total reviews:', response.total_reviews);
                            
                            // Only update if we have reviews for this specific product
                            if (response.reviews.data.length > 0) {
                                // Double-check all reviews belong to this product
                                var validReviews = response.reviews.data.filter(function(review) {
                                    return review.product_id == productId;
                                });
                                
                                if (validReviews.length != response.reviews.data.length) {
                                    console.error('ERROR: Some reviews belong to different products!');
                                    console.error('Valid reviews:', validReviews.length, 'Total reviews:', response.reviews.data.length);
                                }
                                
                                updateRatingDisplay(response.average_rating, response.total_reviews);
                                updateReviewsList(validReviews, page === 1);
                                updateRatingBreakdown(validReviews);
                            } else {
                                // No reviews - show default state
                                updateRatingDisplay(0, 0);
                                updateReviewsList([], page === 1);
                                updateRatingBreakdown([]);
                            }
                            
                            // Show/hide load more button
                            if (response.reviews.next_page_url) {
                                $('#load-more-container').show();
                                currentPage = page + 1;
                            } else {
                                $('#load-more-container').hide();
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading reviews for', productName, ':', xhr.responseText);
                    },
                    complete: function() {
                        isLoading = false;
                    }
                });
            }

            // Update rating display
            function updateRatingDisplay(averageRating, totalReviews) {
                // Handle null/undefined values and ensure they are numbers
                var rating = parseFloat(averageRating) || 0;
                var reviews = parseInt(totalReviews) || 0;
                
                $('#overall-rating').text(rating.toFixed(1));
                $('#rating-count').text(reviews + ' reviews');
                
                // Update stars in product info
                var stars = '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));
                $('#overall-stars').text(stars);
                $('#rating-stars').text(stars);
                $('#rating-text').text('(' + reviews + ' reviews)');
            }

            // Update reviews list
            function updateReviewsList(reviews, isFirstLoad) {
                if (isFirstLoad) {
                    $('#reviews-list').empty();
                }

                if (reviews && reviews.length > 0) {
                    console.log('Displaying reviews for product ID:', {{ $product->id }});
                    reviews.forEach(function(review) {
                        console.log('Review belongs to product ID:', review.product_id, 'Current product ID:', {{ $product->id }});
                        
                        // Verify this review belongs to the current product
                        if (review.product_id != {{ $product->id }}) {
                            console.error('ERROR: Review belongs to different product! Review product_id:', review.product_id, 'Current product_id:', {{ $product->id }});
                            return; // Skip this review
                        }
                        
                        var reviewHtml = '<div class="review-item">' +
                            '<div class="review-header">' +
                                '<div class="reviewer-info">' +
                                    '<div class="reviewer-name">' + review.user.first_name + ' ' + review.user.last_name + '</div>' +
                                    '<div class="review-stars">' + 
                                        '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating) +
                                    '</div>' +
                                '</div>' +
                                '<div class="review-date">' + new Date(review.created_at).toLocaleDateString() + '</div>' +
                            '</div>' +
                            '<div class="review-text">' + (review.comment || 'No comment provided') + '</div>' +
                            (review.image ? '<div class="review-image-container"><img src="/storage/' + review.image + '" alt="Review image" class="review-image" onclick="openImageModal(this.src)"></div>' : '') +
                        '</div>';
                        $('#reviews-list').append(reviewHtml);
                    });
                } else {
                    $('#reviews-list').html('<div class="text-center text-muted p-4"><i class="fas fa-comment-slash fa-2x mb-2"></i><br>No reviews yet. Be the first to review this product!</div>');
                }
            }

            // Update rating breakdown
            function updateRatingBreakdown(reviews) {
                var ratingCounts = {5: 0, 4: 0, 3: 0, 2: 0, 1: 0};
                
                // Handle null/undefined reviews array
                if (reviews && Array.isArray(reviews)) {
                    reviews.forEach(function(review) {
                        if (review && review.rating) {
                            ratingCounts[review.rating]++;
                        }
                    });
                }

                var totalReviews = reviews ? reviews.length : 0;
                var breakdownHtml = '';

                for (var i = 5; i >= 1; i--) {
                    var percentage = totalReviews > 0 ? Math.round((ratingCounts[i] / totalReviews) * 100) : 0;
                    breakdownHtml += '<div class="rating-bar">' +
                        '<span>' + i + '★</span>' +
                        '<div class="bar-fill">' +
                            '<div class="bar-fill-inner" style="width: ' + percentage + '%"></div>' +
                        '</div>' +
                        '<span>' + percentage + '%</span>' +
                    '</div>';
                }

                $('#rating-bars').html(breakdownHtml);
            }

            // Load more reviews
            $('#load-more-reviews').on('click', function() {
                loadReviews(currentPage);
            });

            // Enhanced Rating System
            $('.star-label').on('mouseenter', function() {
                var rating = $(this).data('rating');
                var feedback = getRatingFeedback(rating);
                $('#rating-feedback').text(feedback).addClass('show');
                
                // Highlight stars up to hovered rating
                $('.star-label').each(function() {
                    if ($(this).data('rating') <= rating) {
                        $(this).addClass('hovered');
                    } else {
                        $(this).removeClass('hovered');
                    }
                });
            });

            $('.rating-input').on('mouseleave', function() {
                $('#rating-feedback').removeClass('show');
                $('.star-label').removeClass('hovered');
                
                // Show feedback for selected rating if any
                var selectedRating = $('input[name="rating"]:checked').val();
                if (selectedRating) {
                    var feedback = getRatingFeedback(selectedRating);
                    $('#rating-feedback').text(feedback).addClass('show');
                }
            });

            $('.star-label').on('click', function() {
                var rating = $(this).data('rating');
                var feedback = getRatingFeedback(rating);
                $('#rating-feedback').text(feedback).addClass('show');
                
                // Update all stars based on selected rating
                $('.star-label').each(function() {
                    if ($(this).data('rating') <= rating) {
                        $(this).addClass('selected');
                    } else {
                        $(this).removeClass('selected');
                    }
                });
            });

            function getRatingFeedback(rating) {
                var feedbacks = {
                    5: "Excellent! This product exceeded my expectations! ⭐⭐⭐⭐⭐",
                    4: "Good product, I'm satisfied with my purchase ⭐⭐⭐⭐",
                    3: "Average product, it's okay but nothing special ⭐⭐⭐",
                    2: "Poor quality, I wouldn't recommend this ⭐⭐",
                    1: "Terrible experience, very disappointed ⭐"
                };
                return feedbacks[rating] || '';
            }

            // Form validation enhancement
            $('#comment').on('input', function() {
                var length = $(this).val().length;
                var minLength = 10;
                
                if (length < minLength) {
                    $(this).addClass('is-invalid');
                    $('.form-text').text(`Minimum ${minLength} characters required (${length}/${minLength})`).addClass('text-danger');
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                    $('.form-text').text(`${length} characters`).removeClass('text-danger').addClass('text-success');
                }
            });

            // Tab functionality
            $('.tab-btn').on('click', function() {
                var targetTab = $(this).data('tab');
                
                // Remove active class from all tabs and contents
                $('.tab-btn').removeClass('active');
                $('.tab-content').removeClass('active').hide();
                
                // Add active class to clicked tab
                $(this).addClass('active');
                
                // Show target content
                $('#' + targetTab).addClass('active').show();
                
                // Reviews are already loaded on page load, no need to reload
            });
        })();
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

        // Add event listener for image input
        document.getElementById('image').addEventListener('change', function() {
            previewImage(this);
        });

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
    </script>
@endpush