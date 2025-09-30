@extends('erp.master')

@section('title', 'Customer Service Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Customer Service List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Customer Service List</h2>
                    <p class="text-muted mb-0">Manage customer service information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('customerService.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Add Customer Service
                        </a>
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="container-fluid px-4 py-4">
            <div class="mb-3">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search (service #, Customer, Salesman)</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="service #, Customer, Salesman">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="issue_date" class="form-control" value="{{ $filters['issue_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ $filters['due_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" id="customerSearchSelect">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                        <a href="{{ route('customerService.list') }}" class="btn btn-outline-danger">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Service List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="border-0">Service #</th>
                                    <th class="border-0">Customer</th>
                                    <th class="border-0">Technician</th>
                                    <th class="border-0">Issue Date</th>
                                    <th class="border-0">Address</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($services as $service)
                                    <tr>
                                        <td><a href="{{ route('customerService.show',$service->id) }}" class="btn btn-outline-primary">#{{ $service->service_number }}</a></td>
                                        <td>{{ optional($service->customer)->name ?? 'Walk-in-Customer' }}</td>
                                        <td>{{  @$service->technician->user->first_name }} {{  @$service->technician->user->last_name }}</td>
                                        <td>{{ $service->requested_date ? \Carbon\Carbon::parse($service->requested_date)->format('d M Y, h:i A') : '' }}</td>
                                        <td>{{ $service->address }}</td>
                                        <td>
                                            <span class="badge bg-secondary status-badge" 
                                                  data-id="{{ $service->id }}" 
                                                  data-status="{{ $service->status }}"
                                                  style="cursor:pointer;">
                                                {{ ucfirst($service->status) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($service->total, 2) }} ৳</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No services found for the given criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $services->firstItem() }} to {{ $services->lastItem() }} of {{ $services->total() }} services
                        </span>
                        {{ $services->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script>
$(document).ready(function() {
    $('#customerSearchSelect').select2({
        placeholder: 'Search and select customer...',
        allowClear: true,
        ajax: {
            url: '/erp/customers/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: data.map(function (customer) {
                        return {
                            id: customer.id,
                            text: customer.name + (customer.email ? ' (' + customer.email + ')' : '')
                        };
                    })
                };
            },
            cache: true
        },
        width: 'resolve',
    });
});
</script>
@endpush