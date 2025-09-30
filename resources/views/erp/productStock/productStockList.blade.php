@extends('erp.master')

@section('title', 'Stock Management')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')

        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Stock Management</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Stock Inventory Overview</h2>
                    <p class="text-muted mb-0">Monitor and manage stock levels across all locations</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#stockAdjustmentModal">
                            <i class="fas fa-adjust me-2"></i>Stock Adjustment
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4"> 

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Search Products</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" placeholder="Product name, SKU..." name="search" value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Category</label>
                                <select class="form-select" id="categoryFilter" name="category_id" style="width: 100%">
                                    <option value="">All Categories</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Branch</label>
                                <select class="form-select" name="branch_id">
                                    <option value="">All Branches</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{$branch->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Warehouse</label>
                                <select class="form-select" name="warehouse_id">
                                    <option value="">All Warehouses</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary flex-fill" type="submit">
                                        <i class="fas fa-filter me-2"></i>Filter
                                    </button>
                                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stock Listing Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Product Stock Details</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="stockTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Product</th>
                                    <th class="border-0">SKU</th>
                                    <th class="border-0">Category</th>
                                    <th class="border-0 text-center">Total Stock</th>
                                    <th class="border-0 text-center">Branches</th>
                                    <th class="border-0 text-center">Warehouses</th>
                                    <th class="border-0 text-center">Employee Inv.</th>
                                    <th class="border-0">Status</th>
                                </tr>
                            </thead>
                            <tbody id="stockTableBody">

                                @foreach ($productStocks as $stock)

                                    <tr class="stock-row" data-product-id="1">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset($stock->image) }}" alt="Product"
                                                    class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-1 fw-medium">{{ $stock->name }}</h6>
                                                    <small class="text-muted">{{ $stock->discount ?? $stock->price}}৳</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="font-monospace">{{$stock->sku}}</span></td>
                                        <td><span class="badge bg-light text-dark">{{$stock->category->name}}</span></td>
                                        <td class="text-center">
                                            <span class="h5 fw-bold text-primary">
                                                {{ @$stock->branchStock->sum('quantity') + @$stock->warehouseStock->sum('quantity') }}
                                            </span>
                                            <small class="text-muted d-block">units</small>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-info add-branch-stock-btn" data-product-name="{{ $stock->name }}" data-product-id="{{ $stock->id }}">
                                                Add stock to branch
                                            </button>
                                            @if($stock->branchStock->count() > 0)
                                            <button class="btn btn-sm btn-info branch-stock-list" data-branch-stock='@json($stock->branchStock->map(function($bs) { return ["branch_name" => $bs->branch->name ?? '', "quantity" => $bs->quantity]; }))'>{{$stock->branchStock->count().' Locations'}}</button>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-success add-warehouse-stock-btn" data-product-name="{{ $stock->name }}" data-product-id="{{ $stock->id }}">
                                                Add stock to warehouse
                                            </button>
                                            @if($stock->warehouseStock && $stock->warehouseStock->count() > 0)
                                            <button class="btn btn-sm btn-success warehouse-stock-list" data-warehouse-stock='@json($stock->warehouseStock->map(function($ws) { return ["warehouse_name" => $ws->warehouse->name ?? '', "quantity" => $ws->quantity]; }))'>{{$stock->warehouseStock->count().' Warehouses'}}</button>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse"
                                                data-bs-target="#employees-1">
                                                5 Employees
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">In Stock</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $productStocks->firstItem() }} to {{ $productStocks->lastItem() }} of {{ $productStocks->total() }} products
                        </span>
                        {{ $productStocks->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('erp.productStock.components.stockAdjustment',['branches'=>$branches, 'warehouses'=> $warehouses])

    <!-- Add Stock to Branch Modal -->
    <div class="modal fade" id="addBranchStockModal" tabindex="-1" aria-labelledby="addBranchStockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBranchStockModalLabel">Add Stock to Branch - <span id="modalProductName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addBranchStockForm">
                    <input type="hidden" id="modalProductId" name="product_id" value="">
                    <div class="modal-body">
                        <div class="d-flex gap-2 align-items-end">
                            <div class="mb-3 w-100">
                                <label for="branchSelect" class="form-label">Select Branch</label>
                                <select class="form-select" id="branchSelect" name="branch_id">
                                    <option value="">Choose branch...</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{$branch->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 w-100">
                                <label for="stockQuantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="stockQuantity" name="quantity" min="1">
                            </div>
                            <button type="button" class="btn btn-outline-primary mb-3" id="addBranchToList">Add</button>
                        </div>
                        <div>
                            <table class="table table-sm mt-3" id="branchStockTable" style="display:none;">
                                <thead>
                                    <tr>
                                        <th>Branch</th>
                                        <th>Quantity</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamically added rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Branch Stock List Modal -->
    <div class="modal fade" id="branchStockListModal" tabindex="-1" aria-labelledby="branchStockListModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="branchStockListModalLabel">Branch Stock List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="branchStockListTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Stock to Warehouse Modal -->
    <div class="modal fade" id="addWarehouseStockModal" tabindex="-1" aria-labelledby="addWarehouseStockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addWarehouseStockModalLabel">Add Stock to Warehouse - <span id="modalWarehouseProductName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addWarehouseStockForm">
                    <input type="hidden" id="modalWarehouseProductId" name="product_id" value="">
                    <div class="modal-body">
                        <div class="d-flex gap-2 align-items-end">
                            <div class="mb-3 w-100">
                                <label for="warehouseSelect" class="form-label">Select Warehouse</label>
                                <select class="form-select" id="warehouseSelect" name="warehouse_id">
                                    <option value="">Choose warehouse...</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 w-100">
                                <label for="warehouseStockQuantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="warehouseStockQuantity" name="quantity" min="1">
                            </div>
                            <button type="button" class="btn btn-outline-success mb-3" id="addWarehouseToList">Add</button>
                        </div>
                        <div>
                            <table class="table table-sm mt-3" id="warehouseStockTable" style="display:none;">
                                <thead>
                                    <tr>
                                        <th>Warehouse</th>
                                        <th>Quantity</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamically added rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Warehouse Stock List Modal -->
    <div class="modal fade" id="warehouseStockListModal" tabindex="-1" aria-labelledby="warehouseStockListModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warehouseStockListModalLabel">Warehouse Stock List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="warehouseStockListTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%) !important;
        }

        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .stock-row:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        .table th {
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
        }

        .sticky-top {
            top: 0;
            z-index: 1020;
        }
    </style>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script>
$(document).ready(function() {
    let allBranches = @json($branches);
    let addedBranches = [];

    function resetModal() {
        $('#branchSelect').val('');
        $('#stockQuantity').val('');
        $('#branchStockTable tbody').empty();
        $('#branchStockTable').hide();
        addedBranches = [];
        $('#branchSelect option').show();
    }

    $('.add-branch-stock-btn').on('click', function(e) {
        e.preventDefault();
        var productName = $(this).data('product-name');
        var productId = $(this).data('product-id');
        $('#modalProductName').text(productName);
        $('#modalProductId').val(productId);
        resetModal();
        $('#addBranchStockModal').modal('show');
    });

    $('#addBranchToList').on('click', function() {
        let branchId = $('#branchSelect').val();
        let branchName = $('#branchSelect option:selected').text();
        let quantity = $('#stockQuantity').val();

        if (!branchId || !quantity || quantity < 1) {
            alert('Please select a branch and enter a valid quantity.');
            return;
        }

        $('#branchStockTable tbody').append(`
            <tr data-branch-id="${branchId}">
                <td>
                    <input type="hidden" name="branches[]" value="${branchId}">
                    ${branchName}
                </td>
                <td>
                    <input type="hidden" name="quantities[]" value="${quantity}">
                    ${quantity}
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-branch-row">Remove</button>
                </td>
            </tr>
        `);
        $('#branchStockTable').show();
        $('#branchSelect option[value="'+branchId+'"]').hide();
        $('#branchSelect').val('');
        $('#stockQuantity').val('');
        addedBranches.push(branchId);
    });

    $(document).on('click', '.remove-branch-row', function() {
        let row = $(this).closest('tr');
        let branchId = row.data('branch-id');
        row.remove();
        $('#branchSelect option[value="'+branchId+'"]').show();
        addedBranches = addedBranches.filter(id => id != branchId);
        if ($('#branchStockTable tbody tr').length === 0) {
            $('#branchStockTable').hide();
        }
    });

    $('#addBranchStockModal').on('hidden.bs.modal', function () {
        resetModal();
    });

    // Handle form submission
    $('#addBranchStockForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        $.ajax({
            url: '{{ route('stock.addToBranches') }}',
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if(response.success) {
                    $('#addBranchStockModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || 'Failed to add stock.');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to add stock.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            }
        });
    });

    $('.branch-stock-list').on('click', function() {
        var branchStock = $(this).data('branch-stock') || [];
        var tbody = $('#branchStockListTableBody');
        tbody.empty();
        if(branchStock.length === 0) {
            tbody.append('<tr><td colspan="2" class="text-center text-muted">No branch stock found.</td></tr>');
        } else {
            branchStock.forEach(function(bs) {
                tbody.append('<tr><td>' + bs.branch_name + '</td><td>' + bs.quantity + '</td></tr>');
            });
            
        }
        $('#branchStockListModal').modal('show');
    });

    let allWarehouses = @json($warehouses);
    let addedWarehouses = [];

    function resetWarehouseModal() {
        $('#warehouseSelect').val('');
        $('#warehouseStockQuantity').val('');
        $('#warehouseStockTable tbody').empty();
        $('#warehouseStockTable').hide();
        addedWarehouses = [];
        $('#warehouseSelect option').show();
    }

    $('.add-warehouse-stock-btn').on('click', function(e) {
        e.preventDefault();
        var productName = $(this).data('product-name');
        var productId = $(this).data('product-id');
        $('#modalWarehouseProductName').text(productName);
        $('#modalWarehouseProductId').val(productId);
        resetWarehouseModal();
        $('#addWarehouseStockModal').modal('show');
    });

    $('#addWarehouseToList').on('click', function() {
        let warehouseId = $('#warehouseSelect').val();
        let warehouseName = $('#warehouseSelect option:selected').text();
        let quantity = $('#warehouseStockQuantity').val();

        if (!warehouseId || !quantity || quantity < 1) {
            alert('Please select a warehouse and enter a valid quantity.');
            return;
        }

        $('#warehouseStockTable tbody').append(`
            <tr data-warehouse-id="${warehouseId}">
                <td>
                    <input type="hidden" name="warehouses[]" value="${warehouseId}">
                    ${warehouseName}
                </td>
                <td>
                    <input type="hidden" name="quantities[]" value="${quantity}">
                    ${quantity}
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-warehouse-row">Remove</button>
                </td>
            </tr>
        `);
        $('#warehouseStockTable').show();
        $('#warehouseSelect option[value="'+warehouseId+'"]').hide();
        $('#warehouseSelect').val('');
        $('#warehouseStockQuantity').val('');
        addedWarehouses.push(warehouseId);
    });

    $(document).on('click', '.remove-warehouse-row', function() {
        let row = $(this).closest('tr');
        let warehouseId = row.data('warehouse-id');
        row.remove();
        $('#warehouseSelect option[value="'+warehouseId+'"]').show();
        addedWarehouses = addedWarehouses.filter(id => id != warehouseId);
        if ($('#warehouseStockTable tbody tr').length === 0) {
            $('#warehouseStockTable').hide();
        }
    });

    $('#addWarehouseStockModal').on('hidden.bs.modal', function () {
        resetWarehouseModal();
    });

    // Handle form submission
    $('#addWarehouseStockForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        $.ajax({
            url: '{{ route('stock.addToWarehouses') }}', // You must define this route in your web.php and controller
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if(response.success) {
                    $('#addWarehouseStockModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || 'Failed to add stock.');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to add stock.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            }
        });
    });

    $('.warehouse-stock-list').on('click', function() {
        var warehouseStock = $(this).data('warehouse-stock') || [];
        var tbody = $('#warehouseStockListTableBody');
        tbody.empty();
        if(warehouseStock.length === 0) {
            tbody.append('<tr><td colspan="2" class="text-center text-muted">No warehouse stock found.</td></tr>');
        } else {
            warehouseStock.forEach(function(ws) {
                tbody.append('<tr><td>' + ws.warehouse_name + '</td><td>' + ws.quantity + '</td></tr>');
            });
        }
        $('#warehouseStockListModal').modal('show');
    });

    $('#categoryFilter').select2({
        placeholder: 'Search or select a category',
        allowClear: true,
        ajax: {
            url: '/erp/categories/search',
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
    });
    // Set the selected category if present in the query string
    var selectedCategory = '{{ request('category_id') }}';
    if(selectedCategory) {
        $.ajax({
            type: 'GET',
            url: '/erp/categories/search',
            data: { q: '' },
            dataType: 'json'
        }).then(function(data) {
            var option = data.find(function(cat) { return cat.id == selectedCategory; });
            if(option) {
                var newOption = new Option(option.name, option.id, true, true);
                $('#categoryFilter').append(newOption).trigger('change');
            }
        });
    }
});
</script>
@endpush