@extends('erp.master')

@section('title', 'Purchase Management')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h3>Purchase #{{ $purchase->id }}</h3>
                    <div class="mb-2">
                        <span class="me-2">
                            <strong>Date:</strong> {{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d-m-Y') : '-' }}
                        </span>
                        <span class="badge 
                            @if($purchase->status == 'pending') bg-warning text-dark
                            @elseif($purchase->status == 'approved' || $purchase->status == 'paid') bg-success
                            @elseif($purchase->status == 'unpaid' || $purchase->status == 'rejected') bg-danger
                            @else bg-secondary
                            @endif
                        ">
                            {{ ucfirst($purchase->status ?? '-') }}
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    @if($purchase->bill)
                        <span class="badge 
                            @if($purchase->bill->status == 'unpaid') bg-danger
                            @elseif($purchase->bill->status == 'paid') bg-success
                            @elseif($purchase->bill->status == 'pending') bg-warning text-dark
                            @else bg-secondary
                            @endif
                        ">
                            Bill: {{ ucfirst($purchase->bill->status) }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">Purchase Info</div>
                        <div class="card-body">
                            <p><strong>Supplier:</strong> {{ $purchase->vendor->name ?? '-' }}</p>
                            <p><strong>Location:</strong> {{ $purchase->location_name }} ({{ ucfirst($purchase->ship_location_type) }})</p>
                            <p><strong>Notes:</strong> {{ $purchase->notes ?? '-' }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">Bill Info</div>
                        <div class="card-body">
                            @if($purchase->bill)
                                <p><strong>Total:</strong> {{ number_format($purchase->bill->total_amount, 2) }}</p>
                                <p><strong>Paid:</strong> {{ number_format($purchase->bill->paid_amount, 2) }}</p>
                                <p><strong>Due:</strong> {{ number_format($purchase->bill->due_amount, 2) }}</p>
                                <p><strong>Description:</strong> {{ $purchase->bill->description }}</p>
                            @else
                                <p>No bill information available.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Items</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Discount</th>
                                    <th>Total</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchase->items as $i => $item)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $item->product->name ?? '-' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                        <td>{{ number_format($item->discount, 2) }}</td>
                                        <td>{{ number_format($item->total_price, 2) }}</td>
                                        <td>{{ $item->description ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No items found.</td>
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