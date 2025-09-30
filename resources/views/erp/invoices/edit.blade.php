@extends('erp.master')

@section('title', 'Edit Invoice')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')

        <div class="container-fluid px-4 py-3">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="border-0 mb-3">
                        <h4 class="fw-bold mb-0">Edit Invoice</h4>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('invoice.update', $invoice->id) }}" class="row">
                            @csrf
                            @method('PATCH')

                            <!-- Customer & Address Section -->
                            <div class=" col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Customer & Address Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                                <select name="customer_id" id="customerSelect" class="form-select" required style="width:100%">
                                                    <option value="">Search and select customer...</option>
                                                    <option value="{{ $invoice->customer->id ?? '' }}" selected>{{ $invoice->customer->name ?? '' }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 pe-4" style="border-right: 1px solid rgb(219, 215, 215);">
                                                <h6 class="fw-bold mb-3">Billing Address</h6>
                                                <div class="mb-2">
                                                    <label class="form-label">Address 1 <span class="text-danger">*</span></label>
                                                    <input type="text" name="billing_address_1" class="form-control" value="{{ old('billing_address_1', $invoice->invoiceAddress->billing_address_1 ?? '') }}" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label">Address 2</label>
                                                    <input type="text" name="billing_address_2" class="form-control" value="{{ old('billing_address_2', $invoice->invoiceAddress->billing_address_2 ?? '') }}">
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">City</label>
                                                        <input type="text" name="billing_city" class="form-control" value="{{ old('billing_city', $invoice->invoiceAddress->billing_city ?? '') }}">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">State</label>
                                                        <input type="text" name="billing_state" class="form-control" value="{{ old('billing_state', $invoice->invoiceAddress->billing_state ?? '') }}">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">Country</label>
                                                        <input type="text" name="billing_country" class="form-control" value="{{ old('billing_country', $invoice->invoiceAddress->billing_country ?? '') }}">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">Zip Code</label>
                                                        <input type="text" name="billing_zip_code" class="form-control" value="{{ old('billing_zip_code', $invoice->invoiceAddress->billing_zip_code ?? '') }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 ps-4">
                                                <h6 class="fw-bold mb-3">Shipping Address</h6>
                                                <div class="mb-2">
                                                    <label class="form-label">Address 1</label>
                                                    <input type="text" name="shipping_address_1" class="form-control" value="{{ old('shipping_address_1', $invoice->invoiceAddress->shipping_address_1 ?? '') }}">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label">Address 2</label>
                                                    <input type="text" name="shipping_address_2" class="form-control" value="{{ old('shipping_address_2', $invoice->invoiceAddress->shipping_address_2 ?? '') }}">
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">City</label>
                                                        <input type="text" name="shipping_city" class="form-control" value="{{ old('shipping_city', $invoice->invoiceAddress->shipping_city ?? '') }}">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">State</label>
                                                        <input type="text" name="shipping_state" class="form-control" value="{{ old('shipping_state', $invoice->invoiceAddress->shipping_state ?? '') }}">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">Country</label>
                                                        <input type="text" name="shipping_country" class="form-control" value="{{ old('shipping_country', $invoice->invoiceAddress->shipping_country ?? '') }}">
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">Zip Code</label>
                                                        <input type="text" name="shipping_zip_code" class="form-control" value="{{ old('shipping_zip_code', $invoice->invoiceAddress->shipping_zip_code ?? '') }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Template & Date Information -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Invoice Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Template <span class="text-danger">*</span></label>
                                                <select name="template_id" id="templateSelect" class="form-select" required>
                                                    <option value="">Select Template</option>
                                                    @foreach($templates as $template)
                                                        <option value="{{ $template->id }}" data-footer="{!! addslashes($template->footer_note) !!}" {{ $invoice->template_id == $template->id ? 'selected' : '' }}>{{ $template->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                                <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', $invoice->issue_date ?? '') }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Due Date</label>
                                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Send Date</label>
                                                <input type="date" name="send_date" class="form-control" value="{{ old('send_date', $invoice->send_date ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Note</label>
                                            <textarea name="note" class="form-control" rows="3" placeholder="Add any internal notes about this invoice...">{{ old('note', $invoice->note) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Invoice Items -->
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Invoice Items</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive mb-3">
                                            <table class="table table-bordered align-middle" id="itemsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="40%">Product</th>
                                                        <th width="15%">Quantity</th>
                                                        <th width="15%">Unit Price</th>
                                                        <th width="15%">Total Price</th>
                                                        <th width="15%">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($invoice->items as $i => $item)
                                                    <tr>
                                                        <td>
                                                            <select name="items[{{ $i }}][product_id]" class="form-select product-select" required style="width:100%">
                                                                <option value="{{ $item->product_id }}" selected>{{ $item->product->name ?? '' }}</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $i }}][quantity]" class="form-control item-qty" value="{{ $item->quantity }}" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $i }}][unit_price]" class="form-control item-unit" min="0" step="0.01" value="{{ $item->unit_price }}" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $i }}][total_price]" class="form-control item-total" min="0" step="0.01" value="{{ $item->total_price }}" readonly required>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-danger btn-sm remove-item" {{ $i == 0 ? 'disabled' : '' }}>
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
                                                <i class="fas fa-plus"></i> Add Item
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Information -->
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Payment Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Discount Amount</label>
                                                    <input type="number" name="discount" class="form-control" min="0" step="0.01" value="{{ old('discount', $invoice->discount_apply) }}" id="discountAmount">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="bg-light p-3 rounded">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Subtotal:</span>
                                                        <span class="fw-bold" id="subtotalDisplay">{{ number_format($invoice->subtotal, 2) }}৳</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Discount:</span>
                                                        <span class="fw-bold text-danger" id="discountDisplay">-{{ number_format($invoice->discount_apply, 2) }}৳</span>
                                                    </div>
                                                    <hr>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="fw-bold">Total:</span>
                                                        <span class="fw-bold fs-5" id="totalDisplay">{{ number_format($invoice->total_amount, 2) }}৳</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Paid:</span>
                                                        <span class="fw-bold text-success" id="paidDisplay" data-paid="{{ $invoice->paid_amount }}">{{ number_format($invoice->paid_amount, 2) }}৳</span>
                                                    </div>
                                                    <hr>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="fw-bold">Due Amount:</span>
                                                        <span class="fw-bold fs-5 text-warning" id="dueDisplay">{{ number_format($invoice->due_amount, 2) }}৳</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Text -->
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Footer Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Footer Text</label>
                                            <div id="footerTextEditor" style="height: 120px;">{!! old('footer_text', $invoice->footer_text) !!}</div>
                                            <input type="hidden" name="footer_text" id="footerTextInput" value="{{ old('footer_text', $invoice->footer_text) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Update Invoice
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<style>
    .select2 {
        border: 1px solid #dee2e6;
        border-radius: 7px;
    }
</style>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#customerSelect').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select customer...',
                ajax: {
                    url: '/erp/customers/search',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function (customer) {
                                return {
                                    id: customer.id,
                                    text: customer.name + (customer.email ? ' (' + customer.email + ')' : '') + (customer.phone ? ' [' + customer.phone + ']' : '')
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });

            // Autofill address on customer select
            $('#customerSelect').on('select2:select', function (e) {
                var customerId = e.params.data.id;
                $.get('/erp/customers/' + customerId + '/address', function (data) {
                    // Only autofill if fields are empty
                    if (!$('input[name="billing_address_1"]').val()) $('input[name="billing_address_1"]').val(data.address_1);
                    if (!$('input[name="billing_address_2"]').val()) $('input[name="billing_address_2"]').val(data.address_2);
                    if (!$('input[name="billing_city"]').val()) $('input[name="billing_city"]').val(data.city);
                    if (!$('input[name="billing_state"]').val()) $('input[name="billing_state"]').val(data.state);
                    if (!$('input[name="billing_country"]').val()) $('input[name="billing_country"]').val(data.country);
                    if (!$('input[name="billing_zip_code"]').val()) $('input[name="billing_zip_code"]').val(data.zip_code);
                    // Optionally autofill shipping as well if empty
                    if (!$('input[name="shipping_address_1"]').val()) $('input[name="shipping_address_1"]').val(data.address_1);
                    if (!$('input[name="shipping_address_2"]').val()) $('input[name="shipping_address_2"]').val(data.address_2);
                    if (!$('input[name="shipping_city"]').val()) $('input[name="shipping_city"]').val(data.city);
                    if (!$('input[name="shipping_state"]').val()) $('input[name="shipping_state"]').val(data.state);
                    if (!$('input[name="shipping_country"]').val()) $('input[name="shipping_country"]').val(data.country);
                    if (!$('input[name="shipping_zip_code"]').val()) $('input[name="shipping_zip_code"]').val(data.zip_code);
                });
            });

            // Remove template select2
            $('#templateSelect').on('change', function () {
                var selected = $(this).find('option:selected');
                var footer = selected.data('footer') || '';
                $('textarea[name="footer_text"]').val(footer);
            });

            function initProductSelect2(selector) {
                $(selector).select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Search and select product...',
                    ajax: {
                        url: '/erp/products/search',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(function (product) {
                                    return {
                                        id: product.id,
                                        text: product.name
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                });
            }
            initProductSelect2('.product-select');

            $('#addItemBtn').on('click', function () {
                setTimeout(function () {
                    initProductSelect2('.product-select');
                }, 100);
            });
        });
        let itemIndex = {{ count($invoice->items) }};

        function recalcRow(row) {
            const qty = parseFloat(row.find('.item-qty').val()) || 0;
            const unit = parseFloat(row.find('.item-unit').val()) || 0;
            const total = qty * unit;
            row.find('.item-total').val(total.toFixed(2));
            updateTotals();
        }

        function updateTotals() {
            let subtotal = 0;

            $('#itemsTable tbody tr').each(function () {
                const total = parseFloat($(this).find('.item-total').val()) || 0;
                subtotal += total;
            });

            const discount = parseFloat($('#discountAmount').val()) || 0;
            const paid = parseFloat($('#paidDisplay').data('paid')) || 0;
            const total = subtotal - discount;
            const due = total - paid;

            $('#subtotalDisplay').text(subtotal.toFixed(2) + '৳');
            $('#discountDisplay').text(discount.toFixed(2) + '৳');
            $('#totalDisplay').text(total.toFixed(2) + '৳');
            $('#paidDisplay').text(paid.toFixed(2) + '৳');
            $('#dueDisplay').text(due.toFixed(2) + '৳');
        }

        $(document).on('input', '.item-qty, .item-unit', function () {
            const row = $(this).closest('tr');
            recalcRow(row);
        });

        $(document).on('change', '.product-select', function() {
            var row = $(this).closest('tr');
            var productId = $(this).val();
            if (!productId) return;
            $.get('/erp/products/' + productId + '/price', function(data) {
                row.find('.item-unit').val(data.price);
                row.find('.item-qty').val(1);
                recalcRow(row);
            });
        });

        $(document).on('input', '#discountAmount', function () {
            updateTotals();
        });

        $('#addItemBtn').on('click', function () {
            const row = $('#itemsTable tbody tr:first').clone();
            row.find('select, input').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\d+/, itemIndex);
                    $(this).attr('name', newName);
                }
                if ($(this).is('select')) $(this).val('');
                else $(this).val('');
            });
            row.find('.remove-item').prop('disabled', false);
            $('#itemsTable tbody').append(row);
            itemIndex++;
            updateTotals();
        });

        $(document).on('click', '.remove-item', function () {
            if ($('#itemsTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateTotals();
            }
        });

        // Initialize totals on page load
        $(document).ready(function () {
            updateTotals();
        });
    </script>
    <!-- Quill Editor CDN -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        $(document).ready(function () {
            var quill = new Quill('#footerTextEditor', {
                theme: 'snow',
                placeholder: 'Add footer text that will appear at the bottom of the invoice...',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, false] }],
                        ['bold', 'italic', 'underline'],
                        ['link', 'clean']
                    ]
                }
            });
            // Set Quill content from hidden input (for old input)
            var oldFooter = $('#footerTextInput').val();
            if (oldFooter) {
                quill.root.innerHTML = oldFooter;
            }
            // On form submit, copy Quill HTML to hidden input
            $('form').on('submit', function () {
                $('#footerTextInput').val(quill.root.innerHTML);
            });
            // On template select, set Quill content to selected template's footer_note
            $('#templateSelect').on('change', function () {
                var selected = $(this).find('option:selected');
                var footer = selected.data('footer') || '';
                quill.root.innerHTML = footer;
            });
        });
    </script>
@endpush 