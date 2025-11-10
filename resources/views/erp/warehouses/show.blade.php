@extends('erp.master')

@section('title', 'Warehouse Details')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">

        @include('erp.components.header')

        <div class="container">
            <div class="row my-4">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="fas fa-warehouse fa-2x text-primary" style="font-size: 20px"></i>
                            </div>
                            <div>
                                <h2 class="mb-0">Warehouse: {{ $warehouse->name }}</h2>
                                <small class="text-muted">Location: {{ $warehouse->location }}</small>
                                @if($warehouse->branch)
                                    <br><small class="text-info">Branch: {{ $warehouse->branch->name }}</small>
                                @else
                                    <br><small class="text-secondary">Ecommerce Warehouse (No Branch)</small>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                {{-- Manager card removed for ecommerce-only setup --}}
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4 h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Status</h5>
                            <span class="badge bg-{{ ($warehouse->status ?? 'active') == 'active' ? 'success' : 'secondary' }} fs-6">
                                {{ ucfirst($warehouse->status ?? 'active') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4 h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-2">Total Products</h5>
                            <span class="display-6 text-primary">{{ $products_count }}</span>
                            <small class="text-muted d-block">Different products</small>
                        </div>
                    </div>
                </div>
                
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold">
                            <i class="fas fa-info-circle text-primary me-2"></i>Warehouse Details
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Name</dt>
                                <dd class="col-sm-8">{{ $warehouse->name }}</dd>
                                <dt class="col-sm-4">Location</dt>
                                <dd class="col-sm-8">{{ $warehouse->location }}</dd>
                                {{-- Manager row removed --}}
                                <dt class="col-sm-4">Branch</dt>
                                <dd class="col-sm-8">
                                    @if($warehouse->branch)
                                        {{ $warehouse->branch->name }}
                                    @else
                                        <span class="text-muted">Not assigned</span>
                                    @endif
                                </dd>
                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-{{ ($warehouse->status ?? 'active') == 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($warehouse->status ?? 'active') }}
                                    </span>
                                </dd>
                                <dt class="col-sm-4">Created At</dt>
                                <dd class="col-sm-8">{{ $warehouse->created_at->format('M d, Y') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                {{-- Warehouse Staff section removed --}}
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border w-100 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-box text-primary me-2"></i>Warehouse Products
                                </h5>
                                <div class="d-flex gap-2">
                                    <input type="search" class="form-control form-control-sm" placeholder="Search products..." style="width: 200px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if($recent_products->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0 fw-semibold">Product</th>
                                                <th class="border-0 fw-semibold">SKU</th>
                                                <th class="border-0 fw-semibold">Sale Price</th>
                                                <th class="border-0 fw-semibold">Cost Price</th>
                                                <th class="border-0 fw-semibold">Category</th>
                                                <th class="border-0 fw-semibold">Stock</th>
                                                <th class="border-0 fw-semibold">Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recent_products as $stockItem)
                                                @php
                                                    $product = $stockItem['product'] ?? null;
                                                    $quantity = $stockItem['quantity'] ?? 0;
                                                    $stockType = $stockItem['stock_type'] ?? 'product';
                                                @endphp
                                                <tr>
                                                    <td class="border-0 py-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                                                @if($product && $product->image)
                                                                    <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                                @else
                                                                    <i class="fas fa-box text-primary"></i>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0 fw-semibold">{{ $product ? ($product->name ?? 'Unknown Product') : 'Unknown Product' }}</h6>
                                                                <small class="text-muted">ID: #{{ $product ? ($product->id ?? 'N/A') : 'N/A' }}</small>
                                                                @if($stockType === 'variation')
                                                                    <br><small class="text-info"><i class="fas fa-layer-group"></i> Variation Product</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="border-0 py-3">
                                                        <code class="bg-light px-2 py-1 rounded">{{ $product ? ($product->sku ?? 'N/A') : 'N/A' }}</code>
                                                    </td>
                                                    <td class="border-0 py-3">
                                                        <span class="fw-semibold text-success">৳{{ number_format(($product && $product->discount && $product->discount > 0) ? $product->discount : ($product ? ($product->price ?? 0) : 0), 2) }}</span>
                                                    </td>
                                                    <td class="border-0 py-3">
                                                        <span class="fw-semibold">৳{{ number_format($product ? ($product->cost ?? 0) : 0, 2) }}</span>
                                                    </td>
                                                    <td class="border-0 py-3">
                                                        <span class="badge bg-info bg-opacity-25 text-info">
                                                            {{ $product && $product->category ? $product->category->name : 'No Category' }}
                                                        </span>
                                                    </td>
                                                    <td class="border-0 py-3">
                                                        @if($quantity < 0)
                                                            <span class="badge bg-danger bg-opacity-25 text-danger" title="Negative stock: This usually occurs when items are returned to supplier or stock is adjusted below zero. Please add stock to correct this.">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>{{ $quantity }} {{ $quantity == 1 ? 'unit' : 'units' }}
                                                            </span>
                                                        @elseif($quantity == 0)
                                                            <span class="badge bg-warning bg-opacity-25 text-warning">
                                                                {{ $quantity }} {{ $quantity == 1 ? 'unit' : 'units' }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success bg-opacity-25 text-success">
                                                                {{ $quantity }} {{ $quantity == 1 ? 'unit' : 'units' }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="border-0 py-3">
                                                        @if($quantity < 0)
                                                            <span class="fw-semibold text-danger">৳{{ number_format($quantity * ($product ? (($product->cost ?? null) !== null ? $product->cost : ($product->price ?? 0)) : 0), 2) }}</span>
                                                        @else
                                                            <span class="fw-semibold text-primary">৳{{ number_format($quantity * ($product ? (($product->cost ?? null) !== null ? $product->cost : ($product->price ?? 0)) : 0), 2) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-box fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted">No Products in Warehouse</h5>
                                    <p class="text-muted">This warehouse doesn't have any products yet.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection