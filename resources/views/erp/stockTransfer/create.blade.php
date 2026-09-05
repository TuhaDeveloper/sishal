@extends('erp.master')

@section('title', isset($originalTransfer) ? 'Transfer Return' : 'Record Stock Transfer')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')

        <!-- Premium Header -->
        <div class="glass-header">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 breadcrumb-premium">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('stocktransfer.list') }}" class="text-decoration-none text-muted">Stock Transfer</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">
                                {{ isset($originalTransfer) ? 'Return Items' : 'New Dispatch' }}
                            </li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm {{ isset($originalTransfer) ? 'bg-warning' : 'bg-primary' }} text-white d-flex align-items-center justify-content-center rounded-circle fw-bold">
                            <i class="fas {{ isset($originalTransfer) ? 'fa-undo-alt' : 'fa-truck-loading' }}"></i>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark">
                            {{ isset($originalTransfer) ? 'Return Transferred Stock' : 'Initiate Stock Transfer' }}
                        </h4>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-column flex-md-row justify-content-md-end gap-2 align-items-md-center">
                    @if(isset($originalTransfer))
                        <a href="{{ route('stocktransfer.show', $originalTransfer->id) }}" class="btn btn-light fw-bold shadow-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back to Invoice
                        </a>
                    @else
                        <a href="{{ route('stocktransfer.list') }}" class="btn btn-light fw-bold shadow-sm">
                            <i class="fas fa-arrow-left me-2"></i>Transfer History
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                </div>
            @endif

            {{-- Return Notice Banner --}}
            @if(isset($originalTransfer))
            <div class="alert border-0 shadow-sm mb-4 d-flex align-items-start gap-3" style="background: linear-gradient(135deg, #fff3cd, #ffe8a0); border-left: 5px solid #f0a500 !important;">
                <div class="flex-shrink-0 mt-1">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background-color:#f0a500;">
                        <i class="fas fa-undo-alt text-white"></i>
                    </div>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-dark">📦 Stock Return — Ref: {{ $originalTransfer->invoice_number ?? 'TRF-'.$originalTransfer->id }}</h6>
                    <p class="mb-0 small text-dark">
                        You are returning leftover items from a previous branch transfer back to the source location. 
                        The <strong>Source</strong> (returning from) and <strong>Destination</strong> (returning to) have been pre-filled and locked. 
                        Adjust quantities to match what is actually being returned.
                    </p>
                </div>
            </div>
            @endif

            <form action="{{ route('stocktransfer.store') }}" method="POST" id="transferForm">
                @csrf

                {{-- Hidden field for return linkage --}}
                @if(isset($originalTransfer))
                    <input type="hidden" name="return_of_id" value="{{ $originalTransfer->id }}">
                @endif
                
                <!-- Main Configuration Card -->
                <div class="premium-card mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-info-circle me-2 text-primary"></i>Transfer Configuration</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" name="transfer_date" class="form-control shadow-none" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">
                                    {{ isset($originalTransfer) ? 'Return From (Source)' : 'Sender Outlet' }}
                                    <span class="text-danger">*</span>
                                </label>

                                @if($restrictedBranchId && !isset($originalTransfer))
                                    {{-- Branch user: can only send FROM their own branch --}}
                                    @php
                                        $myBranch = $branches->firstWhere('id', $restrictedBranchId);
                                    @endphp
                                    @if($myBranch)
                                        <input type="text"
                                               class="form-control shadow-none bg-light fw-bold"
                                               value="{{ $myBranch->name }}"
                                               readonly>
                                        <input type="hidden" id="from_outlet" name="from_outlet" value="branch_{{ $myBranch->id }}">
                                        <div class="extra-small text-muted mt-1">
                                            <i class="fas fa-lock me-1"></i>You can only dispatch from your own branch.
                                        </div>
                                    @else
                                        <div class="alert alert-danger py-2 small">Your branch was not found. Contact admin.</div>
                                    @endif
                                @else
                                    {{-- Super admin OR return mode: see all locations --}}
                                    <select name="from_outlet" id="from_outlet" class="form-select shadow-none select2-basic {{ isset($originalTransfer) ? 'bg-light' : '' }}" required {{ isset($originalTransfer) ? 'disabled' : '' }}>
                                        <option value="">Select Source Location</option>
                                        <optgroup label="Warehouses">
                                            @foreach($warehouses as $warehouse)
                                                <option value="warehouse_{{ $warehouse->id }}" {{ (isset($fromOutlet) && $fromOutlet == 'warehouse_'.$warehouse->id) ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Warehouse Branches">
                                            @foreach($branches->where('is_warehouse', true) as $branch)
                                                <option value="branch_{{ $branch->id }}" {{ (isset($fromOutlet) && $fromOutlet == 'branch_'.$branch->id) ? 'selected' : '' }}>{{ $branch->name }} (Warehouse)</option>
                                            @endforeach
                                        </optgroup>
                                        @if(isset($originalTransfer))
                                        <optgroup label="Branches">
                                            @foreach($branches->where('is_warehouse', false) as $branch)
                                                <option value="branch_{{ $branch->id }}"
                                                    {{ (isset($fromOutlet) && $fromOutlet == 'branch_'.$branch->id) ? 'selected' : '' }}>
                                                    {{ $branch->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        @endif
                                    </select>
                                    {{-- Mirror disabled select as hidden input so it submits --}}
                                    @if(isset($originalTransfer))
                                        <input type="hidden" name="from_outlet" value="{{ $fromOutlet ?? '' }}">
                                    @endif
                                @endif
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">
                                    {{ isset($originalTransfer) ? 'Return To (Destination/Original Source)' : 'Receiver Outlet' }}
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="to_outlet" id="to_outlet" class="form-select shadow-none select2-basic {{ isset($originalTransfer) ? 'bg-light' : '' }}" required {{ isset($originalTransfer) ? 'disabled' : '' }}>
                                    <option value="">Select Target Destination</option>
                                    <optgroup label="Branches">
                                        @foreach($branches as $branch)
                                            <option value="branch_{{ $branch->id }}" {{ (isset($toOutlet) && $toOutlet == 'branch_'.$branch->id) ? 'selected' : '' }}>{{ $branch->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    @if(isset($originalTransfer))
                                    <optgroup label="Warehouses">
                                        @foreach($warehouses as $warehouse)
                                            <option value="warehouse_{{ $warehouse->id }}" {{ (isset($toOutlet) && $toOutlet == 'warehouse_'.$warehouse->id) ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endif
                                </select>
                                @if(isset($originalTransfer))
                                    <input type="hidden" name="to_outlet" value="{{ $toOutlet ?? '' }}">
                                @endif
                            </div>

                            @if(!isset($originalTransfer))
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Scan/Select Style Number <span class="text-danger">*</span></label>
                                <select name="style_number" id="style_number" class="form-select shadow-none" disabled>
                                    <option value="">Select Source First...</option>
                                </select>
                            </div>
                            @endif

                            <div class="col-md-12">
                                <div class="p-3 bg-light rounded-3 border d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;">
                                        <i class="fas fa-truck-loading"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small">Dispatch & Receipt Workflow</div>
                                        <div class="text-muted" style="font-size: 0.8rem;">Stock will be deducted from source location upon creating this transfer. The destination branch will inspect items and click <strong>Confirm Receipt</strong> to add to their stock.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table Card -->
                <div class="premium-card mb-4">
                    <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-box-open me-2 text-primary"></i>
                            {{ isset($originalTransfer) ? 'Items to Return' : 'Allocated Items' }}
                        </h6>
                        @if(isset($originalTransfer))
                        <span class="badge bg-warning text-dark px-3 py-2 small fw-bold">
                            <i class="fas fa-lock me-1"></i>Products locked — adjust quantities only
                        </span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table premium-table align-middle mb-0 compact" id="productTable">
                                <thead>
                                    <tr>
                                        <th class="ps-3" style="width: 50px;">Media</th>
                                        <th>Product Details</th>
                                        <th>Style No</th>
                                        <th>Variant</th>
                                        <th>Attributes</th>
                                        <th class="text-center">Avail.</th>
                                        <th style="width: 130px;">
                                            {{ isset($originalTransfer) ? 'Return Qty' : 'Transfer Qty' }}
                                        </th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total Price</th>
                                        <th class="text-center pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody">
                                    @if(!isset($items) || $items->isEmpty())
                                    <tr class="empty-placeholder">
                                        <td colspan="10" class="text-center py-5">
                                            <div class="text-muted opacity-50">
                                                <i class="fas fa-barcode fa-3x mb-3"></i>
                                                <p class="fw-bold mb-0">Scan or select a style number to build the dispatch list.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @else
                                    {{-- Pre-filled rows for return --}}
                                    @foreach($items as $item)
                                    @php
                                        $product = $item->product;
                                        if (!$product) continue;
                                        $variation = $item->variation;
                                        $rowId = $variation ? "var_{$variation->id}" : "prod_{$product->id}";
                                        $unitPrice = $item->unit_price;
                                        $originalQty = $item->quantity;
                                        $displayImage = ($variation && $variation->image) ? $variation->image : ($product->image ?? '');
                                        
                                        // Get size/color from variation combinations
                                        $size = '-'; $color = '-';
                                        if ($variation && $variation->combinations) {
                                            foreach($variation->combinations as $combo) {
                                                $attrName = strtolower($combo->attribute->name ?? '');
                                                if (in_array($attrName, ['color','colour'])) $color = $combo->attributeValue->value ?? '-';
                                                if (in_array($attrName, ['size','sizes'])) $size = $combo->attributeValue->value ?? '-';
                                            }
                                        }

                                        // Available stock at return-from location (original to_type/to_id)
                                        $availStock = 0;
                                        if ($variation) {
                                            if ($item->to_type === 'branch') {
                                                $vs = \App\Models\ProductVariationStock::where('variation_id', $variation->id)->where('branch_id', $item->to_id)->whereNull('warehouse_id')->first();
                                            } else {
                                                $vs = \App\Models\ProductVariationStock::where('variation_id', $variation->id)->where('warehouse_id', $item->to_id)->whereNull('branch_id')->first();
                                            }
                                            $availStock = $vs ? $vs->quantity : 0;
                                        } else {
                                            if ($item->to_type === 'branch') {
                                                $bs = \App\Models\BranchProductStock::where('product_id', $product->id)->where('branch_id', $item->to_id)->first();
                                            } else {
                                                $bs = \App\Models\WarehouseProductStock::where('product_id', $product->id)->where('warehouse_id', $item->to_id)->first();
                                            }
                                            $availStock = $bs ? $bs->quantity : 0;
                                        }
                                        $maxReturnQty = min($originalQty, $availStock);
                                    @endphp
                                    <tr id="{{ $rowId }}" class="item-row">
                                        <td class="ps-3">
                                            @if($displayImage)
                                                <img src="/{{ $displayImage }}" class="rounded border shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">
                                            @else
                                                <div class="bg-light rounded border d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                    <i class="fas fa-image text-muted opacity-50"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $product->name ?? '-' }}</div>
                                            <div class="extra-small text-muted text-uppercase">{{ $product->category->name ?? 'General' }}</div>
                                        </td>
                                        <td class="text-pink fw-bold">{{ $product->style_number ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border me-1">{{ $size }}</span>
                                            <span class="badge bg-light text-dark border">{{ $color }}</span>
                                        </td>
                                        <td class="extra-small text-muted">
                                            {{ $product->brand->name ?? '-' }} | {{ $product->season->name ?? '-' }}
                                        </td>
                                        <td class="text-center fw-bold">
                                            <span class="{{ $availStock < $originalQty ? 'text-danger' : 'text-success' }}">
                                                {{ $availStock }}
                                            </span>
                                            <div class="extra-small text-muted">(orig: {{ $originalQty }})</div>
                                        </td>
                                        <td>
                                            <input type="number"
                                                   class="form-control form-control-sm transfer-qty shadow-none border-warning"
                                                   data-row-id="{{ $rowId }}"
                                                   data-price="{{ $unitPrice }}"
                                                   min="0" step="1"
                                                   max="{{ $maxReturnQty }}"
                                                   value="{{ $maxReturnQty }}">
                                            <input type="hidden" name="items[{{ $rowId }}][quantity]" value="{{ $maxReturnQty }}">
                                            <input type="hidden" name="items[{{ $rowId }}][product_id]" value="{{ $product->id }}">
                                            <input type="hidden" name="items[{{ $rowId }}][variation_id]" value="{{ $variation ? $variation->id : '' }}">
                                            <input type="hidden" name="items[{{ $rowId }}][unit_price]" value="{{ $unitPrice }}">
                                            @if($maxReturnQty < $originalQty)
                                            <div class="extra-small text-danger mt-1">⚠ Only {{ $availStock }} at branch</div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($unitPrice, 2) }}৳</td>
                                        <td class="text-end fw-bold total-price-col" id="total_price_{{ $rowId }}" data-value="{{ number_format($maxReturnQty * $unitPrice, 2, '.', '') }}">{{ number_format($maxReturnQty * $unitPrice, 2) }}৳</td>
                                        <td class="pe-3 text-center">
                                            <button type="button" class="btn btn-sm btn-light border-0 action-circle remove-row" data-row-id="{{ $rowId }}">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 p-3">
                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="premium-card bg-white shadow-none border mb-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 text-dark">
                                            <span class="small fw-bold text-muted text-uppercase">Subtotal Items Value</span>
                                            <span class="fw-bold" id="display_total">0.00৳</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-0">
                                            <span class="small fw-bold text-muted text-uppercase">Allocated Qty Balance</span>
                                            <span class="fw-bold text-primary" id="display_qty">0</span>
                                        </div>
                                        <input type="hidden" id="total_amount" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial & Logistics Card -->
                <div class="premium-card mb-5">
                    <div class="card-header bg-white border-bottom p-4">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Consignment Note / Instructions</label>
                                <textarea name="note" class="form-control shadow-none" rows="3" placeholder="{{ isset($originalTransfer) ? 'Return of items from '.($originalTransfer->invoice_number ?? 'transfer') : 'Enter any specific shipping or handling instructions...' }}"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Controls -->
                <div class="mt-5 pt-4 border-top text-center">
                    <button type="submit" class="btn {{ isset($originalTransfer) ? 'btn-warning' : 'btn-create-premium' }} px-5 py-3 me-3">
                        <i class="fas {{ isset($originalTransfer) ? 'fa-undo-alt' : 'fa-check-circle' }} me-2"></i>
                        {{ isset($originalTransfer) ? 'CONFIRM STOCK RETURN' : 'FINALIZE TRANSFER DISPATCH' }}
                    </button>
                    <a href="{{ isset($originalTransfer) ? route('stocktransfer.show', $originalTransfer->id) : route('stocktransfer.list') }}" class="btn btn-light border fw-bold px-5 py-3">
                        CANCEL
                    </a>
                </div>
            </form>
        </div>
    </div>

@push('css')
    <style>
        .breadcrumb-premium { font-size: 0.8rem; }
        .form-control-sm, .form-select-sm { font-size: 0.75rem !important; }
        .extra-small { font-size: 0.72rem; }
    </style>
@endpush

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2-basic').select2();

            @if(!isset($originalTransfer))
            // --- Style number search (only for new transfers) ---
            $('#style_number').select2({
                placeholder: 'Scan or search style number...',
                ajax: {
                    url: '/erp/products/search-by-style',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return {
                            results: data.map(function(item) {
                                return { 
                                    id: item.id, 
                                    text: item.style_number ? (item.style_number + ' - ' + item.name) : item.name, 
                                    product: item,
                                    style_number: item.style_number,
                                    name: item.name
                                };
                            })
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (!data.id) return data.text;
                    var styleNum = data.style_number ? `<span class="badge bg-primary me-2">${data.style_number}</span>` : '';
                    return $(`<div>${styleNum}<strong>${data.name}</strong></div>`);
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });

            $('#style_number').on('select2:select', function(e) {
                const selectedData = e.params.data;
                if (selectedData && selectedData.product) {
                    $('.empty-placeholder').hide();
                    loadProductVariations(selectedData.product);
                    
                    // Reset the selection for the next scan
                    $(this).val(null).trigger('change');
                }
            });

            // Clear table when sender changes to prevent stock mismatch
            $('#from_outlet').on('change', function() {
                if ($('#productTableBody tr:not(.empty-placeholder)').length > 0) {
                    if(confirm('Changing the sender will clear the current item list. Continue?')) {
                        resetTable();
                    }
                }
            });

            function resetTable() {
                $('#productTableBody').html(`
                    <tr class="empty-placeholder">
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted opacity-50">
                                <i class="fas fa-barcode fa-3x mb-3"></i>
                                <p class="fw-bold mb-0">Scan or select a style number to build the dispatch list.</p>
                            </div>
                        </td>
                    </tr>
                `);
                updateTotals();
                $('#style_number').val(null).trigger('change');
            }

            function loadProductVariations(product) {
                const fromOutlet = $('#from_outlet').val();
                let queryParams = '';
                if (fromOutlet) {
                    const parts = fromOutlet.split('_');
                    if (parts.length === 2) {
                        queryParams = `?location_type=${parts[0]}&location_id=${parts[1]}`;
                    }
                }
                $.ajax({
                    url: '/erp/products/' + product.id + '/variations-with-stock' + queryParams,
                    type: 'GET',
                    success: function(variations) {
                        if (variations && variations.length > 0) {
                            variations.forEach(function(variation) { addProductRow(product, variation); });
                        } else {
                            addProductRow(product, null);
                        }
                    },
                    error: function() { alert('Error loading product variations'); }
                });
            }

            function addProductRow(product, variation) {
                const rowId = (variation && variation.id) ? `var_${variation.id}` : `prod_${product.id}`;
                if ($(`#${rowId}`).length > 0) return;

                const stock = variation ? (variation.stock || 0) : (product.stock || 0);
                const unitPrice = variation ? (variation.cost && variation.cost > 0 ? variation.cost : (product.cost || 0)) : (product.cost || 0);
                const displayImage = (variation && variation.image) ? variation.image : (product.image || '');
                const imgHtml = displayImage 
                    ? `<img src="/${displayImage}" class="rounded border shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">`
                    : `<div class="bg-light rounded border d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;"><i class="fas fa-image text-muted opacity-50"></i></div>`;

                const row = `
                    <tr id="${rowId}" class="item-row">
                        <td class="ps-3">${imgHtml}</td>
                        <td>
                            <div class="fw-bold text-dark">${product.name}</div>
                            <div class="extra-small text-muted text-uppercase">${product.category?.name || 'General'}</div>
                        </td>
                        <td class="text-pink fw-bold">${product.style_number || product.sku || '-'}</td>
                        <td>
                            <span class="badge bg-light text-dark border me-1">${variation && variation.size ? variation.size : '-'}</span>
                            <span class="badge bg-light text-dark border">${variation && variation.color ? variation.color : '-'}</span>
                        </td>
                        <td class="extra-small text-muted">
                            ${product.brand?.name || '-'} | ${product.season?.name || '-'}
                        </td>
                        <td class="text-center fw-bold text-muted">${stock}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm transfer-qty shadow-none border-info" 
                                   data-row-id="${rowId}" data-price="${unitPrice}" 
                                   min="0" step="1" max="${stock}" value="0">
                            <input type="hidden" name="items[${rowId}][quantity]" value="0">
                            <input type="hidden" name="items[${rowId}][product_id]" value="${product.id}">
                            <input type="hidden" name="items[${rowId}][variation_id]" value="${(variation && variation.id) ? variation.id : ''}">
                            <input type="hidden" name="items[${rowId}][unit_price]" value="${unitPrice}">
                        </td>
                        <td class="text-end fw-bold">${parseFloat(unitPrice).toFixed(2)}৳</td>
                        <td class="text-end fw-bold total-price-col" id="total_price_${rowId}" data-value="0">0.00৳</td>
                        <td class="pe-3 text-center">
                            <button type="button" class="btn btn-sm btn-light border-0 action-circle remove-row" data-row-id="${rowId}">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#productTableBody').append(row);
            }
            @endif

            // --- Shared: quantity change handler ---
            $(document).on('input', '.transfer-qty', function() {
                const rowId = $(this).data('row-id');
                let qty = parseFloat($(this).val()) || 0;
                const maxStock = parseFloat($(this).attr('max')) || 0;
                const price = $(this).data('price') ? parseFloat($(this).data('price')) : 0;
                
                if (qty > maxStock) {
                    alert(`Exceeds available stock (${maxStock})`);
                    qty = maxStock;
                    $(this).val(maxStock);
                }
                
                const total = (qty * price).toFixed(2);
                $(`#total_price_${rowId}`).text(parseFloat(total).toLocaleString('en-BD', {minimumFractionDigits:2}) + '৳').attr('data-value', total);
                
                $(`input[name="items[${rowId}][quantity]"]`).val(qty);
                
                updateTotals();
            });

            function updateTotals() {
                let totalAmount = 0;
                let totalQty = 0;
                
                $('.total-price-col').each(function() {
                    const val = parseFloat($(this).attr('data-value')) || 0;
                    totalAmount += val;
                });
                
                $('.transfer-qty').each(function() {
                    totalQty += parseFloat($(this).val()) || 0;
                });
                
                $('#total_amount').val(totalAmount);
                $('#display_total').text(totalAmount.toFixed(2) + '৳');
                $('#display_qty').text(totalQty);
            }

            $('#transferForm').on('submit', function(e) {
                if ($('#productTableBody tr:not(.empty-placeholder)').length === 0) {
                    e.preventDefault(); alert('Please add products'); return false;
                }
            });

            $('#from_outlet').on('change', function() {
                const val = $(this).val();
                
                // Always clear items when source changes to prevent stock mismatches
                $('#productTableBody').empty().append('<tr class="empty-placeholder"><td colspan="20" class="text-center py-5 text-muted">Select an outlet and add products to start...</td></tr>');
                updateTotals();

                if (val) {
                    $('#style_number').prop('disabled', false);
                    $('#style_number').next('.select2-container').find('.select2-selection').css('background-color', '');
                } else {
                    $('#style_number').prop('disabled', true);
                }

                // Prevent same-location transfer
                const fromVal = $(this).val();
                const toVal = $('#to_outlet').val();
                if (fromVal && toVal && fromVal === toVal) {
                    alert('Source and Destination cannot be the same location.');
                    $('#to_outlet').val('').trigger('change');
                }
            });

            $('#to_outlet').on('change', function() {
                // Prevent same-location transfer
                const fromVal = $('#from_outlet').val();
                const toVal = $(this).val();
                if (fromVal && toVal && fromVal === toVal) {
                    alert('Source and Destination cannot be the same location.');
                    $(this).val('').trigger('change');
                }
            });

            // Run once on load
            if ($('#from_outlet').val()) {
                $('#style_number').prop('disabled', false);
            }

            $(document).on('click', '.remove-row', function() {
                const rowId = $(this).data('row-id');
                $(`#${rowId}`).remove();
                if($('#productTableBody tr').length === 0 || $('#productTableBody tr:not(.empty-placeholder)').length === 0) {
                    $('#productTableBody').html(`
                        <tr class="empty-placeholder">
                            <td colspan="10" class="text-center py-5">
                                <div class="text-muted opacity-50">
                                    <i class="fas fa-barcode fa-3x mb-3"></i>
                                    <p class="fw-bold mb-0">Scan or select a style number to build the dispatch list.</p>
                                </div>
                            </td>
                        </tr>
                    `);
                }
                updateTotals();
            });

            // Initialize totals on page load (important for pre-filled return rows)
            updateTotals();
        });
    </script>
@endpush
@endsection
