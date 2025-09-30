@extends('erp.master')

@section('title', 'Edit Bill')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <h2 class="mb-4">Edit Bill</h2>
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form id="billForm" action="{{ route('bill.update', $bill->id) }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select" required data-selected-id="{{ $bill->supplier_id }}" data-selected-text="{{ $bill->vendor->name ?? '' }}"></select>
                    </div>
                    <div class="col-md-4">
                        <label for="bill_date" class="form-label">Bill Date</label>
                        <input type="date" name="bill_date" id="bill_date" class="form-control" value="{{ $bill->bill_date }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" name="due_date" id="due_date" class="form-control" value="{{ $bill->due_date }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Items</label>
                    <table class="table table-bordered align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Description</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bill->items as $index => $item)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][product_id]" class="form-select product-select" required data-selected-id="{{ $item->product_id }}" data-selected-text="{{ $item->product->name ?? '' }}"></select>
                                    </td>
                                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity" min="0.01" step="0.01" value="{{ $item->quantity }}" required></td>
                                    <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control unit_price" min="0" step="0.01" value="{{ $item->unit_price }}" required></td>
                                    <td class="item-total">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                                    <td><textarea name="items[{{ $index }}][description]" class="form-control" placeholder="Description">{{ $item->description }}</textarea></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-row" @if($loop->first && count($bill->items) == 1) disabled @endif>&times;</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-secondary btn-sm" id="addItemRow">Add Item</button>
                    <div class="d-flex justify-content-end">
                        <table class="table w-25">
                            <tbody>
                                <tr>
                                    <th class="text-end">Subtotal</th>
                                    <td><input type="number" name="subtotal" id="subtotalCell" class="form-control" step="0.01" min="0" value="{{ $bill->items->sum(function($i){return $i->quantity * $i->unit_price;}) }}" required></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Discount</th>
                                    <td><input type="number" name="discount" id="discountCell" class="form-control" step="0.01" min="0" value="{{ $bill->items->sum('discount') }}" required></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Total</th>
                                    <td><input type="number" name="total_amount" id="total_amount" class="form-control" step="0.01" min="0" value="{{ $bill->total_amount }}" required></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Paid Amount</th>
                                    <td><input type="number" name="paid_amount" id="paid_amount" class="form-control" step="0.01" min="0" value="{{ $bill->paid_amount }}" required></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Due Amount</th>
                                    <td><input type="number" name="due_amount" id="due_amount" class="form-control" step="0.01" min="0" value="{{ $bill->due_amount }}" required></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mb-3 text-end">
                    <button type="submit" class="btn btn-primary">Update Bill</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function initProductSelect2(selector) {
            $(selector).select2({
                placeholder: 'Select Product',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route('products.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        const results = data.map(function (item) {
                            return {
                                id: item.id,
                                text: item.name
                            };
                        });
                        return { results: results };
                    },
                    cache: true
                }
            });
        }
        function setSelect2Value($select, id, text) {
            if (id && text) {
                if ($select.find("option[value='" + id + "']").length === 0) {
                    var newOption = new Option(text, id, true, true);
                    $select.append(newOption).trigger('change');
                } else {
                    $select.val(id).trigger('change');
                }
            }
        }
        $(document).ready(function () {
            // Supplier select2
            let supplierId = $('#supplier_id').data('selected-id');
            let supplierText = $('#supplier_id').data('selected-text');
            $('#supplier_id').select2({
                placeholder: 'Select Supplier',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route('supplier.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data.results };
                    },
                    cache: true
                }
            });
            setSelect2Value($('#supplier_id'), supplierId, supplierText);
            // Product select2 for each item row
            $('#itemsTable tbody tr').each(function () {
                let $select = $(this).find('.product-select');
                let id = $select.data('selected-id');
                let text = $select.data('selected-text');
                initProductSelect2($select);
                setSelect2Value($select, id, text);
            });
            // Add item row logic (same as create)
            let itemIndex = {{ $bill->items->count() }};
            $('#addItemRow').on('click', function () {
                const tbody = $('#itemsTable tbody');
                const row = $(
                    `<tr>
                        <td><select name="items[${itemIndex}][product_id]" class="form-select product-select" required></select></td>
                        <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control quantity" min="0.01" step="0.01" required></td>
                        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control unit_price" min="0" step="0.01" required></td>
                        <td class="item-total">0.00</td>
                        <td><textarea name="items[${itemIndex}][description]" class="form-control" placeholder="Description"></textarea></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
                    </tr>`
                );
                tbody.append(row);
                initProductSelect2(row.find('.product-select'));
                itemIndex++;
            });
            // Remove row logic
            $('#itemsTable').on('click', '.remove-row', function () {
                $(this).closest('tr').remove();
            });
            // Calculation logic (same as create)
            function updateTotals() {
                let subtotal = 0;
                let totalDiscount = 0;
                $('#itemsTable tbody tr').each(function () {
                    const qty = parseFloat($(this).find('.quantity').val()) || 0;
                    const price = parseFloat($(this).find('.unit_price').val()) || 0;
                    let total = (qty * price) ;
                    if (total < 0) total = 0;
                    $(this).find('.item-total').text(total.toFixed(2));
                    subtotal += qty * price;
                });
                const grandTotal = subtotal ;
                $('#subtotalCell').val(subtotal.toFixed(2));
                $('#total_amount').val(grandTotal.toFixed(2));
                // Optionally auto-calculate due_amount
                const paid = parseFloat($('#paid_amount').val()) || 0;
                $('#due_amount').val((grandTotal - paid).toFixed(2));
            }
            $('#itemsTable').on('input', '.quantity, .unit_price', updateTotals);
            $('#paid_amount').on('input', function () {
                const grandTotal = parseFloat($('#total_amount').val()) || 0;
                const paid = parseFloat($(this).val()) || 0;
                $('#due_amount').val((grandTotal - paid).toFixed(2));
            });
            // Bi-directional summary calculation
            function recalcSummary(triggered) {
                let subtotal = parseFloat($('#subtotalCell').val()) || 0;
                let total = parseFloat($('#total_amount').val()) || 0;
                let paid = parseFloat($('#paid_amount').val()) || 0;
                let due = parseFloat($('#due_amount').val()) || 0;
                if (triggered === 'subtotal' || triggered === 'discount') {
                    total = subtotal ;
                    $('#total_amount').val(total.toFixed(2));
                    due = total - paid;
                    $('#due_amount').val(due.toFixed(2));
                } else if (triggered === 'total') {
                    subtotal = total ;
                    $('#subtotalCell').val(subtotal.toFixed(2));
                    due = total - paid;
                    $('#due_amount').val(due.toFixed(2));
                } else if (triggered === 'paid') {
                    due = total - paid;
                    $('#due_amount').val(due.toFixed(2));
                } else if (triggered === 'due') {
                    paid = total - due;
                    $('#paid_amount').val(paid.toFixed(2));
                }
            }
            $('#subtotalCell').on('input', function () { recalcSummary('subtotal'); });
            $('#total_amount').on('input', function () { recalcSummary('total'); });
            $('#paid_amount').on('input', function () { recalcSummary('paid'); });
            $('#due_amount').on('input', function () { recalcSummary('due'); });
        });
    </script>
@endsection 