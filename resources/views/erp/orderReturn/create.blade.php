@extends('erp.master')

@section('title', 'Create Sale Return')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <h2 class="mb-4">Create Sale Return</h2>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form id="saleReturnForm" action="{{ route('orderReturn.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select name="customer_id" id="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="pos_sale_id" class="form-label">Order Sale</label>
                        <select name="order_id" id="pos_sale_id" class="form-select" required>
                            <option value="">Select Order Sale</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->id }}">Order #{{ $order->id }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="return_date" class="form-label">Return Date</label>
                        <input type="date" name="return_date" id="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="refund_type" class="form-label">Refund Type</label>
                        <select name="refund_type" id="refund_type" class="form-select" required>
                            <option value="none">None</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="return_to_type" class="form-label">Return To</label>
                        <select name="return_to_type" id="return_to_type" class="form-select" required>
                            <option value="">Select Return To</option>
                            <option value="branch">Branch</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="employee">Employee</option>
                        </select>
                        <select name="return_to_id" id="return_to_id" class="form-select mt-2" style="display:none;" required>
                            <option value="">Select Location</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reason" class="form-label">Reason</label>
                    <input type="text" name="reason" id="reason" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Return Items</label>
                    <table class="table table-bordered align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Returned Qty</th>
                                <th>Unit Price</th>
                                <th>Reason</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="items[0][product_id]" class="form-select product-select" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="items[0][order_item_id]" class="sale-item-id">
                                </td>
                                <td><input type="number" name="items[0][returned_qty]" class="form-control returned_qty" min="1" required></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-control unit_price" min="1" required></td>
                                <td><input type="text" name="items[0][reason]" class="form-control"></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row" disabled>&times;</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-secondary btn-sm" id="addItemRow">Add Item</button>
                </div>
                <div class="mb-3 text-end">
                    <button type="submit" class="btn btn-primary">Create Sale Return</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let itemIndex = 1;
        $(document).ready(function() {
            // Initialize Select2 for customer search
            $('#customer_id').select2({
                placeholder: 'Select Customer',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '/erp/customers/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(function(item) {
                                return { id: item.id, text: item.name + (item.email ? ' (' + item.email + ')' : '') };
                            })
                        };
                    },
                    cache: true
                }
            });
            // Initialize Select2 for initial product dropdown
            $('.product-select').select2({
                placeholder: 'Search Product',
                allowClear: true,
                width: '100%'
            });
            // Initialize Select2 for POS Sale search
            $('#pos_sale_id').select2({
                placeholder: 'Select Order Sale',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '/erp/order/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });
            // Add new item row
            $('#addItemRow').on('click', function() {
                const row = `<tr>
                    <td>
                        <select name="items[${itemIndex}][product_id]" class="form-select product-select" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="items[${itemIndex}][order_item_id]" class="sale-item-id">
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
                $(this).closest('tr').remove();
            });
            // When return_to_type changes, populate and show the return_to_id select
            $('#return_to_type').on('change', function() {
                const returnToType = $(this).val();
                const returnToIdSelect = $('#return_to_id');
                returnToIdSelect.hide().prop('required', false).val('').empty();
                if (returnToType === 'branch') {
                    returnToIdSelect.append('<option value="">Select Branch</option>');
                    @foreach ($branches as $branch)
                        returnToIdSelect.append('<option value="{{ $branch->id }}">{{ $branch->name }}</option>');
                    @endforeach
                    returnToIdSelect.show().prop('required', true);
                    returnToIdSelect.select2('destroy');
                } else if (returnToType === 'warehouse') {
                    returnToIdSelect.append('<option value="">Select Warehouse</option>');
                    @foreach ($warehouses as $warehouse)
                        returnToIdSelect.append('<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>');
                    @endforeach
                    returnToIdSelect.show().prop('required', true);
                    returnToIdSelect.select2('destroy');
                } else if (returnToType === 'employee') {
                    returnToIdSelect.append('<option value="">Select Employee</option>');
                    returnToIdSelect.show().prop('required', true);
                    // Initialize AJAX select2 for employee
                    returnToIdSelect.select2({
                        placeholder: 'Select Employee',
                        allowClear: true,
                        width: '100%',
                        ajax: {
                            url: '/erp/employees/search',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    q: params.term
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.map(function(item) {
                                        return { id: item.id, text: item.name + (item.email ? ' (' + item.email + ')' : '') };
                                    })
                                };
                            },
                            cache: true
                        }
                    });
                }
            });
        });
    </script>
@endsection