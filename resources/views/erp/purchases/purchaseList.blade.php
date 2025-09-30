@extends('erp.master')

@section('title', 'Purchase Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Purchase List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Purchase List</h2>
                    <p class="text-muted mb-0">Manage purchase information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('purchase.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-adjust me-2"></i>Add Purchase
                        </a>
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            <div class="mb-3">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search (ID or Vendor)</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Purchase ID or Vendor Name">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ $filters['purchase_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="received" {{ ($filters['status'] ?? '') == 'received' ? 'selected' : '' }}>Received</option>
                            <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bill Status</label>
                        <select name="bill_status" class="form-select">
                            <option value="">All</option>
                            <option value="unpaid" {{ ($filters['bill_status'] ?? '') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid" {{ ($filters['bill_status'] ?? '') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="partial" {{ ($filters['bill_status'] ?? '') == 'partial' ? 'selected' : '' }}>Partial</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('purchase.list') }}" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Stock purchase Listing Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Purchase List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="purchaseTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Purchase</th>
                                    <th class="border-0">Vendor</th>
                                    <th class="border-0">Purchase Date</th>
                                    <th class="border-0 text-center">Status</th>
                                    <th class="border-0">Bill Status</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchases as $purchase)
                                    <tr>
                                        <td>{{ $purchase->id ?? '-' }}</td>
                                        <td>
                                            {{ $purchase->vendor->name ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('d-m-Y') : '-' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge 
                                                @if($purchase->status == 'pending') bg-warning text-dark
                                                @elseif($purchase->status == 'approved' || $purchase->status == 'paid') bg-success
                                                @elseif($purchase->status == 'unpaid' || $purchase->status == 'rejected') bg-danger
                                                @else bg-secondary
                                                @endif
                                                update-status-btn"
                                                style="cursor:pointer;"
                                                data-id="{{ $purchase->id }}"
                                                data-status="{{ $purchase->status }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#updateStatusModal"
                                            >
                                                {{ ucfirst($purchase->status ?? '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($purchase->bill && $purchase->bill->status == 'unpaid') bg-danger
                                                @elseif($purchase->bill && $purchase->bill->status == 'paid') bg-success
                                                @elseif($purchase->bill && $purchase->bill->status == 'pending') bg-warning text-dark
                                                @else bg-secondary
                                                @endif
                                            ">
                                                {{ ucfirst($purchase->bill->status ?? '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('purchase.show', $purchase->id) }}" class="text-info"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('purchase.edit', $purchase->id) }}" class="text-primary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('purchase.delete', $purchase->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this purchase?');">
                                                @csrf
                                                <button type="submit" class="btn btn-link text-danger p-0 m-0 align-baseline"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty   
                                <tr>
                                    <td colspan="6" class="text-center">No purchase Found</td></tr> 
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $purchases->firstItem() }} to {{ $purchases->lastItem() }} of {{ $purchases->total() }} purchases
                        </span>
                        {{ $purchases->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="updateStatusForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateStatusModalLabel">Update Purchase Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="purchase_id" id="modalPurchaseId">
                        <div class="mb-3">
                            <label for="modalStatus" class="form-label">Status</label>
                            <select name="status" id="modalStatus" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="received">Received</option>
                                <option value="cancelled">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var updateStatusForm = document.getElementById('updateStatusForm');
            var modalStatus = document.getElementById('modalStatus');
            document.querySelectorAll('.update-status-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.getAttribute('data-id');
                    var status = this.getAttribute('data-status');
                    updateStatusForm.action = '/erp/purchases/' + id + '/status';
                    modalStatus.value = status;
                    if (status === 'received') {
                        modalStatus.setAttribute('disabled', 'disabled');
                    } else {
                        modalStatus.removeAttribute('disabled');
                    }
                });
            });
        });
    </script>
@endsection