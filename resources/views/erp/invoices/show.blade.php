@extends('erp.master')

@section('title', '#' . $invoice->invoice_number . ' | Invoice')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')

        <div class="container-fluid px-4 py-3">
            <!-- Invoice Header -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-file-invoice text-white"></i>
                                </div>
                                <div>
                                    <h2 class="mb-0 fw-bold text-dark">Invoice #{{ $invoice->invoice_number }}</h2>
                                    <small class="text-muted">Generated invoice document</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-alt text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted d-block">Date</small>
                                            <span
                                                class="fw-medium">{{ $invoice->date ?? $invoice->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted d-block">Status</small>
                                            <span
                                                class="badge {{ $invoice->status === 'Paid' ? 'bg-success' : 'bg-warning' }} rounded-pill">
                                                {{ $invoice->status ?? 'Pending' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted d-block">Due Date</small>
                                            <span
                                                class="fw-medium">{{ $invoice->due_date ? date('M d, Y', strtotime($invoice->due_date)) : '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>
                                        <div>
                                            <small class="text-muted d-block">Amount</small>
                                            <span
                                                class="fw-bold text-primary fs-5">{{ number_format($invoice->total_amount ?? 0, 2) }}৳</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mt-4 mt-md-0">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        <h6 class="mb-0 fw-semibold">Customer Details</h6>
                                    </div>
                                    <h5 class="mb-1 fw-bold">{{ @$invoice->customer->name ?? '-' }}</h5>
                                    @if(@$invoice->customer->email)
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-envelope me-1"></i>
                                            {{ @$invoice->customer->email }}
                                        </p>
                                    @endif
                                    @if(@$invoice->customer->phone)
                                        <p class="mb-0 text-muted small">
                                            <i class="fas fa-phone me-1"></i>
                                            {{ @$invoice->customer->phone }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Addresses & Salesman -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-circle px-2 py-1 me-3">
                                    <i class="fas fa-map-marker-alt text-info"></i>
                                </div>
                                <h6 class="mb-0 fw-semibold">Billing Address</h6>
                            </div>
                            @php $billing = $invoice->invoiceAddress; @endphp
                            <div class="text-muted">
                                {{ $billing->billing_address_1 ?? '-' }}<br>
                                {{ $billing->billing_city ?? '' }} {{ $billing->billing_state ?? '' }}<br>
                                {{ $billing->billing_country ?? '' }} {{ $billing->billing_zip_code ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 rounded-circle px-2 py-1 me-3">
                                    <i class="fas fa-shipping-fast text-success"></i>
                                </div>
                                <h6 class="mb-0 fw-semibold">Shipping Address</h6>
                            </div>
                            @php $shipping = $invoice->invoiceAddress; @endphp
                            <div class="text-muted">
                                {{ $shipping->shipping_address_1 ?? '-' }}<br>
                                {{ $shipping->shipping_city ?? '' }} {{ $shipping->shipping_state ?? '' }}<br>
                                {{ $shipping->shipping_country ?? '' }} {{ $shipping->shipping_zip_code ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-warning bg-opacity-10 rounded-circle px-2 py-1 me-3">
                                    <i class="fas fa-user-tie text-warning"></i>
                                </div>
                                <h6 class="mb-0 fw-semibold">Sales Representative</h6>
                            </div>
                            <div class="text-muted">
                                <div class="fw-medium text-dark">
                                    {{ $invoice->salesman->first_name . ' ' . $invoice->salesman->last_name ?? '-' }}</div>
                                @if($invoice->salesman->email)
                                    <small class="d-block">{{ $invoice->salesman->email }}</small>
                                @endif
                                @if($invoice->salesman->phone)
                                    <small class="d-block">{{ $invoice->salesman->phone }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle px-2 py-1 me-3">
                            <i class="fas fa-list text-primary"></i>
                        </div>
                        <h5 class="mb-0 fw-semibold">Invoice Items</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4 py-3 text-muted fw-medium">#</th>
                                    <th class="border-0 px-4 py-3 text-muted fw-medium">Product</th>
                                    <th class="border-0 px-4 py-3 text-muted fw-medium text-center">Qty</th>
                                    <th class="border-0 px-4 py-3 text-muted fw-medium text-end">Unit Price</th>
                                    <th class="border-0 px-4 py-3 text-muted fw-medium text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($invoice->items ?? collect()) as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-muted">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3">
                                            <div class="fw-medium text-dark">{{ $item->product->name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge bg-light text-dark rounded-pill">{{ $item->quantity }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-end">{{ number_format($item->unit_price, 2) }}৳</td>
                                        <td class="px-4 py-3 text-end fw-semibold">{{ number_format($item->total_price, 2) }}৳</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-5 text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-3"></i>
                                            <div>No items found.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payments & Summary -->
            <div class="row g-4">
                <!-- Payments -->
                <div class="col-xl-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0 p-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle px-2 py-1 me-3">
                                    <i class="fas fa-credit-card text-success"></i>
                                </div>
                                <h5 class="mb-0 fw-semibold">Payment History</h5>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0 px-4 py-3 text-muted fw-medium">#</th>
                                            <th class="border-0 px-4 py-3 text-muted fw-medium">Date</th>
                                            <th class="border-0 px-4 py-3 text-muted fw-medium">Method</th>
                                            <th class="border-0 px-4 py-3 text-muted fw-medium text-end">Amount</th>
                                            <th class="border-0 px-4 py-3 text-muted fw-medium">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse(($invoice->payments ?? collect()) as $payment)
                                            <tr>
                                                <td class="px-4 py-3 text-muted">{{ $loop->iteration }}</td>
                                                <td class="px-4 py-3">
                                                    {{ $payment->date ?? ($payment->created_at->format('M d, Y') ?? '-') }}</td>
                                                <td class="px-4 py-3">
                                                    <span
                                                        class="badge bg-light text-dark">{{ $payment->payment_method ?? '-' }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-end fw-semibold">
                                                    {{ number_format($payment->amount ?? 0, 2) }}৳</td>
                                                <td class="px-4 py-3">
                                                    {{ $payment->note ?? '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-5 text-center text-muted">
                                                    <i class="fas fa-credit-card fa-2x mb-3"></i>
                                                    <div>No payments found.</div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Summary -->
                <div class="col-xl-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0 p-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle px-2 py-1 me-3">
                                    <i class="fas fa-calculator text-primary"></i>
                                </div>
                                <h5 class="mb-0 fw-semibold">Invoice Summary</h5>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-medium">{{ number_format($invoice->subtotal ?? 0, 2) }}৳</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Tax</span>
                                <span class="fw-medium">${{ number_format($invoice->tax ?? 0, 2) }}৳</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Discount</span>
                                <span
                                    class="fw-medium text-success">-{{ number_format($invoice->discount_apply ?? 0, 2) }}৳</span>
                            </div>
                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="h5 mb-0 fw-bold">Total Amount</span>
                                <span
                                    class="h4 mb-0 fw-bold text-primary">{{ number_format($invoice->total_amount ?? 0, 2) }}৳</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Paid Amount</span>
                                <span class="fw-medium">{{ number_format($invoice->paid_amount ?? 0, 2) }}৳</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Due Amount</span>
                                <span
                                    class="fw-medium text-danger">{{ number_format($invoice->due_amount ?? 0, 2) }}৳</span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                {{-- @php dd(floatval($invoice->due_amount));  @endphp --}}
                                @if(floatval($invoice->due_amount) != 0.0)
                                    <button class="btn btn-primary" type="button" data-bs-toggle="modal"
                                        data-bs-target="#addPaymentModal">
                                        <i class="fas fa-plus me-2"></i>Add Payment
                                    </button>
                                @endif
                                <a href="{{ route('invoice.print', $invoice->invoice_number) }}?action=print" target="_blank" class="btn btn-primary" type="button">
                                    <i class="fas fa-print me-2"></i>Print Invoice
                                </a>
                                <a href="{{ route('invoice.print', $invoice->invoice_number) }}?action=download" target="_blank" class="btn btn-outline-primary" type="button">
                                    <i class="fas fa-download me-2"></i>Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addPaymentForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPaymentModalLabel">Add Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="paymentAmount"
                                name="amount" value="{{ $invoice->due_amount }}" max="{{ $invoice->due_amount }}" required>
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
                            <label for="accountId" class="form-label">Account (optional)</label>
                            <select class="form-select" id="accountId" name="account_id">
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->provider_name . ' - ' . $bankAccount->account_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="paymentNote" class="form-label">Note</label>
                            <textarea class="form-control" id="paymentNote" name="note" rows="2"></textarea>
                        </div>
                        <div id="addPaymentError" class="alert alert-danger d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function () {
            $('#addPaymentForm').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find('button[type="submit"]');
                btn.prop('disabled', true);
                $('#addPaymentError').addClass('d-none').text('');
                $.ajax({
                    url: '/erp/invoices/add-payment/{{ $invoice->id }}',
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (res) {
                        if (res.success) {
                            $('#addPaymentModal').modal('hide');
                            location.reload();
                        } else {
                            $('#addPaymentError').removeClass('d-none').text(res.message || 'Failed to add payment.');
                        }
                    },
                    error: function (xhr) {
                        let msg = 'Failed to add payment.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        $('#addPaymentError').removeClass('d-none').text(msg);
                    },
                    complete: function () {
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush