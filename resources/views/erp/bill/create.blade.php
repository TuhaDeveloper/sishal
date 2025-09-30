@extends('erp.master')

@section('title', 'Bill Management')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <h2 class="mb-4">Create Bill</h2>
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
            <form id="billForm" action="{{ route('bill.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-4">
                        <label for="bill_date" class="form-label">Bill Date</label>
                        <input type="date" name="bill_date" id="bill_date" class="form-control" value="{{ date('Y-m-d') }}"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" name="due_date" id="due_date" class="form-control">
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
                            <tr>
                                <td>
                                    <select name="items[0][product_id]" class="form-select product-select"
                                        required></select>
                                </td>
                                <td><input type="number" name="items[0][quantity]" class="form-control quantity" min="0.01"
                                        step="0.01" required></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-control unit_price" min="0"
                                        step="0.01" required></td>
                                <td class="item-total">0.00</td>
                                <td><textarea name="items[0][description]" class="form-control"
                                        placeholder="Description"></textarea></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row" disabled>&times;</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-secondary btn-sm" id="addItemRow">Add Item</button>
                    <div class="d-flex justify-content-end">
                        <table class="table w-25">
                            <tbody>
                                <tr>
                                    <th class="text-end">Subtotal</th>
                                    <td><input type="number" name="subtotal" id="subtotalCell" class="form-control"
                                            step="0.01" min="0" required></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Discount</th>
                                    <td><input type="number" name="discount" id="discountCell" class="form-control"
                                            step="0.01" min="0" required></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Total</th>
                                    <td><input type="number" name="total_amount" id="total_amount" class="form-control"
                                            step="0.01" min="0" required></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Paid Amount</th>
                                    <td><input type="number" name="paid_amount" id="paid_amount" class="form-control"
                                            step="0.01" min="0"></td>
                                </tr>
                                <tr>
                                    <th class="text-end">Due Amount</th>
                                    <td><input type="number" name="due_amount" id="due_amount" class="form-control"
                                            step="0.01" min="0"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mb-3 text-end">
                    <button type="submit" class="btn btn-primary">Create Bill</button>
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
                        // Transform the data to Select2 format
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

        // Helper to set value for Select2 AJAX
        function setSelect2Value($select, id, text) {
            if ($select.find("option[value='" + id + "']").length === 0) {
                var newOption = new Option(text, id, true, true);
                $select.append(newOption).trigger('change');
            } else {
                $select.val(id).trigger('change');
            }
        }

        $(document).ready(function () {
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

            initProductSelect2('.product-select');

            // Example usage for edit page:
            // setSelect2Value($('#items_0_product_id'), 123, 'Product Name');
        });

        function reinitProductSelect2() {
            initProductSelect2('.product-select');
        }

        let itemIndex = 1;
        document.getElementById('addItemRow').addEventListener('click', function () {
            const tbody = document.querySelector('#itemsTable tbody');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <select name="items[${itemIndex}][product_id]" class="form-select product-select" required></select>
                </td>
                <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control quantity" min="0.01" step="0.01" required></td>
                <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control unit_price" min="0" step="0.01" required></td>
                
                <td class="item-total">0.00</td>
                <td><textarea name="items[${itemIndex}][description]" class="form-control" placeholder="Description"></textarea></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
            `;
            tbody.appendChild(row);
            itemIndex++;

            // Initialize Select2 for the new row
            initProductSelect2(row.querySelector('.product-select'));
        });

        document.querySelector('#itemsTable').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('tr').remove();
            }
        });
        // Calculate item total
        function updateTotals() {
            let subtotal = 0;
            let totalDiscount = 0;
            document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
                const qty = parseFloat(row.querySelector('.quantity')?.value) || 0;
                const price = parseFloat(row.querySelector('.unit_price')?.value) || 0;
                
                let total = (qty * price) ;
                if (total < 0) total = 0;
                row.querySelector('.item-total').textContent = total.toFixed(2);
                subtotal += qty * price;
                
            });
            const grandTotal = subtotal ;
            document.getElementById('subtotalCell').value = subtotal.toFixed(2);
            
            document.getElementById('total_amount').value = grandTotal.toFixed(2);
            // Optionally auto-calculate due_amount
            const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
            document.getElementById('due_amount').value = (grandTotal - paid).toFixed(2);
        }
        document.querySelector('#itemsTable').addEventListener('input', function (e) {
            if (
                e.target.classList.contains('quantity') ||
                e.target.classList.contains('unit_price') ||
                e.target.classList.contains('discount')
            ) {
                updateTotals();
            }
        });
        document.getElementById('paid_amount').addEventListener('input', function () {
            const grandTotal = parseFloat(document.getElementById('total_amount').value) || 0;
            const paid = parseFloat(this.value) || 0;
            document.getElementById('due_amount').value = (grandTotal - paid).toFixed(2);
        });
        // Bi-directional summary calculation
        function recalcSummary(triggered) {
            let subtotal = parseFloat(document.getElementById('subtotalCell').value) || 0;
            let discount = parseFloat(document.getElementById('discountCell').value) || 0;
            let total = parseFloat(document.getElementById('total_amount').value) || 0;
            let paid = parseFloat(document.getElementById('paid_amount').value) || 0;
            let due = parseFloat(document.getElementById('due_amount').value) || 0;

            if (triggered === 'subtotal' || triggered === 'discount') {
                total = subtotal - discount;
                document.getElementById('total_amount').value = total.toFixed(2);
                due = total - paid;
                document.getElementById('due_amount').value = due.toFixed(2);
            } else if (triggered === 'total') {
                subtotal = total + discount;
                document.getElementById('subtotalCell').value = subtotal.toFixed(2);
                due = total - paid;
                document.getElementById('due_amount').value = due.toFixed(2);
            } else if (triggered === 'paid') {
                due = total - paid;
                document.getElementById('due_amount').value = due.toFixed(2);
            } else if (triggered === 'due') {
                paid = total - due;
                document.getElementById('paid_amount').value = paid.toFixed(2);
            }
        }
        document.getElementById('subtotalCell').addEventListener('input', function () { recalcSummary('subtotal'); });
        document.getElementById('discountCell').addEventListener('input', function () { recalcSummary('discount'); });
        document.getElementById('total_amount').addEventListener('input', function () { recalcSummary('total'); });
        document.getElementById('paid_amount').addEventListener('input', function () { recalcSummary('paid'); });
        document.getElementById('due_amount').addEventListener('input', function () { recalcSummary('due'); });
    </script>
@endsection