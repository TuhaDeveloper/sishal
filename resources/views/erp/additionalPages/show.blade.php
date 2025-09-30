@extends('erp.master')

@section('title','Additional Page Details')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-0">{{ $page->title }}</h2>
                    <p class="text-muted mb-0">Slug: {{ $page->slug }}</p>
                </div>
            </div>
        </div>
        <div class="container-fluid px-4 py-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    {!! nl2br(e($page->content)) !!}
                </div>
            </div>
        </div>
    </div>
@endsection

