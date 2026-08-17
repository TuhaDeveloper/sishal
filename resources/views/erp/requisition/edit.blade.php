@extends('erp.master')

@section('title', 'Edit Requisition - ' . $requisition->requisition_number)

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
                            <li class="breadcrumb-item"><a href="{{ route('requisition.index') }}" class="text-decoration-none text-muted">Requisitions</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">Edit {{ $requisition->requisition_number }}</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm bg-warning text-dark d-flex align-items-center justify-content-center rounded-circle fw-bold">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark">Edit Requisition {{ $requisition->requisition_number }}</h4>
                            <div class="small text-muted">Created by {{ optional($requisition->creator)->name ?? '—' }} on {{ \Carbon\Carbon::parse($requisition->requisition_date)->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <a href="{{ route('requisition.show', $requisition->id) }}" class="btn btn-light fw-bold shadow-sm me-2">
                        <i class="fas fa-eye me-1"></i>View Details
                    </a>
                    <a href="{{ route('requisition.index') }}" class="btn btn-light fw-bold shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                </div>
            @endif

            <form action="{{ route('requisition.update', $requisition->id) }}" method="POST" id="requisitionForm">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-12">
                        <!-- Info Card -->
                        <div class="premium-card mb-4">
                            <div class="card-header bg-white border-bottom p-4">
                                <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-info-circle me-2 text-primary"></i>General Information</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Request Date <span class="text-danger">*</span></label>
                                        <input type="date" name="requisition_date" class="form-control shadow-none" value="{{ $requisition->requisition_date }}" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Request From (Branch) <span class="text-danger">*</span></label>
                                        @if($restrictedBranchId)
                                            @php $myBranch = $branches->firstWhere('id', $restrictedBranchId); @endphp
                                            <input type="text" class="form-control shadow-none bg-light fw-bold" value="{{ $myBranch->name ?? 'My Branch' }}" readonly>
                                            <input type="hidden" name="branch_id" value="{{ $restrictedBranchId }}">
                                        @else
                                            <select name="branch_id" class="form-select shadow-none select2-basic" required>
                                                <option value="">Select Branch</option>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}" {{ $requisition->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Request To (Warehouse) <span class="text-danger">*</span></label>
                                        <select name="warehouse_id" class="form-select shadow-none select2-basic" required>
                                            <option value="">Select Warehouse</option>
                                            @foreach($warehouses as $wh)
                                                <option value="{{ $wh->id }}" {{ $requisition->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Add More Products</label>
                                        <select id="product_search" class="form-select shadow-none">
                                            <option value="">Scan or search style number to add new items...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Card -->
                        <div class="premium-card mb-4">
                            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                                <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-box-open me-2 text-warning"></i>Requested Items</h6>
                                <span class="badge bg-warning text-dark">Edit Mode — All items will be replaced on save</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table premium-table align-middle mb-0 compact" id="itemTable">
                                        <thead>
                                            <tr>
                                                <th class="ps-3" style="width: 50px;">Media</th>
                                                <th>Product Details</th>
                                                <th>Style No</th>
                                                <th>Variant</th>
                                                <th style="width: 140px;" class="text-center">Available Stock</th>
                                                <th style="width: 150px;">Quantity</th>
                                                <th class="text-center pe-3">Remove</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemTableBody">
                                            {{-- Pre-load existing items --}}
                                            @foreach($requisition->items as $idx => $item)
                                                @php
                                                    $comboParts = [];
                                                    if ($item->variation && $item->variation->combinations) {
                                                        foreach ($item->variation->combinations as $combo) {
                                                            $val = $combo->attributeValue->value ?? null;
                                                            if ($val) $comboParts[] = $val;
                                                        }
                                                    }
                                                    $varLabel = implode(' / ', $comboParts) ?: ($item->variation->name ?? null);
                                                    $img = ($item->variation && $item->variation->image) ? $item->variation->image : $item->product->image;
                                                    $rowId = 'existing_' . $item->id;
                                                    $stock = $item->variation ? $item->variation->stocks->sum('quantity') : (($item->product->branchStock->sum('quantity') ?? 0) + ($item->product->warehouseStock->sum('quantity') ?? 0));
                                                @endphp
                                                <tr id="{{ $rowId }}">
                                                    <td class="ps-3">
                                                        @if($img)
                                                            <img src="/{{ $img }}" class="rounded border shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">
                                                        @else
                                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;"><i class="fas fa-image text-muted opacity-50"></i></div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark">{{ $item->product->name }}</div>
                                                        <div class="extra-small text-muted text-uppercase">{{ $item->product->category->name ?? 'General' }}</div>
                                                    </td>
                                                    <td class="text-pink fw-bold">{{ $item->product->style_number ?? $item->product->sku }}</td>
                                                    <td>
                                                        @if($varLabel)
                                                            <span class="badge bg-light text-dark border px-2">{{ $varLabel }}</span>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge {{ $stock > 0 ? 'bg-info-subtle text-info border-info-subtle' : 'bg-warning-subtle text-warning border-warning-subtle' }} border px-2 py-1 fw-bold">
                                                            <i class="fas fa-cubes me-1"></i>{{ (float)$stock }} pcs
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="items[{{ $rowId }}][quantity]" class="form-control form-control-sm shadow-none border-primary" min="1" step="1" value="{{ (int) $item->quantity }}" required>
                                                        <input type="hidden" name="items[{{ $rowId }}][product_id]" value="{{ $item->product_id }}">
                                                        <input type="hidden" name="items[{{ $rowId }}][variation_id]" value="{{ $item->variation_id ?? '' }}">
                                                    </td>
                                                    <td class="text-center pe-3">
                                                        <button type="button" class="btn btn-sm btn-light border-0 action-circle remove-row" data-id="{{ $rowId }}">
                                                            <i class="fas fa-trash text-danger"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="premium-card mb-5">
                            <div class="card-body p-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Reason / Additional Notes</label>
                                <textarea name="notes" class="form-control shadow-none" rows="3">{{ $requisition->notes }}</textarea>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 pt-4 border-top text-center">
                            <button type="submit" class="btn btn-warning px-5 py-3 me-3 fw-bold text-dark">
                                <i class="fas fa-save me-2"></i>SAVE CHANGES
                            </button>
                            <a href="{{ route('requisition.show', $requisition->id) }}" class="btn btn-light border fw-bold px-5 py-3">CANCEL</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single { height: 45px; display: flex; align-items: center; border-color: #dee2e6; border-radius: 8px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 45px; }
        .extra-small { font-size: 0.72rem; }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-basic').select2();
            let newItemIndex = 1000;

            $('#product_search').select2({
                placeholder: 'Scan or search style number...',
                ajax: {
                    url: '{{ route("products.search.by.style") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: (item.style_number || item.sku) + ' - ' + item.name,
                                    product: item
                                };
                            })
                        };
                    },
                    cache: true
                }
            });

            $('#product_search').on('select2:select', function(e) {
                const product = e.params.data.product;
                loadVariations(product);
                $(this).val(null).trigger('change');
            });

            function loadVariations(product) {
                const warehouseId = $('select[name="warehouse_id"]').val();
                const branchId = $('select[name="branch_id"]').val();
                let queryParams = '';
                if (warehouseId) {
                    queryParams = `?location_type=warehouse&location_id=${warehouseId}`;
                } else if (branchId) {
                    queryParams = `?location_type=branch&location_id=${branchId}`;
                }

                $.ajax({
                    url: `/erp/products/${product.id}/variations-with-stock${queryParams}`,
                    type: 'GET',
                    success: function(variations) {
                        if (variations && variations.length > 0) {
                            variations.forEach(v => addRow(product, v));
                        } else {
                            addRow(product, null);
                        }
                    }
                });
            }

            function addRow(product, variation) {
                const rowId = variation ? `new_var_${variation.id}_${newItemIndex}` : `new_prod_${product.id}_${newItemIndex}`;
                newItemIndex++;

                const displayImage = variation?.image || product.image;
                const imgHtml = displayImage
                    ? `<img src="/${displayImage}" class="rounded border shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">`
                    : `<div class="bg-light rounded border d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;"><i class="fas fa-image text-muted opacity-50"></i></div>`;

                const varLabel = variation?.name || (variation ? 'Variation' : '—');
                const availStock = variation?.stock !== undefined ? variation.stock : (product.stock || 0);
                const stockBadgeClass = availStock > 0 ? 'bg-info-subtle text-info border-info-subtle' : 'bg-warning-subtle text-warning border-warning-subtle';

                const row = `
                    <tr id="${rowId}">
                        <td class="ps-3">${imgHtml}</td>
                        <td>
                            <div class="fw-bold text-dark">${product.name}</div>
                            <div class="extra-small text-muted text-uppercase">${product.category?.name || 'General'}</div>
                        </td>
                        <td class="text-pink fw-bold">${product.style_number || product.sku || '-'}</td>
                        <td><span class="badge bg-light text-dark border px-2">${varLabel}</span></td>
                        <td class="text-center">
                            <span class="badge ${stockBadgeClass} border px-2 py-1 fw-bold">
                                <i class="fas fa-cubes me-1"></i>${availStock} pcs
                            </span>
                        </td>
                        <td>
                            <input type="number" name="items[${rowId}][quantity]" class="form-control form-control-sm shadow-none border-primary" min="1" step="1" value="1" required>
                            <input type="hidden" name="items[${rowId}][product_id]" value="${product.id}">
                            <input type="hidden" name="items[${rowId}][variation_id]" value="${variation?.id || ''}">
                        </td>
                        <td class="text-center pe-3">
                            <button type="button" class="btn btn-sm btn-light border-0 action-circle remove-row" data-id="${rowId}">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#itemTableBody').append(row);
            }

            $(document).on('click', '.remove-row', function() {
                $(`#${$(this).data('id')}`).remove();
            });

            $('#requisitionForm').on('submit', function(e) {
                if ($('#itemTableBody tr').length === 0) {
                    e.preventDefault();
                    alert('Please add at least one product.');
                }
            });
        });
    </script>
@endpush
