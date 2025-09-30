@extends('erp.master')

@section('title', 'Additional Page Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Additional Page List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Additional Page List</h2>
                    <p class="text-muted mb-0">Manage additional page information, locations, and staff efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('additionalPages.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Add Additional Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid px-4 py-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Slug</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($additionalPages as $page)
                                <tr>
                                    <td>{{ $page->title }}</td>
                                    <td>{{ $page->slug }}</td>
                                    <td>{{ $page->positioned_at ?: '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $page->is_active ? 'success' : 'secondary' }}">{{ $page->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('additionalPages.edit',$page->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form action="{{ route('additionalPages.destroy',$page->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this page?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No pages found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection