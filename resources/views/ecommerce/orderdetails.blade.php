@extends('ecommerce.master')

@section('main-section')
<div class="container py-5" style="min-height:60vh;">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="mb-0 fw-bold">Order #{{ $order->order_number }}</h3>
                            <span class="badge 
                                {{ 
                                    $order->status == 'pending' ? 'bg-secondary' : 
                                    ($order->status == 'approved' ? 'bg-warning' : 
                                    ($order->status == 'shipping' ? 'bg-info' : 
                                    ($order->status == 'delivered' ? 'bg-success' : 
                                    ($order->status == 'cancelled' ? 'bg-danger' : 'bg-secondary')))) 
                                }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <div class="text-end">
                            <div class="text-muted">Placed on {{ $order->created_at ? $order->created_at->format('M d, Y') : '-' }}</div>
                            <div class="fw-bold h5 mb-0">Total: {{ number_format($order->total, 2) }}৳</div>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h5 class="mb-3">Customer Information</h5>
                            <div class="mb-2"><strong>Name:</strong> {{ $order->name ?? ($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? '') }}</div>
                            <div class="mb-2"><strong>Email:</strong> {{ $order->email ?? $order->user->email ?? '-' }}</div>
                            <div class="mb-2"><strong>Phone:</strong> {{ $order->phone ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Delivery Information</h5>
                            <div class="mb-2"><strong>Address:</strong> {{ optional($order->invoice->invoiceAddress)->billing_address_1 ?? '-' }}</div>
                            <div class="mb-2"><strong>City:</strong> {{ optional($order->invoice->invoiceAddress)->billing_city ?? '-' }}</div>
                            <div class="mb-2"><strong>State:</strong> {{ optional($order->invoice->invoiceAddress)->billing_state ?? '-' }}</div>
                            <div class="mb-2"><strong>ZIP:</strong> {{ optional($order->invoice->invoiceAddress)->billing_zip_code ?? '-' }}</div>
                            <div class="mb-2"><strong>Country:</strong> {{ optional($order->invoice->invoiceAddress)->billing_country ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Order Items</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ asset($item->product->image) }}" alt="Product" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px;">
                                            <span>{{ $item->product->name ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $item->product->sku ?? '-' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ number_format($item->unit_price, 2) }}৳</td>
                                    <td class="text-end fw-bold">{{ number_format($item->total_price, 2) }}৳</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Invoice & Payment</h5>
                            <div class="mb-2"><strong>Invoice Number:</strong> {{ $order->invoice->invoice_number ?? '-' }}</div>
                            <div class="mb-2"><strong>Status:</strong> <span class="badge {{ $order->invoice->status == 'paid' ? 'bg-success' : ($order->invoice->status == 'partial' ? 'bg-warning' : 'bg-danger') }}">{{ ucfirst($order->invoice->status ?? '-') }}</span></div>
                            <div class="mb-2"><strong>Paid Amount:</strong> {{ number_format($order->invoice->paid_amount ?? 0, 2) }}৳</div>
                            <div class="mb-2"><strong>Due Amount:</strong> {{ number_format($order->invoice->due_amount ?? 0, 2) }}৳</div>
                            <div class="mb-2"><strong>Payment Method:</strong> {{ ucfirst($order->payment_method ?? '-') }}</div>
                            <div class="mb-2"><strong>Note:</strong> {{ $order->notes ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Payment History</h5>
                            @if($order->invoice && $order->invoice->payments->count())
                                <ul class="list-group list-group-flush">
                                    @foreach($order->invoice->payments as $payment)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">{{ number_format($payment->amount, 2) }}৳</div>
                                            <small class="text-muted">{{ $payment->payment_date }}</small>
                                        </div>
                                        <span class="badge bg-primary">{{ ucfirst($payment->payment_method) }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-muted">No payments recorded.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection