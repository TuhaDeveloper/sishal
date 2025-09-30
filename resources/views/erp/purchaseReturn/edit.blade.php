@extends('erp.master')

@section('title', 'Edit Purchase Return')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('purchaseReturn.list') }}" class="text-decoration-none">Purchase Returns</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('purchaseReturn.show', $purchaseReturn->id) }}" class="text-decoration-none">Return #{{ $purchaseReturn->id }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit</li>
                        </ol>
                    </nav>
                    <h2 class="mb-4">Edit Purchase Return #{{ $purchaseReturn->id }}</h2>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('purchaseReturn.show', $purchaseReturn->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Details
                    </a>
                </div>
            </div>
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form id="purchaseReturnForm" action="{{ route('purchaseReturn.update', $purchaseReturn->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="purchase_id" class="form-label">Purchase</label>
                        <select name="purchase_id" id="purchase_id" class="form-select" required>
                            <option value="">Select Purchase</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="return_date" class="form-label">Return Date</label>
                        <input type="date" name="return_date" id="return_date" class="form-control"
                            value="{{ $purchaseReturn->return_date ? $purchaseReturn->return_date->format('Y-m-d') : '' }}" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="return_type" class="form-label">Return Type</label>
                        <select name="return_type" id="return_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="refund" {{ $purchaseReturn->return_type === 'refund' ? 'selected' : '' }}>Refund</option>
                            <option value="adjust_to_due" {{ $purchaseReturn->return_type === 'adjust_to_due' ? 'selected' : '' }}>Adjust to Due</option>
                            <option value="none" {{ $purchaseReturn->return_type === 'none' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label for="reason" class="form-label">Reason</label>
                        <input type="text" name="reason" id="reason" class="form-control" value="{{ $purchaseReturn->reason }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2">{{ $purchaseReturn->notes }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Return Items</label>
                    <table class="table table-bordered align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Purchase Item</th>
                                <th>Return From</th>
                                <th>Returned Qty</th>
                                <th>Unit Price</th>
                                <th>Reason</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseReturn->items as $index => $item)
                            <tr>
                                <td>
                                    <select name="items[{{ $index }}][product_id]" class="form-select product-select" required>
                                        <option value="">Select Product</option>
                                        <!-- Will be populated by JS based on purchase selection -->
                                    </select>
                                    <input type="hidden" name="items[{{ $index }}][purchase_item_id]" class="purchase-item-id" value="{{ $item->purchase_item_id }}">
                                </td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <select name="items[{{ $index }}][return_from]" class="form-select return-from-select" required>
                                            <option value="">Select Return From</option>
                                            <option value="branch" {{ $item->return_from_type === 'branch' ? 'selected' : '' }}>Branch</option>
                                            <option value="warehouse" {{ $item->return_from_type === 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                                        </select>
                                        <select name="items[{{ $index }}][from_id]" class="form-select from-id-select" style="display:none;" required>
                                            <option value="">Select Location</option>
                                        </select>
                                        <span class="stock-info text-muted" style="min-width: 80px;"></span>
                                    </div>
                                </td>
                                <td><input type="number" name="items[{{ $index }}][returned_qty]" class="form-control returned_qty" min="1" value="{{ $item->returned_qty }}" required></td>
                                <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control unit_price" min="1" value="{{ $item->unit_price }}" required></td>
                                <td><input type="text" name="items[{{ $index }}][reason]" class="form-control" value="{{ $item->reason }}"></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row" {{ $purchaseReturn->items->count() === 1 ? 'disabled' : '' }}>&times;</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-secondary btn-sm" id="addItemRow">Add Item</button>
                </div>
                <div class="mb-3 text-end">
                    <a href="{{ route('purchaseReturn.show', $purchaseReturn->id) }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Purchase Return</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let purchaseItems = [];
        let productUnitPriceMap = {};

        function loadPurchaseItems(purchaseId) {
            if (!purchaseId) {
                $('.purchase-item-select').empty().append('<option value="">Select Purchase Item</option>');
                $('.product-select').empty().append('<option value="">Select Product</option>');
                productUnitPriceMap = {};
                return;
            }

            $.ajax({
                url: `/erp/purchase-products/search/${purchaseId}`,
                method: 'GET',
                success: function(response) {
                    purchaseItems = response.results;
                    productUnitPriceMap = {};
                    response.results.forEach(item => {
                        productUnitPriceMap[item.product_id] = item.unit_price;
                    });
                    // Update all purchase-item-select dropdowns
                    $('.purchase-item-select').each(function() {
                        const select = $(this);
                        select.empty();
                        select.append('<option value="">Select Purchase Item</option>');
                        purchaseItems.forEach(item => {
                            select.append(
                                `<option value="${item.id}" data-product-id="${item.product_id}" data-unit-price="${item.unit_price}">${item.text}</option>`
                                );
                        });
                    });
                    // Update all product-select dropdowns with Select2
                    $('.product-select').each(function() {
                        const select = $(this);
                        const currentValue = select.val(); // Store current value
                        select.empty();
                        select.append('<option value="">Select Product</option>');
                        purchaseItems.forEach(item => {
                            select.append(
                                `<option value="${item.product_id}">${item.product_name}</option>`
                                );
                        });
                        // Reinitialize Select2 for this dropdown
                        if (select.hasClass('select2-hidden-accessible')) {
                            select.select2('destroy');
                        }
                        select.select2({
                            placeholder: 'Search Product',
                            allowClear: true,
                            width: '100%'
                        });
                        
                        // Set the value after Select2 is initialized
                        if (currentValue) {
                            select.val(currentValue).trigger('change');
                        }
                    });
                },
                error: function() {
                    console.error('Failed to load purchase items');
                }
            });
        }

        $(document).ready(function() {
            // Initialize supplier select2 with current value
            $('#supplier_id').select2({
                placeholder: 'Select Supplier',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route('supplier.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });

            // Initialize purchase select2 with current value
            $('#purchase_id').select2({
                placeholder: 'Select Purchase',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route('purchase.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            supplier: $('#supplier_id').val()
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });

            // Set initial values for supplier and purchase
            setTimeout(function() {
                // Set supplier value
                const supplierId = '{{ $purchaseReturn->supplier_id }}';
                const supplierName = '{{ $purchaseReturn->supplier->name ?? "" }}';
                if (supplierId && supplierName) {
                    const supplierOption = new Option(supplierName, supplierId, true, true);
                    $('#supplier_id').append(supplierOption).trigger('change');
                }

                // Set purchase value
                const purchaseId = '{{ $purchaseReturn->purchase_id }}';
                const purchaseText = 'Purchase #{{ $purchaseReturn->purchase_id }}';
                if (purchaseId) {
                    const purchaseOption = new Option(purchaseText, purchaseId, true, true);
                    $('#purchase_id').append(purchaseOption).trigger('change');
                }
            }, 100);

            // Initialize Select2 for initial product dropdowns
            $('.product-select').select2({
                placeholder: 'Search Product',
                allowClear: true,
                width: '100%'
            });

            // Store current product values
            const currentProductValues = [];
            @foreach($purchaseReturn->items as $index => $item)
                currentProductValues[{{ $index }}] = '{{ $item->product_id }}';
            @endforeach

            // Load purchase items for current purchase
            loadPurchaseItems('{{ $purchaseReturn->purchase_id }}');

            // When purchase is selected, load purchase items
            $('#purchase_id').on('change', function() {
                const purchaseId = $(this).val();
                loadPurchaseItems(purchaseId);
            });

            // Set product values after purchase items are loaded
            setTimeout(function() {
                currentProductValues.forEach(function(productId, index) {
                    if (productId) {
                        const productSelect = $('select[name="items[' + index + '][product_id]"]');
                        productSelect.val(productId).trigger('change');
                    }
                });
            }, 800);

            // When supplier changes, clear purchase select2
            $('#supplier_id').on('change', function() {
                $('#purchase_id').val(null).trigger('change');
            });

            // When product is selected, auto-fill unit price
            $(document).on('change', '.product-select', function() {
                const productId = $(this).val();
                const row = $(this).closest('tr');
                // Find the purchase item in your purchaseItems array
                const purchaseItem = purchaseItems.find(item => item.product_id == productId);
                if (purchaseItem) {
                    row.find('.purchase-item-id').val(purchaseItem.id);
                    row.find('.unit_price').val(purchaseItem.unit_price);
                } else {
                    row.find('.purchase-item-id').val('');
                    row.find('.unit_price').val('');
                }
            });

            // When purchase item is selected, auto-fill unit price (optional, if you want both ways)
            $(document).on('change', '.purchase-item-select', function() {
                const selectedOption = $(this).find('option:selected');
                const unitPrice = selectedOption.data('unit-price');
                const row = $(this).closest('tr');
                row.find('.unit_price').val(unitPrice);
            });

            // Add new item row
            let itemIndex = {{ $purchaseReturn->items->count() }};
            $('#addItemRow').on('click', function() {
                const row = `<tr>
                    <td>
                        <select name="items[${itemIndex}][product_id]" class="form-select product-select" required>
                            <option value="">Select Product</option>
                            ${purchaseItems.map(item => `<option value="${item.product_id}">${item.product_name}</option>`).join('')}
                        </select>
                        <input type="hidden" name="items[${itemIndex}][purchase_item_id]" class="purchase-item-id">
                    </td>
                    <td>
                        <div class="d-flex gap-2 align-items-center">
                            <select name="items[${itemIndex}][return_from]" class="form-select return-from-select" required>
                                <option value="">Select Return From</option>
                                <option value="branch">Branch</option>
                                <option value="warehouse">Warehouse</option>
                            </select>
                            <select name="items[${itemIndex}][from_id]" class="form-select from-id-select" style="display:none;" required>
                                <option value="">Select Location</option>
                            </select>
                            <span class="stock-info text-muted" style="min-width: 80px;"></span>
                        </div>
                    </td>
                    <td><input type="number" name="items[${itemIndex}][returned_qty]" class="form-control returned_qty" min="1" required></td>
                    <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control unit_price" min="1" required></td>
                    <td><input type="text" name="items[${itemIndex}][reason]" class="form-control"></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
                </tr>`;
                $('#itemsTable tbody').append(row);
                // Initialize Select2 for the new product dropdown
                const newRow = $('#itemsTable tbody tr:last');
                newRow.find('.product-select').select2({
                    placeholder: 'Search Product',
                    allowClear: true,
                    width: '100%'
                });
                itemIndex++;
            });

            // Remove item row
            $('#itemsTable').on('click', '.remove-row', function() {
                if ($('#itemsTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });

            // When return_from changes, populate and show the from_id select
            $(document).on('change', '.return-from-select', function() {
                const row = $(this).closest('tr');
                const returnFrom = $(this).val();
                const fromIdSelect = row.find('.from-id-select');
                
                fromIdSelect.hide().prop('required', false).val('').empty();
                row.find('.stock-info').text('');
                
                if (returnFrom === 'branch') {
                    fromIdSelect.append('<option value="">Select Branch</option>');
                    @foreach ($branches as $branch)
                        fromIdSelect.append('<option value="{{ $branch->id }}">{{ $branch->name }}</option>');
                    @endforeach
                    fromIdSelect.show().prop('required', true);
                } else if (returnFrom === 'warehouse') {
                    fromIdSelect.append('<option value="">Select Warehouse</option>');
                    @foreach ($warehouses as $warehouse)
                        fromIdSelect.append('<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>');
                    @endforeach
                    fromIdSelect.show().prop('required', true);
                }
            });

            // When product or from_id changes, fetch and show stock
            $(document).on('change', '.product-select, .from-id-select, .return-from-select',
                function() {
                    const row = $(this).closest('tr');
                    const productId = row.find('.product-select').val();
                    const returnFrom = row.find('.return-from-select').val();
                    const fromId = row.find('.from-id-select').val();
                    
                    if (productId && fromId && returnFrom) {
                        $.ajax({
                            url: `/erp/purchase-return/searchbytype/${productId}/${fromId}`,
                            method: 'GET',
                            data: {
                                return_from: returnFrom
                            },
                            success: function(stock) {
                                let qty = stock && stock.quantity ? stock.quantity : 0;
                                row.find('.stock-info').text(`Stock: ${qty}`);
                            },
                            error: function() {
                                row.find('.stock-info').text('Stock: 0');
                            }
                        });
                    } else {
                        row.find('.stock-info').text('');
                    }
                });

            // Initialize existing return from selects
            $('.return-from-select').each(function() {
                const row = $(this).closest('tr');
                const returnFromType = $(this).val();
                
                // Trigger change to populate the from_id select
                $(this).trigger('change');
            });

            // Set from_id values after a delay to ensure options are populated
            setTimeout(function() {
                @foreach($purchaseReturn->items as $index => $item)
                    const row{{ $index }} = $('select[name="items[{{ $index }}][return_from]"]').closest('tr');
                    const fromId{{ $index }} = '{{ $item->return_from_id }}';
                    if (fromId{{ $index }}) {
                        row{{ $index }}.find('.from-id-select').val(fromId{{ $index }});
                    }
                @endforeach
            }, 300);
        });
    </script>
@endsection 