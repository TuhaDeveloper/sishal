@extends('erp.master')

@section('title', 'Employee Details - ' . ($fullName ?: 'Employee'))

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-gray-50 min-vh-100" id="mainContent">
        @include('erp.components.header')

        <!-- Page Header -->
        <div class="container-fluid px-4 py-4 border-bottom bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2 text-dark fw-semibold">Employee Profile</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none text-muted">Employees</a></li>
                            <li class="breadcrumb-item active">{{ $fullName }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                    <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Employee
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container-fluid px-4 py-4">
            <div class="row g-4">
                <!-- Left: Overview -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start mb-4">
                                <div class="bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                    <i class="fas fa-user-tie fa-lg text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h2 class="h4 mb-1 text-dark fw-semibold">{{ $fullName }}</h2>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @if($employee->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                        @if($primaryRole)
                                            <span class="badge bg-light text-primary border border-primary">{{ $primaryRole }}</span>
                                        @endif
                                        @if($employee->position)
                                            <span class="badge bg-light text-dark border">{{ $employee->position }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-medium">Email Address</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-envelope text-muted me-2"></i>
                                            <span>{{ $employee->user->email ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-medium">Phone Number</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-phone text-muted me-2"></i>
                                            <span>{{ $employee->phone ?? '-' }}</span>
                                        </div>
                                    </div>
                                    {{-- Branch and Balance sections hidden for ecommerce only business
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-medium">Branch</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-code-branch text-muted me-2"></i>
                                            <span>{{ $employee->branch->name ?? 'Not assigned' }}</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-medium">Balance</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-wallet text-muted me-2"></i>
                                            <span class="fw-semibold">{{ number_format($employeeBalance, 2) }}৳</span>
                                        </div>
                                    </div>
                                    --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Quick Stats -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pb-2">
                            <h6 class="mb-0 fw-semibold text-dark">Employment Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                        <div class="h6 mb-1 text-primary">{{ $employee->designation ?? '—' }}</div>
                                        <small class="text-muted">Position</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                        <div class="h6 mb-1 text-success">{{ $employee->status ? ucfirst($employee->status) : '—' }}</div>
                                        <small class="text-muted">Status</small>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                        <div class="h6 mb-1 text-info">{{ number_format($salesCount) }}</div>
                                        <small class="text-muted">Number of Sales</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .main-content { background-color: #f8f9fa; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); }
        .card:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); transition: box-shadow .15s ease-in-out; }
        .badge { font-size: .75rem; font-weight: 500; }
        .form-label { font-weight: 500; margin-bottom: .25rem; }
    </style>
@endsection


