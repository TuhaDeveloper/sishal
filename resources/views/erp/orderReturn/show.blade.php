@extends('erp.master')

@section('title', 'Sale Return Details')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        
        <!-- Enhanced Header Section -->
        <div class="container-fluid px-4 py-4 bg-white border-bottom shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-3">
                            <li class="breadcrumb-item">
                                <a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-primary">
                                    <i class="fas fa-home me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('orderReturn.list') }}" class="text-decoration-none text-primary">
                                    <i class="fas fa-undo me-1"></i>Order Returns
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Return #{{ $orderReturn->id }}</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center mb-2">
                        <h1 class="fw-bold mb-0 me-3">Order Return #{{ $orderReturn->id }}</h1>
                        <div class="status-indicator">
                            @switch($orderReturn->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                        <i class="fas fa-clock me-1"></i>Pending
                                    </span>
                                    @break
                                @case('approved')
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i>Approved
                                    </span>
                                    @break
                                @case('rejected')
                                    <span class="badge bg-danger fs-6 px-3 py-2">
                                        <i class="fas fa-times-circle me-1"></i>Rejected
                                    </span>
                                    @break
                                @case('processed')
                                    <span class="badge bg-info fs-6 px-3 py-2">
                                        <i class="fas fa-cog me-1"></i>Processed
                                    </span>
                                    @break
                                @default
                                    <span class="badge bg-secondary fs-6 px-3 py-2">{{ ucfirst($orderReturn->status) }}</span>
                            @endswitch
                        </div>
                    </div>
                    <p class="text-muted mb-0 fs-5">Detailed view of sale return information and items.</p>
                </div>
            </div>
        </div>

        <!-- Enhanced Main Content -->
        <div class="container-fluid px-4 py-4">
            <div class="row">
                <!-- Left Column - Return Details -->
                <div class="col-lg-8">
                    <!-- Return Information Card -->
                    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                        <div class="card-header bg-gradient-primary text-white border-0 py-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle fa-lg me-3"></i>
                                <h4 class="fw-bold mb-0">Return Information</h4>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="info-item mb-4">
                                        <div class="info-icon bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                            <i class="fas fa-hashtag"></i>
                                        </div>
                                        <label class="form-label fw-semibold text-muted text-uppercase small">Return ID</label>
                                        <p class="mb-0 fw-bold fs-5 text-dark">#{{ $orderReturn->id }}</p>
                                    </div>
                                    
                                    <div class="info-item mb-4">
                                        <div class="info-icon bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <label class="form-label fw-semibold text-muted text-uppercase small">Customer</label>
                                        <p class="mb-0 fw-bold fs-5 text-dark">{{ $orderReturn->customer->name ?? 'N/A' }}</p>
                                    </div>
                                    
                                    <div class="info-item mb-4">
                                        <div class="info-icon bg-info bg-opacity-10 text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                            <i class="fas fa-cash-register"></i>
                                        </div>
                                        <label class="form-label fw-semibold text-muted text-uppercase small">Order</label>
                                        <p class="mb-0 fw-bold fs-5 text-dark">{{ $orderReturn->order ? '#' . $orderReturn->order->order_number : 'N/A' }}</p>
                                    </div>
                                    
                                    <div class="info-item mb-4">
                                        <div class="info-icon bg-secondary bg-opacity-10 text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <label class="form-label fw-semibold text-muted text-uppercase small">Return Date</label>
                                        <p class="mb-0 fw-bold fs-5 text-dark">{{ $orderReturn->return_date }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-4">
                                        <div class="info-icon bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                            <i class="fas fa-money-check-alt"></i>
                                        </div>
                                        <label class="form-label fw-semibold text-muted text-uppercase small">Refund Type</label>
                                        <p class="mb-0 fw-bold fs-5 text-dark">{{ ucfirst($orderReturn->refund_type) }}</p>
                                    </div>
                                    
                                    <div class="info-item mb-4">
                                        <div class="info-icon bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <label class="form-label fw-semibold text-muted text-uppercase small">Return To</label>
                                        <p class="mb-0 fw-bold fs-5 text-dark">
                                            {{ ucfirst($orderReturn->return_to_type) }}:
                                            @if($orderReturn->return_to_type === 'branch' && $orderReturn->return_to_id)
                                                {{ optional($orderReturn->branch)->name ?? 'N/A' }}
                                            @elseif($orderReturn->return_to_type === 'warehouse' && $orderReturn->return_to_id)
                                                {{ optional($orderReturn->warehouse)->name ?? 'N/A' }}
                                            @elseif($orderReturn->return_to_type === 'employee' && $orderReturn->employee)
                                                {{ $orderReturn->employee->user->first_name ?? '' }} {{ $orderReturn->employee->user->last_name ?? '' }}
                                            @else
                                                N/A
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            @if($orderReturn->reason)
                            <div class="mb-4 mt-4">
                                <label class="form-label fw-semibold text-muted text-uppercase small">Reason</label>
                                <div class="bg-light border-start border-4 border-warning p-3 rounded-end">
                                    <p class="mb-0 fs-6">{{ $orderReturn->reason }}</p>
                                </div>
                            </div>
                            @endif
                            
                            @if($orderReturn->notes)
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-muted text-uppercase small">Notes</label>
                                <div class="bg-light border-start border-4 border-info p-3 rounded-end">
                                    <pre class="mb-0 text-dark fs-6 font-monospace">{{ $orderReturn->notes }}</pre>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Enhanced Return Items Card -->
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="card-header bg-gradient-success text-white border-0 py-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-boxes fa-lg me-3"></i>
                                    <h4 class="fw-bold mb-0">Return Items</h4>
                                </div>
                                <span class="badge bg-white text-success fs-6 px-3 py-2">
                                    {{ $orderReturn->items->count() }} {{ Str::plural('Item', $orderReturn->items->count()) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="border-0 px-4 py-3">
                                                <i class="fas fa-box me-2"></i>Product
                                            </th>
                                            <th class="border-0 text-center px-3 py-3">
                                                <i class="fas fa-sort-numeric-up me-2"></i>Quantity
                                            </th>
                                            <th class="border-0 text-center px-3 py-3">
                                                <i class="fas fa-dollar-sign me-2"></i>Unit Price
                                            </th>
                                            <th class="border-0 text-center px-3 py-3">
                                                <i class="fas fa-calculator me-2"></i>Total
                                            </th>
                                            <th class="border-0 text-center px-3 py-3">
                                                <i class="fas fa-comment me-2"></i>Reason
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orderReturn->items as $item)
                                        <tr class="border-bottom">
                                            <td class="px-4 py-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="product-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-cube fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-dark">{{ $item->product->name ?? 'N/A' }}</h6>
                                                        <small class="text-muted">
                                                            <i class="fas fa-barcode me-1"></i>
                                                            SKU: {{ $item->product->sku ?? 'N/A' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center px-3 py-4">
                                                <span class="badge bg-info fs-6 px-3 py-2">{{ $item->returned_qty }}</span>
                                            </td>
                                            <td class="text-center px-3 py-4">
                                                <span class="fw-bold text-success fs-6">{{ number_format($item->unit_price, 2) }}৳</span>
                                            </td>
                                            <td class="text-center px-3 py-4">
                                                <span class="fw-bold text-primary fs-5">{{ number_format($item->total_price, 2) }}৳</span>
                                            </td>
                                            <td class="text-center px-3 py-4">
                                                <span class="text-muted">
                                                    @if($item->reason)
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        {{ Str::limit($item->reason, 30) }}
                                                    @else
                                                        <i class="fas fa-minus text-light"></i>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <div class="text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                                    <h5 class="mb-1">No return items found</h5>
                                                    <p class="mb-0">This return doesn't have any items associated with it.</p>
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
                
                <!-- Right Column - Summary & Quick Actions -->
                <div class="col-lg-4">
                    <!-- Return Summary Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-gradient-info text-white border-0 py-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-chart-line me-2"></i>
                                <h5 class="fw-bold mb-0">Return Summary</h5>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="summary-item">
                                        <div class="summary-icon bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-2" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-boxes fa-lg"></i>
                                        </div>
                                        <h3 class="fw-bold mb-1">{{ $orderReturn->items->count() }}</h3>
                                        <small class="text-muted">Total Items</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="summary-item">
                                        <div class="summary-icon bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-2" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-bangladeshi-taka-sign fa-lg"></i>
                                        </div>
                                        <h3 class="fw-bold mb-1">{{ number_format($orderReturn->items->sum('total_price'), 2) }}৳</h3>
                                        <small class="text-muted">Total Value</small>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Return Date:</span>
                                <span class="fw-bold">{{ $orderReturn->return_date }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="text-muted">Current Status:</span>
                                <span class="fw-bold">{{ ucfirst($orderReturn->status) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-gradient-secondary text-white border-0 py-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-bolt me-2"></i>
                                <h5 class="fw-bold mb-0">Quick Actions</h5>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-grid gap-2">
                                <a href="{{ route('orderReturn.edit', $orderReturn->id) }}" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-edit me-2"></i>Edit Return
                                </a>
                                <button class="btn btn-outline-info btn-lg">
                                    <i class="fas fa-print me-2"></i>Print Return
                                </button>
                                <hr class="my-3">
                                <form action="{{ route('orderReturn.delete', $orderReturn->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-lg w-100" onclick="return confirm('Are you sure you want to delete this return?')">
                                        <i class="fas fa-trash me-2"></i>Delete Return
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .bg-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .bg-gradient-info {
            background: linear-gradient(135deg, #3494e6 0%, #ec6ead 100%);
        }
        .bg-gradient-secondary {
            background: linear-gradient(135deg, #485563 0%, #29323c 100%);
        }
        .info-item {
            position: relative;
            padding-left: 60px;
        }
        .info-icon {
            position: absolute;
            left: 0;
            top: 0;
        }
        .product-icon {
            min-width: 50px;
            min-height: 50px;
        }
        .summary-item {
            padding: 1rem;
        }
        .table-dark th {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .card {
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .btn-group-vertical .btn {
            min-width: 150px;
        }
        @media (max-width: 768px) {
            .info-item {
                padding-left: 0;
                text-align: center;
            }
            .info-icon {
                position: relative;
                margin: 0 auto 0.5rem;
            }
        }
    </style>
@endsection