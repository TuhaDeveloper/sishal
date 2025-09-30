@extends('erp.master')

@section('title', 'Customer Details - ' . $customer->name)

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-gray-50 min-vh-100" id="mainContent">
        @include('erp.components.header')
        
        <!-- Page Header -->
        <div class="container-fluid px-4 py-4 border-bottom bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2 text-dark fw-semibold">Customer Profile</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customers.list') }}" class="text-decoration-none text-muted">Customers</a></li>
                            <li class="breadcrumb-item active">{{ $customer->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('customers.list') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Customer
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container-fluid px-4 py-4">
            
            <!-- Customer Overview Section -->
            <div class="row mb-4">
                <!-- Customer Info Card -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start mb-4">
                                <div class="bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                    <i class="fas fa-user fa-lg text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h2 class="h4 mb-2 text-dark fw-semibold">{{ $customer->name }}</h2>
                                    <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                                        @if($customer->is_active)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Active
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>Inactive
                                            </span>
                                        @endif

                                        @if($customer->is_premium)
                                            <span class="badge bg-warning">
                                                <i class="fas fa-crown me-1"></i>Premium
                                            </span>
                                            <form action="{{ route('customers.removePremium',$customer->id) }}" method="post" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-link text-danger p-0 small">Remove Premium</button>
                                            </form>
                                        @else
                                            <form action="{{ route('customers.makePremium',$customer->id) }}" method="post" class="d-inline">
                                                @csrf
                                                <button type="submit" class="badge bg-light text-primary border border-primary">Mark as Premium</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-medium">Email Address</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-envelope text-muted me-2"></i>
                                            <span>{{ $customer->email ?: 'Not provided' }}</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-medium">Phone Number</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-phone text-muted me-2"></i>
                                            <span>{{ $customer->phone }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-medium">Member Since</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar text-muted me-2"></i>
                                            <span>{{ $customer->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-medium">Added By</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-plus text-muted me-2"></i>
                                            <span>{{ $customer->addedBy->first_name ?? 'System' }} {{ $customer->addedBy->last_name ?? '' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pb-2">
                            <h6 class="mb-0 fw-semibold text-dark">Financial Overview</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4 p-3 bg-primary bg-opacity-10 rounded text-center">
                                <h4 class="mb-1 fw-bold text-primary">৳{{ number_format($totalRevenue, 2) }}</h4>
                                <small class="text-muted">Total Revenue</small>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="h6 mb-1 text-warning">৳{{ number_format($outstandingAmount, 2) }}</div>
                                        <small class="text-muted">Outstanding</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="h6 mb-1 text-success">৳{{ number_format($paidAmount, 2) }}</div>
                                        <small class="text-muted">Paid</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="h6 mb-1 text-info">৳{{ number_format($posSales->sum('total_amount'), 2) }}</div>
                                        <small class="text-muted">POS Sales</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <div class="h6 mb-1 text-danger">৳{{ number_format($overdueAmount, 2) }}</div>
                                        <small class="text-muted">Overdue</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Sections -->
            <div class="row g-4">
                <!-- Left Sidebar: Customer Details -->
                <div class="col-lg-3">
                    <!-- Contact Information -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 pb-2">
                            <h6 class="mb-0 fw-semibold text-dark">Contact Information</h6>
                        </div>
                        <div class="card-body">
                            @if($customer->tax_number)
                            <div class="mb-3">
                                <label class="form-label small text-muted">Tax Number</label>
                                <div class="fw-medium">{{ $customer->tax_number }}</div>
                            </div>
                            @endif
                            
                            <div class="mb-3">
                                <label class="form-label small text-muted">Account Status</label>
                                <div>
                                    @if($customer->user_id)
                                        <span class="badge bg-success">
                                            <i class="fas fa-user-check me-1"></i>Registered User
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-user-times me-1"></i>Guest Customer
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($customer->address_1 || $customer->city || $customer->state)
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Address</label>
                                    <div class="small">
                                        @if($customer->address_1)
                                            <div>{{ $customer->address_1 }}</div>
                                        @endif
                                        @if($customer->address_2)
                                            <div>{{ $customer->address_2 }}</div>
                                        @endif
                                        <div>
                                            @if($customer->city){{ $customer->city }}, @endif
                                            @if($customer->state){{ $customer->state }} @endif
                                            @if($customer->zip_code){{ $customer->zip_code }}@endif
                                        </div>
                                        @if($customer->country)
                                            <div>{{ $customer->country }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Customer Notes -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold text-dark">Notes</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editNotesModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-2 small" id="customerNotes">
                                {{ $customer->notes ?: 'No notes available for this customer.' }}
                            </p>
                            <small class="text-muted">
                                Last updated: {{ $customer->updated_at->format('M d, Y') }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Main Content: Transactions -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <!-- Tab Navigation -->
                        <div class="card-header bg-white border-0">
                            <ul class="nav nav-tabs card-header-tabs border-0" id="customerTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active fw-semibold" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                                        Orders <span class="badge bg-primary ms-1">{{ $orders->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold" id="pos-tab" data-bs-toggle="tab" data-bs-target="#pos" type="button" role="tab">
                                        POS Sales <span class="badge bg-primary ms-1">{{ $posSales->count() }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab">
                                        Invoices <span class="badge bg-primary ms-1">{{ $invoices->count() }}</span>
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <!-- Orders Tab -->
                            <div class="tab-pane fade show active" id="orders" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="fw-semibold border-0 py-3">Order</th>
                                                <th class="fw-semibold border-0 py-3">Date</th>
                                                <th class="fw-semibold border-0 py-3">Amount</th>
                                                <th class="fw-semibold border-0 py-3">Status</th>
                                                <th class="fw-semibold border-0 py-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($orders as $order)
                                                <tr>
                                                    <td class="py-3">
                                                        <div class="fw-semibold text-primary">#{{ $order->order_number }}</div>
                                                        <small class="text-muted">{{ $order->items->count() }} items</small>
                                                    </td>
                                                    <td class="py-3">{{ $order->created_at->format('M d, Y') }}</td>
                                                    <td class="py-3 fw-semibold">৳{{ number_format($order->total, 2) }}</td>
                                                    <td class="py-3">
                                                        <span class="badge bg-{{ $order->status == 'completed' ? 'success' : ($order->status == 'pending' ? 'warning' : 'info') }}">
                                                            {{ ucfirst($order->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3">
                                                        <a href="{{ route('order.show', $order->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-5 text-muted">
                                                        <i class="fas fa-shopping-cart mb-2 opacity-25" style="font-size: 2rem;"></i>
                                                        <div>No orders found</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($orders->count() > 0)
                                    <div class="p-3 border-top bg-light">
                                        <a href="{{ route('order.list') }}?search={{ $customer->name }}" class="btn btn-outline-primary btn-sm d-block text-center">
                                            View All Orders
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- POS Sales Tab -->
                            <div class="tab-pane fade" id="pos" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="fw-semibold border-0 py-3">Sale</th>
                                                <th class="fw-semibold border-0 py-3">Date</th>
                                                <th class="fw-semibold border-0 py-3">Amount</th>
                                                <th class="fw-semibold border-0 py-3">Status</th>
                                                <th class="fw-semibold border-0 py-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($posSales as $sale)
                                                <tr>
                                                    <td class="py-3">
                                                        <div class="fw-semibold text-primary">#{{ $sale->sale_number }}</div>
                                                        <small class="text-muted">{{ $sale->items->count() }} items</small>
                                                    </td>
                                                    <td class="py-3">{{ $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') : $sale->created_at->format('M d, Y') }}</td>
                                                    <td class="py-3 fw-semibold">৳{{ number_format($sale->total_amount, 2) }}</td>
                                                    <td class="py-3">
                                                        <span class="badge bg-{{ $sale->status == 'approved' ? 'success' : ($sale->status == 'pending' ? 'warning' : 'info') }}">
                                                            {{ ucfirst($sale->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3">
                                                        <a href="{{ route('pos.show', $sale->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-5 text-muted">
                                                        <i class="fas fa-cash-register mb-2 opacity-25" style="font-size: 2rem;"></i>
                                                        <div>No POS sales found</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($posSales->count() > 0)
                                    <div class="p-3 border-top bg-light">
                                        <a href="{{ route('pos.list') }}?search={{ $customer->name }}" class="btn btn-outline-primary btn-sm d-block text-center">
                                            View All POS Sales
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- Invoices Tab -->
                            <div class="tab-pane fade" id="invoices" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="fw-semibold border-0 py-3">Invoice</th>
                                                <th class="fw-semibold border-0 py-3">Date</th>
                                                <th class="fw-semibold border-0 py-3">Amount</th>
                                                <th class="fw-semibold border-0 py-3">Status</th>
                                                <th class="fw-semibold border-0 py-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($invoices as $invoice)
                                                <tr>
                                                    <td class="py-3">
                                                        <div class="fw-semibold text-primary">#{{ $invoice->invoice_number }}</div>
                                                        <small class="text-muted">Due: {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') : 'N/A' }}</small>
                                                    </td>
                                                    <td class="py-3">{{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('M d, Y') : $invoice->created_at->format('M d, Y') }}</td>
                                                    <td class="py-3 fw-semibold">৳{{ number_format($invoice->total_amount, 2) }}</td>
                                                    <td class="py-3">
                                                        @php
                                                            $statusClass = $invoice->status == 'paid' ? 'success' : ($invoice->status == 'unpaid' && $invoice->due_date && \Carbon\Carbon::parse($invoice->due_date)->isPast() ? 'danger' : ($invoice->status == 'unpaid' ? 'warning' : 'info'));
                                                            $statusText = $invoice->status == 'unpaid' && $invoice->due_date && \Carbon\Carbon::parse($invoice->due_date)->isPast() ? 'Overdue' : ucfirst($invoice->status);
                                                        @endphp
                                                        <span class="badge bg-{{ $statusClass }}">
                                                            {{ $statusText }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3">
                                                        <a href="{{ route('invoice.show', $invoice->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-5 text-muted">
                                                        <i class="fas fa-file-invoice mb-2 opacity-25" style="font-size: 2rem;"></i>
                                                        <div>No invoices found</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($invoices->count() > 0)
                                    <div class="p-3 border-top bg-light">
                                        <a href="{{ route('invoice.list') }}?search={{ $customer->name }}" class="btn btn-outline-primary btn-sm d-block text-center">
                                            View All Invoices
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar: Recent Activity -->
                <div class="col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pb-2">
                            <h6 class="mb-0 fw-semibold text-dark">Recent Activity</h6>
                        </div>
                        <div class="card-body">
                            @forelse($recentActivity as $activity)
                                <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                    <div class="bg-{{ $activity['type'] == 'order' ? 'primary' : ($activity['type'] == 'invoice' ? 'success' : 'info') }} rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 8px; height: 8px; margin-top: 8px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="small text-muted mb-1">{{ $activity['date']->diffForHumans() }}</div>
                                        <div class="fw-medium small">{{ $activity['title'] }}</div>
                                        <div class="small text-muted">৳{{ number_format($activity['amount'], 2) }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-clock mb-2 opacity-25" style="font-size: 2rem;"></i>
                                    <div class="small">No recent activity</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Notes Modal -->
    <div class="modal fade" id="editNotesModal" tabindex="-1" aria-labelledby="editNotesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="editNotesModalLabel">Edit Customer Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editNotesForm" action="{{ route('customers.editNotes', $customer->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label fw-medium">Customer Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Enter customer notes here...">{{ $customer->notes }}</textarea>
                            <div class="form-text">Add any important information about this customer.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">Customer notes updated successfully.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">Failed to update customer notes.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle form submission with AJAX
        document.getElementById('editNotesForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Saving...';
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('customerNotes').textContent = formData.get('notes') || 'No notes available for this customer.';
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editNotesModal'));
                    modal.hide();
                    
                    const successToast = new bootstrap.Toast(document.getElementById('successToast'));
                    successToast.show();
                } else {
                    throw new Error(data.message || 'Failed to update notes');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
                errorToast.show();
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    </script>

    <style>
        .main-content {
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: box-shadow 0.15s ease-in-out;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: #0d6efd !important;
            background-color: transparent;
            border-bottom: 2px solid #0d6efd;
        }
        
        .nav-tabs .nav-link:hover {
            color: #0d6efd;
            border-color: transparent;
        }
        
        .table th {
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
        }
        
        .table td {
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.04);
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .btn {
            font-weight: 500;
        }
        
        .btn-outline-primary:hover {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .border-bottom:last-child {
            border-bottom: none !important;
        }
        
        .opacity-25 {
            opacity: 0.25;
        }
        
        .bg-gray-50 {
            background-color: #f8f9fa;
        }
    </style>
@endsection