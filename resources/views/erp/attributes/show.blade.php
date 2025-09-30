@extends('erp.master')

@section('title', 'Attribute Details')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')

        <div class="container py-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Attribute #{{ $attribute->id }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Name</dt>
                        <dd class="col-sm-9">{{ $attribute->name }}</dd>
                        <dt class="col-sm-3">Slug</dt>
                        <dd class="col-sm-9">{{ $attribute->slug }}</dd>
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9"><span class="badge bg-{{ $attribute->status === 'active' ? 'success' : 'secondary' }}">{{ $attribute->status ?? 'inactive' }}</span></dd>
                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">{!! nl2br(e($attribute->description)) !!}</dd>
                    </dl>
                    <div class="mt-3">
                        <a href="{{ route('attribute.edit', $attribute->id) }}" class="btn btn-primary">Edit</a>
                        <a href="{{ route('attribute.list') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


