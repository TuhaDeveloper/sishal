@extends('erp.master')

@section('title', 'Stock Transfer Details')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h4 class="mb-0">Stock Transfer Details</h4>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Product</dt>
                                <dd class="col-sm-8">{{ $transfer->product->name ?? '-' }}</dd>

                                <dt class="col-sm-4">SKU</dt>
                                <dd class="col-sm-8">{{ $transfer->product->sku ?? '-' }}</dd>

                                <dt class="col-sm-4">Category</dt>
                                <dd class="col-sm-8">{{ $transfer->product->category->name ?? '-' }}</dd>

                                <dt class="col-sm-4">From</dt>
                                <dd class="col-sm-8">
                                    @if($transfer->from_type === 'branch')
                                        Branch: {{ $transfer->fromBranch->name ?? '-' }}
                                    @elseif($transfer->from_type === 'warehouse')
                                        Warehouse: {{ $transfer->fromWarehouse->name ?? '-' }}
                                    @else
                                        {{ ucfirst($transfer->from_type) }}
                                    @endif
                                </dd>

                                <dt class="col-sm-4">To</dt>
                                <dd class="col-sm-8">
                                    @if($transfer->to_type === 'branch')
                                        Branch: {{ $transfer->toBranch->name ?? '-' }}
                                    @elseif($transfer->to_type === 'warehouse')
                                        Warehouse: {{ $transfer->toWarehouse->name ?? '-' }}
                                    @else
                                        {{ ucfirst($transfer->to_type) }}
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Quantity</dt>
                                <dd class="col-sm-8">{{ $transfer->quantity }}</dd>

                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8"><span class="badge bg-info">{{ ucfirst($transfer->status) }}</span></dd>

                                <dt class="col-sm-4">Requested By</dt>
                                <dd class="col-sm-8">{{ optional($transfer->requestedPerson)->first_name }} {{ optional($transfer->requestedPerson)->last_name }}</dd>

                                <dt class="col-sm-4">Approved By</dt>
                                <dd class="col-sm-8">{{ optional($transfer->approvedPerson)->first_name }} {{ optional($transfer->approvedPerson)->last_name }}</dd>

                                <dt class="col-sm-4">Requested At</dt>
                                <dd class="col-sm-8">{{ $transfer->requested_at ? \Carbon\Carbon::parse($transfer->requested_at)->format('Y-m-d H:i') : '-' }}</dd>

                                <dt class="col-sm-4">Approved At</dt>
                                <dd class="col-sm-8">{{ $transfer->approved_at ? \Carbon\Carbon::parse($transfer->approved_at)->format('Y-m-d H:i') : '-' }}</dd>

                                <dt class="col-sm-4">Shipped At</dt>
                                <dd class="col-sm-8">{{ $transfer->shipped_at ? \Carbon\Carbon::parse($transfer->shipped_at)->format('Y-m-d H:i') : '-' }}</dd>

                                <dt class="col-sm-4">Delivered At</dt>
                                <dd class="col-sm-8">{{ $transfer->delivered_at ? \Carbon\Carbon::parse($transfer->delivered_at)->format('Y-m-d H:i') : '-' }}</dd>

                                <dt class="col-sm-4">Notes</dt>
                                <dd class="col-sm-8">{{ $transfer->notes ?? '-' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection