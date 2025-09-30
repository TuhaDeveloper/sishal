<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .order-number {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 0 8px 8px 0;
        }
        
        .order-number h2 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .order-number p {
            color: #666;
            font-size: 16px;
        }
        
        .customer-info {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .customer-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .customer-info p {
            margin-bottom: 8px;
            color: #666;
        }
        
        .order-items {
            margin-bottom: 30px;
        }
        
        .order-items h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            flex: 2;
            font-weight: 500;
        }
        
        .item-quantity {
            flex: 1;
            text-align: center;
            color: #666;
        }
        
        .item-price {
            flex: 1;
            text-align: right;
            font-weight: 600;
            color: #667eea;
        }
        
        .order-summary {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            font-weight: 600;
            font-size: 18px;
            color: #667eea;
        }
        
        .footer {
            background-color: #333;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .footer h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .footer p {
            margin-bottom: 10px;
            opacity: 0.8;
        }
        
        .contact-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #555;
        }
        
        .contact-info p {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 0;
                box-shadow: none;
            }
            
            .header, .content, .footer {
                padding: 20px;
            }
            
            .item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .item-quantity, .item-price {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎉 Order Confirmed!</h1>
            <p>Thank you for your purchase. Your order has been successfully placed.</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Order Number -->
            <div class="order-number">
                <h2>Order #{{ $order->order_number }}</h2>
                <p>Placed on {{ \Carbon\Carbon::parse($order->created_at)->format('F j, Y \a\t g:i A') }}</p>
            </div>
            
            <!-- Customer Information -->
            <div class="customer-info">
                <h3>📋 Customer Information</h3>
                <p><strong>Name:</strong> {{ $order->name ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $order->email ?? 'N/A' }}</p>
                <p><strong>Phone:</strong> {{ $order->phone ?? 'N/A' }}</p>
                @if($order->shipping_address)
                    <p><strong>Shipping Address:</strong> {{ $order->shipping_address }}</p>
                @endif
            </div>
            
            <!-- Order Items -->
            <div class="order-items">
                <h3>🛍️ Order Items</h3>
                @foreach($order->items as $item)
                    <div class="item" style="width: 100%;">
                        <div class="item-name" style="width: 33%;">{{ $item->product->name ?? 'Product' }}</div>
                        <div class="item-quantity" style="width: 33%;">Qty: {{ $item->quantity }}</div>
                        <div class="item-price" style="width: 33%;">{{ number_format($item->unit_price, 2) }}৳</div>
                    </div>
                @endforeach
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h3 style="margin-bottom: 10px;">💰 Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>{{ number_format($order->subtotal ?? 0, 2) }}৳</span>
                </div>
                @if($order->discount > 0)
                <div class="summary-row">
                    <span>Discount:</span>
                    <span>-{{ number_format($order->discount, 2) }}৳</span>
                </div>
                @endif
                @if($order->tax > 0)
                <div class="summary-row">
                    <span>Tax:</span>
                    <span>{{ number_format($order->tax, 2) }}৳</span>
                </div>
                @endif
                @if($order->shipping_cost > 0)
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>{{ number_format($order->shipping_cost, 2) }}৳</span>
                </div>
                @endif
                <div class="summary-row">
                    <span>Total:</span>
                    <span>{{ number_format($order->total_amount, 2) }}৳</span>
                </div>
            </div>
            
            <!-- Payment Information -->
            @if($order->paid_amount > 0)
            <div class="customer-info">
                <h3>💳 Payment Information</h3>
                <p><strong>Paid Amount:</strong> {{ number_format($order->paid_amount, 2) }}৳</p>
                @if($order->due_amount > 0)
                    <p><strong>Due Amount:</strong> {{ number_format($order->due_amount, 2) }}৳</p>
                @endif
                <p><strong>Payment Status:</strong> 
                    @if($order->status == 'paid')
                        <span style="color: #28a745;">✓ Paid</span>
                    @elseif($order->status == 'partial')
                        <span style="color: #ffc107;">⚠ Partial</span>
                    @else
                        <span style="color: #dc3545;">⏳ Pending</span>
                    @endif
                </p>
            </div>
            @endif
            
            <!-- Next Steps -->
            <div class="customer-info">
                <h3>📦 What's Next?</h3>
                <p>• We'll process your order within 24-48 hours</p>
                <p>• You'll receive shipping confirmation once your order ships</p>
                <p>• Track your order status in your account dashboard</p>
                <p>• For any questions, please contact our support team</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <h3>Thank you for choosing us!</h3>
            <p>We appreciate your business and look forward to serving you again.</p>
            
            <div class="contact-info">
                <p><strong>Need Help?</strong></p>
                <p>Email: {{ $general_settings->contact_email }}</p>
                <p>Phone: {{ $general_settings->contact_phone }}</p>
            </div>
        </div>
    </div>
</body>
</html> 