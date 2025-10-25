@forelse ($orders as $order)
<div class="order-card-simple">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h6 class="mb-1 fw-bold">{{$order->order_number}}</h6>
            <small class="text-muted-simple">
                Placed on {{$order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('M d, Y') : '-'}}
            </small>
        </div>
        <div class="text-end">
            <div class="h6 mb-1 fw-bold text-primary">{{$order->total}}৳</div>
            @php
                // If invoice is paid, show "paid" status, otherwise show order status
                $displayStatus = ($order->invoice && $order->invoice->status === 'paid') ? 'paid' : $order->status;
                $badgeClass = match($displayStatus) {
                    'paid' => 'bg-success',
                    'delivered' => 'bg-success',
                    'approved' => 'bg-warning',
                    'shipping' => 'bg-info',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary'
                };
            @endphp
            <span class="badge {{ $badgeClass }}">
                {{ ucfirst($displayStatus) }}
            </span>
        </div>
    </div>
    <div class="row g-2">
        @foreach ($order->items as $item)
        <div class="col-md-4">
            <div class="d-flex align-items-center p-2 rounded order-item">
                <div class="me-2">
                    @if($item->product && $item->product->image)
                        <img src="{{ asset($item->product->image) }}" alt="Product" class="order-thumb">
                    @else
                        <div class="rounded d-flex align-items-center justify-content-center bg-white"
                            style="width: 48px; height: 48px;">
                            <i class="fas fa-image text-muted"></i>
                        </div>
                    @endif
                </div>
                <div class="flex-grow-1">
                    @if($item->product)
                        <a href="{{ route('product.details', $item->product->slug) }}" class="d-block fw-semibold text-dark text-decoration-none small">{{ $item->product->name }}</a>
                    @else
                        <span class="d-block fw-semibold text-muted small">Product Deleted</span>
                    @endif
                    <small class="text-muted-simple">Qty: {{number_format($item->quantity,0)}}</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
        <div class="d-flex gap-2">
            <a href="{{ route('order.details', $order->order_number) }}" class="btn-outline-simple btn-sm">
                View Details
            </a>
            @if(($order->status === 'pending' || $order->status === 'approved') && !($order->invoice && $order->invoice->status === 'paid'))
                <button type="button" class="btn-outline-simple btn-sm btn-cancel-order" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                    Cancel Order
                </button>
            @endif
            @if($order->status === 'cancelled')
                <button type="button" class="btn-outline-simple btn-sm btn-delete-order" data-bs-toggle="modal" data-bs-target="#deleteOrderModal" data-order-number="{{ $order->order_number }}">
                    Delete Order
                </button>
            @endif
        </div>
        <div class="text-end">
            <small class="text-muted-simple">
                @if($order->invoice && $order->invoice->status === 'paid')
                    <i class="fas fa-check-circle text-success me-1"></i>Paid
                @else
                    <i class="fas fa-clock text-warning me-1"></i>Payment Pending
                @endif
            </small>
        </div>
    </div>
    @if(($order->status === 'pending' || $order->status === 'approved') && !($order->invoice && $order->invoice->status === 'paid'))
        <form action="{{ route('order.cancel', $order->id) }}" method="POST" style="display: none;">
            @csrf
            @method('PATCH')
        </form>
    @endif
    @if($order->status === 'cancelled')
        <form action="{{ route('order.delete', $order->id) }}" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
@empty
    <div class="text-center py-4">
        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
        <h6 class="text-muted">
            @if(request('status') && request('status') !== 'all')
                No {{ ucfirst(request('status')) }} Orders Found
            @else
                No orders found
            @endif
        </h6>
        <p class="text-muted-simple">
            @if(request('status') && request('status') !== 'all')
                You don't have any {{ request('status') }} orders.
            @else
                You haven't placed any orders yet.
            @endif
        </p>
        @if(!request('status') || request('status') === 'all')
            <a href="{{ route('product.archive') }}" class="btn-simple">Start Shopping</a>
        @else
            <a href="{{ route('profile.edit', ['tab' => 'orders']) }}" class="btn-simple">View All Orders</a>
        @endif
    </div>
@endforelse
