@extends('erp.master')

@section('title', 'Invoice Management')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Attribute List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Attribute List</h2>
                    <p class="text-muted mb-0">Manage attribute information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('attribute.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Add Attribute
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="container py-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attributes as $attribute)
                                    <tr>
                                        <td>{{ $attribute->id }}</td>
                                        <td><a href="{{ route('attribute.show', $attribute->id) }}">{{ $attribute->name }}</a></td>
                                        <td>{{ $attribute->slug }}</td>
                                        <td><span class="badge bg-{{ $attribute->status === 'active' ? 'success' : 'secondary' }}">{{ $attribute->status ?? 'inactive' }}</span></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('attribute.edit', $attribute->id) }}"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('attribute.destroy', $attribute->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No attributes found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $attributes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection