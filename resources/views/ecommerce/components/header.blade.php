@php
    use Illuminate\Support\Str;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@if($pageTitle) {{ $pageTitle . ' | '}} @endif {{ $general_settings->site_title ?? '' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @if(isset($product) && $product)
        {{-- Product-specific meta tags --}}
        <meta name="title" content="{{ $product->meta_title ?? $product->name }}">
        <meta name="description" content="{{ $product->meta_description ?? Str::limit(strip_tags($product->description ?? ''), 160) }}">
        @php
            $keywords = '';
            if ($product->meta_keywords) {
                if (is_array($product->meta_keywords)) {
                    $keywords = implode(', ', $product->meta_keywords);
                } else {
                    // It's a JSON string, decode it
                    $decoded = json_decode($product->meta_keywords, true);
                    if (is_array($decoded)) {
                        $keywords = implode(', ', $decoded);
                    } else {
                        $keywords = $product->meta_keywords;
                    }
                }
            }
        @endphp
        
        <meta name="keywords" content="{{ $keywords }}">
              
        <meta property="og:title" content="{{ $product->meta_title ?? $product->name }}">
        <meta property="og:description" content="{{ $product->meta_description ?? Str::limit(strip_tags($product->description ?? ''), 160) }}">
        <meta property="og:image" content="{{ $product->image ? asset($product->image) : asset('static/default-product.jpg') }}">
        <meta property="og:type" content="product">
        <meta property="og:url" content="{{ url()->current() }}">
        
    @else
        {{-- Default meta tags for general pages --}}
        <meta name="title" content="{{ $general_settings->site_title ?? 'Your Store' }}">
        <meta name="description" content="{{ $general_settings->site_description ?? 'Welcome to our online store. Find the best products at great prices.' }}">
        <meta name="keywords" content="{{ $general_settings->site_keywords ?? 'online store, shopping, products, deals' }}">
        
        {{-- Default Open Graph meta tags --}}
        <meta property="og:title" content="{{ $general_settings->site_title ?? 'Your Store' }}">
        <meta property="og:description" content="{{ $general_settings->site_description ?? 'Welcome to our online store. Find the best products at great prices.' }}">
        <meta property="og:image" content="{{ $general_settings->site_logo ? asset($general_settings->site_logo) : asset('static/default-site-logo.png') }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
    @endif
    <link rel="icon" href="{{ $general_settings && $general_settings->site_favicon ? asset($general_settings->site_favicon) : asset('static/default-site-icon.webp') }}" type="image/x-icon">
    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link href="{{ asset('ecommerce.css') }}?v={{ @filemtime(public_path('ecommerce.css')) }}" rel="stylesheet" />
    
    <!-- Fallback for non-JS browsers -->
    <noscript>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.css" rel="stylesheet">
    </noscript>
    
    <!-- Removed Turbo CDN to fix JavaScript functionality issues -->
    @stack('styles')
</head>

<body>


