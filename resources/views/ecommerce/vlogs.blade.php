@extends('ecommerce.master')

@section('main-section')
    <section class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="section-title text-start mb-2">Vlogs</h1>
                    <p class="section-subtitle text-start mb-0">Watch our latest installs, tips and product highlights</p>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0">
                    <form method="GET" class="d-flex justify-content-lg-end">
                        <select name="sort" class="form-select w-auto" onchange="this.form.submit()">
                            <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Latest</option>
                            <option value="featured" {{ $sort === 'featured' ? 'selected' : '' }}>Featured</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4 mb-4">
                @forelse($vlogs as $vlog)
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="ratio ratio-16x9">
                                {!! $vlog->frame_code !!}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted">No vlogs found.</div>
                @endforelse
            </div>

            @if($vlogs->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $vlogs->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </section>
@endsection

