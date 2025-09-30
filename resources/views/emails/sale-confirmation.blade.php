<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Confirmation</title>
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
            background: linear-gradient(135deg, #28a745, #20c997);
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
        
        .sale-number {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 0 8px 8px 0;
        }
        
        .sale-number h2 {
            color: #28a745;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sale-number p {
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
        
        .sale-items {
            margin-bottom: 30px;
        }
        
        .sale-items h3 {
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
            color: #28a745;
        }
        
        .sale-summary {
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
            color: #28a745;
        }
        
        .payment-info {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .payment-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .payment-info p {
            margin-bottom: 8px;
            color: #666;
        }
        
        .delivery-info {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .delivery-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .delivery-info p {
            margin-bottom: 8px;
            color: #666;
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
            <h1>🎉 Sale Completed!</h1>
            <p>Thank you for your purchase. Your sale has been successfully processed.</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Sale Number -->
            <div class="sale-number">
                <h2>Sale #{{ $pos->sale_number }}</h2>
                <p>Completed on {{ \Carbon\Carbon::parse($pos->sale_date)->format('F j, Y \a\t g:i A') }}</p>
            </div>
            
            <!-- Customer Information -->
            <div class="customer-info">
                <h3>👤 Customer Information</h3>
                <p><strong>Name:</strong> {{ $pos->customer->name ?? 'Walk-in Customer' }}</p>
                <p><strong>Email:</strong> {{ $pos->customer->email ?? 'N/A' }}</p>
                <p><strong>Phone:</strong> {{ $pos->customer->phone ?? 'N/A' }}</p>
                @if($pos->customer && $pos->customer->address)
                    <p><strong>Address:</strong> {{ $pos->customer->address }}</p>
                @endif
            </div>
            
            <!-- Sale Items -->
            <div class="sale-items">
                <h3>🛍️ Sale Items</h3>
                @foreach($pos->items as $item)
                    <div class="item" style="width: 100%;">
                        <div class="item-name" style="width: 33%;">{{ $item->product->name ?? 'Product' }}</div>
                        <div class="item-quantity" style="width: 33%;">Qty: {{ $item->quantity }}</div>
                        <div class="item-price" style="width: 33%;">{{ number_format($item->unit_price, 2) }}৳</div>
                    </div>
                @endforeach
            </div>
            
            <!-- Sale Summary -->
            <div class="sale-summary">
                <h3 style="margin-bottom: 10px;">💰 Sale Summary</h3>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>{{ number_format($pos->sub_total ?? 0, 2) }}৳</span>
                </div>
                @if($pos->discount > 0)
                <div class="summary-row">
                    <span>Discount:</span>
                    <span>-{{ number_format($pos->discount, 2) }}৳</span>
                </div>
                @endif
                @if($pos->delivery > 0)
                <div class="summary-row">
                    <span>Delivery:</span>
                    <span>{{ number_format($pos->delivery, 2) }}৳</span>
                </div>
                @endif
                <div class="summary-row">
                    <span>Total:</span>
                    <span>{{ number_format($pos->total_amount, 2) }}৳</span>
                </div>
            </div>
            
            <!-- Payment Information -->
            @if($pos->payments && $pos->payments->count() > 0)
            <div class="payment-info">
                <h3>💳 Payment Information</h3>
                @foreach($pos->payments as $payment)
                    <p><strong>Payment {{ $loop->iteration }}:</strong> {{ number_format($payment->amount, 2) }}৳ ({{ ucfirst($payment->payment_method) }})</p>
                @endforeach
                <p><strong>Total Paid:</strong> {{ number_format($pos->payments->sum('amount'), 2) }}৳</p>
                @if($pos->invoice && $pos->invoice->due_amount > 0)
                    <p><strong>Due Amount:</strong> {{ number_format($pos->invoice->due_amount, 2) }}৳</p>
                @endif
            </div>
            @endif
            
            <!-- Delivery Information -->
            @if($pos->estimated_delivery_date)
            <div class="delivery-info">
                <h3>🚚 Delivery Information</h3>
                <p><strong>Estimated Delivery Date:</strong> {{ \Carbon\Carbon::parse($pos->estimated_delivery_date)->format('F j, Y') }}</p>
                @if($pos->estimated_delivery_time)
                    <p><strong>Estimated Delivery Time:</strong> {{ $pos->estimated_delivery_time }}</p>
                @endif
                @if($pos->employee)
                    <p><strong>Assigned Technician:</strong> {{ $pos->employee->user->first_name ?? 'N/A' }} {{ $pos->employee->user->last_name ?? '' }}</p>
                @endif
            </div>
            @endif
            
            <!-- Sale Status -->
            <div class="customer-info">
                <h3>📊 Sale Status</h3>
                <p><strong>Status:</strong> 
                    @if($pos->status == 'completed')
                        <span style="color: #28a745;">✓ Completed</span>
                    @elseif($pos->status == 'pending')
                        <span style="color: #ffc107;">⏳ Pending</span>
                    @elseif($pos->status == 'cancelled')
                        <span style="color: #dc3545;">❌ Cancelled</span>
                    @else
                        <span style="color: #6c757d;">{{ ucfirst($pos->status) }}</span>
                    @endif
                </p>
                @if($pos->soldBy)
                    <p><strong>Sold By:</strong> {{ $pos->soldBy->first_name ?? 'N/A' }} {{ $pos->soldBy->last_name ?? '' }}</p>
                @endif
                @if($pos->notes)
                    <p><strong>Notes:</strong> {{ $pos->notes }}</p>
                @endif
            </div>
            
            <!-- Next Steps -->
            <div class="customer-info">
                <h3>📦 What's Next?</h3>
                @if($pos->estimated_delivery_date)
                    <p>• Your items will be delivered on {{ \Carbon\Carbon::parse($pos->estimated_delivery_date)->format('F j, Y') }}</p>
                @else
                    <p>• Your items are ready for pickup</p>
                @endif
                @if($pos->invoice && $pos->invoice->due_amount > 0)
                    <p>• Please complete the remaining payment of {{ number_format($pos->invoice->due_amount, 2) }}৳</p>
                @endif
                <p>• Keep this sale number for reference: <strong>{{ $pos->sale_number }}</strong></p>
                <p>• For any questions, please contact our support team</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <h3>Thank you for choosing us!</h3>
            <p>We appreciate your business and look forward to serving you again.</p>
            
            <div class="contact-info">
                <p><strong>Need Help?</strong></p>
                <p>Email: {{ $general_settings->contact_email ?? 'support@yourcompany.com' }}</p>
                <p>Phone: {{ $general_settings->contact_phone ?? '+1 (555) 123-4567' }}</p>
            </div>
        </div>
    </div>
</body>
</html> 