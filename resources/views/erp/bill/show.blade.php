@extends('erp.master')

@section('title', 'Bill Details')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-gradient-to-br from-gray-50 to-gray-100 min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-6">
            <!-- Page Header -->
            <div class="row mb-6">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Bill Details</h1>
                            <p class="text-muted mb-0">View and manage bill information</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download me-1"></i> Download
                            </button>
                            <button class="btn btn-outline-success btn-sm">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bill Overview Card -->
            <div class="row my-5">
                <div class="col-12">
                    <div class="card border-0 shadow-lg rounded-3 overflow-hidden">
                        <div class="card-header text-white py-4" style="background-color: rgba(14, 165, 233, 1)">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1 font-weight-bold">Bill #{{ $bill->id }}</h4>
                                    <p class="mb-0 opacity-75">{{ $bill->vendor->name ?? 'N/A' }}</p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{{ $bill->status == 'paid' ? 'success' : ($bill->status == 'overdue' ? 'danger' : 'warning') }} px-3 py-2 rounded-pill text-uppercase fw-bold">
                                        {{ ucfirst($bill->status ?? 'pending') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded-3">
                                        <i class="fas fa-calendar-alt text-primary mb-2 fs-4"></i>
                                        <h6 class="mb-1 text-muted">Bill Date</h6>
                                        <p class="mb-0 fw-bold">{{ $bill->created_at ? $bill->created_at->format('d M Y') : '-' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded-3">
                                        <i class="fas fa-clock text-warning mb-2 fs-4"></i>
                                        <h6 class="mb-1 text-muted">Due Date</h6>
                                        <p class="mb-0 fw-bold">{{ $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('d M Y') : '-' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded-3">
                                        <i class="fa-solid fa-bangladeshi-taka-sign text-success mb-2 fs-4"></i>
                                        <h6 class="mb-1 text-muted">Total Amount</h6>
                                        <p class="mb-0 fw-bold text-success">৳{{ number_format($bill->total_amount ?? 0, 2) }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded-3">
                                        <i class="fas fa-balance-scale text-info mb-2 fs-4"></i>
                                        <h6 class="mb-1 text-muted">Due</h6>
                                        <p class="mb-0 fw-bold text-danger">৳{{ number_format(($bill->total_amount ?? 0) - ($bill->payments->sum('amount') ?? 0), 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Bill Items Section -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold text-gray-800">
                                    <i class="fas fa-list-ul text-primary me-2"></i>
                                    Bill Items
                                </h5>
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                    {{ $bill->items->count() }} items
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="border-0 py-3 px-4 text-uppercase text-muted fw-bold">#</th>
                                            <th class="border-0 py-3 px-4 text-uppercase text-muted fw-bold">Product</th>
                                            <th class="border-0 py-3 px-4 text-uppercase text-muted fw-bold text-center">Quantity</th>
                                            <th class="border-0 py-3 px-4 text-uppercase text-muted fw-bold text-end">Unit Price</th>
                                            <th class="border-0 py-3 px-4 text-uppercase text-muted fw-bold text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bill->items as $index => $item)
                                            <tr class="border-bottom">
                                                <td class="py-3 px-4">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <span class="badge bg-light text-dark rounded-circle" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                                            {{ $index + 1 }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary-subtle rounded-circle px-2 py-1 me-3">
                                                            <i class="fas fa-box text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-bold">{{ $item->product->name ?? 'N/A' }}</h6>
                                                            <small class="text-muted">Product ID: {{ $item->product->id ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4 text-center">
                                                    <span class="badge bg-info-subtle text-info px-3 py-2 rounded-pill">
                                                        {{ $item->quantity }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-end fw-bold">৳{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="py-3 px-4 text-end fw-bold text-success">৳{{ number_format($item->total_price, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <div class="text-muted">
                                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                                        <p class="mb-0">No items found for this bill.</p>
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

                <!-- Payments Section -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold text-gray-800">
                                    <i class="fas fa-credit-card text-success me-2"></i>
                                    Payments
                                </h5>
                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                    ৳{{ number_format($bill->payments->sum('amount') ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @forelse($bill->payments as $payment)
                                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success-subtle rounded-circle px-2 py-1 me-3">
                                            <i class="fas fa-check text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">৳{{ number_format($payment->amount, 2) }}</h6>
                                            <small class="text-muted">
                                                {{ $payment->created_at ? $payment->created_at->format('d M Y') : '-' }}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark rounded-pill px-2 py-1">
                                            {{ ucfirst($payment->method ?? 'N/A') }}
                                        </span>
                                        @if($payment->note)
                                            <small class="d-block text-muted mt-1">{{ $payment->note }}</small>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-wallet fa-2x mb-3"></i>
                                        <p class="mb-0">No payments recorded yet.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                        @if($bill->payments->count() > 0)
                            <div class="card-footer bg-light border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Total Paid:</span>
                                    <span class="fw-bold text-success">৳{{ number_format($bill->payments->sum('amount') ?? 0, 2) }}</span>
                                </div>
                            </div>
                        @endif

                        @if($bill->due_amount != 0)
                        <button class="btn btn-success btn-sm d-flex align-items-center justify-content-center m-2" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="fas fa-plus me-2"></i>
                            Add Payment
                        </button>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('bill.addPayment', $bill->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPaymentModalLabel">Add Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $bill->due_amount }}" value="{{$bill->due_amount}}" class="form-control" id="amount" name="amount" required>
                            <div class="form-text">Due: ৳{{ number_format($bill->due_amount, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <input type="hidden" name="method" value="cash">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .bg-gradient-to-br {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .bg-primary-subtle {
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        .bg-success-subtle {
            background-color: rgba(25, 135, 84, 0.1);
        }
        
        .bg-info-subtle {
            background-color: rgba(13, 202, 240, 0.1);
        }
        
        .text-gray-800 {
            color: #2d3748;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .shadow-lg {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .rounded-3 {
            border-radius: 0.75rem;
        }
        
        .fs-4 {
            font-size: 1.25rem;
        }
        
        .opacity-75 {
            opacity: 0.75;
        }
        
        .fw-bold {
            font-weight: 700;
        }
    </style>
@endsection