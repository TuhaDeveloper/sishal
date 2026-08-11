@extends('erp.master')

@section('title', 'Transfer Invoice')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')

        <div class="glass-header no-print">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 breadcrumb-premium">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('stocktransfer.list') }}" class="text-decoration-none text-muted">Stock Transfer</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">Invoice Details</li>
                        </ol>
                    </nav>
                    <h4 class="fw-bold mb-0 text-dark">
                        @if($transfer->invoice_number)
                            Invoice #{{ $transfer->invoice_number }}
                        @else
                            Transfer Voucher #{{ str_pad($transfer->id, 6, '0', STR_PAD_LEFT) }}
                        @endif
                    </h4>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2 align-items-md-center">
                    <!-- <button onclick="window.print()" class="btn btn-light fw-bold shadow-sm">
                        <i class="fas fa-print me-2"></i>Print Invoice
                    </button> -->
                    <a href="{{ route('stocktransfer.list') }}" class="btn btn-create-premium">
                        <i class="fas fa-list me-2"></i>Back to History
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 fw-bold" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 fw-bold" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Print-Ready Invoice -->
                    <div class="invoice-container bg-white shadow-sm">
                        <!-- Company Header -->
                        @php
                            $settings = \App\Models\GeneralSetting::first();
                        @endphp
                        <div class="invoice-header border-bottom pb-3 mb-4">
                            <div class="text-center mb-2">
                                <h2 class="fw-bold text-dark mb-2">{{ $settings->site_title ?? config('app.name', 'Your Company Name') }}</h2>
                            </div>
                            <div class="text-center">
                                <p class="text-muted small mb-1">{{ $settings->contact_address ?? 'Address Line 1, City, Country' }}</p>
                                <p class="text-muted small mb-0">
                                    Phone: {{ $settings->contact_phone ?? '+880-XXX-XXXXXX' }} | 
                                    Email: {{ $settings->contact_email ?? 'info@company.com' }}
                                    @if($settings->website_url)
                                     | {{ $settings->website_url }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Invoice Title -->
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-uppercase mb-1" style="letter-spacing: 2px;">Stock Transfer Invoice</h3>
                            <p class="text-muted mb-0">
                                @if($transfer->invoice_number)
                                    Invoice No: <strong>{{ $transfer->invoice_number }}</strong>
                                @else
                                    Voucher No: <strong>ST-{{ str_pad($transfer->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                @endif
                            </p>
                        </div>

                        <!-- Invoice Info Grid -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold text-uppercase small text-muted mb-2">From</h6>
                                    <p class="mb-0 fw-bold">
                                        @if($transfer->from_type === 'branch')
                                            {{ $transfer->fromBranch->name ?? '-' }}
                                        @else
                                            {{ $transfer->fromWarehouse->name ?? '-' }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold text-uppercase small text-muted mb-2">To</h6>
                                    <p class="mb-0 fw-bold">
                                        @if($transfer->to_type === 'branch')
                                            {{ $transfer->toBranch->name ?? '-' }}
                                        @else
                                            {{ $transfer->toWarehouse->name ?? '-' }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-4">
                                <p class="mb-1"><strong class="text-muted small">Date:</strong></p>
                                <p class="fw-bold">{{ $transfer->requested_at ? \Carbon\Carbon::parse($transfer->requested_at)->format('d M Y') : '-' }}</p>
                            </div>
                            <div class="col-4">
                                <p class="mb-1"><strong class="text-muted small">Requested By:</strong></p>
                                <p class="fw-bold">{{ $transfer->requestedPerson->name ?? 'Admin User' }}</p>
                            </div>
                            <div class="col-4">
                                <p class="mb-1"><strong class="text-muted small">Status:</strong></p>
                                <p>
                                    <span class="badge {{ $transfer->status === 'delivered' ? 'bg-success' : ($transfer->status === 'rejected' ? 'bg-danger' : ($transfer->status === 'approved' ? 'bg-info' : 'bg-warning')) }} px-3 py-1">
                                        {{ strtoupper($transfer->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-uppercase small text-muted mb-3">Transfer Items</h6>
                            <table class="table table-bordered invoice-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th>Product Description</th>
                                        <th style="width: 200px;">Attributes</th>
                                        <th style="width: 100px;" class="text-center">Quantity</th>
                                        <th style="width: 120px;" class="text-end">Unit Price</th>
                                        <th style="width: 120px;" class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transfers as $index => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $item->product->name ?? '-' }}</div>
                                            <div class="small text-muted">SKU: {{ $item->product->style_number ?? 'N/A' }}</div>
                                        </td>
                                        <td>
                                            @if($item->variation)
                                                @php
                                                    $color = null; $size = null;
                                                    if($item->variation->combinations) {
                                                        foreach($item->variation->combinations as $combo) {
                                                            $name = strtolower($combo->attribute->name ?? '');
                                                            if(in_array($name, ['color','colour'])) $color = $combo->attributeValue->value ?? '';
                                                            if(in_array($name, ['size','sizes'])) $size = $combo->attributeValue->value ?? '';
                                                        }
                                                    }
                                                @endphp
                                                <span class="badge bg-light text-dark border me-1 small">{{ $size ?? '-' }}</span>
                                                <span class="badge bg-light text-dark border small">{{ $color ?? '-' }}</span>
                                            @else
                                                <span class="text-muted small">Standard</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($item->quantity, 0) }}</td>
                                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($item->total_price, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">TOTAL</td>
                                        <td class="text-center fw-bold text-primary">{{ number_format($transfers->sum('quantity'), 0) }}</td>
                                        <td></td>
                                        <td class="text-end fw-bold text-success fs-5">{{ number_format($transfers->sum('total_price'), 2) }} ৳</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        


                        <!-- Notes Section -->
                        @if($transfer->notes)
                        <div class="mb-4">
                            <h6 class="fw-bold text-uppercase small text-muted mb-2">Notes / Remarks</h6>
                            <div class="border rounded p-3 bg-light">
                                <p class="mb-0 small">{{ $transfer->notes }}</p>
                            </div>
                        </div>
                        @endif

                        <!-- Signature Section -->
                        <div class="row mt-5 pt-4 border-top">
                            <div class="col-6 text-center">
                                <div class="border-top pt-2 mt-5 d-inline-block" style="min-width: 200px;">
                                    <p class="mb-0 small fw-bold">Prepared By</p>
                                    <p class="mb-0 small text-muted">{{ $transfer->requestedPerson->name ?? 'Admin' }}</p>
                                </div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="border-top pt-2 mt-5 d-inline-block" style="min-width: 200px;">
                                    <p class="mb-0 small fw-bold">Received By</p>
                                    <p class="mb-0 small text-muted">{{ $transfer->deliveredPerson->name ?? '___________' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="small text-muted mb-0">This is a computer-generated document. No signature is required.</p>
                            <p class="small text-muted mb-0">Printed on: {{ now()->format('d M Y, h:i A') }}</p>
                        </div>

                        <!-- Action Buttons (No Print) -->
                        <div class="no-print mt-4 pt-4 border-top">
                            @php
                                // Role-based button visibility
                                // $restrictedBranchId = null  → Super Admin (can do everything)
                                // $restrictedBranchId = X     → Branch user restricted to branch X

                                $isSuperAdmin    = !$restrictedBranchId;

                                // Is the current user on the SENDING side?
                                $isSenderUser    = $restrictedBranchId
                                    && $transfer->from_type === 'branch'
                                    && $restrictedBranchId == $transfer->from_id;

                                // Is the current user on the RECEIVING side?
                                $isReceiverUser  = $restrictedBranchId
                                    && $transfer->to_type === 'branch'
                                    && $restrictedBranchId == $transfer->to_id;

                                // Permissions:
                                // Approve / Reject / Ship  → Admin OR Sender
                                // Confirm Delivery         → Admin OR Receiver (destination branch)
                                // Return / Void            → Admin OR Sender
                                $canManageSending  = $isSuperAdmin || $isSenderUser;
                                $canConfirmDelivery = $isSuperAdmin || $isReceiverUser;
                            @endphp

                            {{-- Role indicator badge (helpful context) --}}
                            <div class="text-center mb-3">
                                @if($isSuperAdmin)
                                    <span class="badge bg-dark px-3 py-2"><i class="fas fa-shield-alt me-1"></i>Super Admin — Full Access</span>
                                @elseif($isSenderUser)
                                    <span class="badge bg-info text-white px-3 py-2"><i class="fas fa-warehouse me-1"></i>Sending Side — You dispatched this transfer</span>
                                @elseif($isReceiverUser)
                                    <span class="badge bg-success px-3 py-2"><i class="fas fa-store me-1"></i>Receiving Side — Awaiting your confirmation</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2"><i class="fas fa-eye me-1"></i>View Only</span>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap justify-content-center gap-3">

                                {{-- PENDING STATUS ACTIONS --}}
                                @if($transfer->status == 'pending')
                                    @if($canConfirmDelivery || $canManageSending)
                                        <form action="{{ route('stocktransfer.status', $transfer->id) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="delivered">
                                            <button type="submit" class="btn btn-success px-5 fw-bold" onclick="return confirm('Confirm and deliver this transfer? Source stock will be deducted and added to destination inventory.')">
                                                <i class="fas fa-box-open me-2"></i>CONFIRM & DELIVER INVOICE
                                            </button>
                                        </form>
                                    @endif

                                    @if($canManageSending || $canConfirmDelivery)
                                        <form action="{{ route('stocktransfer.status', $transfer->id) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-warning px-4 fw-bold text-white" onclick="return confirm('Reject this transfer invoice?')">
                                                <i class="fas fa-times-circle me-2"></i>REJECT
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                {{-- CONFIRM DELIVERY: Receiver or Admin only (for legacy approved transfers) --}}
                                @if($transfer->status == 'approved' && $canConfirmDelivery)
                                    <form action="{{ route('stocktransfer.status', $transfer->id) }}" method="POST" class="d-inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="delivered">
                                        <button type="submit" class="btn btn-primary px-5 fw-bold" onclick="return confirm('Confirm you have received these items? Stock will be added to your inventory.')">
                                            <i class="fas fa-box-open me-2"></i>CONFIRM RECEIPT
                                        </button>
                                    </form>
                                @elseif($transfer->status == 'approved' && !$canConfirmDelivery)
                                    <div class="alert alert-info border-0 py-2 px-4 mb-0 small">
                                        <i class="fas fa-clock me-2"></i>Approved — waiting for <strong>destination branch to confirm receipt</strong>.
                                    </div>
                                @endif

                                {{-- RETURN ITEMS: Sender or Admin only (after delivery) --}}
                                @if($transfer->status == 'delivered' && $transfer->type !== 'return' && $canManageSending)
                                @can('approve transfers')
                                    <a href="{{ route('stocktransfer.return', $transfer->id) }}" class="btn btn-warning px-5 fw-bold text-dark"
                                       onclick="return confirm('You are about to initiate a Return of items from this transfer. Proceed?')">
                                        <i class="fas fa-undo-alt me-2"></i>RETURN ITEMS TO SOURCE
                                    </a>
                                    @endcan
                                @endif

                                 {{-- RECONCILE QUANTITIES: Super Admin only --}}
                                @if($isSuperAdmin || auth()->user()->hasPermissionTo('reconcile transfers'))
                                @can('approve transfers')
                                    <button type="button" class="btn btn-danger px-5 fw-bold" data-bs-toggle="modal" data-bs-target="#reconcileModal">
                                        <i class="fas fa-edit me-2"></i>RECONCILE QUANTITIES (ADMIN)
                                    </button>
                                @endcan
                                @endif

                                {{-- VOID TRANSFER: Sender or Admin only --}}
                                @if(in_array($transfer->status, ['pending', 'rejected']) && $canManageSending)
                                @can('delete transfers')
                                    <form action="{{ route('stocktransfer.destroy', $transfer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this entire transfer invoice? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger px-4 fw-bold">
                                            <i class="fas fa-trash-alt me-2"></i>VOID TRANSFER
                                        </button>
                                    </form>
                                @endcan
                                @endif

                            </div>

                            {{-- Show linked returns if any --}}
                            @php
                                $returnCount = \App\Models\StockTransfer::where('return_of_id', $transfer->id)->count();
                            @endphp
                            @if($returnCount > 0)
                            <div class="mt-4 alert alert-info border-0 d-flex align-items-center gap-2" style="background: #e8f4fd;">
                                <i class="fas fa-info-circle text-info"></i>
                                <span class="small fw-bold">
                                    This transfer has <strong>{{ $returnCount }} return(s)</strong> recorded against it.
                                    <a href="{{ route('stocktransfer.list') }}?search={{ $transfer->invoice_number }}" class="ms-2 text-decoration-none fw-bold text-info">
                                        View in History →
                                    </a>
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($isSuperAdmin || auth()->user()->hasPermissionTo('reconcile transfers'))
    <!-- Reconcile Modal -->
    <div class="modal fade" id="reconcileModal" tabindex="-1" aria-labelledby="reconcileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
                <div class="modal-header bg-danger text-white border-0 py-3 px-4">
                    <h5 class="modal-title fw-bold" id="reconcileModalLabel">
                        <i class="fas fa-tools me-2"></i>Super Admin Stock Reconciliation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="reconcileForm" action="{{ route('stocktransfer.reconcile', $transfer->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 small mb-4 d-flex align-items-center gap-2" style="background-color: #fff9db; border-radius: 8px;">
                            <i class="fas fa-exclamation-triangle text-warning fs-5"></i>
                            <div>
                                <span class="fw-bold">Warning:</span> You are modifying a <strong>{{ strtoupper($transfer->status) }}</strong> stock transfer. 
                                @if($transfer->status === 'delivered')
                                    The system will automatically recalculate the difference and correct the stock levels at both the source and destination locations.
                                @elseif($transfer->status === 'approved')
                                    The system will automatically correct the stock levels at the source location.
                                @else
                                    This will update the invoice details directly (no stock movements have occurred yet).
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase font-monospace" style="font-size: 11px;">
                                    <tr>
                                        <th>Product Description</th>
                                        <th>Attributes</th>
                                        <th class="text-center" style="width: 120px;">Current Qty</th>
                                        <th class="text-center" style="width: 140px;">Source Stock</th>
                                        <th class="text-center" style="width: 160px;">New Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transfers as $item)
                                        @php
                                            $availStock = 0;
                                            if ($item->variation_id) {
                                                $vQuery = \App\Models\ProductVariationStock::where('variation_id', $item->variation_id);
                                                if ($item->from_type === 'branch') {
                                                    $vQuery->where('branch_id', $item->from_id)->whereNull('warehouse_id');
                                                } else {
                                                    $vQuery->where('warehouse_id', $item->from_id)->whereNull('branch_id');
                                                }
                                                $vStock = $vQuery->first();
                                                $availStock = $vStock ? ($vStock->available_quantity ?? ($vStock->quantity - ($vStock->reserved_quantity ?? 0))) : 0;
                                            } else {
                                                if ($item->from_type === 'branch') {
                                                    $bStock = \App\Models\BranchProductStock::where('product_id', $item->product_id)->where('branch_id', $item->from_id)->first();
                                                    $availStock = $bStock ? $bStock->quantity : 0;
                                                } else {
                                                    $wStock = \App\Models\WarehouseProductStock::where('product_id', $item->product_id)->where('warehouse_id', $item->from_id)->first();
                                                    $availStock = $wStock ? $wStock->quantity : 0;
                                                }
                                            }
                                            $currentQty = intval($item->quantity);
                                            $availStock = intval($availStock);
                                            $maxAllowed = in_array($item->status, ['approved', 'delivered']) ? ($currentQty + $availStock) : 999999;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $item->product->name ?? '-' }}</div>
                                                <div class="small text-muted">SKU: {{ $item->product->style_number ?? 'N/A' }}</div>
                                            </td>
                                            <td>
                                                @if($item->variation)
                                                    @php
                                                        $color = null; $size = null;
                                                        if($item->variation->combinations) {
                                                            foreach($item->variation->combinations as $combo) {
                                                                $name = strtolower($combo->attribute->name ?? '');
                                                                if(in_array($name, ['color','colour'])) $color = $combo->attributeValue->value ?? '';
                                                                if(in_array($name, ['size','sizes'])) $size = $combo->attributeValue->value ?? '';
                                                            }
                                                        }
                                                    @endphp
                                                    <span class="badge bg-light text-dark border me-1 small">{{ $size ?? '-' }}</span>
                                                    <span class="badge bg-light text-dark border small">{{ $color ?? '-' }}</span>
                                                @else
                                                    <span class="text-muted small">Standard</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-bold text-muted">{{ number_format($currentQty, 0) }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $availStock > 0 ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-danger bg-opacity-10 text-danger border border-danger' }} px-2 py-1">
                                                    {{ $availStock }} Pcs
                                                </span>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="quantities[{{ $item->id }}]" 
                                                        class="form-control text-center fw-bold form-control-sm reconcile-qty-input" 
                                                        value="{{ $currentQty }}" min="0" max="{{ $maxAllowed }}"
                                                        data-item-name="{{ $item->product->name ?? 'Product' }}"
                                                        data-old-qty="{{ $currentQty }}"
                                                        data-avail-stock="{{ $availStock }}"
                                                        data-max-allowed="{{ $maxAllowed }}" required>
                                                    <span class="input-group-text bg-light text-muted small">Pcs</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 py-3 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btnApplyReconcile" class="btn btn-danger fw-bold px-4 shadow-sm">
                            <i class="fas fa-save me-2"></i>Apply Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('css')
    <style>
        .breadcrumb-premium { font-size: 0.85rem; }
        .extra-small { font-size: 0.72rem; }
        
        /* Invoice Styles */
        .invoice-container {
            padding: 40px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .invoice-header h2 {
            color: #2c3e50;
            font-size: 28px;
        }
        
        .invoice-table {
            font-size: 14px;
        }
        
        .invoice-table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            padding: 12px 8px;
        }
        
        .invoice-table tbody td {
            padding: 10px 8px;
            vertical-align: middle;
        }
        
        /* Print Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            
            .invoice-container, .invoice-container * {
                visibility: visible;
            }
            
            .invoice-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .invoice-table {
                page-break-inside: auto;
            }
            
            .invoice-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tfoot {
                display: table-footer-group;
            }
            
            @page {
                margin: 1cm;
            }
        }
    </style>
@endpush

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show SweetAlert popup if session error/success exists
    @if(session('error'))
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Stock Validation Error',
                html: "{!! addslashes(session('error')) !!}",
                customClass: { popup: 'rounded-4' }
            });
        }
    @endif
    @if(session('success'))
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "{{ session('success') }}",
                timer: 3000,
                showConfirmButton: false,
                customClass: { popup: 'rounded-4' }
            });
        }
    @endif

    // Client-side validation for Reconcile modal form
    const reconcileForm = document.getElementById('reconcileForm');
    if (reconcileForm) {
        reconcileForm.addEventListener('submit', function(e) {
            let hasError = false;
            let errorHtml = '';

            const inputs = reconcileForm.querySelectorAll('.reconcile-qty-input');
            inputs.forEach(function(input) {
                if (hasError) return;

                let newQty = parseInt(input.value) || 0;
                let oldQty = parseInt(input.getAttribute('data-old-qty')) || 0;
                let availStock = parseInt(input.getAttribute('data-avail-stock')) || 0;
                let maxAllowed = parseInt(input.getAttribute('data-max-allowed')) || 0;
                let itemName = input.getAttribute('data-item-name') || 'Product';

                if (newQty > maxAllowed) {
                    hasError = true;
                    let additionalNeeded = newQty - oldQty;
                    errorHtml = `<div class="text-start">` +
                        `<p class="mb-2 fs-6">That amount is not available in stock for <strong>${itemName}</strong>!</p>` +
                        `<ul class="list-unstyled mb-3 small bg-light p-3 rounded-3 border">` +
                        `<li>• Current Transfer Qty: <strong>${oldQty} Pcs</strong></li>` +
                        `<li>• Available Stock at Source: <strong>${availStock} Pcs</strong></li>` +
                        `<li>• Requested Quantity: <strong>${newQty} Pcs</strong></li>` +
                        `<li>• Additional Needed: <strong class="text-danger">${additionalNeeded} Pcs</strong></li>` +
                        `</ul>` +
                        `<div class="alert alert-warning border-0 small mb-0 fw-bold">` +
                        `<i class="fas fa-shopping-cart me-2 text-warning"></i>You do not have enough stock available. You need to purchase stock first!</div>` +
                        `</div>`;
                }
            });

            if (hasError) {
                e.preventDefault();
                e.stopPropagation();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Insufficient Stock!',
                        html: errorHtml,
                        customClass: { popup: 'rounded-4' }
                    });
                } else {
                    alert('That amount is not available in stock! You need to purchase stock first.');
                }
                return false;
            }

            if (!confirm('Are you sure you want to apply these reconciled stock changes? This will instantly adjust live inventory.')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
@endpush