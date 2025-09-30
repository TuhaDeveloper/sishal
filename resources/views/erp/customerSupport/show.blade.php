@extends('erp.master')

@section('title', 'Service Management')

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
                            <h1 class="h3 fw-bold mb-1 text-dark">Service Details</h1>
                            <p class="text-muted mb-0">Service #{{ $service->service_number ?? $service->id }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('customerService.list') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>

            <!-- Status Banner -->
            <div class="row mb-4">
                <div class="col-12">
                    <div
                        class="alert alert-{{ $service->status == 'pending' ? 'warning' : ($service->status == 'completed' ? 'success' : 'secondary') }} border-0 rounded-4 shadow-sm d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i
                                class="fas fa-{{ $service->status == 'pending' ? 'clock' : ($service->status == 'completed' ? 'check-circle' : 'info-circle') }} me-2"></i>
                            <strong>Status: {{ ucfirst($service->status) }}</strong>
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
                            <h5 class="modal-title" id="changeStatusModalLabel">Change Invoice Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changeStatusForm">
                                <div class="mb-3">
                                    <label for="saleStatusSelect" class="form-label">Status</label>
                                    <select class="form-select" id="saleStatusSelect" name="status" required>
                                        <option value="pending" {{ $service->status == 'pending' ? 'selected' : '' }}>Pending
                                        </option>
                                        <option value="assigned" {{ $service->status == 'assigned' ? 'selected' : '' }}>Assigned
                                        </option>
                                        <option value="in_progress" {{ $service->status == 'in_progress' ? 'selected' : '' }}>In Progress
                                        </option>
                                        <option value="completed" {{ $service->status == 'completed' ? 'selected' : '' }}>
                                            Completed</option>
                                        <option value="cancelled" {{ $service->status == 'cancelled' ? 'selected' : '' }}>
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
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-chart-line text-primary"></i>
                                    </div>
                                    <h5 class="card-title mb-0 fw-bold">Service Summary</h5>
                                </div>
                                <button class="btn btn-outline-primary btn-sm" id="updateSummaryBtn">Update Summary</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="bg-light rounded-3 p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Service Number</span>
                                            <span class="fw-bold">{{ $service->service_number }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Requested Date</span>
                                            <span>{{ $service->requested_date ? \Carbon\Carbon::parse($service->requested_date)->format('d M Y') : '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Service Fee</span>
                                            <span>{{ @$service->service_fee ?? '-' }}৳</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Branch</span>
                                            <span>{{ @$service->branch->name ?? '-' }}</span>
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
                                @if(!$service->technician)
                                    <button class="btn btn-outline-primary py-1" style="height: max-content;"
                                        id="addTechnicianBtn">Add Technician</button>
                                @endif
                            </div>
                            <div class="row g-3">
                                @if($service->technician)
                                    <div class="col-12">
                                        <div class="d-flex align-items-center p-3 bg-light rounded-3 position-relative">
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                                style="width: 40px; height: 40px;">
                                                <i class="fas fa-tools text-white small"></i>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ @$service->technician->user->first_name ?? '-' }}
                                                    {{ @$service->technician->user->last_name ?? '' }}</div>
                                                <small class="text-muted">Technician</small>
                                            </div>
                                            <button id="removeTechnicianBtn" class="btn btn-sm btn-outline-danger position-absolute" style="right: 20px;" data-service-id="{{ $service->id }}"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
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
                                    <div class="fw-medium">{{ @$service->user->first_name ?? '' }} {{ @$service->user->last_name ?? '' }}</div>
                                </div>
                                @if($service->user)
                                <div class="mb-3">
                                    <label class="small text-muted">Email</label>
                                    <div>{{ @$service->user->email ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted">Phone</label>
                                    <div>{{ @$service->user->phone ?? '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted">Address</label>
                                    @php $billing = @$service->invoice->invoiceAddress ?? null; @endphp
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
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-secondary bg-opacity-10 rounded-3 p-2 me-3">
                                        <i class="fas fa-shopping-cart text-secondary"></i>
                                    </div>
                                    <h5 class="card-title mb-0 fw-bold">Sale Items</h5>
                                </div>
                                <button id="addItemBtn" class="btn btn-sm btn-outline-primary ms-auto">Add Item</button>
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
                                            <th class="border-0 py-3 fw-bold text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($service->serviceProvidedParts as $item)
                                            <tr>
                                                <td class="border-0 py-3">
                                                    <div class="fw-medium">{{ $item->product->name ?? '-' }}</div>
                                                </td>
                                                <td class="border-0 py-3">
                                                    <span
                                                        class="badge bg-light text-dark">{{ $item->product->sku ?? '-' }}</span>
                                                </td>
                                                <td class="border-0 py-3 text-center">
                                                    <span class="badge bg-primary">{{ $item->qty }}</span>
                                                </td>
                                                <td class="border-0 py-3 text-end">{{ number_format($item->price, 2) }}৳
                                                </td>
                                                <td class="border-0 py-3 text-end fw-bold">
                                                    {{ number_format($item->price * $item->qty, 2) }}৳</td>
                                                <td class="border-0 py-3 fw-bold text-end">
                                                    @if($service->status == 'completed')
                                                        Completed
                                                    @elseif($service->status == 'in_progress' || $service->status == 'cancelled')
                                                        {{ ucfirst($service->status) }}
                                                    @elseif(is_null($item->current_position_type))
                                                        <button class="btn btn-sm btn-primary request-stock-btn" data-product-id="{{ $item->product_id }}" data-service-item-id="{{ $item->id }}">Request Stock</button>
                                                    @else
                                                        {{ $item->current_position_type == 'branch'
                                                            ? $item->branch->name
                                                            : ($item->current_position_type == 'warehouse'
                                                                ? $item->warehouse->name
                                                                : (@$item->technician->user->first_name . ' ' . @$item->technician->user->last_name)
                                                            )
                                                        }}
                                                        <div class="d-flex gap-2 fw-normal justify-content-end" style="font-size: 10px;">
                                                            @if(@$item->current_position_id != $service->technician_id)
                                                            <a href="#" class="request-stock-btn" data-product-id="{{ $item->product_id }}" data-service-item-id="{{ $item->id }}" data-current-type="{{ $item->current_position_type }}" data-current-id="{{ $item->current_position_id }}">Change Stock</a>
                                                            |
                                                            <a href="#" class="transfer-to-employee-link" data-product-id="{{ $item->product_id }}" data-service-item-id="{{ $item->id }}" data-current-type="{{ $item->current_position_type }}" data-current-id="{{ $item->current_position_id }}">Transfer To Employee</a>
                                                            @endif
                                                        </div>
                                                    @endif 
                                                </td>
                                                <td class="border-0 py-3 fw-bold text-end">
                                                    <button class="btn btn-outline-danger delete-extra-part-btn" data-part-id="{{ $item->id }}"><i class="fa-solid fa-trash"></i></button>
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
                                            <a href="{{ route('invoice.show',@$service->invoice->id) }}" class="fw-bold" style="text-decoration: none;">{{ @$service->invoice->invoice_number ?? '-' }}</a>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-muted">Status</span>
                                            <span
                                                class="badge bg-{{ @$service->invoice->status == 'unpaid' ? 'danger' : (@$service->invoice->status == 'paid' ? 'success' : (@$service->invoice->status == 'partial' ? 'warning' : 'secondary')) }} rounded-pill">
                                                {{ ucfirst(@$service->invoice->status ?? '-') }}
                                            </span>
                                        </div>
                                        <div class="bg-light rounded-3 p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted">Total Amount</span>
                                                <span
                                                    class="fw-bold h6 mb-0">{{ number_format(@$service->invoice->total_amount ?? 0, 2) }}৳</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-success">Paid Amount</span>
                                                <span
                                                    class="text-success fw-bold">{{ number_format(@$service->invoice->paid_amount ?? 0, 2) }}৳</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-danger">Due Amount</span>
                                                <span
                                                    class="text-danger fw-bold">{{ number_format(@$service->invoice->due_amount ?? 0, 2) }}৳</span>
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
                                        @if(@$service->invoice->status != 'paid')
                                            <button id="addPaymentBtn" class="btn btn-sm btn-outline-success ms-auto">Add
                                                Payment</button>
                                        @endif
                                    </div>
                                    <div class="payments-list" style="max-height: 250px; overflow-y: auto;">
                                        @forelse($service->payments as $payment)
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
                                <h5 class="card-title mb-0 fw-bold">Service Note</h5>
                                <button id="editNoteBtn" class="btn btn-sm btn-outline-primary ms-auto">Edit</button>
                            </div>
                            <div class="notes-content bg-light rounded-3 p-3 position-relative">
                                <p class="mb-0" id="noteText">{{ $service->service_notes ?? 'No note available for this sale.' }}</p>
                                <div id="noteEditArea" style="display:none;">
                                    <textarea id="noteTextarea" class="form-control mb-2"
                                        rows="3">{{ $service->service_notes }}</textarea>
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
                            <input type="number" max="{{ @$service->invoice->due_amount }}"
                                value="{{ @$service->invoice->due_amount }}" class="form-control" id="paymentAmount"
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
                            <label for="paymentAccount" class="form-label">Account (optional)</label>
                            <input type="text" class="form-control" id="paymentAccount" name="account_id">
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
                <div class="mb-2"><input type="text" class="form-control" name="address_1" placeholder="Address 1" value="{{ $billing && $billing->billing_address_1 ? $billing->billing_address_1 : (@$service->customer->address_1 ?? '') }}" required></div>
                <div class="mb-2"><input type="text" class="form-control" name="address_2" placeholder="Address 2" value="{{ $billing && $billing->billing_address_2 ? $billing->billing_address_2 : (@$service->customer->address_2 ?? '') }}"></div>
                <div class="mb-2"><input type="text" class="form-control" name="city" placeholder="City" value="{{ $billing && $billing->billing_city ? $billing->billing_city : (@$service->customer->city ?? '') }}"></div>
                <div class="mb-2"><input type="text" class="form-control" name="state" placeholder="State" value="{{ $billing && $billing->billing_state ? $billing->billing_state : (@$service->customer->state ?? '') }}"></div>
                <div class="mb-2"><input type="text" class="form-control" name="country" placeholder="Country" value="{{ $billing && $billing->billing_country ? $billing->billing_country : (@$service->customer->country ?? '') }}"></div>
                <div class="mb-3"><input type="text" class="form-control" name="zip_code" placeholder="Zip Code" value="{{ $billing && $billing->billing_zip_code ? $billing->billing_zip_code : (@$service->customer->zip_code ?? '') }}"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveAddressBtn">Save Address</button>
          </div>
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

    <!-- Add Extra Item Modal -->
    <div class="modal fade" id="addExtraItemModal" tabindex="-1" aria-labelledby="addExtraItemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addExtraItemModalLabel">Add Extra Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="addExtraItemForm">
              <input type="hidden" name="service_id" value="{{ $service->id }}">
              <div class="mb-3">
                <label for="product_type" class="form-label">Type</label>
                <select name="product_type" id="product_type" class="form-select" required>
                  <option value="product">Product</option>
                  <option value="material">Material</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="product_id" class="form-label">Product/Material</label>
                <select name="product_id" id="product_id" class="form-select" required></select>
              </div>
              <div class="mb-3">
                <label for="qty" class="form-label">Quantity</label>
                <input type="number" name="qty" id="qty" class="form-control" min="1" value="1" required>
              </div>
              <div class="mb-3">
                <label for="price" class="form-label">Unit Price</label>
                <input type="number" name="price" id="price" class="form-control" min="0" step="0.01" required>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitAddExtraItemBtn">Add Item</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Update Summary Modal -->
    <div class="modal fade" id="updateSummaryModal" tabindex="-1" aria-labelledby="updateSummaryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateSummaryModalLabel">Update Service Summary</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="updateSummaryForm">
              <input type="hidden" name="service_id" value="{{ $service->id }}">
              <div class="mb-3">
                <label for="service_fee" class="form-label">Service Fee</label>
                <input type="number" name="service_fee" id="service_fee" class="form-control" min="0" step="0.01" value="{{ $service->service_fee }}" required>
              </div>
              <div class="mb-3">
                <label for="travel_fee" class="form-label">Travel Fee</label>
                <input type="number" name="travel_fee" id="travel_fee" class="form-control" min="0" step="0.01" value="{{ $service->travel_fee }}" required>
              </div>
              <div class="mb-3">
                <label for="discount" class="form-label">Discount</label>
                <input type="number" name="discount" id="discount" class="form-control" min="0" step="0.01" value="{{ $service->discount }}" required>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitUpdateSummaryBtn">Update</button>
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
                    var serviceId = @json($service->id);
                    var url = '/erp/customer-services/update-technician/' + serviceId + '/' + selectedId;
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

            $(document).on('click', '#removeTechnicianBtn', function () {
                if (!confirm('Are you sure you want to remove the technician from this order?')) return;
                var serviceId = $(this).data('service-id');
                $.ajax({
                    url: '{{ route('customerService.deleteTechnician', ['id' => '__SERVICE_ID__']) }}'.replace('__SERVICE_ID__', serviceId),
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
                var saleId = @json($service->id);
                $.ajax({
                    url: '/erp/customer-services/update-note/' + saleId,
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
            // Submit Payment
            $('#submitPaymentBtn').on('click', function () {
                var saleId = @json($service->id);
                var form = $('#addPaymentForm');
                var data = form.serialize();
                $.ajax({
                    url: '/erp/customer-services/add-payment/' + saleId,
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
                var saleId = @json($service->id);
                var form = $('#changeStatusForm');
                var data = form.serialize();
                $.ajax({
                    url: '/erp/customer-services/update-status/{{ $service->id }}',
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
                var invoiceId = @json(@$service->invoice->id);
                var form = $('#editAddressForm');
                var data = form.serialize();
                $.ajax({
                    url: '/erp/customer-services/add-address/{{ $service->invoice->id }}',
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
            

            // stock management
            $(document).on('click', '.request-stock-btn', function() {
                var productId = $(this).data('product-id');
                currentServiceItemId = $(this).data('service-item-id');
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

                if (!currentServiceItemId || !current_position_type || !current_position_id) {
                    alert('Missing data to add stock.');
                    return;
                }

                $.ajax({
                    url: '/erp/customer-services/product-stock-add/' + currentServiceItemId,
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
                var serviceItemId = $(this).data('service-item-id');
                if (!serviceItemId) return;
                if (!confirm('Are you sure you want to transfer this stock to the assigned employee?')) return;
                $.ajax({
                    url: '/erp/customer-services/transfer-stock-to-employee/' + serviceItemId,
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

            // Open Add Extra Item Modal
            $(document).on('click', '#addItemBtn', function() {
                $('#addExtraItemModal').modal('show');
            });

            // Re-initialize select2 every time the modal is shown
            $('#addExtraItemModal').on('shown.bs.modal', function () {
                // Destroy previous select2 if exists
                if ($('#product_id').hasClass('select2-hidden-accessible')) {
                    $('#product_id').select2('destroy');
                }
                $('#product_id').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Search and select...',
                    minimumInputLength: 1,
                    dropdownParent: $('#addExtraItemModal'),
                    ajax: {
                        url: function() {
                            return $('#product_type').val() === 'material' ? '/erp/materials/search' : '/erp/products/search';
                        },
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { q: params.term };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(function(item) {
                                    // Attach price and discount to each option
                                    return {
                                        id: item.id,
                                        text: item.name || item.label || item.text,
                                        price: item.price ?? 0,
                                        discount: item.discount ?? 0
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    width: '100%'
                });
            });

            // Re-initialize select2 when type changes
            $('#product_type').on('change', function() {
                if ($('#product_id').hasClass('select2-hidden-accessible')) {
                    $('#product_id').select2('destroy');
                }
                $('#product_id').val(null).trigger('change');
                $('#product_id').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Search and select...',
                    minimumInputLength: 1,
                    dropdownParent: $('#addExtraItemModal'),
                    ajax: {
                        url: function() {
                            return $('#product_type').val() === 'material' ? '/erp/materials/search' : '/erp/products/search';
                        },
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { q: params.term };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: item.name || item.label || item.text,
                                        price: item.price ?? 0,
                                        discount: item.discount ?? 0
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    width: '100%'
                });
            });

            // Auto-fill price when a product is selected
            $('#product_id').on('select2:select', function(e) {
                var data = e.params.data;
                var productId = data.id;
                var type = $('#product_type').val();
                var $priceInput = $('#price');
                var url = type === 'material'
                    ? '/erp/materials/' + productId + '/price'
                    : '/erp/products/' + productId + '/price';

                $.get(url, function(response) {
                    if (response && typeof response.price !== 'undefined') {
                        $priceInput.val(response.price);
                    }
                });
            });

            // Submit Add Extra Item
            $('#submitAddExtraItemBtn').on('click', function() {
                var form = $('#addExtraItemForm');
                var data = form.serialize();
                var btn = $(this);
                btn.prop('disabled', true).text('Adding...');
                $.ajax({
                    url: '{{ route('customerService.addExtraPart') }}',
                    type: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        btn.prop('disabled', false).text('Add Item');
                        if (response.success) {
                            $('#addExtraItemModal').modal('hide');
                            form[0].reset();
                            $('#product_id').val(null).trigger('change');
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to add item.');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).text('Add Item');
                        let msg = 'Failed to add item.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });

            $(document).on('click', '.delete-extra-part-btn', function() {
                var partId = $(this).data('part-id');
                if (!confirm('Are you sure you want to delete this part?')) return;
                var btn = $(this);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                $.ajax({
                    url: '{{ route('customerService.deleteExtraPart') }}',
                    type: 'POST',
                    data: { part_id: partId },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to delete part.');
                            btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Failed to delete part.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                        btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    }
                });
            });

            // Open Update Summary Modal
            $(document).on('click', '#updateSummaryBtn', function() {
                // Pre-fill modal fields with current values
                $('#service_fee').val({{ $service->service_fee }});
                $('#travel_fee').val({{ $service->travel_fee }});
                $('#discount').val({{ $service->discount }});
                $('#updateSummaryModal').modal('show');
            });

            // Submit Update Summary
            $('#submitUpdateSummaryBtn').on('click', function() {
                var form = $('#updateSummaryForm');
                var data = form.serialize();
                var btn = $(this);
                btn.prop('disabled', true).text('Updating...');
                $.ajax({
                    url: '{{ route('customerService.updateServiceFees') }}',
                    type: 'POST',
                    data: data,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        btn.prop('disabled', false).text('Update');
                        if (response.success) {
                            $('#updateSummaryModal').modal('hide');
                            form[0].reset();
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to update summary.');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).text('Update');
                        let msg = 'Failed to update summary.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        alert(msg);
                    }
                });
            });
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
@endpush