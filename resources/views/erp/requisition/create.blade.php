@extends('erp.master')

@section('title', 'New Requisition')

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
                            <li class="breadcrumb-item active text-primary fw-600">New Request</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm bg-primary text-white d-flex align-items-center justify-content-center rounded-circle fw-bold">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark">Create Product Request</h4>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <a href="{{ route('requisition.index') }}" class="btn btn-light fw-bold shadow-sm">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
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

            <form action="{{ route('requisition.store') }}" method="POST" id="requisitionForm">
                @csrf
                
                <div class="row">
                    <div class="col-lg-12">
                        <!-- Configuration Card -->
                        <div class="premium-card mb-4">
                            <div class="card-header bg-white border-bottom p-4">
                                <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-info-circle me-2 text-primary"></i>General Information</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Request Date <span class="text-danger">*</span></label>
                                        <input type="date" name="requisition_date" class="form-control shadow-none" value="{{ date('Y-m-d') }}" required>
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
                                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Request To (Warehouse) <span class="text-danger">*</span></label>
                                        @if($warehouses->isEmpty())
                                            <div class="alert alert-warning py-2 mb-0 small fw-bold border-0 shadow-sm">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                No branch warehouse found. Please <a href="{{ route('branches.index') }}" class="alert-link">mark a branch as warehouse</a> first.
                                            </div>
                                            <input type="hidden" name="warehouse_id" value="">
                                        @else
                                            <select name="warehouse_id" class="form-select shadow-none select2-basic" required>
                                                <option value="">Select Warehouse</option>
                                                @foreach($warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Scan/Select Product <span class="text-danger">*</span></label>
                                        <select id="product_search" class="form-select shadow-none">
                                            <option value="">Search by style number or name...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table Card -->
                        <div class="premium-card mb-4">
                            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                                <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-box-open me-2 text-primary"></i>Requested Items</h6>
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
                                                <th style="width: 150px;">Requested Qty</th>
                                                <th class="text-center pe-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemTableBody">
                                            <tr class="empty-placeholder">
                                                <td colspan="7" class="text-center py-5 text-muted opacity-50">
                                                    <i class="fas fa-barcode fa-3x mb-3"></i>
                                                    <p class="fw-bold mb-0">Search products to add to your request list.</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Card -->
                        <div class="premium-card mb-5">
                            <div class="card-body p-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Reason / Additional Notes</label>
                                <textarea name="notes" class="form-control shadow-none" rows="3" placeholder="Explain why these items are needed or add any special instructions..."></textarea>
                            </div>
                        </div>

                        <!-- Form Controls -->
                        <div class="mt-4 pt-4 border-top text-center">
                            <button type="submit" class="btn btn-create-premium px-5 py-3 me-3">
                                <i class="fas fa-paper-plane me-2"></i>SUBMIT REQUISITION
                            </button>
                            <a href="{{ route('requisition.index') }}" class="btn btn-light border fw-bold px-5 py-3">CANCEL</a>
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

            // Product Search
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
                $('.empty-placeholder').hide();
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
                const rowId = variation ? `var_${variation.id}` : `prod_${product.id}`;
                if ($(`#${rowId}`).length > 0) return;

                const displayImage = variation?.image || product.image;
                const imgHtml = displayImage 
                    ? `<img src="/${displayImage}" class="rounded border shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">`
                    : `<div class="bg-light rounded border d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;"><i class="fas fa-image text-muted opacity-50"></i></div>`;

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
                        <td>
                            <span class="badge bg-light text-dark border me-1">${variation?.size || '-'}</span>
                            <span class="badge bg-light text-dark border">${variation?.color || '-'}</span>
                        </td>
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
                if ($('#itemTableBody tr').length === 0) $('.empty-placeholder').show();
            });

            $('#requisitionForm').on('submit', function(e) {
                if ($('#itemTableBody tr:not(.empty-placeholder)').length === 0) {
                    e.preventDefault();
                    alert('Please add at least one product.');
                }
            });
        });
    </script>
@endpush
