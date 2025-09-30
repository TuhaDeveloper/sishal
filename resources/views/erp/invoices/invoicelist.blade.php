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
                            <li class="breadcrumb-item active" aria-current="page">Invoice List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Invoice List</h2>
                    <p class="text-muted mb-0">Manage invoice information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('invoice.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Add Invoice
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
                        <label class="form-label">Search (Invoice #, Customer, Salesman)</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Invoice #, Customer, Salesman">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ ($filters['status'] ?? '') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
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
                        <select name="customer_id" class="form-select">
                            <option value="">All</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ ($filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                        <a href="{{ route('invoice.list') }}" class="btn btn-outline-danger">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Invoice List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="border-0">Invoice #</th>
                                    <th class="border-0">Customer</th>
                                    <th class="border-0">Salesman</th>
                                    <th class="border-0">Issue Date</th>
                                    <th class="border-0">Due Date</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Total</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    <tr>
                                        <td><a href="{{ route('invoice.show',$invoice->id) }}" class="btn btn-outline-primary">#{{ $invoice->invoice_number }}</a></td>
                                        <td>{{ optional($invoice->customer)->name ?? 'Walk-in-Customer' }}</td>
                                        <td>{{ optional($invoice->salesman) ? $invoice->salesman->first_name . ' ' . $invoice->salesman->last_name : '' }}</td>
                                        <td>{{ $invoice->issue_date }}</td>
                                        <td>{{ $invoice->due_date }}</td>
                                        <td>
                                            <span class="badge bg-secondary status-badge" 
                                                  data-id="{{ $invoice->id }}" 
                                                  data-status="{{ $invoice->status }}"
                                                  style="cursor:pointer;">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($invoice->total_amount, 2) }} ৳</td>
                                        <td>
                                            {{-- <a href="{{ route('invoice.show', $invoice->id) }}" class="btn btn-info btn-sm">View</a> --}}
                                            <a href="{{ route('invoice.edit', $invoice->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                            
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No invoices found for the given criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                        </span>
                        {{ $invoices->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection