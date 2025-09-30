@extends('erp.master')

@section('title', 'Purchase Return Details')

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
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('purchaseReturn.list') }}" class="text-decoration-none">Purchase Returns</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Return #{{ $purchaseReturn->id }}</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Purchase Return #{{ $purchaseReturn->id }}</h2>
                    <p class="text-muted mb-0">Detailed view of purchase return information and items.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('purchaseReturn.list') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container-fluid px-4 py-4">
            <div class="row">
                <!-- Left Column - Return Details -->
                <div class="col-lg-8">
                    <!-- Return Information Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0">Return Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium text-muted">Return ID</label>
                                        <p class="mb-0 fw-bold">#{{ $purchaseReturn->id }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium text-muted">Purchase Reference</label>
                                        <p class="mb-0 fw-bold">Purchase #{{ $purchaseReturn->purchase->id ?? 'N/A' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium text-muted">Supplier</label>
                                        <p class="mb-0 fw-bold">{{ $purchaseReturn->supplier->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium text-muted">Return Date</label>
                                        <p class="mb-0 fw-bold">{{ $purchaseReturn->return_date }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium text-muted">Return Type</label>
                                        <p class="mb-0 fw-bold">{{ ucfirst(str_replace('_', ' ', $purchaseReturn->return_type)) }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium text-muted">Status</label>
                                        <div>
                                            <span class="status-badge" style="cursor: pointer;" data-return-id="{{ $purchaseReturn->id }}" data-current-status="{{ $purchaseReturn->status }}">
                                                @switch($purchaseReturn->status)
                                                    @case('pending')
                                                        <span class="badge bg-warning fs-6">Pending</span>
                                                        @break
                                                    @case('approved')
                                                        <span class="badge bg-success fs-6">Approved</span>
                                                        @break
                                                    @case('rejected')
                                                        <span class="badge bg-danger fs-6">Rejected</span>
                                                        @break
                                                    @case('processed')
                                                        <span class="badge bg-info fs-6">Processed</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary fs-6">{{ ucfirst($purchaseReturn->status) }}</span>
                                                @endswitch
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium text-muted">Created By</label>
                                        <p class="mb-0 fw-bold">{{ $purchaseReturn->createdBy->first_name ?? 'N/A' }} {{ $purchaseReturn->createdBy->last_name ?? '' }}</p>
                                    </div>
                                    @if($purchaseReturn->approved_by)
                                    <div class="mb-3">
                                        <label class="form-label fw-medium text-muted">Approved By</label>
                                        <p class="mb-0 fw-bold">{{ $purchaseReturn->approvedBy->first_name ?? 'N/A' }} {{ $purchaseReturn->approvedBy->last_name ?? '' }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            @if($purchaseReturn->reason)
                            <div class="mb-3">
                                <label class="form-label fw-medium text-muted">Reason</label>
                                <p class="mb-0">{{ $purchaseReturn->reason }}</p>
                            </div>
                            @endif
                            
                            @if($purchaseReturn->notes)
                            <div class="mb-3">
                                <label class="form-label fw-medium text-muted">Notes</label>
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0 text-muted">{{ $purchaseReturn->notes }}</pre>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Return Items Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0">Return Items</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">Product</th>
                                            <th class="border-0 text-center">Return From</th>
                                            <th class="border-0 text-center">Quantity</th>
                                            <th class="border-0 text-center">Unit Price</th>
                                            <th class="border-0 text-center">Total</th>
                                            <th class="border-0 text-center">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($purchaseReturn->items as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="ms-3">
                                                        <h6 class="mb-0 fw-medium">{{ $item->product->name ?? 'N/A' }}</h6>
                                                        <small class="text-muted">SKU: {{ $item->product->sku ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                                                                         <td class="text-center">
                                                 <span class="badge bg-light text-dark">
                                                     {{ ucfirst($item->return_from_type) }}: 
                                                     @if($item->return_from_type === 'branch' && $item->branch)
                                                         {{ $item->branch->name }}
                                                     @elseif($item->return_from_type === 'warehouse' && $item->warehouse)
                                                         {{ $item->warehouse->name }}
                                                     @elseif($item->return_from_type === 'employee' && $item->employee)
                                                         {{ $item->employee->name }}
                                                     @else
                                                         N/A
                                                     @endif
                                                 </span>
                                             </td>
                                            <td class="text-center">
                                                <span class="fw-bold">{{ $item->returned_qty }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold">${{ number_format($item->unit_price, 2) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold text-primary">${{ number_format($item->total_price, 2) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-muted">{{ Str::limit($item->reason, 30) }}</span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-inbox fa-2x mb-3"></i>
                                                    <p class="mb-0">No return items found</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary & Actions -->
                <div class="col-lg-4">
                    <!-- Summary Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0">Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-medium">Total Items:</span>
                                <span class="fw-bold">{{ $purchaseReturn->items->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-medium">Total Quantity:</span>
                                <span class="fw-bold">{{ $purchaseReturn->items->sum('returned_qty') }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-medium">Total Amount:</span>
                                <span class="fw-bold text-primary fs-5">${{ number_format($purchaseReturn->items->sum('total_price'), 2) }}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-medium">Created:</span>
                                <span class="text-muted">{{ $purchaseReturn->created_at ? $purchaseReturn->created_at->format('M d, Y H:i') : 'N/A' }}</span>
                            </div>
                            @if($purchaseReturn->approved_at)
                            <div class="d-flex justify-content-between">
                                <span class="fw-medium">Approved:</span>
                                <span class="text-muted">{{ $purchaseReturn->approved_at ? $purchaseReturn->approved_at->format('M d, Y H:i') : 'N/A' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if($purchaseReturn->status === 'pending')
                                    <a href="{{ route('purchaseReturn.edit', $purchaseReturn->id) }}" class="btn btn-warning">
                                        <i class="fas fa-edit me-2"></i>Edit Return
                                    </a>
                                    <button type="button" class="btn btn-success update-status-btn" 
                                            data-return-id="{{ $purchaseReturn->id }}" data-status="approved">
                                        <i class="fas fa-check me-2"></i>Approve Return
                                    </button>
                                    <button type="button" class="btn btn-danger update-status-btn" 
                                            data-return-id="{{ $purchaseReturn->id }}" data-status="rejected">
                                        <i class="fas fa-times me-2"></i>Reject Return
                                    </button>
                                @elseif($purchaseReturn->status === 'approved')
                                    <button type="button" class="btn btn-info update-status-btn" 
                                            data-return-id="{{ $purchaseReturn->id }}" data-status="processed">
                                        <i class="fas fa-cogs me-2"></i>Process Return
                                    </button>
                                @endif
                                <a href="{{ route('purchaseReturn.list') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-list me-2"></i>Back to List
                                </a>
                            </div>
                        </div>
                    </div>
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
    <script>
        $(document).ready(function() {
            // Handle status badge click to open modal
            $(document).on('click', '.status-badge', function() {
                const returnId = $(this).data('return-id');
                const currentStatus = $(this).data('current-status');
                
                // Set modal data
                $('#returnId').val(returnId);
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
                $('#newStatus').val('pending');
                $('#statusNotes').val('');
                $('#updateStatusBtn').prop('disabled', false);
                $('.btn-text').show();
                $('.btn-loading').hide();
            });
        });
    </script>
@endsection