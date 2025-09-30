@extends('erp.master')

@section('title', 'Purchase Return Management')

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
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Purchase Return List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Purchase Return List</h2>
                    <p class="text-muted mb-0">Manage Purchase Return information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('purchaseReturn.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-adjust me-2"></i>Add Purchase Return
                        </a>
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Search</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" placeholder="Purchase ID, Vendor name..." name="search" value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Supplier</label>
                            <select class="form-select" name="supplier_id" id="supplierFilter">
                                <option value="">All Suppliers</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Purchase</label>
                            <select class="form-select" name="purchase_id" id="purchaseFilter">
                                <option value="">All Purchases</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Created By</label>
                            <select class="form-select" name="created_by" id="userFilter">
                                <option value="">All Users</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 align-items-end mt-2">
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Return Date From</label>
                            <input type="date" class="form-control" name="return_date_from" value="{{ request('return_date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Return Date To</label>
                            <input type="date" class="form-control" name="return_date_to" value="{{ request('return_date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary flex-fill" type="submit">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                                <a href="{{ route('purchaseReturn.list') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Summary -->
        @if(request('search') || request('supplier_id') || request('purchase_id') || request('status') || request('created_by') || request('return_date_from') || request('return_date_to'))
            <div class="alert alert-info m-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Filtered Results:</strong>
                        @if(request('search'))
                            <span class="badge bg-primary me-2">Search: "{{ request('search') }}"</span>
                        @endif
                        @if(request('supplier_id'))
                            <span class="badge bg-info me-2" id="supplierBadge">Supplier: Loading...</span>
                        @endif
                        @if(request('purchase_id'))
                            <span class="badge bg-info me-2" id="purchaseBadge">Purchase: Loading...</span>
                        @endif
                        @if(request('status'))
                            <span class="badge bg-info me-2">Status: {{ ucfirst(request('status')) }}</span>
                        @endif
                        @if(request('created_by'))
                            <span class="badge bg-info me-2" id="userBadge">Created By: Loading...</span>
                        @endif
                        @if(request('return_date_from') || request('return_date_to'))
                            <span class="badge bg-info me-2">
                                Date: {{ request('return_date_from') ?? 'Any' }} to {{ request('return_date_to') ?? 'Any' }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('purchaseReturn.list') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear All Filters
                    </a>
                </div>
            </div>
        @endif

        <!-- Stock Listing Table -->
        <div class="card border-0 shadow-sm m-2">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Purchase Return List</h5>
                    <div class="text-muted">
                        <small>Total: {{ $returns->total() }} returns</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="stockTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="border-0">Purchase</th>
                                <th class="border-0">Supplier</th>
                                <th class="border-0">Return Date</th>
                                <th class="border-0 text-center">Total Items</th>
                                <th class="border-0 text-center">Status</th>
                                <th class="border-0 text-center">Note</th>
                                <th class="border-0 text-center">Issued By</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>
                        <tbody id="stockTableBody">
                            @forelse ($returns as $return)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="ms-3">
                                                <h6 class="mb-0 fw-medium">Purchase #{{ $return->purchase->id ?? 'N/A' }}</h6>
                                                <small class="text-muted">{{ $return->purchase->purchase_date ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $return->supplier->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $return->return_date->format('d M, Y') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $return->items->sum('returned_qty') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge" style="cursor: pointer;" data-return-id="{{ $return->id }}" data-current-status="{{ $return->status }}">
                                            @switch($return->status)
                                                @case('pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                    @break
                                                @case('approved')
                                                    <span class="badge bg-success">Approved</span>
                                                    @break
                                                @case('rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                    @break
                                                @case('processed')
                                                    <span class="badge bg-info">Processed</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($return->status) }}</span>
                                            @endswitch
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">{{ Str::limit($return->reason, 30) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium">{{ $return->createdBy->first_name ?? 'N/A' }} {{ $return->createdBy->last_name ?? '' }}</span>
                                    </td>
                                                                          <td>
                                          <div class="d-flex gap-2" role="group">
                                              <a href="{{ route('purchaseReturn.show',$return->id) }}" class="btn btn-sm btn-outline-info" title="View">
                                                  <i class="fas fa-eye"></i>
                                              </a>
                                                                                            @if($return->status === 'pending')
                                              <a href="{{ route('purchaseReturn.edit',$return->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                  <i class="fas fa-edit"></i>
                                              </a>
                                              @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-2x mb-3"></i>
                                            <p class="mb-0">No purchase returns found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">
                        Showing {{ $returns->firstItem() }} to {{ $returns->lastItem() }} of {{ $returns->total() }} Returns
                    </span>
                    {{ $returns->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-labelledby="statusUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusUpdateModalLabel">Update Purchase Return Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="statusUpdateForm">
                        <input type="hidden" id="returnId" name="return_id">
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">New Status</label>
                            <select class="form-select" id="newStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="processed">Processed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="statusNotes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="statusNotes" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateStatusBtn">
                        <span class="btn-text">Update Status</span>
                        <span class="btn-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Updating...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2 for supplier filter with AJAX
            $('#supplierFilter').select2({
                placeholder: 'Search Supplier',
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

            // Set selected value if supplier_id is in URL params
            @if(request('supplier_id'))
                // Fetch the supplier details to set the selected option
                $.ajax({
                    url: '{{ route('supplier.search') }}',
                    data: { q: '{{ request('supplier_id') }}' },
                    success: function(data) {
                        if (data.results && data.results.length > 0) {
                            const supplier = data.results[0];
                            const option = new Option(supplier.text, supplier.id, true, true);
                            $('#supplierFilter').append(option).trigger('change');
                            
                            // Update the supplier badge
                            $('#supplierBadge').text('Supplier: ' + supplier.text);
                        }
                    }
                });
            @endif

            // Initialize Select2 for purchase filter with AJAX
            $('#purchaseFilter').select2({
                placeholder: 'Search Purchase',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route('purchase.search') }}',
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

            // Set selected value if purchase_id is in URL params
            @if(request('purchase_id'))
                // Fetch the purchase details to set the selected option
                $.ajax({
                    url: '{{ route('purchase.search') }}',
                    data: { q: '{{ request('purchase_id') }}' },
                    success: function(data) {
                        if (data.results && data.results.length > 0) {
                            const purchase = data.results[0];
                            const option = new Option(purchase.text, purchase.id, true, true);
                            $('#purchaseFilter').append(option).trigger('change');
                            
                            // Update the purchase badge
                            $('#purchaseBadge').text('Purchase: ' + purchase.text);
                        }
                    }
                });
            @endif

            // Initialize Select2 for status filter (only for the filter form, not the modal)
            $('#filterForm select[name="status"]').select2({
                placeholder: 'Select Status',
                allowClear: true,
                width: '100%'
            });

            // Initialize Select2 for user filter with AJAX
            $('#userFilter').select2({
                placeholder: 'Search User',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route('user.search') }}',
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

            // Set selected value if created_by is in URL params
            @if(request('created_by'))
                // Fetch the user details to set the selected option
                $.ajax({
                    url: '{{ route('user.search') }}',
                    data: { q: '{{ request('created_by') }}' },
                    success: function(data) {
                        if (data.results && data.results.length > 0) {
                            const user = data.results[0];
                            const option = new Option(user.text, user.id, true, true);
                            $('#userFilter').append(option).trigger('change');
                            
                            // Update the user badge
                            $('#userBadge').text('Created By: ' + user.text);
                        }
                    }
                });
            @endif

            // Auto-submit form when filters change (optional)
            $('#supplierFilter, #purchaseFilter, #filterForm select[name="status"], #userFilter').on('change', function() {
                // Uncomment the line below if you want auto-submit on filter change
                // $('#filterForm').submit();
            });

            // Show active filters count
            function updateActiveFiltersCount() {
                let activeFilters = 0;
                $('input[name="search"]').val() && activeFilters++;
                $('#supplierFilter').val() && activeFilters++;
                $('#purchaseFilter').val() && activeFilters++;
                $('#filterForm select[name="status"]').val() && activeFilters++;
                $('#userFilter').val() && activeFilters++;
                $('input[name="return_date_from"]').val() && activeFilters++;
                $('input[name="return_date_to"]').val() && activeFilters++;

                if (activeFilters > 0) {
                    $('.btn-primary').html(`<i class="fas fa-filter me-2"></i>Filter (${activeFilters})`);
                } else {
                    $('.btn-primary').html('<i class="fas fa-filter me-2"></i>Filter');
                }
            }

            // Update count on page load
            updateActiveFiltersCount();

            // Update count when inputs change
            $('input, select').on('change keyup', updateActiveFiltersCount);

            // Handle status badge click to open modal
            $(document).on('click', '.status-badge', function() {
                const returnId = $(this).data('return-id');
                const currentStatus = $(this).data('current-status');
                
                // Set modal data
                $('#returnId').val(returnId);
                
                // Set current status as selected in dropdown
                $('#newStatus').val(currentStatus);
                
                // Show modal
                $('#statusUpdateModal').modal('show');
            });

            // Handle status update form submission
            $('#updateStatusBtn').on('click', function() {
                const returnId = $('#returnId').val();
                const newStatus = $('#newStatus').val();
                const notes = $('#statusNotes').val();
                const button = $(this);
                
                if (!newStatus) {
                    alert('Please select a new status.');
                    return;
                }

                // Show confirmation dialog
                let confirmMessage = '';
                switch(newStatus) {
                    case 'approved':
                        confirmMessage = 'Are you sure you want to approve this purchase return?';
                        break;
                    case 'rejected':
                        confirmMessage = 'Are you sure you want to reject this purchase return?';
                        break;
                    case 'processed':
                        confirmMessage = 'Are you sure you want to process this purchase return? This will adjust stock levels.';
                        break;
                }

                if (!confirm(confirmMessage)) {
                    return;
                }

                // Disable button and show loading
                button.prop('disabled', true);
                $('.btn-text').hide();
                $('.btn-loading').show();

                // Send AJAX request
                $.ajax({
                    url: `/erp/purchase-return/${returnId}/update-status`,
                    method: 'POST',
                    data: {
                        status: newStatus,
                        notes: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            alert(response.message);
                            
                            // Close modal
                            $('#statusUpdateModal').modal('hide');
                            
                            // Reload page to reflect changes
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            button.prop('disabled', false);
                            $('.btn-text').show();
                            $('.btn-loading').hide();
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while updating the status.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert('Error: ' + errorMessage);
                        button.prop('disabled', false);
                        $('.btn-text').show();
                        $('.btn-loading').hide();
                    }
                });
            });

            // Reset modal when closed
            $('#statusUpdateModal').on('hidden.bs.modal', function() {
                $('#statusUpdateForm')[0].reset();
                $('#returnId').val('');
                $('#newStatus').val('pending'); // Reset to default option
                $('#statusNotes').val('');
                $('#updateStatusBtn').prop('disabled', false);
                $('.btn-text').show();
                $('.btn-loading').hide();
            });
        });
    </script>
@endsection