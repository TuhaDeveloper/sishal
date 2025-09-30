@extends('ecommerce.master')

@section('title', $pageTitle)

@section('main-section')
<!-- Categories Grid Only -->
<section class="categories-section py-4">
    <div class="container">
        <h2 class="section-title mb-4">All Categories</h2>
        <div class="row g-4">
            @forelse($categories as $category)
            <div class="col-6 col-md-4 col-lg-3 col-xxl-2">
                <a href="{{ route('product.archive') }}?category={{ $category->slug }}" class="d-block text-decoration-none category-tile">
                    <div class="tile-card">
                        <div class="tile-img">
                            @if($category->image)
                                <img src="{{ asset($category->image) }}" alt="{{ $category->name }}">
                            @else
                                <div class="placeholder-image">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="60" height="60" fill="currentColor"><path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="tile-title">{{ $category->name }}</div>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12 text-center">
                <div class="no-categories">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="80" height="80" fill="currentColor"><path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2"/></svg>
                    <h3>No Categories Available</h3>
                    <p>Check back later for new categories!</p>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</section>
@endsection

@push('styles')
    <style>
        .category-tile .tile-card{background:#fff;border:1px solid #eef0f4;border-radius:16px;padding:22px;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:transform .2s,box-shadow .2s}
        .category-tile .tile-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(31,41,55,.08)}
        .category-tile .tile-img{width:100%;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;overflow:hidden}
        .category-tile .tile-img img{max-width:100%;max-height:100%;object-fit:contain}
        .category-tile .tile-title{margin-top:12px;color:#111827;font-weight:600;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .section-title{font-size:28px;font-weight:700}
        @media (max-width:576px){.section-title{font-size:22px}}
    </style>
@endpush
