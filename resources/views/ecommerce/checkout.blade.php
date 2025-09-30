@extends('ecommerce.master')

@section('main-section')
    <div class="checkout-container">
        <div class="container-fluid py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <!-- Progress Steps -->
                    <div class="checkout-progress mb-5">
                        <div class="progress-container">
                            <div class="progress-step active">
                                <div class="step-circle">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <span class="step-label">Cart</span>
                            </div>
                            <div class="progress-line active"></div>
                            <div class="progress-step active">
                                <div class="step-circle">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <span class="step-label">Checkout</span>
                            </div>
                            <div class="progress-line"></div>
                            <div class="progress-step">
                                <div class="step-circle">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="step-label">Complete</span>
                            </div>
                        </div>
                    </div>

                    <form class="row g-4" action="{{ route('order.make') }}" method="POST">
                        @csrf
                        <!-- Left Column - Forms -->
                        <div class="col-lg-8">
                            <!-- Shipping Information -->
                            <div class="checkout-section">
                                <div class="section-header">
                                    <h4><i class="fas fa-truck me-2"></i>Shipping Information</h4>
                                    <p class="text-muted">Please provide your shipping details</p>
                                </div>
                                <div class="section-body">
                                    <div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">First Name *</label>
                                                <input type="text" class="form-control modern-input" name="first_name" value="{{ auth()->user()->first_name }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Last Name *</label>
                                                <input type="text" class="form-control modern-input" name="last_name" value="{{ auth()->user()->last_name }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Email Address *</label>
                                                <input type="email" class="form-control modern-input" name="email" value="{{ auth()->user()->email }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Phone Number *</label>
                                                <input type="tel" class="form-control modern-input" name="phone" value="{{ optional(auth()->user()->customer)->phone }}" required>
                                            </div>
                                            <h5 class="mt-3"><i class="fas fa-file-invoice me-2"></i>Billing Address</h5>
                                            <div class="col-12">
                                                <label class="form-label">Address *</label>
                                                <input type="text" class="form-control modern-input" name="billing_address_1" value="{{ optional(auth()->user()->customer)->address_1 }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">City *</label>
                                                <input type="text" class="form-control modern-input" name="billing_city" value="{{ optional(auth()->user()->customer)->city }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">State *</label>
                                                <input type="text" class="form-control modern-input" name="billing_state" value="{{ optional(auth()->user()->customer)->state }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">ZIP Code *</label>
                                                <input type="text" class="form-control modern-input" name="billing_zip_code" value="{{ optional(auth()->user()->customer)->zip_code }}" required>
                                            </div>
                                            <input type="hidden" name="billing_address_2" value="">
                                            <input type="hidden" name="billing_country" value="">
                                            <input type="hidden" name="shipping_address_2" value="">
                                            <input type="hidden" name="shipping_country" value="">
                                        </div>
                                        <div class="billing-address-toggle mt-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="sameAsBilling" checked>
                                                <label class="form-check-label" for="sameAsBilling">
                                                    Billing address same as shipping address
                                                </label>
                                            </div>
                                        </div>
                                        <div id="billingAddressSection" style="display: none; margin-top: 1.5rem;">
                                            <h5 class="mb-3"><i class="fas fa-file-invoice me-2"></i>Shipping Address</h5>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">First Name *</label>
                                                    <input type="text" class="form-control modern-input" name="shipping_first_name">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Last Name *</label>
                                                    <input type="text" class="form-control modern-input" name="shipping_last_name">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Phone Number *</label>
                                                    <input type="tel" class="form-control modern-input" name="shipping_phone">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Address *</label>
                                                    <input type="text" class="form-control modern-input" name="shipping_address_1" value="">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">City *</label>
                                                    <input type="text" class="form-control modern-input" name="shipping_city" value="">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">State *</label>
                                                    <input type="text" class="form-control modern-input" name="shipping_state" value="">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">ZIP Code *</label>
                                                    <input type="text" class="form-control modern-input" name="shipping_zip_code" value="">
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="shipping_method" id="shippingMethodInput" value="standard">
                                        {{-- <input type="hidden" name="payment_method" id="paymentMethodInput" value="cod"> --}}
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping Method -->
                            <div class="checkout-section">
                                <div class="section-header">
                                    <h4><i class="fas fa-shipping-fast me-2"></i>Shipping Method</h4>
                                    <p class="text-muted">Choose your preferred delivery option</p>
                                </div>
                                <div class="section-body">
                                    <div class="shipping-options">
                                        <div class="shipping-option">
                                            <input type="radio" name="shipping" id="standard" value="standard" checked>
                                            <label for="standard" class="shipping-label">
                                                <div class="shipping-info">
                                                    <div class="shipping-name">Standard Shipping</div>
                                                    <div class="shipping-desc">5-7 business days</div>
                                                </div>
                                                <div class="shipping-price">60৳</div>
                                            </label>
                                        </div>
                                        <div class="shipping-option">
                                            <input type="radio" name="shipping" id="express" value="express">
                                            <label for="express" class="shipping-label">
                                                <div class="shipping-info">
                                                    <div class="shipping-name">Express Shipping</div>
                                                    <div class="shipping-desc">2-3 business days</div>
                                                </div>
                                                <div class="shipping-price">100৳</div>
                                            </label>
                                        </div>
                                        <div class="shipping-option">
                                            <input type="radio" name="shipping" id="overnight" value="overnight">
                                            <label for="overnight" class="shipping-label">
                                                <div class="shipping-info">
                                                    <div class="shipping-name">Overnight Shipping</div>
                                                    <div class="shipping-desc">Next business day</div>
                                                </div>
                                                <div class="shipping-price">120৳</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="checkout-section">
                                <div class="section-header">
                                    <h4><i class="fas fa-credit-card me-2"></i>Payment Method</h4>
                                    <p class="text-muted">Secure payment processing</p>
                                </div>
                                <div class="section-body">
                                    <div class="payment-options mb-4">
                                        <div class="payment-option">
                                            <input type="radio" name="payment_method" id="cod" value="cash" checked>
                                            <label for="cod" class="payment-label">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <span>Cash on Delivery</span>
                                            </label>
                                        </div>
                                        <div class="payment-option">
                                            <input type="radio" name="payment_method" id="online" value="online-payment">
                                            <label for="online" class="payment-label">
                                                <i class="fas fa-globe"></i>
                                                <span>Online Payment</span>
                                            </label>
                                        </div>
                                        <div class="payment-option">
                                            <input type="radio" name="payment_method" id="bank" value="bank-transfer">
                                            <label for="bank" class="payment-label">
                                                <i class="fas fa-university"></i>
                                                <span>Bank Transfer</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="credit-card-form" id="onlinePaymentForm" style="display: none;">
                                        
                                    </div>
                                    <div class="bank-transfer-info" id="bankTransferInfo" style="display: none;">
                                        <div class="alert alert-info mt-3">
                                            <strong>Bank Transfer Instructions:</strong><br>
                                            Please transfer the total amount to the following bank account and upload your
                                            payment slip.<br>
                                            <ul class="mb-1 mt-2">
                                                <li>Bank: Example Bank</li>
                                                <li>Account Name: AquaPure Ltd.</li>
                                                <li>Account Number: 1234567890</li>
                                                <li>Branch: Main Branch</li>
                                            </ul>
                                            <label class="form-label mt-2">Upload Payment Slip:</label>
                                            <input type="file" class="form-control modern-input"
                                                accept="image/*,application/pdf">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Order Summary -->
                        <div class="col-lg-4">
                            <div class="order-summary">
                                <div class="summary-header">
                                    <h4><i class="fas fa-receipt me-2"></i>Order Summary</h4>
                                </div>
                                <div class="summary-body">
                                    <!-- Order Items -->
                                    <div class="order-items">
                                        @foreach ($carts as $cart)

                                            <div class="order-item">
                                                <img src="{{ asset(@$cart->product->image) }}"
                                                    alt="Product">
                                                <div class="item-details">
                                                    <div class="item-name">{{ @$cart->product->name }}</div>
                                                    <div class="item-quantity">Qty: {{$cart->qty}}</div>
                                                </div>
                                                <div class="item-price">{{ $cart->qty * ($cart->product->discount > 0 ? $cart->product->discount : $cart->product->price) }}৳</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Promo Code -->
                                    <div class="promo-code-section">
                                        <div class="input-group">
                                            <input type="text" class="form-control modern-input"
                                                placeholder="Enter promo code">
                                            <button class="btn btn-outline-primary" type="button">Apply</button>
                                        </div>
                                    </div>

                                    <!-- Price Breakdown -->
                                    <div class="price-breakdown">
                                        <div class="price-row">
                                            <span>Subtotal</span>
                                            <span>{{$cartTotal}}৳</span>
                                        </div>
                                        <div class="price-row">
                                            <span>Shipping</span>
                                            <span>60৳</span>
                                        </div>
                                        <div class="price-row">
                                            <span>Tax</span>
                                            <span>{{ $cartTotal * 0.05 }}৳</span>
                                        </div>
                                        <div class="price-row discount-row" style="display: none;">
                                            <span>Discount</span>
                                            <span class="text-success">-10.00৳</span>
                                        </div>
                                        <div class="price-row total-row">
                                            <span>Total</span>
                                            <span>{{$cartTotal + ($cartTotal * 0.05) + 60}}৳</span>
                                        </div>
                                    </div>

                                    <!-- Place Order Button -->
                                    <button type="submit" class="btn btn-place-order w-100" style="background-color: var(--primary-blue); color: #fff;">
                                        <i class="fas fa-credit-card me-2"></i>
                                        Place Order - {{$cartTotal + ($cartTotal * 0.05) + 60}}৳
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --bg-light: #f8fafc;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .checkout-container {
            background: var(--bg-light);
            min-height: 100vh;
            padding-top: 2rem;
        }

        /* Progress Steps */
        .checkout-progress {
            text-align: center;
        }

        .progress-container {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e2e8f0;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s;
            margin-bottom: 0.5rem;
        }

        .progress-step.active .step-circle {
            background: var(--primary-blue);
            color: white;
        }

        .step-label {
            font-size: 0.875rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .progress-step.active .step-label {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .progress-line {
            flex: 1;
            height: 2px;
            background: #e2e8f0;
            margin: 0 1rem;
            margin-bottom: 2rem;
        }

        .progress-line.active {
            background: var(--primary-blue);
        }

        /* Checkout Sections */
        .checkout-section {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, var(--light-blue), white);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-header h4 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .section-header p {
            margin: 0;
            font-size: 0.9rem;
        }

        .section-body {
            padding: 1.5rem;
        }

        /* Modern Input Styles */
        .modern-input {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .modern-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(13, 162, 231, 0.15);
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        /* Shipping Options */
        .shipping-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .shipping-option {
            position: relative;
        }

        .shipping-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .shipping-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .shipping-option input[type="radio"]:checked+.shipping-label {
            border-color: var(--primary-blue);
            background: var(--light-blue);
        }

        .shipping-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .shipping-desc {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .shipping-price {
            font-weight: 700;
            color: var(--primary-blue);
            font-size: 1.1rem;
        }

        /* Payment Options */
        .payment-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .payment-option {
            flex: 1;
            min-width: 150px;
        }

        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .payment-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            text-align: center;
        }

        .payment-option input[type="radio"]:checked+.payment-label {
            border-color: var(--primary-blue);
            background: var(--light-blue);
        }

        .payment-label i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary-blue);
        }

        .payment-label span {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        /* Order Summary */
        .order-summary {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 2rem;
            overflow: hidden;
        }

        .summary-header {
            background: linear-gradient(135deg, var(--light-blue), white);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .summary-header h4 {
            color: var(--text-dark);
            margin: 0;
            font-weight: 600;
        }

        .summary-body {
            padding: 1.5rem;
        }

        .order-items {
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 1rem;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .item-quantity {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .item-price {
            font-weight: 700;
            color: var(--primary-blue);
        }

        .promo-code-section {
            margin-bottom: 1.5rem;
        }

        .price-breakdown {
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
            margin-bottom: 1.5rem;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }

        .total-row {
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
            margin-top: 1rem;
            margin-bottom: 0;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-dark);
        }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success-color);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            padding: 0.75rem;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 8px;
        }

        .btn-place-order {
            background: var(--primary-blue);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-place-order:hover {
            background: var(--dark-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .progress-container {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .progress-line {
                display: none;
            }

            .payment-options {
                flex-direction: column;
            }

            .order-summary {
                position: static;
                margin-top: 2rem;
            }
        }
    </style>

    <script>
        // Card number formatting
        document.addEventListener('DOMContentLoaded', function () {
            const cardInput = document.querySelector('input[placeholder="1234 5678 9012 3456"]');
            const expiryInput = document.querySelector('input[placeholder="MM/YY"]');

            if (cardInput) {
                cardInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                    if (formattedValue.length > 19) formattedValue = formattedValue.substr(0, 19);
                    e.target.value = formattedValue;
                });
            }

            if (expiryInput) {
                expiryInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                });
            }

            // Shipping method price update
            const shippingOptions = document.querySelectorAll('input[name="shipping"]');
            shippingOptions.forEach(option => {
                option.addEventListener('change', function () {
                    updateShippingPrice(this.value);
                });
            });

            // Payment method toggle
            const paymentOptions = document.querySelectorAll('input[name="payment"]');
            const creditCardForm = document.querySelector('.credit-card-form');

            paymentOptions.forEach(option => {
                option.addEventListener('change', function () {
                    if (this.value === 'credit') {
                        creditCardForm.style.display = 'block';
                    } else {
                        creditCardForm.style.display = 'none';
                    }
                });
            });

            // Billing address toggle
            document.getElementById('sameAsBilling').addEventListener('change', function () {
                document.getElementById('billingAddressSection').style.display = this.checked ? 'none' : '';
            });
            // Payment method toggle
            document.querySelectorAll('input[name="payment"]').forEach(function (option) {
                option.addEventListener('change', function () {
                    document.getElementById('onlinePaymentForm').style.display = (this.value === 'online') ? '' : 'none';
                    document.getElementById('bankTransferInfo').style.display = (this.value === 'bank') ? '' : 'none';
                });
            });
        });

        function updateShippingPrice(method) {
            const prices = {
                'standard': 60,
                'express': 100,
                'overnight': 120
            };

            const subtotal = {{ $cartTotal }};
            const tax = {{ $cartTotal * 0.05 }};
            const shipping = prices[method];
            const total = subtotal + tax + shipping;

            document.querySelector('.price-breakdown .price-row:nth-child(2) span:last-child').textContent = `${shipping.toFixed(2)}৳`;
            document.querySelector('.total-row span:last-child').textContent = `${total.toFixed(2)}৳`;
            document.querySelector('.btn-place-order').innerHTML = `<i class="fas fa-credit-card me-2"></i>Place Order - ${total.toFixed(2)}৳`;
        }
    </script>
@endsection