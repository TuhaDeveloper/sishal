@php
    use App\Models\Banner;
    
    $banners = Banner::currentlyActive()
        ->orderBy('sort_order', 'asc')
        ->get();
@endphp

@if($banners->count() > 0)
    <div class="banner-container">
        @foreach($banners as $banner)
            <div class="banner-item mb-3">
                @if($banner->image)
                    <div class="banner-image">
                        @if($banner->link_url)
                            <a href="{{ $banner->link_url }}" target="_blank" class="d-block">
                                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-fluid rounded">
                            </a>
                        @else
                            <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="img-fluid rounded">
                        @endif
                    </div>
                @endif
                
                @if($banner->title || $banner->description)
                    <div class="banner-content mt-2">
                        @if($banner->title)
                            <h5 class="banner-title">{{ $banner->title }}</h5>
                        @endif
                        @if($banner->description)
                            <p class="banner-description text-muted">{{ $banner->description }}</p>
                        @endif
                        @if($banner->link_url && $banner->link_text)
                            <a href="{{ $banner->link_url }}" target="_blank" class="btn btn-primary btn-sm">
                                {{ $banner->link_text }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif

<style>
.banner-container {
    width: 100%;
}

/* Position-specific classes removed */

.banner-item {
    position: relative;
}

.banner-image img {
    width: 100%;
    height: auto;
    transition: transform 0.3s ease;
}

.banner-image img:hover {
    transform: scale(1.02);
}

.banner-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.banner-description {
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}
</style>
