@extends('erp.master')

@section('title', 'Stock Transfer')

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
                            <li class="breadcrumb-item active" aria-current="page">Stock Transfer</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Stock Transfer</h2>
                    <p class="text-muted mb-0">Transfer stock levels across all locations, warehouses & employees</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stockTransferModal">
                            <i class="fas fa-adjust me-2"></i>Make Transfer
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Search Product Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" placeholder="Product name..." name="search" value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">From Branch</label>
                                <select class="form-select" name="from_branch_id">
                                    <option value="">All Branches</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('from_branch_id') == $branch->id ? 'selected' : '' }}>{{$branch->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">From Warehouse</label>
                                <select class="form-select" name="from_warehouse_id">
                                    <option value="">All Warehouses</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ request('from_warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">To Branch</label>
                                <select class="form-select" name="to_branch_id">
                                    <option value="">All Branches</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('to_branch_id') == $branch->id ? 'selected' : '' }}>{{$branch->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">To Warehouse</label>
                                <select class="form-select" name="to_warehouse_id">
                                    <option value="">All Warehouses</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ request('to_warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
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

            <!-- Stock Transfer Listing Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Stock Transfers</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="transferTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Product</th>
                                    <th class="border-0">From</th>
                                    <th class="border-0">To</th>
                                    <th class="border-0 text-center">Quantity</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Request By</th>
                                    <th class="border-0">Requested At</th>
                                    <th class="border-0">Approved By</th>
                                    <th class="border-0">Approved At</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transfers as $transfer)
                                    <tr>
                                        <td>{{ $transfer->product->name ?? '-' }}</td>
                                        <td>
                                            @if($transfer->from_type === 'branch')
                                                Branch: {{ $transfer->fromBranch->name ?? '-' }}
                                            @elseif($transfer->from_type === 'warehouse')
                                                Warehouse: {{ $transfer->fromWarehouse->name ?? '-' }}
                                            @else
                                                {{ ucfirst($transfer->from_type) }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($transfer->to_type === 'branch')
                                                Branch: {{ $transfer->toBranch->name ?? '-' }}
                                            @elseif($transfer->to_type === 'warehouse')
                                                Warehouse: {{ $transfer->toWarehouse->name ?? '-' }}
                                            @else
                                                {{ ucfirst($transfer->to_type) }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $transfer->quantity }}</td>
                                        <td>
                                            <span class="badge bg-info status-badge" style="cursor:pointer;" data-transfer-id="{{ $transfer->id }}" data-current-status="{{ $transfer->status }}">{{ ucfirst($transfer->status) }}</span>
                                        </td>
                                        <td>{{@$transfer->requestedPerson->first_name}} {{@$transfer->requestedPerson->last_name}}</td>
                                        <td>{{ $transfer->requested_at ? \Carbon\Carbon::parse($transfer->requested_at)->format('Y-m-d H:i') : '-' }}</td>
                                        <td>{{@$transfer->approvedPerson->first_name}} {{@$transfer->approvedPerson->last_name}}</td>
                                        <td>{{ $transfer->approved_at ? \Carbon\Carbon::parse($transfer->approved_at)->format('Y-m-d H:i') : '-' }}</td>
                                        <td>
                                            <a href="{{ route('stocktransfer.show',$transfer->id) }}" class="text-info me-2"><i class="fas fa-eye"></i></a>
                                            <a href="#" class="text-danger"><i class="fas fa-trash"></i></a>
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
                            Showing {{ $transfers->firstItem() }} to {{ $transfers->lastItem() }} of {{ $transfers->total() }} transfers
                        </span>
                        {{ $transfers->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>

            <!-- Stock Transfer Modal -->
            <div class="modal fade" id="stockTransferModal" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content" method="POST" action="{{ route('stocktransfer.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">New Stock Transfer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex align-items-end gap-2 w-100">
                                <div class="mb-3">
                                    <label class="form-label">From</label>
                                    <select class="form-select from-type-select" name="from_type" required>
                                        <option value="branch">Branch</option>
                                        <option value="warehouse">Warehouse</option>
                                        <option value="employee">Employee</option>
                                    </select>
                                </div>
                                <div class="mb-3 w-100 from-branch-group">
                                    <select name="from_branch_id" class="form-select">
                                        <option value="">Select Branch</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{$branch->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3 w-100 from-warehouse-group" style="display:none;">
                                    <select name="from_warehouse_id" class="form-select">
                                        <option value="">Select Warehouse</option>
                                        @foreach ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{$warehouse->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex align-items-end gap-2 w-100">
                                <div class="mb-3">
                                    <label class="form-label">To</label>
                                    <select class="form-select to-type-select" name="to_type" required>
                                        <option value="branch">Branch</option>
                                        <option value="warehouse">Warehouse</option>
                                        <option value="employee">Employee</option>
                                    </select>
                                </div>
                                <div class="mb-3 w-100 to-branch-group">
                                    <select name="to_branch_id" class="form-select">
                                        <option value="">Select Branch</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{$branch->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3 w-100 to-warehouse-group" style="display:none;">
                                    <select name="to_warehouse_id" class="form-select">
                                        <option value="">Select Warehouse</option>
                                        @foreach ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{$warehouse->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Product</label>
                                <select class="form-select" name="product_id" id="productSelect" required style="width: 100%">
                                    <option value="">Select Product...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" min="0.01" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Transfer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Status Update Modal -->
            <div class="modal fade" id="statusUpdateModal" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content" id="statusUpdateForm" method="POST" action="">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h5 class="modal-title">Update Transfer Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="transfer_id" id="modalTransferId">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="modalStatusSelect">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-bs-target="#stockTransferModal"]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var modal = new bootstrap.Modal(document.getElementById('stockTransferModal'));
                        modal.show();
                    });
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
                    dropdownParent: $('#stockTransferModal'),
                });

                // Show/hide branch/warehouse selects for FROM
                $('.from-type-select').on('change', function() {
                    if ($(this).val() === 'branch') {
                        $('.from-branch-group').show();
                        $('.from-warehouse-group').hide();
                    } else if ($(this).val() === 'warehouse') {
                        $('.from-branch-group').hide();
                        $('.from-warehouse-group').show();
                    } else {
                        $('.from-branch-group').hide();
                        $('.from-warehouse-group').hide();
                    }
                }).trigger('change');
                // Show/hide branch/warehouse selects for TO
                $('.to-type-select').on('change', function() {
                    if ($(this).val() === 'branch') {
                        $('.to-branch-group').show();
                        $('.to-warehouse-group').hide();
                    } else if ($(this).val() === 'warehouse') {
                        $('.to-branch-group').hide();
                        $('.to-warehouse-group').show();
                    } else {
                        $('.to-branch-group').hide();
                        $('.to-warehouse-group').hide();
                    }
                }).trigger('change');

                $(document).on('click', '.status-badge', function() {
                    var transferId = $(this).data('transfer-id');
                    var currentStatus = $(this).data('current-status');
                    $('#modalTransferId').val(transferId);
                    $('#modalStatusSelect').val(currentStatus);

                    // Enable all options first
                    $('#modalStatusSelect option').prop('disabled', false);

                    // Disable options based on current status
                    if (currentStatus === 'delivered') {
                        $('#modalStatusSelect option').prop('disabled', true);
                        $('#modalStatusSelect option[value="delivered"]').prop('disabled', false);
                    } else if (currentStatus === 'approved') {
                        $('#modalStatusSelect option[value="pending"]').prop('disabled', true);
                        $('#modalStatusSelect option[value="approved"]').prop('disabled', false);
                        $('#modalStatusSelect option[value="shipped"]').prop('disabled', false);
                        $('#modalStatusSelect option[value="delivered"]').prop('disabled', true);
                        $('#modalStatusSelect option[value="rejected"]').prop('disabled', false);
                    } else if (currentStatus === 'shipped') {
                        $('#modalStatusSelect option[value="pending"]').prop('disabled', true);
                        $('#modalStatusSelect option[value="approved"]').prop('disabled', true);
                        $('#modalStatusSelect option[value="shipped"]').prop('disabled', false);
                        $('#modalStatusSelect option[value="delivered"]').prop('disabled', false);
                        $('#modalStatusSelect option[value="rejected"]').prop('disabled', false);
                    } else if (currentStatus === 'pending') {
                        $('#modalStatusSelect option[value="pending"]').prop('disabled', false);
                        $('#modalStatusSelect option[value="approved"]').prop('disabled', false);
                        $('#modalStatusSelect option[value="shipped"]').prop('disabled', true);
                        $('#modalStatusSelect option[value="delivered"]').prop('disabled', true);
                        $('#modalStatusSelect option[value="rejected"]').prop('disabled', false);
                    }

                    var actionUrl = "{{ route('stocktransfer.status', ['id' => 'TRANSFER_ID']) }}".replace('TRANSFER_ID', transferId);
                    $('#statusUpdateForm').attr('action', actionUrl);
                    var modal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));
                    modal.show();
                });
            });
        </script>
    </div>
@endsection