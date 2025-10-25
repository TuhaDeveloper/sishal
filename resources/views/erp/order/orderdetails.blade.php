@extends('erp.master')

@section('title', $order->order_number.' | Order Management')

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
                            <h1 class="h3 fw-bold mb-1 text-dark">Order Details</h1>
                            <p class="text-muted mb-0">Order #{{ $order->order_number ?? $order->id }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('order.list') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>

            <!-- Status Banner -->
            <div class="row mb-4">
                <div class="col-12">
                    <div
                        class="alert alert-{{ $order->status == 'pending' ? 'warning' : ($order->status == 'approved' ? 'success' : 'secondary') }} border-0 rounded-4 shadow-sm d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i
                                class="fas fa-{{ $order->status == 'pending' ? 'clock' : ($order->status == 'approved' ? 'check-circle' : 'info-circle') }} me-2"></i>
                            <strong>Status: {{ ucfirst($order->status) }}</strong>
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
                            <h5 class="modal-title" id="changeStatusModalLabel">Change order Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changeStatusForm">
                                <div class="mb-3">
                                    <label for="orderStatusSelect" class="form-label">Status</label>
                                    <select class="form-select" id="orderStatusSelect" name="status" required>
                                        @php
                                            $status = $order->status;
                                        @endphp
                                        <option value="pending"
                                            {{ $status == 'pending' ? 'selected' : '' }}
                                            {{ !in_array($status, ['approved', 'pending']) ? 'disabled' : '' }}>
                                            Pending
                                        </option>
                                        <option value="approved"
                                            {{ $status == 'approved' ? 'selected' : '' }}
                                            {{ !in_array($status, ['pending', 'approved']) ? 'disabled' : '' }}>
                                            Approved
                                        </option>
                                        <option value="shipping"
                                            {{ $status == 'shipping' ? 'selected' : '' }}
                                            {{ !in_array($status, ['approved', 'shipping']) ? 'disabled' : '' }}>
                                            Shipping
                                        </option>
                                        <option value="delivered"
                                            {{ $status == 'delivered' ? 'selected' : '' }}
                                            {{ $status != 'shipping' ? 'disabled' : '' }}>
                                            Delivered
                                        </option>
                                        <option value="cancelled"
                                            {{ $status == 'cancelled' ? 'selected' : '' }}
                                            {{ !in_array($status, ['pending', 'approved', 'shipping']) ? 'disabled' : '' }}>
                                            Cancelled
                                        </option>
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
                    <!-- order Summary Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-3 p-2 me-3">
                                    <i class="fas fa-chart-line text-primary"></i>
                                </div>
                                <h5 class="card-title mb-0 fw-bold">Order Summary</h5>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="bg-light rounded-3 p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Order Number</span>
                                            <span class="fw-bold">{{ $order->order_number }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Order Date</span>
                                            <span>{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y') : '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">Estimated Delivery</span>
                                            <span
                                                class="small">
                                                @if ($order->estimated_delivery_date)
                                                    {{ \Carbon\Carbon::parse($order->estimated_delivery_date)->format('d M Y') }}
                                                    {{ $order->estimated_delivery_time ? '(' . \Carbon\Carbon::parse($order->estimated_delivery_time)->format('h:i A') . ')' : '' }}
                                                    <button id="edit-estimated-delivery-btn" type="button" class="btn btn-sm btn-outline-secondary ms-1" data-bs-toggle="modal" data-bs-target="#estimatedDeliveryModal" style="padding:2px 6px; font-size:14px;">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                @else
                                                    <button id="add-estimated-delivery-btn" type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#estimatedDeliveryModal" style="padding:2px 6px; font-size:14px;">
                                                        +
                                                    </button>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Information - HIDDEN -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4" style="display: none;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-info bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-users text-info"></i>
                                    </div>
                                    <h5 class="card-title mb-0 fw-bold">Team</h5>
                                </div>
                                @if(!$order->employee)
                                    <button class="btn btn-outline-primary py-1" style="height: max-content;"
                                        id="addTechnicianBtn">Add Technician</button>
                                @endif
                            </div>
                            <div class="row g-3">
                                @if($order->employee)
                                    <div class="col-12">
                                        <div class="d-flex align-items-center p-3 bg-light rounded-3 position-relative">
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                                style="width: 40px; height: 40px;">
                                                <i class="fas fa-tools text-white small"></i>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ @$order->employee->user->first_name ?? '-' }}
                                                    {{ @$order->employee->user->last_name ?? '' }}</div>
                                                <small class="text-muted">Technician | {{@$order->employee->phone}}</small>
                                            </div>
                                            <button id="removeTechnicianBtn" class="btn btn-sm btn-outline-danger position-absolute" style="right: 20px;" data-order-id="{{ $order->id }}"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                @else
                                <p class="text-gray text-center">No Technician Assigned</p>
                                @endif
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
                                    <div class="fw-medium">{{ @$order->name ?? 'Walk-in-Customer' }}</div>
                                </div>
                                @if($order->customer)
                                <div class="mb-3">
                                    <label class="small text-muted">Email</label>
                                    <div>{{ @$order->email ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted">Phone</label>
                                    <div>{{ @$order->phone ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted">Billing Address</label>
                                    @php $billing = @$order->invoice->invoiceAddress ?? null; @endphp
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
                    <!-- order Items -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-secondary bg-opacity-10 rounded-3 p-2 me-3">
                                    <i class="fas fa-shopping-cart text-secondary"></i>
                                </div>
                                <h5 class="card-title mb-0 fw-bold">Order Items</h5>
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
                                        @foreach($order->items as $item)
                                            <tr>
                                                <td class="border-0 py-3">
                                                    <div class="fw-medium">{{ @$item->product->name ?? '-' }}</div>
                                                </td>
                                                <td class="border-0 py-3">
                                                    <span
                                                        class="badge bg-light text-dark">{{ @$item->product->sku ?? '-' }}</span>
                                                </td>
                                                <td class="border-0 py-3 text-center">
                                                    <span class="badge bg-primary">{{ $item->quantity }}</span>
                                                </td>
                                                <td class="border-0 py-3 text-end">{{ number_format($item->unit_price, 2) }}৳
                                                </td>
                                                <td class="border-0 py-3 text-end fw-bold">
                                                    {{ number_format($item->total_price, 2) }}৳</td>
                                                <td class="border-0 py-3 fw-bold text-end">
                                                    @if($order->status == 'delivered')
                                                        Delivered
                                                    @elseif($order->status == 'shipping' || $order->status == 'cancelled')
                                                        {{ ucfirst($order->status) }}
                                                    @elseif(is_null($item->current_position_type))
                                                        <button class="btn btn-sm btn-primary request-stock-btn" data-product-id="{{ $item->product_id }}" data-order-item-id="{{ $item->id }}">Request Stock</button>
                                                    @else
                                                        {{ $item->current_position_type == 'branch'
                                                            ? ($item->branch ? $item->branch->name : 'Unknown Branch')
                                                            : ($item->current_position_type == 'warehouse'
                                                                ? ($item->warehouse ? $item->warehouse->name : 'Unknown Warehouse')
                                                                : (@$item->technician->user->first_name . ' ' . @$item->technician->user->last_name)
                                                              )
                                                        }}
                                                        <div class="d-flex gap-2 fw-normal justify-content-end" style="font-size: 10px;">
                                                            @if(@$item->current_position_id != $order->employee_id)
                                                            <a href="#" class="request-stock-btn" data-product-id="{{ $item->product_id }}" data-order-item-id="{{ $item->id }}" data-current-type="{{ $item->current_position_type }}" data-current-id="{{ $item->current_position_id }}">Change Stock</a>
                                                            |
                                                            <a href="#" class="transfer-to-employee-link" data-product-id="{{ $item->product_id }}" data-order-item-id="{{ $item->id }}" data-current-type="{{ $item->current_position_type }}" data-current-id="{{ $item->current_position_id }}">Transfer To Employee</a>
                                                            @endif
                                                        </div>
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
                                            <a href="{{ route('invoice.show', @$order->invoice->id) }}" class="fw-bold" style="text-decoration: none;">{{ @$order->invoice->invoice_number ?? '-' }}</a>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-muted">Status</span>
                                            <span
                                                class="badge bg-{{ @$order->invoice->status == 'unpaid' ? 'danger' : (@$order->invoice->status == 'paid' ? 'success' : (@$order->invoice->status == 'partial' ? 'warning' : 'secondary')) }} rounded-pill">
                                                {{ ucfirst(@$order->invoice->status ?? '-') }}
                                            </span>
                                        </div>
                                        <div class="bg-light rounded-3 p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted">Total Amount</span>
                                                <span
                                                    class="fw-bold h6 mb-0">{{ number_format(@$order->invoice->total_amount ?? 0, 2) }}৳</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-success">Paid Amount</span>
                                                <span
                                                    class="text-success fw-bold">{{ number_format(@$order->invoice->paid_amount ?? 0, 2) }}৳</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-danger">Due Amount</span>
                                                <span
                                                    class="text-danger fw-bold">{{ number_format(@$order->invoice->due_amount ?? 0, 2) }}৳</span>
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
                                        @if(@$order->invoice->status != 'paid')
                                            <button id="addPaymentBtn" class="btn btn-sm btn-outline-success ms-auto">Add
                                                Payment</button>
                                        @endif
                                    </div>
                                    <div class="payments-list" style="max-height: 250px; overflow-y: auto;">
                                        @forelse(@$order->invoice->payments as $payment)
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
                            <div class="notes-content bg-light rounded-3 p-3 orderition-relative">
                                <p class="mb-0" id="noteText">{{ $order->notes ?? 'No note available for this order.' }}</p>
                                <div id="noteEditArea" style="display:none;">
                                    <textarea id="noteTextarea" class="form-control mb-2"
                                        rows="3">{{ $order->notes }}</textarea>
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
                            <input type="number" max="{{ @$order->invoice->due_amount }}"
                                value="{{ @$order->invoice->due_amount }}" class="form-control" id="paymentAmount"
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
                                @if ($order->employee)
                                    <option value="{{ $order->employee->user->id }}">{{ @$order->employee->user->first_name . ' ' . @$order->employee->user->last_name }} (Technician)</option>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="paymentAccount" class="form-label">Account (optional)</label>
                            <select class="form-select" id="paymentAccount" name="account_id">
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
    @if($order->customer)
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
              <div class="mb-2"><input type="text" class="form-control" name="billing_address_1" placeholder="Address 1" value="{{ $billing && $billing->billing_address_1 ? $billing->billing_address_1 : (@$order->customer->address_1 ?? '') }}" required></div>
              <div class="mb-2"><input type="text" class="form-control" name="billing_address_2" placeholder="Address 2" value="{{ $billing && $billing->billing_address_2 ? $billing->billing_address_2 : (@$order->customer->address_2 ?? '') }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="billing_city" placeholder="City" value="{{ $billing && $billing->billing_city ? $billing->billing_city : (@$order->customer->city ?? '') }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="billing_state" placeholder="State" value="{{ $billing && $billing->billing_state ? $billing->billing_state : (@$order->customer->state ?? '') }}"></div>
              <div class="mb-2"><input type="text" class="form-control" name="billing_country" placeholder="Country" value="{{ $billing && $billing->billing_country ? $billing->billing_country : (@$order->customer->country ?? '') }}"></div>
              <div class="mb-3"><input type="text" class="form-control" name="billing_zip_code" placeholder="Zip Code" value="{{ $billing && $billing->billing_zip_code ? $billing->billing_zip_code : (@$order->customer->zip_code ?? '') }}"></div>
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

    <!-- Bootstrap Modal for adding estimated delivery date/time -->
    <div class="modal fade" id="estimatedDeliveryModal" tabindex="-1" aria-labelledby="estimatedDeliveryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="estimatedDeliveryModalLabel">Set Estimated Delivery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="estimatedDeliveryForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="estimated_delivery_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="estimated_delivery_date" name="estimated_delivery_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="estimated_delivery_time" class="form-label">Time</label>
                            <input type="time" class="form-control" id="estimated_delivery_time" name="estimated_delivery_time" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Request Stock Modal -->
    <div class="modal fade" id="requestStockModal" tabindex="-1" aria-labelledby="requestStockModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="requestStockModalLabel">Available Stocks</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="stockList"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

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
            var currentOrderItemId = null;
            var currentPositionType = null;
            var currentPositionId = null;
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

            // Assign button: send AJAX orderT to assign technician
            $('#assignTechnicianBtn').on('click', function () {
                var selectedId = $('#technicianSearchInput').val();
                var selectedText = $('#technicianSearchInput option:selected').text();
                if (selectedId) {
                    var orderId = @json($order->id);
                    var url = '/erp/order/update-technician/' + orderId + '/' + selectedId;
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
                var orderId = @json($order->id);
                $.ajax({
                    url: '/erp/order/update-note/' + orderId,
                    type: 'POST',
                    data: { note: newNote },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        if (response.success) {
                            $('#noteText').text(newNote || 'No note available for this order.').show();
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
            // Initialize payment fields visibility when modal is shown
            $('#addPaymentModal').on('shown.bs.modal', function () {
                toggleOrderPaymentFields();
            });
            // Toggle account field on payment method change
            $(document).on('change', '#paymentMethod', function () {
                toggleOrderPaymentFields();
            });
            // Helper to show/hide Cash Received By vs Account based on method
            function toggleOrderPaymentFields() {
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
                var orderId = @json($order->id);
                var form = $('#addPaymentForm');
                var data = form.serialize();
                $.ajax({
                    url: '/erp/order/add-payment/' + orderId,
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
                var orderId = @json($order->id);
                var form = $('#changeStatusForm');
                var data = form.serialize();
                $.ajax({
                    url: '{{ route('order.updateStatus', ['id' => $order->id]) }}',
                    type: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        if (response.success) {
                            $('#changeStatusModal').modal('hide');
                            showCustomAlert('Order status updated successfully!', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showCustomAlert(response.message || 'Failed to update status.', 'error');
                        }
                    },
                    error: function (xhr) {
                        let msg = 'Failed to update status.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        showCustomAlert(msg, 'error');
                    }
                });
            });

            // Open Edit Address Modal
            $(document).on('click', '#editAddressBtn', function() {
                $('#editAddressModal').modal('show');
            });
            // Save Address
            $('#saveAddressBtn').on('click', function() {
                var invoiceId = @json(@$order->invoice->id);
                var form = $('#editAddressForm');
                var data = form.serialize();
                $.ajax({
                    url: '{{ route('pos.add.address', ['invoiceId' => @$order->invoice->id]) }}',
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

            // Clear modal fields when opened
            $('#estimatedDeliveryModal').on('show.bs.modal', function () {
                $('#estimated_delivery_date').val('');
                $('#estimated_delivery_time').val('');
            });

            // Add/Edit modal logic
            $('#estimatedDeliveryModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var mode = button && button.attr('id') === 'edit-estimated-delivery-btn' ? 'edit' : 'add';
                if (mode === 'edit') {
                    // Pre-fill with current values
                    $('#estimated_delivery_date').val(@json($order->estimated_delivery_date));
                    $('#estimated_delivery_time').val(@json($order->estimated_delivery_time));
                } else {
                    $('#estimated_delivery_date').val('');
                    $('#estimated_delivery_time').val('');
                }
            });

            $('#estimatedDeliveryForm').on('submit', function(e) {
                e.preventDefault();
                var orderId = @json($order->id);
                var form = $(this);
                var data = form.serialize();
                $.ajax({
                    url: '/erp/order/set-estimated-delivery/' + orderId,
                    type: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            var modal = bootstrap.Modal.getInstance(document.getElementById('estimatedDeliveryModal'));
                            if (modal) modal.hide();
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to set estimated delivery date/time.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Failed to set estimated delivery date/time.';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            $(document).on('click', '#removeTechnicianBtn', function () {
                if (!confirm('Are you sure you want to remove the technician from this order?')) return;
                var orderId = $(this).data('order-id');
                $.ajax({
                    url: '{{ route('order.deleteTechnician', ['id' => '__ORDER_ID__']) }}'.replace('__ORDER_ID__', orderId),
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to remove technician.');
                        }
                    },
                    error: function (xhr) {
                        let msg = 'Failed to remove technician.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            $(document).on('click', '.request-stock-btn', function() {
                var productId = $(this).data('product-id');
                currentOrderItemId = $(this).data('order-item-id');
                currentPositionType = $(this).data('current-type');
                currentPositionId = $(this).data('current-id');
                $('#stockList').html('<div class="text-center">Loading...</div>');
                $('#requestStockModal').modal('show');
                $.ajax({
                    url: '/erp/order/product-stocks/' + productId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.stocks.length > 0) {
                            var html = '<ul class="list-group">';
                            response.stocks.forEach(function(stock) {
                                var isSelected = (stock.type === currentPositionType && (
                                    (stock.type === 'branch' && stock.branch_id == currentPositionId) ||
                                    (stock.type === 'warehouse' && stock.warehouse_id == currentPositionId) ||
                                    (stock.type === 'employee' && stock.employee_id == currentPositionId)
                                ));
                                html += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                html += '<span><strong>' + stock.type.charAt(0).toUpperCase() + stock.type.slice(1) + '</strong>: ' + stock.location + '</span>';
                                html += '<span>';
                                html += '<span class="badge bg-primary rounded-pill me-2">' + (stock.quantity ?? 0) + '</span>';
                                html += '<button class="btn btn-sm ' + (isSelected ? 'btn-secondary' : 'btn-success') + ' add-stock-btn" ' +
                                    'data-type="' + stock.type + '" ' +
                                    (stock.branch_id ? 'data-branch-id="' + stock.branch_id + '" ' : '') +
                                    (stock.warehouse_id ? 'data-warehouse-id="' + stock.warehouse_id + '" ' : '') +
                                    (stock.employee_id ? 'data-employee-id="' + stock.employee_id + '" ' : '') +
                                    'data-location="' + stock.location + '" ' +
                                    (isSelected ? 'disabled' : '') + '>' +
                                    (isSelected ? 'Added' : 'Add') + '</button>';
                                html += '</span>';
                                html += '</li>';
                            });
                            html += '</ul>';
                            $('#stockList').html(html);
                        } else {
                            $('#stockList').html('<div class="alert alert-warning mb-0">No available stocks found.</div>');
                        }
                    },
                    error: function() {
                        $('#stockList').html('<div class="alert alert-danger mb-0">Failed to fetch stock data.</div>');
                    }
                });
            });

            $(document).on('click', '.add-stock-btn', function() {
                var $btn = $(this);
                var type = $btn.data('type');
                var branchId = $btn.data('branch-id');
                var warehouseId = $btn.data('warehouse-id');
                var employeeId = $btn.data('employee-id');
                var current_position_type = type;
                var current_position_id = branchId || warehouseId || employeeId;

                if (!currentOrderItemId || !current_position_type || !current_position_id) {
                    alert('Missing data to add stock.');
                    return;
                }

                $.ajax({
                    url: '/erp/order/product-stock-add/' + currentOrderItemId,
                    type: 'POST',
                    data: {
                        current_position_type: current_position_type,
                        current_position_id: current_position_id
                    },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            // Revert all other buttons to 'Add' and enable them
                            $('.add-stock-btn').text('Add').prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
                            // Set this button to 'Added' and disable
                            $btn.text('Added').prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to add stock.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Failed to add stock.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            $(document).on('click', '.transfer-to-employee-link', function(e) {
                e.preventDefault();
                var orderItemId = $(this).data('order-item-id');
                if (!orderItemId) return;
                if (!confirm('Are you sure you want to transfer this stock to the assigned employee?')) return;
                $.ajax({
                    url: '/erp/order/transfer-stock-to-employee/' + orderItemId,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to transfer stock.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Failed to transfer stock.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });
        });

        // Custom Alert Function
        function showCustomAlert(message, type = 'warning') {
            const modal = $('#customAlertModal');
            const icon = $('#alertIcon');
            const title = $('#customAlertModalLabel');
            const messageEl = $('#alertMessage');
            const okBtn = $('#alertOkBtn');

            // Set icon and colors based on type
            switch(type) {
                case 'success':
                    icon.html('<i class="fas fa-check-circle text-success fa-2x"></i>');
                    title.text('Success');
                    okBtn.removeClass('btn-primary btn-danger btn-warning').addClass('btn-success');
                    break;
                case 'error':
                    icon.html('<i class="fas fa-times-circle text-danger fa-2x"></i>');
                    title.text('Error');
                    okBtn.removeClass('btn-primary btn-success btn-warning').addClass('btn-danger');
                    break;
                case 'info':
                    icon.html('<i class="fas fa-info-circle text-info fa-2x"></i>');
                    title.text('Information');
                    okBtn.removeClass('btn-primary btn-success btn-danger').addClass('btn-info');
                    break;
                default: // warning
                    icon.html('<i class="fas fa-exclamation-triangle text-warning fa-2x"></i>');
                    title.text('Warning');
                    okBtn.removeClass('btn-primary btn-success btn-danger').addClass('btn-warning');
            }

            messageEl.text(message);
            modal.modal('show');
        }
    </script>

    <!-- Custom Alert Modal -->
    <div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="alert-icon me-3" id="alertIcon">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                        <h5 class="modal-title mb-0" id="customAlertModalLabel">Alert</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-0" id="alertMessage">This is an alert message.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="alertOkBtn">OK</button>
                </div>
            </div>
        </div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
@endpush