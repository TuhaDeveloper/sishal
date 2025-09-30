@extends('erp.master')

@section('title', 'Employee List')

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
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Employee List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Employee List</h2>
                    <p class="text-muted mb-0">Manage employee information, roles, branches and status.</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('employees.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Employee
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Name" value="{{ request('name') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Phone</label>
                            <input type="text" class="form-control" name="phone" placeholder="Phone" value="{{ request('phone') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Designation</label>
                            <input type="text" class="form-control" name="designation" placeholder="Designation" value="{{ request('designation') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listing Table -->
        <div class="card border-0 shadow-sm m-2">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Employees</h5>
                    <div class="text-muted">
                        <small>Total: {{ $employees->total() }} employees</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="employeesTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="border-0">ID</th>
                                <th class="border-0">Name</th>
                                <th class="border-0">Email</th>
                                <th class="border-0 text-center">Phone</th>
                                <th class="border-0 text-center">Designation</th>
                                <th class="border-0 text-center">Balance</th>
                                <th class="border-0 text-center">Status</th>
                                <th class="border-0 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $employee)
                                <tr>
                                    <td>
                                        <h6 class="mb-0 fw-medium">#{{ $loop->iteration }}</h6>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ ($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? '') }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $employee->user->email ?? '—' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $employee->phone ?? '—' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium">{{ $employee->designation ?? '—' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium">{{ $employee->balance ? number_format($employee->balance->balance, 2) . '৳' : '—' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">{{ $employee->status ? ucfirst($employee->status) : 'Active' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center" role="group">
                                            <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-sm btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('employees.destroy', $employee->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this employee?')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-users fa-2x mb-3"></i>
                                            <p class="mb-0">No Employees found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">
                        Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} Employees
                    </span>
                    {{ $employees->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection