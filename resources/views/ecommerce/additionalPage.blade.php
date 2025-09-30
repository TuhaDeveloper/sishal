@extends('ecommerce.master')

@section('main-section')
    <section class="py-5">
        <div class="container">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    {!! $page->content !!}
                </div>
            </div>
        </div>
    </section>
@endsection