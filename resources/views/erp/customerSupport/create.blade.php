@extends('erp.master')

@section('title', 'Create Service')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container py-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Create Service</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form method="POST" action="{{ route('service.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customerSelect" class="form-select" required
                                    style="width:100%">
                                    <option value="">Search and select customer...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Technician <span class="text-danger">*</span></label>
                                <select name="technician_id" id="technician_id" class="form-select" required
                                    style="width:100%">
                                    <option value="">Search and select technician...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="product_service_id" class="form-label">Product/Service</label>
                                <select name="product_service_id" id="product_service_id" class="form-select">
                                    <option value="">Search and select product/service...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="service_type" class="form-label">Service Type</label>
                                <select name="service_type" id="service_type" class="form-select" required>
                                    <option value="installation" selected>Installation</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="repair">Repair</option>
                                    <option value="filter_change">Filter Change</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="requested_date" class="form-label">Requested Date</label>
                                <input type="datetime-local" name="requested_date" id="requested_date" class="form-control"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label for="preferred_time" class="form-label">Preferred Time</label>
                                <input type="text" name="preferred_time" id="preferred_time" class="form-control"
                                    placeholder="e.g. Morning, Afternoon">
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Service Address</label>
                            <textarea name="address" id="address" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="service_notes" class="form-label">Service Notes</label>
                            <textarea name="service_notes" id="service_notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea name="admin_notes" id="admin_notes" class="form-control" rows="2"></textarea>
                        </div>

                        <hr>
                        <h5>Provided Parts</h5>
                        <table class="table table-bordered align-middle" id="partsTable">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Product/Material</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>
                                        <button type="button" class="btn btn-sm btn-success" id="addPartRow">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic rows here -->
                            </tbody>
                        </table>



                        <div class="d-flex flex-column justify-content-end align-items-end mb-3">
                            <div class="mb-3">
                                <label for="service_fee" class="form-label">Service Fee</label>
                                <input type="number" step="1" name="service_fee" id="service_fee" class="form-control"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="travel_fee" class="form-label">Travel Fee</label>
                                <input type="number" step="1" name="travel_fee" id="travel_fee" class="form-control"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="discount" class="form-label">Discount</label>
                                <input type="number" step="1" name="discount" id="discount" class="form-control"
                                    required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mb-4">
                            <div id="price-overview" class="p-3 border rounded bg-light" style="min-width: 320px;">
                                <div class="d-flex justify-content-between mb-1"><span>Products Total:</span> <span id="productsTotal">0.00</span></div>
                                <div class="d-flex justify-content-between mb-1"><span>Service Fee:</span> <span id="serviceFee">0.00</span></div>
                                <div class="d-flex justify-content-between mb-1"><span>Travel Fee:</span> <span id="travelFee">0.00</span></div>
                                <div class="d-flex justify-content-between mb-1"><span>Discount:</span> <span id="discountVal">0.00</span></div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fw-bold fs-5"><span>Total:</span> <span id="grandTotal">0.00</span></div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Create Service</button>
                        </div>
                    </form>
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

            // Technician select (restore/fix)
            $('#technician_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select technician...',
                ajax: {
                    url: '/erp/employees/search',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function (employee) {
                                return {
                                    id: employee.id,
                                    text: employee.name + (employee.email ? ' (' + employee.email + ')' : '') + (employee.phone ? ' [' + employee.phone + ']' : '')
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
                    if (!$('textarea[name="address"]').val()) {
                        $('textarea[name="address"]').val(
                            (data.address_1 || '') + ', ' +
                            (data.city || '') + ', ' +
                            (data.state || '') + ', ' +
                            (data.zip_code || '')
                        );
                    }
                });
            });
        })
    </script>
    <script>
        function initProductSelect($typeSelect, $productSelect) {
            function getAjaxUrl(type) {
                return type === 'material' ? '/erp/materials/search' : '/erp/products/search';
            }
            $productSelect.select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select...',
                minimumInputLength: 1,
                ajax: {
                    url: getAjaxUrl($typeSelect.val()),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function (item) {
                                return {
                                    id: item.id,
                                    text: item.name || item.label || item.text
                                };
                            })
                        };
                    },
                },
                width: 'resolve',
            });
            // Change AJAX URL if type changes
            $typeSelect.on('change', function() {
                $productSelect.val(null).trigger('change');
                $productSelect.select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Search and select...',
                    minimumInputLength: 1,
                    ajax: {
                        url: getAjaxUrl($typeSelect.val()),
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.name || item.label || item.text
                                    };
                                })
                            };
                        },
                    },
                    width: 'resolve',
                });
            });
            // Autofill price on select
            $productSelect.on('select2:select', function(e) {
                var productId = e.params.data.id;
                var type = $typeSelect.val();
                var $row = $productSelect.closest('tr');
                var $priceInput = $row.find('input[name$="[price]"]');
                var url = type === 'material' ? '/erp/materials/' + productId + '/price' : '/erp/products/' + productId + '/price';
                $.get(url, function(data) {
                    if (data && typeof data.price !== 'undefined') {
                        $priceInput.val(data.price);
                    }
                });
            });
        }

        let partRowIndex = 0;

        function addPartRow() {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <select name="provided_parts[${partRowIndex}][product_type]" class="form-select part-type" required>
                        <option value="product" selected>Product</option>
                        <option value="material">Material</option>
                    </select>
                </td>
                <td>
                    <select name="provided_parts[${partRowIndex}][product_id]" class="form-select part-product" required></select>
                </td>
                <td>
                    <input type="number" name="provided_parts[${partRowIndex}][qty]" class="form-control" min="1" value="1" required>
                </td>
                <td>
                    <input type="number" name="provided_parts[${partRowIndex}][price]" class="form-control" min="0" step="0.01" required>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-part-row">-</button>
                </td>
            `;
            document.querySelector('#partsTable tbody').appendChild(row);
            // Initialize select2 and event handlers for the new row
            const $row = $(row);
            const $typeSelect = $row.find('.part-type');
            const $productSelect = $row.find('.part-product');
            initProductSelect($typeSelect, $productSelect);
            partRowIndex++;
        }

        document.getElementById('addPartRow').addEventListener('click', addPartRow);

        document.querySelector('#partsTable tbody').addEventListener('click', function(e) {
            if (e.target.closest('.remove-part-row')) {
                e.target.closest('tr').remove();
            }
        });

        document.querySelector('#partsTable tbody').addEventListener('change', function(e) {
            if (e.target.classList.contains('part-type')) {
                const $typeSelect = $(e.target);
                const $productSelect = $typeSelect.closest('tr').find('.part-product');
                $productSelect.val(null).trigger('change');
                $productSelect.select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Search and select...',
                    minimumInputLength: 1,
                    ajax: {
                        url: $typeSelect.val() === 'material' ? '/erp/materials/search' : '/erp/products/search',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.name || item.label || item.text
                                    };
                                })
                            };
                        },
                    },
                    width: 'resolve',
                });
            }
        });

        // Add one row by default
        addPartRow();
    </script>
    <script>
        function calculatePriceOverview() {
            let productsTotal = 0;
            $('#partsTable tbody tr').each(function() {
                const qty = parseFloat($(this).find('input[name^="provided_parts"][name$="[qty]"]').val()) || 0;
                const price = parseFloat($(this).find('input[name^="provided_parts"][name$="[price]"]').val()) || 0;
                productsTotal += qty * price;
            });
            const serviceFee = parseFloat($('#service_fee').val()) || 0;
            const travelFee = parseFloat($('#travel_fee').val()) || 0;
            const discount = parseFloat($('#discount').val()) || 0;
            const total = productsTotal + serviceFee + travelFee - discount;
            $('#productsTotal').text(productsTotal.toFixed(2));
            $('#serviceFee').text(serviceFee.toFixed(2));
            $('#travelFee').text(travelFee.toFixed(2));
            $('#discountVal').text(discount.toFixed(2));
            $('#grandTotal').text(total.toFixed(2));
        }

        $(document).on('input change', '#partsTable input, #service_fee, #travel_fee, #discount', calculatePriceOverview);
        $(document).on('click', '#addPartRow, .remove-part-row', function() {
            setTimeout(calculatePriceOverview, 100); // recalc after row add/remove
        });
        $(document).ready(function() {
            calculatePriceOverview();
        });
    </script>
@endpush