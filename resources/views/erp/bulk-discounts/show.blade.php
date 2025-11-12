@extends('erp.master')

@section('title', 'View Bulk Discount')

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
                            <li class="breadcrumb-item"><a href="{{ route('bulk-discounts.index') }}" class="text-decoration-none">Bulk Discount Management</a></li>
                            <li class="breadcrumb-item active" aria-current="page">View Bulk Discount</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">{{ $bulkDiscount->name }}</h2>
                    <p class="text-muted mb-0">Bulk discount details</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('bulk-discounts.edit', $bulkDiscount) }}" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="{{ route('bulk-discounts.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Discounts
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Basic Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3">Name:</dt>
                                <dd class="col-sm-9">{{ $bulkDiscount->name }}</dd>

                                @if($bulkDiscount->description)
                                <dt class="col-sm-3">Description:</dt>
                                <dd class="col-sm-9">{{ $bulkDiscount->description }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Discount Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Discount Settings</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                @php
                                    $discountType = $bulkDiscount->type ?? 'percentage';
                                    $discountValue = $bulkDiscount->value ?? $bulkDiscount->percentage ?? 0;
                                @endphp
                                <dt class="col-sm-3">Type:</dt>
                                <dd class="col-sm-9">
                                    @if($discountType === 'free_delivery')
                                        <span class="badge bg-primary"><i class="fas fa-truck me-1"></i>Free Delivery</span>
                                    @else
                                        <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $discountType)) }}</span>
                                    @endif
                                </dd>
                                @if($discountType !== 'free_delivery')
                                <dt class="col-sm-3">Value:</dt>
                                <dd class="col-sm-9">
                                    @if($discountType === 'percentage')
                                        <span class="badge bg-success fs-6">{{ number_format($discountValue, 2) }}%</span>
                                    @else
                                        <span class="badge bg-info fs-6">{{ number_format($discountValue, 2) }}৳</span>
                                    @endif
                                </dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Scope Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Applicability Scope</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3">Scope Type:</dt>
                                <dd class="col-sm-9">
                                    @if($bulkDiscount->scope_type === 'all')
                                        <span class="badge bg-info">All Products</span>
                                    @else
                                        <span class="badge bg-warning">Specific Products</span>
                                    @endif
                                </dd>

                                @if($bulkDiscount->scope_type === 'products' && $bulkDiscount->applicable_products)
                                <dt class="col-sm-3">Applicable Products:</dt>
                                <dd class="col-sm-9">
                                    <ul class="list-unstyled mb-0">
                                        @foreach($bulkDiscount->applicable_products as $productId)
                                            @php
                                                $product = \App\Models\Product::find($productId);
                                            @endphp
                                            @if($product)
                                                <li>{{ $product->name }} @if($product->sku)({{ $product->sku }})@endif</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                    <small class="text-muted">Total: {{ count($bulkDiscount->applicable_products) }} product(s)</small>
                                </dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Date Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Date Settings</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3">Start Date:</dt>
                                <dd class="col-sm-9">
                                    @if($bulkDiscount->start_date)
                                        {{ \Carbon\Carbon::parse($bulkDiscount->start_date)->format('F d, Y h:i A') }}
                                    @else
                                        <span class="text-muted">Immediate</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-3">End Date:</dt>
                                <dd class="col-sm-9">
                                    @if($bulkDiscount->end_date)
                                        {{ \Carbon\Carbon::parse($bulkDiscount->end_date)->format('F d, Y h:i A') }}
                                    @else
                                        <span class="text-muted">No expiry</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-3">Status:</dt>
                                <dd class="col-sm-9">
                                    @if($bulkDiscount->isValid())
                                        <span class="badge bg-success">Valid & Active</span>
                                    @elseif($bulkDiscount->is_active)
                                        <span class="badge bg-warning">Active (Outside Date Range)</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('bulk-discounts.edit', $bulkDiscount) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Discount
                                </a>
                                <form action="{{ route('bulk-discounts.destroy', $bulkDiscount) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this bulk discount?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Discount
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Status Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Status Information</h5>
                        </div>
                        <div class="card-body">
                            <dl class="mb-0">
                                <dt>Active Status:</dt>
                                <dd>
                                    @if($bulkDiscount->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </dd>

                                <dt>Created:</dt>
                                <dd>{{ \Carbon\Carbon::parse($bulkDiscount->created_at)->format('F d, Y') }}</dd>

                                <dt>Last Updated:</dt>
                                <dd>{{ \Carbon\Carbon::parse($bulkDiscount->updated_at)->format('F d, Y') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

