<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockAdjustmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="post" action="{{ route('stock.adjust') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Stock Adjustment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Location Type</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="location_type" id="branchRadio" value="branch" checked>
                                    <label class="form-check-label" for="branchRadio">Branch</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="location_type" id="warehouseRadio" value="warehouse">
                                    <label class="form-check-label" for="warehouseRadio">Warehouse</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Product</label>
                            <select class="form-select" id="productSelect" name="product_id" style="width: 100%">
                                <option value="">Select Product...</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 location-select-group" id="branchSelectGroup">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id">
                                <option>Select Branch...</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{$branch->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 location-select-group" id="warehouseSelectGroup" style="display:none;">
                            <label class="form-label">Warehouse</label>
                            <select class="form-select" name="warehouse_id">
                                <option>Select Warehouse...</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adjustment Type</label>
                            <select class="form-select" name="type">
                                <option value="stock_in">Stock In</option>
                                <option value="stock_out">Stock Out</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" placeholder="Enter quantity" name="quantity">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Adjust Stock</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script>
$(document).ready(function() {
    $('input[name="location_type"]').on('change', function() {
        if ($(this).val() === 'branch') {
            $('#branchSelectGroup').show();
            $('#warehouseSelectGroup').hide();
        } else {
            $('#branchSelectGroup').hide();
            $('#warehouseSelectGroup').show();
        }
    });

    $('#productSelect').select2({
        placeholder: 'Search or select a product',
        allowClear: true,
        ajax: {
            url: '/erp/products/search',
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
                        return { id: item.id, text: item.name };
                    })
                };
            },
            cache: true
        },
        width: 'resolve',
        dropdownParent: $('#stockAdjustmentModal'),
    });
});
</script>
@endpush