@extends('erp.master')

@section('title', 'Sale Management')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-gray-50 min-vh-100" id="mainContent">
        @include('erp.components.header')

        <!-- Header Section -->
        <div class="container-fluid px-4 py-4">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 48px; height: 48px;">
                            <i class="fas fa-receipt text-white"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1 text-dark">Sale Details</h1>
                            <p class="text-muted mb-0">POS Sale #{{ $pos->sale_number ?? $pos->id }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('pos.list') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>

            <!-- Status Banner -->
            <div class="row mb-4">
                <div class="col-12">
                    <div
                        class="alert alert-{{ $pos->status == 'pending' ? 'warning' : ($pos->status == 'approved' ? 'success' : 'secondary') }} border-0 rounded-4 shadow-sm d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i
                                class="fas fa-{{ $pos->status == 'pending' ? 'clock' : ($pos->status == 'approved' ? 'check-circle' : 'info-circle') }} me-2"></i>
                            <strong>Status: {{ ucfirst($pos->status) }}</strong>
                        </div>
                        <button id="changeStatusBtn" class="btn btn-sm btn-outline-primary">Change Status</button>
                    </div>
                </div>
            </div>

            <!-- Change Status Modal -->
            <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changeStatusModalLabel">Change Sale Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changeStatusForm">
                                <div class="mb-3">
                                    <label for="saleStatusSelect" class="form-label">Status</label>
                                    <select class="form-select" id="saleStatusSelect" name="status" required>
                                        <option value="pending" {{ $pos->status == 'pending' ? 'selected' : '' }}>Pending
                                        </option>
                                        <option value="approved" {{ $pos->status == 'approved' ? 'selected' : '' }}>Approved
                                        </option>
                                        <option value="shipping" {{ $pos->status == 'shipping' ? 'selected' : '' }}>Shipping
                                        </option>
                                        <option value="delivered" {{ $pos->status == 'delivered' ? 'selected' : '' }}>
                                            Delivered</option>
                                        <option value="cancelled" {{ $pos->status == 'cancelled' ? 'selected' : '' }}>
                                            Cancelled</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="submitStatusBtn">Update Status</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-lg-4">
                    <!-- Sale Summary Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-2 me-3">
                                    <i class="fas fa-chart-line text-primary"></i>
                                </div>
                                <h5 class="card-title mb-0 fw-bold">Sale Summary</h5>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="bg-light rounded-3 p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Sale Number</span>
                                            <span class="fw-bold">{{ $pos->sale_number }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Sale Date</span>
                                            <span>{{ $pos->sale_date ? \Carbon\Carbon::parse($pos->sale_date)->format('d M Y') : '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Branch</span>
                                            <span>{{ $pos->branch->name ?? '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">Estimated Delivery</span>
                                            <span
                                                class="small">{{ $pos->estimated_delivery_date ? \Carbon\Carbon::parse($pos->estimated_delivery_date)->format('d M Y') : '-' }}
                                                {{ $pos->estimated_delivery_time ? '(' . $pos->estimated_delivery_time . ')' : '' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Information -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-info bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-users text-info"></i>
                                    </div>
                                    <h5 class="card-title mb-0 fw-bold">Team</h5>
                                </div>
                                @if(!$pos->employee)
                                    <button class="btn btn-outline-primary py-1" style="height: max-content;"
                                        id="addTechnicianBtn">Add Technician</button>
                                @endif
                            </div>
                            <div class="row g-3">
                                @if($pos->employee)
                                    <div class="col-12">
                                        <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                                style="width: 40px; height: 40px;">
                                                <i class="fas fa-tools text-white small"></i>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ @$pos->employee->user->first_name ?? '-' }}
                                                    {{ @$pos->employee->user->last_name ?? '' }}</div>
                                                <small class="text-muted">Technician</small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-12">
                                    <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 40px; height: 40px;">
                                            <i class="fas fa-user-tie text-white small"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $pos->soldBy->first_name ?? '-' }}
                                                {{ $pos->soldBy->last_name ?? '' }}</div>
                                            <small class="text-muted">Sales Representative</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-warning bg-opacity-10 rounded-3 p-2 me-3">
                                    <i class="fas fa-user text-warning"></i>
                                </div>
                                <h5 class="card-title mb-0 fw-bold">Customer Information</h5>
                                <button id="editAddressBtn" class="btn btn-sm btn-outline-primary ms-auto">Edit Address</button>
                            </div>
                            <div class="customer-info">
                                <div class="mb-3">
                                    <label class="small text-muted">Name</label>
                                    <div class="fw-medium">{{ $pos->customer->name ?? 'Walk-in-Customer' }}</div>
                                </div>
                                @if($pos->customer)
                                <div class="mb-3">
                                    <label class="small text-muted">Email</label>
                                    <div>{{ $pos->customer->email ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted">Phone</label>
                                    <div>{{ $pos->customer->phone ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted">Billing Address</label>
                                    @php $billing = @$pos->invoice->invoiceAddress ?? null; @endphp
                                    <div class="small">
                                        @if($billing)
                                            {{ $billing->billing_address_1 }} {{ $billing->billing_address_2 }}<br>
                                            {{ $billing->billing_city }}, {{ $billing->billing_state }}<br>
                                            {{ $billing->billing_country }} {{ $billing->billing_zip_code }}
                                        @else
                                            <span class="text-muted">No billing address</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="small text-muted">Shipping Address</label>
                                    <div class="small">
                                        @if($billing)
                                            {{ $billing->shipping_address_1 }} {{ $billing->shipping_address_2 }}<br>
                                            {{ $billing->shipping_city }}, {{ $billing->shipping_state }}<br>
                                            {{ $billing->shipping_country }} {{ $billing->shipping_zip_code }}
                                        @else
                                            <span class="text-muted">No shipping address</span>
                                        @endif
                                    </div>
                                </div>
                                
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-8">
                    <!-- Sale Items -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-secondary bg-opacity-10 rounded-3 p-2 me-3">
                                    <i class="fas fa-shopping-cart text-secondary"></i>
                                </div>
                                <h5 class="card-title mb-0 fw-bold">Sale Items</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0 fw-bold">Product</th>
                                            <th class="border-0 fw-bold">SKU</th>
                                            <th class="border-0 fw-bold text-center">Qty</th>
                                            <th class="border-0 fw-bold text-end">Unit Price</th>
                                            <th class="border-0 fw-bold text-end">Total</th>
                                            <th class="border-0 fw-bold text-end">Currently At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pos->items as $item)
                                            <tr>
                                                <td class="border-0 py-3">
                                                    <div class="fw-medium">{{ $item->product->name ?? '-' }}</div>
                                                </td>
                                                <td class="border-0 py-3">
                                                    <span
                                                        class="badge bg-light text-dark">{{ $item->product->sku ?? '-' }}</span>
                                                </td>
                                                <td class="border-0 py-3 text-center">
                                                    <span class="badge bg-primary">{{ $item->quantity }}</span>
                                                </td>
                                                <td class="border-0 py-3 text-end">{{ number_format($item->unit_price, 2) }}৳
                                                </td>
                                                <td class="border-0 py-3 text-end fw-bold">
                                                    {{ number_format($item->total_price, 2) }}৳</td>
                                                <td class="border-0 py-3 fw-bold text-end">
                                                    @if($pos->status == 'delivered')
                                                        Delivered
                                                    @else
                                                        {{ $item->current_position_type == 'branch' ? $item->branch->name : @$item->technician->user->first_name . ' ' . @$item->technician->user->last_name }}
                                                    @endif

                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="row g-4 mb-4">
                        <!-- Invoice Card -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success bg-opacity-10 rounded-3 p-2 me-3">
                                            <i class="fas fa-file-invoice text-success"></i>
                                        </div>
                                        <h5 class="card-title mb-0 fw-bold">Invoice</h5>
                                    </div>
                                    <div class="invoice-details">
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                            <span class="text-muted">Invoice Number</span>
                                            <a href="{{ route('invoice.show',@$pos->invoice->id) }}" class="fw-bold" style="text-decoration: none;">{{ @$pos->invoice->invoice_number ?? '-' }}</a>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-muted">Status</span>
                                            <span
                                                class="badge bg-{{ @$pos->invoice->status == 'unpaid' ? 'danger' : (@$pos->invoice->status == 'paid' ? 'success' : (@$pos->invoice->status == 'partial' ? 'warning' : 'secondary')) }} rounded-pill">
                                                {{ ucfirst(@$pos->invoice->status ?? '-') }}
                                            </span>
                                        </div>
                                        <div class="bg-light rounded-3 p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted">Total Amount</span>
                                                <span
                                                    class="fw-bold h6 mb-0">{{ number_format(@$pos->invoice->total_amount ?? 0, 2) }}৳</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-success">Paid Amount</span>
                                                <span
                                                    class="text-success fw-bold">{{ number_format(@$pos->invoice->paid_amount ?? 0, 2) }}৳</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-danger">Due Amount</span>
                                                <span
                                                    class="text-danger fw-bold">{{ number_format(@$pos->invoice->due_amount ?? 0, 2) }}৳</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payments Card -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-warning bg-opacity-10 rounded-3 p-2 me-3">
                                            <i class="fas fa-credit-card text-warning"></i>
                                        </div>
                                        <h5 class="card-title mb-0 fw-bold">Payment History</h5>
                                        @if(@$pos->invoice->status != 'paid')
                                            <button id="addPaymentBtn" class="btn btn-sm btn-outline-success ms-auto">Add
                                                Payment</button>
                                        @endif
                                    </div>
                                    <div class="payments-list" style="max-height: 250px; overflow-y: auto;">
                                        @forelse($pos->payments as $payment)
                                            <div class="payment-item p-3 mb-2 bg-light rounded-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-medium">{{ number_format($payment->amount, 2) }}৳</span>
                                                    <span
                                                        class="badge bg-primary rounded-pill">{{ ucfirst($payment->payment_method) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">{{ $payment->payment_date }}</small>
                                                </div>
                                                @if($payment->note)
                                                    <small class="text-muted d-block mt-1">{{ $payment->note }}</small>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="text-center py-4">
                                                <i class="fas fa-credit-card text-muted mb-2"
                                                    style="font-size: 2rem; opacity: 0.3;"></i>
                                                <p class="text-muted mb-0">No payments recorded</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-3 p-2 me-3">
                                    <i class="fas fa-sticky-note text-info"></i>
                                </div>
                                <h5 class="card-title mb-0 fw-bold">Note</h5>
                                <button id="editNoteBtn" class="btn btn-sm btn-outline-primary ms-auto">Edit</button>
                            </div>
                            <div class="notes-content bg-light rounded-3 p-3 position-relative">
                                <p class="mb-0" id="noteText">{{ $pos->notes ?? 'No note available for this sale.' }}</p>
                                <div id="noteEditArea" style="display:none;">
                                    <textarea id="noteTextarea" class="form-control mb-2"
                                        rows="3">{{ $pos->notes }}</textarea>
                                    <div class="d-flex gap-2">
                                        <button id="saveNoteBtn" class="btn btn-success btn-sm">Save</button>
                                        <button id="cancelNoteBtn" class="btn btn-secondary btn-sm">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Technician Modal -->
    <div class="modal fade" id="assignTechnicianModal" tabindex="-1" aria-labelledby="assignTechnicianModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignTechnicianModalLabel">Assign Technician</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="technicianSearchInput" class="form-label">Search Technician</label>
                    <select id="technicianSearchInput" class="form-select" style="width: 100%;"></select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="assignTechnicianBtn">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPaymentForm">
                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">Amount</label>
                            <input type="number" max="{{ @$pos->invoice->due_amount }}"
                                value="{{ @$pos->invoice->due_amount }}" class="form-control" id="paymentAmount"
                                name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="paymentMethod" class="form-label">Payment Method</label>
                            <select class="form-select" id="paymentMethod" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="mobile">Mobile Payment</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="paymentDate" class="form-label">Cash Received By</label>
                            <select name="received_by" id="received_by" class="form-select">
                                <option value="">Select Receiver</option>
                                @if ($pos->employee)
                                    <option value="{{ $pos->employee->user->id }}">{{ $pos->employee->user->first_name . ' ' . $pos->employee->user->last_name }} (Technician)</option>
                                @endif
                                @if ($pos->soldBy)
                                    <option value="{{ $pos->soldBy->id }}">{{ @$pos->soldBy->first_name . ' ' . @$pos->soldBy->last_name }} (Sales Representative)</option>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="paymentAccount" class="form-label">Account (optional)</label>
                            <select class="form-select" id="paymentAccount" name="account_id">
                                <option value="">Select Account</option>
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->provider_name . ' - ' . $bankAccount->account_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="paymentNote" class="form-label">Note (optional)</label>
                            <textarea class="form-control" id="paymentNote" name="note" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="submitPaymentBtn">Add Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Address Modal -->
    @if($pos->customer)
    <div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editAddressModalLabel">Edit Billing & Shipping Address</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="editAddressForm">
              <h6>Billing Address</h6>
              <div class="mb-2"><input type="text" class="form-control" name="billing_address_1" placeholder="Address 1" value="{{ $billing && $billing->billing_address_1 ? $billing->billing_address_1 : (@$pos->customer->address_1 ?? '') }}" required></div>
              <div class="mb-2"><input type="text" class="form-control" name="billing_address_2" placeholder="Address 2" value="{{ $billing && $billing->billing_address_2 ? $billing->billing_address_2 : (@$pos->customer->address_2 ?? '') }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="billing_city" placeholder="City" value="{{ $billing && $billing->billing_city ? $billing->billing_city : (@$pos->customer->city ?? '') }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="billing_state" placeholder="State" value="{{ $billing && $billing->billing_state ? $billing->billing_state : (@$pos->customer->state ?? '') }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="billing_country" placeholder="Country" value="{{ $billing && $billing->billing_country ? $billing->billing_country : (@$pos->customer->country ?? '') }}"></div>
              <div class="mb-3"><input type="text" class="form-control" name="billing_zip_code" placeholder="Zip Code" value="{{ $billing && $billing->billing_zip_code ? $billing->billing_zip_code : (@$pos->customer->zip_code ?? '') }}"></div>
              <h6 class="d-flex align-items-center">Shipping Address <button type="button" class="btn btn-link btn-sm ms-auto" id="copyBillingToShipping">Copy Billing to Shipping</button></h6>
              <div class="mb-2"><input type="text" class="form-control" name="shipping_address_1" placeholder="Address 1" id="shipping_address_1" value="{{ $billing->shipping_address_1 ?? '' }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="shipping_address_2" placeholder="Address 2" id="shipping_address_2" value="{{ $billing->shipping_address_2 ?? '' }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="shipping_city" placeholder="City" id="shipping_city" value="{{ $billing->shipping_city ?? '' }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="shipping_state" placeholder="State" id="shipping_state" value="{{ $billing->shipping_state ?? '' }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="shipping_country" placeholder="Country" id="shipping_country" value="{{ $billing->shipping_country ?? '' }}"></div>
              <div class="mb-3"><input type="text" class="form-control" name="shipping_zip_code" placeholder="Zip Code" id="shipping_zip_code" value="{{ $billing->shipping_zip_code ?? '' }}"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveAddressBtn">Save Address</button>
          </div>
        </div>
      </div>
    </div>
    @endif

    <style>
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .customer-info>div {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding-bottom: 0.5rem;
        }

        .customer-info>div:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .payment-item {
            transition: background-color 0.2s ease;
        }

        .payment-item:hover {
            background-color: rgba(0, 0, 0, 0.05) !important;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }

        .bg-gray-50 {
            background-color: #f8fafc !important;
        }

        .rounded-4 {
            border-radius: 1rem !important;
        }

        .rounded-pill {
            border-radius: 50rem !important;
        }

        .shadow-sm {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }
    </style>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            // Open modal on button click
            $(document).on('click', '#addTechnicianBtn', function () {
                $('#assignTechnicianModal').modal('show');
            });

            // Re-initialize select2 every time the modal is shown
            $('#assignTechnicianModal').on('shown.bs.modal', function () {
                if ($('#technicianSearchInput').hasClass('select2-hidden-accessible')) {
                    $('#technicianSearchInput').select2('destroy');
                }
                $('#technicianSearchInput').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Type name, email or phone...',
                    minimumInputLength: 1,
                    dropdownParent: $('#assignTechnicianModal'),
                    ajax: {
                        url: "{{ route('employees.search') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(function (emp) {
                                    return {
                                        id: emp.id,
                                        text: emp.name + (emp.email ? ' (' + emp.email + ')' : '') + (emp.phone ? ' - ' + emp.phone : '')
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    width: '100%'
                });
            });

            // Assign button: send AJAX POST to assign technician
            $('#assignTechnicianBtn').on('click', function () {
                var selectedId = $('#technicianSearchInput').val();
                var selectedText = $('#technicianSearchInput option:selected').text();
                if (selectedId) {
                    var saleId = @json($pos->id);
                    var url = '/erp/pos/assign-tech/' + saleId + '/' + selectedId;
                    $.ajax({
                        url: url,
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            if (response.success) {
                                $('#assignTechnicianModal').modal('hide');
                                location.reload();
                            } else {
                                alert(response.message || 'Failed to assign technician.');
                            }
                        },
                        error: function (xhr) {
                            let msg = 'Failed to assign technician.';
                            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                            alert(msg);
                        }
                    });
                } else {
                    alert('Please select a technician.');
                }
            });

            // Note edit logic
            $('#editNoteBtn').on('click', function () {
                $('#noteText').hide();
                $('#noteEditArea').show();
                $('#noteTextarea').focus();
            });
            $('#cancelNoteBtn').on('click', function () {
                $('#noteEditArea').hide();
                $('#noteText').show();
            });
            $('#saveNoteBtn').on('click', function () {
                var newNote = $('#noteTextarea').val();
                var saleId = @json($pos->id);
                $.ajax({
                    url: '/erp/pos/update-note/' + saleId,
                    type: 'POST',
                    data: { note: newNote },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        if (response.success) {
                            $('#noteText').text(newNote || 'No note available for this sale.').show();
                            $('#noteEditArea').hide();
                        } else {
                            alert(response.message || 'Failed to update note.');
                        }
                    },
                    error: function (xhr) {
                        let msg = 'Failed to update note.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            // Open Add Payment Modal
            $(document).on('click', '#addPaymentBtn', function () {
                $('#addPaymentModal').modal('show');
            });
            // Initialize payment method dependent fields visibility when modal is shown
            $('#addPaymentModal').on('shown.bs.modal', function () {
                togglePaymentFields();
            });
            // Toggle fields on payment method change
            $(document).on('change', '#paymentMethod', function () {
                togglePaymentFields();
            });
            // Helper to toggle Cash Received By vs Account fields
            function togglePaymentFields() {
                var method = $('#paymentMethod').val();
                var receivedGroup = $('#received_by').closest('.mb-3');
                var accountGroup = $('#paymentAccount').closest('.mb-3');
                if (method === 'cash') {
                    receivedGroup.show();
                    accountGroup.hide();
                } else {
                    receivedGroup.hide();
                    accountGroup.show();
                }
            }
            // Submit Payment
            $('#submitPaymentBtn').on('click', function () {
                var saleId = @json($pos->id);
                var form = $('#addPaymentForm');
                var data = form.serialize();
                $.ajax({
                    url: '/erp/pos/add-payment/' + saleId,
                    type: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        if (response.success) {
                            $('#addPaymentModal').modal('hide');
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to add payment.');
                        }
                    },
                    error: function (xhr) {
                        let msg = 'Failed to add payment.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            // Open Change Status Modal
            $(document).on('click', '#changeStatusBtn', function () {
                $('#changeStatusModal').modal('show');
            });
            // Submit Status Change
            $('#submitStatusBtn').on('click', function () {
                var saleId = @json($pos->id);
                var form = $('#changeStatusForm');
                var data = form.serialize();
                $.ajax({
                    url: '{{ route('pos.update.status', ['saleId' => $pos->id]) }}',
                    type: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        if (response.success) {
                            $('#changeStatusModal').modal('hide');
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to update status.');
                        }
                    },
                    error: function (xhr) {
                        let msg = 'Failed to update status.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            // Open Edit Address Modal
            $(document).on('click', '#editAddressBtn', function() {
                $('#editAddressModal').modal('show');
            });
            // Save Address
            $('#saveAddressBtn').on('click', function() {
                var invoiceId = @json(@$pos->invoice->id);
                var form = $('#editAddressForm');
                var data = form.serialize();
                $.ajax({
                    url: '{{ route('pos.add.address', ['invoiceId' => $pos->invoice->id]) }}',
                    type: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        $('#editAddressModal').modal('hide');
                        location.reload();
                    },
                    error: function(xhr) {
                        let msg = 'Failed to update address.';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            // Copy billing to shipping
            $('#copyBillingToShipping').on('click', function() {
                $("#shipping_address_1").val($('input[name="billing_address_1"]').val());
                $("#shipping_address_2").val($('input[name="billing_address_2"]').val());
                $("#shipping_city").val($('input[name="billing_city"]').val());
                $("#shipping_state").val($('input[name="billing_state"]').val());
                $("#shipping_country").val($('input[name="billing_country"]').val());
                $("#shipping_zip_code").val($('input[name="billing_zip_code"]').val());
            });
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
@endpush