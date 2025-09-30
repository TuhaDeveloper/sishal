@extends('erp.master')

@section('title', 'Vlog List')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Vlog List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Vlog List</h2>
                    <p class="text-muted mb-0">Manage vlog information, roles, branches and status.</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVlogModal">
                        <i class="fas fa-plus me-2"></i>Add Vlog
                    </button>
                </div>
            </div>
        </div>
        <div class="container-fluid px-4 py-3">
            <div class="row g-3">
                @forelse($vlogs as $vlog)
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card shadow-sm h-100">
                        <div class="ratio ratio-16x9">
                            {!! $vlog->frame_code !!}
                        </div>
                        <div class="card-body py-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge bg-{{ $vlog->is_active ? 'success' : 'secondary' }}">{{ $vlog->is_active ? 'Active' : 'Inactive' }}</span>
                                @if($vlog->is_featured)
                                    <span class="badge bg-info">Featured</span>
                                @endif
                            </div>
                            <div class="btn-group gap-2">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editVlogModal-{{ $vlog->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('vlogging.destroy',$vlog) }}" onsubmit="return confirm('Delete this vlog?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Vlog Modal -->
                <div class="modal fade" id="editVlogModal-{{ $vlog->id }}" tabindex="-1" aria-labelledby="editVlogModalLabel-{{ $vlog->id }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editVlogModalLabel-{{ $vlog->id }}">Edit Vlog</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="{{ route('vlogging.update',$vlog) }}">
                                @csrf
                                @method('PATCH')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="frame_code_{{ $vlog->id }}" class="form-label">YouTube iframe code</label>
                                        <textarea class="form-control" id="frame_code_{{ $vlog->id }}" name="frame_code" rows="4" required>{{ old('frame_code', $vlog->frame_code) }}</textarea>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_active_{{ $vlog->id }}" name="is_active" {{ $vlog->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active_{{ $vlog->id }}">Active</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_featured_{{ $vlog->id }}" name="is_featured" {{ $vlog->is_featured ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_featured_{{ $vlog->id }}">Featured</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center text-muted">No vlogs yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <!-- Add Vlog Modal -->
    <div class="modal fade" id="addVlogModal" tabindex="-1" aria-labelledby="addVlogModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVlogModalLabel">Add Vlog</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('vlogging.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="frame_code" class="form-label">YouTube iframe code</label>
                            <textarea class="form-control" id="frame_code" name="frame_code" rows="4" placeholder="Paste the full <iframe ...></iframe> code" required></textarea>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                            <label class="form-check-label" for="is_featured">Featured</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection