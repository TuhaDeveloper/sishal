@extends('erp.master')

@section('title', 'Branch Details')

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
                            <li class="breadcrumb-item"><a href="{{ route('branches.index') }}"
                                    class="text-decoration-none">Branches</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $branch->name }}</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">{{ $branch->name }}</h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>{{ $branch->location }}
                        @if($branch->contact_info)
                            <span class="ms-3"><i class="fas fa-phone me-2"></i>{{ $branch->contact_info }}</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        @can('edit branch')
                            <a href="{{ route('branches.edit', $branch->id) }}" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Edit Branch
                            </a>
                        @endcan
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            <!-- Manager Details Card -->
            @if($branch->manager)
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-user-tie text-primary me-2"></i>Branch Manager
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 80px; height: 80px;">
                                    <i class="fas fa-user text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4 class="fw-bold text-dark mb-1">{{ $branch->manager->first_name }}
                                    {{ $branch->manager->last_name }}</h4>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-envelope me-2"></i>{{ $branch->manager->email }}
                                </p>
                                <p class="text-muted mb-0">
                                    <i
                                        class="fas fa-phone me-2"></i>{{ $branch->manager->employee->phone ?? 'Phone not available' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-user-tie text-muted me-2"></i>Branch Manager
                        </h5>
                    </div>
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <i class="fas fa-user-slash text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                        <h5 class="text-muted mb-2">No Manager Assigned</h5>
                        <p class="text-muted mb-3">This branch doesn't have a manager assigned yet.</p>
                    </div>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="fas fa-users text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold text-primary mb-0">{{ $employees_count }}</h3>
                                    <p class="text-muted mb-0">Employees</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="fas fa-warehouse text-info fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold text-info mb-0">{{ $warehouses_count }}</h3>
                                    <p class="text-muted mb-0">Warehouses</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="fas fa-box text-warning fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold text-warning mb-0">{{ $products_count }}</h3>
                                    <p class="text-muted mb-0">Products</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="fas fa-chart-line text-success fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold text-success mb-0">৳{{ number_format($revenue, 2) }}</h3>
                                    <p class="text-muted mb-0">Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Warehouse Info -->
            <div class="card mb-5 shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-warehouse text-primary me-2"></i>Branch Warehouses
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#addWarehouseModal">
                            <i class="fas fa-plus me-1"></i>Add Warehouse
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Manager</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($branch->warehouses as $warehouse)
                                    <tr>
                                        <td><a href="{{ route('warehouses.show', $warehouse->id) }}"
                                                class="text-decoration-none text-primary">{{ $warehouse->name }}</a></td>
                                        <td>{{ $warehouse->location }}</td>
                                        <td>{{ @$warehouse->manager->first_name . ' ' . @$warehouse->manager->last_name }}</td>
                                        <td><span
                                                class="badge bg-{{ $warehouse->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($warehouse->status ?? 'active') }}</span>
                                        </td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#editWarehouseModal{{ $warehouse->id }}" title="Edit Warehouse">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Delete this warehouse?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete Warehouse">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editWarehouseModal{{ $warehouse->id }}" tabindex="-1"
                                                aria-labelledby="editWarehouseModalLabel{{ $warehouse->id }}"
                                                aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="editWarehouseModalLabel{{ $warehouse->id }}">Edit Warehouse
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('warehouses.update', $warehouse->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="warehouse_name_{{ $warehouse->id }}"
                                                                        class="form-label">Warehouse Name</label>
                                                                    <input type="text" class="form-control"
                                                                        id="warehouse_name_{{ $warehouse->id }}" name="name"
                                                                        value="{{ $warehouse->name }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="warehouse_location_{{ $warehouse->id }}"
                                                                        class="form-label">Location</label>
                                                                    <input type="text" class="form-control"
                                                                        id="warehouse_location_{{ $warehouse->id }}"
                                                                        name="location" value="{{ $warehouse->location }}"
                                                                        required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="manager_id_{{ $warehouse->id }}"
                                                                        class="form-label">Manager</label>
                                                                    <select class="form-select"
                                                                        id="manager_id_{{ $warehouse->id }}" name="manager_id"
                                                                        required>
                                                                        <option value="">Select Manager</option>
                                                                        @foreach($branch->employees as $employee)
                                                                            <option
                                                                                value="{{ $employee->user ? $employee->user->id : '' }}"
                                                                                @if($warehouse->manager_id == ($employee->user ? $employee->user->id : '')) selected @endif>
                                                                                {{ $employee->user ? $employee->user->first_name : 'N/A' }}
                                                                                {{ $employee->user ? $employee->user->last_name : 'N/A' }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update
                                                                    Warehouse</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No warehouses found for this branch.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Enhanced Tables Layout -->
            <div class="row g-4">
                <!-- Products Table -->
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-box text-primary me-2"></i>Branch Products
                                </h5>
                                <div class="d-flex gap-2">
                                    <input type="search" class="form-control form-control-sm"
                                        placeholder="Search products..." style="width: 200px;" id="productSearch">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="productsTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="border-0">Product</th>
                                            <th class="border-0">SKU</th>
                                            <th class="border-0">Sale Price</th>
                                            <th class="border-0">Purchase Price</th>
                                            <th class="border-0">Category</th>
                                            <th class="border-0">Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($branch_products as $product)
                                            <tr>
                                                <td class="border-0">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                                            <i class="fas fa-box text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $product->product->name ?? 'N/A' }}
                                                            </h6>
                                                            <small class="text-muted">ID:
                                                                #{{ $product->product->id ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="border-0">
                                                    <code
                                                        class="bg-light px-2 py-1 rounded">{{ $product->product->sku ?? 'N/A' }}</code>
                                                </td>
                                                <td class="border-0">
                                                    <span
                                                        class="fw-semibold text-success">৳{{ number_format($product->product->sale_price ?? 0, 2) }}</span>
                                                </td>
                                                <td class="border-0">
                                                    <span
                                                        class="fw-semibold">৳{{ number_format($product->product->purchase_price ?? 0, 2) }}</span>
                                                </td>
                                                <td class="border-0">
                                                    <span class="badge bg-info bg-opacity-25 text-info">
                                                        {{ $product->product->category->name ?? 'No Category' }}
                                                    </span>
                                                </td>
                                                <td class="border-0">
                                                    <span
                                                        class="badge {{ $product->quantity > 0 ? 'bg-success bg-opacity-25 text-success' : 'bg-danger bg-opacity-25 text-danger' }}">
                                                        {{ $product->quantity }} in stock
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-box text-muted mb-3"
                                                            style="font-size: 3rem; opacity: 0.3;"></i>
                                                        <h5>No products found</h5>
                                                        <p>This branch doesn't have any products assigned yet.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employees Table -->
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-users text-primary me-2"></i>Branch Employees
                                </h5>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#addEmployeeModal">
                                    <i class="fas fa-plus me-1"></i>Add
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse($employees->take(5) as $employee)
                                    <div class="list-group-item border-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 rounded-circle px-2 py-1 me-3">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-semibold">
                                                    {{ $employee->user ? $employee->user->first_name : 'N/A' }}
                                                    {{ $employee->user ? $employee->user->last_name : 'N/A' }}</h6>
                                                <small
                                                    class="text-muted d-block">{{ $employee->user ? $employee->user->email : 'No email' }}</small>
                                                <small class="text-muted">{{ $employee->phone ?? 'No phone' }}</small>
                                                @if($employee->user && $employee->user->roles && $employee->user->roles->count() > 0)
                                                    <small class="badge bg-info bg-opacity-25 text-info mt-1">
                                                        {{ $employee->user->roles->first()->name }}
                                                    </small>
                                                @endif
                                            </div>
                                            <form action="{{ route('branches.remove_employee', $employee->id) }}" method="POST"
                                                class="ms-2">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Remove from Branch"
                                                    onclick="return confirm('Remove this employee from branch?')">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="list-group-item border-0 py-4 text-center">
                                        <div class="text-muted">
                                            <i class="fas fa-users text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <h6>No employees found</h6>
                                            <p class="mb-0">This branch doesn't have any employees assigned yet.</p>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            @if($employees->count() > 5)
                                <div class="card-footer bg-light border-0 text-center py-2">
                                    <a href="#" class="text-primary text-decoration-none fw-semibold">
                                        View All {{ $employees->count() }} Employees <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="card mt-4 border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>Recent Sales
                        </h5>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" style="width: auto;" id="salesFilter">
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 3 months</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recent_sales->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="border-0">Sale ID</th>
                                        <th class="border-0">Customer</th>
                                        <th class="border-0">Date</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0">Total</th>
                                        <th class="border-0">Payment</th>
                                        <th class="border-0">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_sales as $sale)
                                        <tr>
                                            <td class="border-0">
                                                <a href="{{ route('pos.show', $sale->id) }}"
                                                    class="text-decoration-none fw-semibold">
                                                    #{{ $sale->sale_number ?? $sale->id }}
                                                </a>
                                            </td>
                                            <td class="border-0">
                                                {{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}
                                            </td>
                                            <td class="border-0">
                                                {{ $sale->created_at->format('d-m-Y H:i') }}
                                            </td>
                                            <td class="border-0">
                                                <span class="badge 
                                                            @if($sale->status == 'pending') bg-warning text-dark
                                                            @elseif($sale->status == 'approved' || $sale->status == 'paid') bg-success
                                                            @elseif($sale->status == 'cancelled') bg-danger
                                                            @else bg-secondary
                                                            @endif">
                                                    {{ ucfirst($sale->status ?? 'Pending') }}
                                                </span>
                                            </td>
                                            <td class="border-0">
                                                <span class="fw-semibold">৳{{ number_format($sale->total_amount, 2) }}</span>
                                            </td>
                                            <td class="border-0">
                                                @if($sale->invoice)
                                                    <span class="badge 
                                                                    @if($sale->invoice->status == 'paid') bg-success
                                                                    @elseif($sale->invoice->status == 'unpaid') bg-danger
                                                                    @else bg-warning text-dark
                                                                    @endif">
                                                        {{ ucfirst($sale->invoice->status) }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">No Invoice</span>
                                                @endif
                                            </td>
                                            <td class="border-0">
                                                <a href="{{ route('pos.show', $sale->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-chart-line text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                            </div>
                            <h5 class="text-muted mb-2">No sales data available</h5>
                            <p class="text-muted mb-4">Start making sales to see your transaction history here.</p>
                            <a href="{{ route('pos.add') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create New Sale
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">Add Employee to Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addEmployeeForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <label for="employee_id" class="form-label">Select Employee</label>
                        <select class="form-select" id="employee_id" name="employee_id" style="width:100%"></select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Warehouse Modal -->
    <div class="modal fade" id="addWarehouseModal" tabindex="-1" aria-labelledby="addWarehouseModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addWarehouseModalLabel">Add Warehouse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('branches.warehouses.store', $branch->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="warehouse_name" class="form-label">Warehouse Name</label>
                            <input type="text" class="form-control" id="warehouse_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="warehouse_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="warehouse_location" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label for="manager_id" class="form-label">Manager</label>
                            <select class="form-select" id="manager_id" name="manager_id" required>
                                <option value="">Select Manager</option>
                                @foreach($branch->employees as $employee)
                                    <option value="{{ $employee->user ? $employee->user->id : '' }}">
                                        {{ $employee->user ? $employee->user->first_name : 'N/A' }}
                                        {{ $employee->user ? $employee->user->last_name : 'N/A' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Warehouse</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function () {
                var branchId = {{ $branch->id }};

                // Employee Select2
                $('#employee_id').select2({
                    dropdownParent: $('#addEmployeeModal'),
                    placeholder: 'Search for an employee',
                    ajax: {
                        url: `/erp/branches/${branchId}/non-branch-employees`,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                search: params.term || ''
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(function (emp) {
                                    return { id: emp.id, text: emp.user_id ? (emp.user ? emp.user.first_name + ' ' + emp.user.last_name : 'User#' + emp.user_id) : 'Employee#' + emp.id };
                                })
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                });

                // Add Employee Form
                $('#addEmployeeForm').on('submit', function (e) {
                    e.preventDefault();
                    var empId = $('#employee_id').val();
                    if (!empId) return;
                    var actionUrl = `/erp/branches/${branchId}/add-employee/${empId}`;
                    $.ajax({
                        url: actionUrl,
                        type: 'POST',
                        data: {
                            _token: $('input[name="_token"]').val()
                        },
                        success: function () {
                            location.reload();
                        },
                        error: function (xhr) {
                            alert('Failed to add employee.');
                        }
                    });
                });

                // Product Search
                $('#productSearch').on('keyup', function () {
                    var value = $(this).val().toLowerCase();
                    $('#productsTable tbody tr').filter(function () {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });

                // Sales Filter
                $('#salesFilter').on('change', function () {
                    var days = $(this).val();
                    // You can implement AJAX call here to filter sales by date range
                    console.log('Filtering sales for last ' + days + ' days');
                });
            });
        </script>
    @endpush

@endsection