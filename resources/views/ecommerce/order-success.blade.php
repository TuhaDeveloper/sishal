@extends('ecommerce.master')



@section('main-section')
<style>
    .confirmation-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        position: relative;
        overflow: hidden;
    }

    .confirmation-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 3rem 2rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        text-align: center;
        position: relative;
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
        max-width: 500px;
        width: 90%;
        margin: 0 auto;
        z-index: 999;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .confirmation-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #00c6ff, #0072ff);
        border-radius: 24px 24px 0 0;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #00c6ff, #0072ff);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        position: relative;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .success-icon::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #00c6ff, #0072ff);
        border-radius: 50%;
        opacity: 0.3;
        animation: ripple 2s infinite;
    }

    @keyframes ripple {
        0% { transform: scale(1); opacity: 0.3; }
        100% { transform: scale(1.5); opacity: 0; }
    }

    .success-icon i {
        color: white;
        z-index: 1;
        position: relative;
    }

    .modern-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-blue);
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--primary-blue),rgb(81, 75, 162));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .modern-message {
        font-size: 1.1rem;
        color: #4a5568;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    .order-details {
        background: rgba(102, 126, 234, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }

    .order-number {
        font-size: 0.9rem;
        color: var(--primary-blue);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .order-id {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1a202c;
        font-family: 'Courier New', monospace;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .modern-btn {
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .modern-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .modern-btn:hover::before {
        left: 100%;
    }

    .modern-btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .modern-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        color: white;
        text-decoration: none;
    }

    .modern-btn-secondary {
        background: rgba(255, 255, 255, 0.8);
        color: #667eea;
        border: 2px solid rgba(102, 126, 234, 0.3);
    }

    .modern-btn-secondary:hover {
        background: rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
        color: #667eea;
        text-decoration: none;
    }

    .features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
    }

    .feature {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 12px;
        font-size: 0.9rem;
        color: #4a5568;
    }

    .feature i {
        color: var(--primary-blue);
    }

    /* Animated background shapes */
    .bg-shapes {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 1;
    }

    .shape {
        position: absolute;
        opacity: 0.1;
        animation: float 20s infinite linear;
    }

    .shape1 {
        top: 20%;
        left: 10%;
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation-delay: 0s;
    }

    .shape2 {
        top: 60%;
        right: 10%;
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        animation-delay: 5s;
    }

    .shape3 {
        bottom: 20%;
        left: 20%;
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        animation-delay: 10s;
    }

    @keyframes float {
        0% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
        100% { transform: translateY(0px) rotate(360deg); }
    }

    @media (max-width: 600px) {
        .confirmation-card {
            padding: 2rem 1.5rem;
        }
        
        .modern-title {
            font-size: 1.5rem;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .modern-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="confirmation-container py-5">
    <div class="bg-shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
        <div class="shape shape3"></div>
    </div>

    <div class="confirmation-card">
        <div class="success-icon">
            <i class="fas fa-check fa-2x"></i>
        </div>
        
        <h2 class="modern-title">Order Confirmed!</h2>
        
        <p class="modern-message">
            Your order has been successfully placed and is being processed.<br>
            You'll receive an email confirmation shortly with tracking details.
        </p>
        
        <div class="order-details">
            <div class="order-number">Order Number</div>
            <div class="order-id">{{$orderId}}</div>
        </div>
        
        <div class="action-buttons">
            @auth
            <a href="{{ route('order.details', urlencode($orderId)) }}" class="modern-btn btn-primary-custom">
                <i class="fas fa-receipt"></i>
                View Order Details
            </a>
            @endauth
            <a href="/" class="modern-btn btn-outline-secondary-custom me-0">
                <i class="fas fa-home"></i>
                Continue Shopping
            </a>
        </div>
        
        <div class="features">
            <div class="feature">
                <i class="fas fa-shipping-fast"></i>
                Fast Shipping
            </div>
            <div class="feature">
                <i class="fas fa-shield-alt"></i>
                Secure Payment
            </div>
            <div class="feature">
                <i class="fas fa-headset"></i>
                24/7 Support
            </div>
        </div>
    </div>
</div>

@if(isset($order) && $order && ($general_settings->gtm_container_id ?? null))
<script>
    window.dataLayer = window.dataLayer || [];
    
    // Prevent duplicate purchase events on page reload using session storage
    var transactionId = {!! json_encode($order->order_number) !!};
    var purchaseKey = 'gtm_purchase_' + transactionId;
    
    
    // Check if this purchase has already been tracked in this session
    if (!sessionStorage.getItem(purchaseKey)) {
        // Mark as tracked to prevent duplicate events on page reload
        sessionStorage.setItem(purchaseKey, 'true');
        
        window.dataLayer.push({
            'event': 'purchase',
            'ecommerce': {
                'transaction_id': transactionId,
                'value': {{ $order->total }},
                'tax': {{ $order->vat ?? 0 }},
                'shipping': {{ $order->delivery ?? 0 }},
                'currency': 'BDT',
                'items': [
                    @foreach($order->items as $item)
                    @if($item->product)
                    {
                        'item_id': '{{ $item->product_id }}',
                        'item_name': {!! json_encode($item->product->name ?? '') !!},
                        'item_category': {!! json_encode($item->product->category->name ?? '') !!},
                        'price': {{ $item->price }},
                        'quantity': {{ $item->qty }}
                    }@if(!$loop->last),@endif
                    @endif
                    @endforeach
                ]
            }
        });
        
    }
</script>
@endif
@endsection