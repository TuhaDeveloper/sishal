@extends('erp.master')

@section('title', 'Order Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Order List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Order List</h2>
                    <p class="text-muted mb-0">Manage order information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            <div class="mb-3">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search (ID, Name, Phone, Email)</label>
                        <input type="text" name="search" class="form-control" placeholder="Order ID or Customer's Name, Phone, Email" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="shipping">Shipping</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estimated Date</label>
                        <input type="date" name="estimated_delivery_date" class="form-control" value="{{ request('estimated_delivery_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bill Status</label>
                        <select name="bill_status" class="form-select">
                            <option value="">All</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('order.list') }}" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Stock order Listing Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Order List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="orderTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Order ID</th>
                                    <th class="border-0">Estimated Date</th>
                                    <th class="border-0">Customer</th>
                                    <th class="border-0">Phone</th>
                                    <th class="border-0 text-center">Status</th>
                                    <th>Bill Status</th>
                                    <th class="border-0">Subtotal</th>
                                    <th class="border-0">Discount</th>
                                    <th class="border-0">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td><a href="{{ route('order.show',$order->id) }}" class="btn btn-outline-primary">{{ $order->order_number ?? '-' }}</a></td>
                                        <td>
                                            {{ $order->estimated_delivery_date ? \Carbon\Carbon::parse($order->estimated_delivery_date)->format('d-m-Y') : '-' }}
                                        </td>
                                        <td>{{@$order->name}}</td>
                                        <td>{{@$order->phone}}</td>
                                        <td class="text-center">
                                            <span class="badge 
                                                @if($order->status == 'pending') bg-warning text-dark
                                                @elseif($order->status == 'approved' || $order->status == 'paid') bg-success
                                                @elseif($order->status == 'unpaid' || $order->status == 'rejected') bg-danger
                                                @else bg-secondary
                                                @endif
                                                update-status-btn"
                                                style="cursor:pointer;"
                                                data-id="{{ $order->id }}"
                                                data-status="{{ $order->status }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#updateStatusModal"
                                            >
                                                {{ ucfirst($order->status ?? '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($order->invoice && $order->invoice->status == 'unpaid') bg-danger
                                                @elseif($order->invoice && $order->invoice->status == 'paid') bg-success
                                                @elseif($order->invoice && $order->invoice->status == 'pending') bg-warning text-dark
                                                @else bg-secondary
                                                @endif
                                            ">
                                                {{ ucfirst($order->invoice->status ?? '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $order->subtotal }}৳
                                        </td>
                                        <td>
                                            {{ $order->discount }}৳
                                        </td>
                                        <td>
                                            {{ $order->total }}৳
                                        </td>
                                    </tr>
                                @empty   
                                <tr>
                                    <td colspan="12" class="text-center">No order Found</td></tr> 
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} orders
                        </span>
                        {{ $orders->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection