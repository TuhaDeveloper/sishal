<div id="checkoutDrawer" class="checkout-drawer w-50" style="display:none;">
    <!-- Backdrop -->
    <div class="drawer-backdrop"></div>

    <!-- Drawer Content -->
    <div class="drawer-container">
        <!-- Header -->
        <div class="drawer-header">
            <div class="header-content">
                <div class="header-left">
                    <i class="fas fa-shopping-bag header-icon"></i>
                    <div>
                        <h3 class="header-title">Checkout</h3>
                        <p class="header-subtitle">Complete your purchase</p>
                    </div>
                </div>
                <button type="button" class="close-button" id="closeCheckoutDrawer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="drawer-content">
            <div class="row">
                <div class="col-6 pe-0">
                    <!-- Cart Section -->
                    <div class="cart-section mb-4">
                        <div class="section-header">
                            <h4 class="section-title">
                                <i class="fas fa-list-ul"></i>
                                Order Summary
                            </h4>
                        </div>

                        <div class="cart-items-container" id="drawerCartItems">
                            <!-- Cart items will be populated here -->
                        </div>

                        <div class="cart-totals">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span id="drawerCartSubtotal">0.00৳</span>
                            </div>
                            <div class="total-row">
                                <span>Shipping</span>
                                <span id="drawerShippingTotal">0.00৳</span>
                            </div>
                            <div class="total-row">
                                <span>Discount</span>
                                <span id="drawerDiscountTotal" class="text-danger">0.00৳</span>
                            </div>
                            <div class="total-row final-total">
                                <span>Total</span>
                                <span id="drawerCartTotal">0.00৳</span>
                            </div>
                            <div class="total-row">
                                <span>Paid Amount</span>
                                <span id="drawerPaidAmountTotal">0.00৳</span>
                            </div>
                            <div class="total-row">
                                <span>Due Amount</span>
                                <span id="drawerDueAmountTotal">0.00৳</span>
                            </div>
                        </div>
                    </div>

                    <div class="cart-section">
                        <div class="section-header">
                            <h4 class="section-title">
                                <i class="fas fa-list-ul"></i>
                                Order Note
                            </h4>
                        </div>

                        <div class="cart-items-container">
                            <textarea name="" id="" class="form-input" rows="6"></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-6 ps-0" style="margin-bottom: 100px;">
                    <!-- Customer & Payment Section -->
                    <div class="payment-section">
                        <div class="section-header">
                            <h4 class="section-title">
                                <i class="fas fa-user"></i>
                                Customer Details
                            </h4>
                        </div>

                        <!-- Customer Type Tabs -->
                        <div class="customer-tabs mb-4">
                            <div class="tab-buttons w-100">
                                <button type="button" class="tab-btn active" data-tab="walk-in" style="font-size: 10px; width: 33.33%;">
                                    Walk-in Customer
                                </button>
                                <button type="button" class="tab-btn" data-tab="new-customer" style="font-size: 10px; width: 33.33%;">
                                    New Customer
                                </button>
                                <button type="button" class="tab-btn" data-tab="existing-customer" style="font-size: 10px; width: 33.33%;">
                                    Existing Customer
                                </button>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <!-- Walk-in Customer Tab -->
                            <div class="tab-pane active mb-3" id="walk-in-tab">
                                <div class="walk-in-info">
                                    <div class="info-card">
                                        <i class="fas fa-info-circle"></i>
                                        <div>
                                            <h6>Walk-in Customer</h6>
                                            <p>No customer details required. Proceed with checkout.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- New Customer Tab -->
                            <div class="tab-pane" id="new-customer-tab">
                                <div class="form-grid mb-3">
                                    <div class="form-group">
                                        <label for="drawerFullName" class="form-label">Full Name *</label>
                                        <div class="input-group">
                                            <i class="fas fa-user input-icon"></i>
                                            <input type="text" class="form-input" style="border-radius: 12px;" id="drawerFullName" placeholder="Enter customer's full name">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="drawerEmail" class="form-label">Email *</label>
                                        <div class="input-group">
                                            <i class="fas fa-envelope input-icon"></i>
                                            <input type="email" class="form-input" style="border-radius: 12px;" id="drawerEmail" placeholder="Enter customer's email">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="drawerPhone" class="form-label">Phone Number</label>
                                        <div class="input-group">
                                            <i class="fas fa-phone input-icon"></i>
                                            <input type="tel" class="form-input" style="border-radius: 12px;" id="drawerPhone" placeholder="Enter phone number">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Customer Tab -->
                            <div class="tab-pane" id="existing-customer-tab">
                                <div class="form-group mb-3">
                                    <label for="drawerCustomerSelect" class="form-label">Search Customer</label>
                                    <div class="input-group">
                                        <i class="fas fa-search input-icon"></i>
                                        <select class="form-input" id="drawerCustomerSelect" style="width: 100%;">
                                            <option value="">Search or select customer</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estimated Date & Time Row -->
                        <div class="row mb-3">

                            <div class="col-12 mb-3">
                                <div class="form-group">
                                    <label for="drawerAddress" class="form-label">Address</label>
                                    <input type="text" class="form-input" id="drawerAddress">
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="form-group">
                                    <label for="drawerCity" class="form-label">City</label>
                                    <input type="text" class="form-input" id="drawerCity">
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="form-group">
                                    <label for="drawerState" class="form-label">State</label>
                                    <input type="text" class="form-input" id="drawerState">
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="form-group">
                                    <label for="drawerZipCode" class="form-label">Zip Code</label>
                                    <input type="text" class="form-input" id="drawerZipCode">
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                            </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="drawerEstimatedDate" class="form-label">Estimated Delivey
                                            Date</label>
                                        <input type="date" class="form-input" id="drawerEstimatedDate">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="drawerEstimatedTime" class="form-label">Estimated Delivey
                                            Time</label>
                                        <input type="time" class="form-input" id="drawerEstimatedTime">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="drawerEmployee" class="form-label">Technician</label>
                                <div class="input-group">
                                    <i class="fas fa-user-tie input-icon"></i>
                                    <select class="form-input" id="drawerEmployee">
                                        <option value="">Choose technician</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6 mb-3">
                                    <div class="form-group">
                                        <label for="drawerPaidAmountInput" class="form-label">Paid Amount</label>
                                        <input type="text" class="form-input" id="drawerPaidAmountInput">
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="form-group">
                                        <label for="drawerDiscountInput" class="form-label">Discount</label>
                                        <input type="text" class="form-input" id="drawerDiscountInput">
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="form-group">
                                        <label for="drawerShippingCharge" class="form-label">Shipping Charge</label>
                                        <div class="input-group">
                                            <i class="fas fa-shipping-fast input-icon"></i>
                                            <input type="number" class="form-input" id="drawerShippingCharge" placeholder="0.00" style="border-radius: 12px;"
                                                min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="form-group">
                                        <label for="drawerPaymentMethod" class="form-label">Payment Method</label>
                                        <div class="input-group">
                                            <i class="fas fa-credit-card input-icon"></i>
                                            <select class="form-input" id="drawerPaymentMethod" style="border-radius: 12px;">
                                                <option value="cash" selected>💵 Cash</option>
                                                <option value="card">💳 Credit/Debit Card</option>
                                                <option value="bank">🏦 Bank Transfer</option>
                                                <option value="mobile">📱 Mobile Payment</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="drawerBankAccount" class="form-label">Bank Account</label>
                                        <div class="input-group">
                                            <i class="fas fa-university input-icon"></i>
                                            <select class="form-input" id="drawerBankAccount" name="account_id" style="border-radius: 12px;">
                                                @foreach ($bankAccounts as $bankAccount)
                                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->provider_name . ' - ' . $bankAccount->account_number }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="drawer-footer position-absolute bottom-0 w-100" style="z-index: 9;">
            <form id="posCheckoutForm" method="POST" action="{{ route('pos.store') }}">
                @csrf
                <input type="hidden" id="hiddenBranchId" name="branch_id" value="">
                <button type="submit" class="checkout-button" id="drawerCheckoutBtn">
                    <i class="fas fa-lock" id="checkoutIcon"></i>
                    <i class="fas fa-spinner fa-spin" id="checkoutSpinner" style="display: none;"></i>
                    <span id="checkoutText">Complete Secure Checkout</span>
                    <div class="button-shine"></div>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    .checkout-drawer {
        position: fixed;
        top: 0;
        right: 0;
        width: 100vw;
        height: 100vh;
        z-index: 1055;
        display: none;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .checkout-drawer.open {
        display: block;
    }

    .drawer-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 1;
    }

    .checkout-drawer.open .drawer-backdrop {
        opacity: 1;
    }

    .drawer-container {
        position: relative;
        width: 100%;
        height: 100%;
        background: #ffffff;
        transform: translateX(100%);
        transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        display: flex;
        flex-direction: column;
        z-index: 2;
        box-shadow: -20px 0 60px rgba(0, 0, 0, 0.15);
    }

    .checkout-drawer.open .drawer-container {
        transform: translateX(0);
    }

    /* Header */
    .drawer-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0ea5e9 100%);
        color: white;
        padding: 24px;
        position: relative;
        overflow: hidden;
    }

    .drawer-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: shimmer 4s infinite linear;
    }

    @keyframes shimmer {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .header-content {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .header-icon {
        font-size: 24px;
        padding: 12px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        backdrop-filter: blur(10px);
    }

    .header-title {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .header-subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
        font-weight: 400;
    }

    .close-button {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        color: white;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close-button:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    /* Content */
    .drawer-content {
        flex: 1;
        overflow-y: auto;
        padding: 0;
        overflow-x: hidden;
        background: #f8fafc;
    }

    .cart-section,
    .payment-section {
        background: white;
        margin: 16px;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f1f5f9;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .section-title i {
        color: #667eea;
    }

    .item-count {
        background: #667eea;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .cart-items-container {
        max-height: 200px;
        overflow-y: auto;
        margin-bottom: 20px;
    }

    .cart-totals {
        border-top: 2px solid #f1f5f9;
        padding-top: 16px;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        font-size: 14px;
        color: #64748b;
    }

    .total-row.final-total {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        padding-top: 12px;
        border-top: 2px solid #f1f5f9;
        margin-top: 8px;
    }

    .form-grid {
        display: grid;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-icon {
        position: absolute;
        left: 16px;
        color: #9ca3af;
        font-size: 14px;
        z-index: 1;
    }

    .form-input {
        width: 100%;
        padding: 16px 16px 16px 48px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 500;
        background: white;
        color: #374151;
        transition: all 0.3s ease;
        outline: none;
    }

    .form-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
    }

    .form-input::placeholder {
        color: #9ca3af;
    }

    /* Footer */
    .drawer-footer {
        padding: 24px;
        background: white;
        border-top: 1px solid #e2e8f0;
    }

    .checkout-button {
        width: 100%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 18px 24px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }

    .checkout-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(16, 185, 129, 0.4);
    }

    .checkout-button:active {
        transform: translateY(0);
    }

    .button-shine {
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .checkout-button:hover .button-shine {
        left: 100%;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .checkout-drawer {
            max-width: 100vw;
        }

        .drawer-header {
            padding: 20px;
        }

        .header-title {
            font-size: 20px;
        }

        .cart-section,
        .payment-section {
            margin: 12px;
            padding: 20px;
            border-radius: 12px;
        }

        .drawer-footer {
            padding: 20px;
        }

        .checkout-button {
            padding: 16px 20px;
            font-size: 15px;
        }
    }

    /* Scrollbar styling */
    .drawer-content::-webkit-scrollbar,
    .cart-items-container::-webkit-scrollbar {
        width: 6px;
    }

    .drawer-content::-webkit-scrollbar-track,
    .cart-items-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .drawer-content::-webkit-scrollbar-thumb,
    .cart-items-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .drawer-content::-webkit-scrollbar-thumb:hover,
    .cart-items-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Animation for opening */
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .checkout-drawer.open .drawer-container {
        animation: slideIn 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    /* Customer Tabs Styles */
    .customer-tabs {
        margin-bottom: 20px;
    }

    .tab-buttons {
        display: flex;
        background: #f8fafc;
        border-radius: 12px;
        padding: 4px;
        gap: 4px;
    }

    .tab-btn {
        flex: 1;
        background: transparent;
        border: none;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .tab-btn:hover {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
    }

    .tab-btn.active {
        background: #667eea;
        color: white;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .tab-btn i {
        font-size: 16px;
    }

    .tab-content {
        position: relative;
    }

    .tab-pane {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .info-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .info-card i {
        font-size: 20px;
        color: #667eea;
    }

    .info-card h6 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
    }

    .info-card p {
        margin: 4px 0 0 0;
        font-size: 14px;
        color: #64748b;
    }

    .info-card small {
        font-size: 12px;
        color: #94a3b8;
    }

    .customer-info-display {
        margin-top: 16px;
    }

    .walk-in-info .info-card {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-color: #0ea5e9;
    }

    .walk-in-info .info-card i {
        color: #0ea5e9;
    }

    .customer-info-display .info-card {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-color: #10b981;
    }

    .customer-info-display .info-card i {
        color: #10b981;
    }

    .customer-info-display .info-card i.fa-spinner {
        color: #667eea;
        animation: spin 1s linear infinite;
    }

    .customer-info-display .info-card i.fa-exclamation-triangle {
        color: #f59e0b;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive design for tabs */
    @media (max-width: 768px) {
        .tab-buttons {
            flex-direction: column;
            gap: 8px;
        }
        
        .tab-btn {
            padding: 10px 12px;
            font-size: 13px;
        }
        
        .tab-btn i {
            font-size: 14px;
        }
        
        .info-card {
            padding: 12px;
        }
        
        .info-card h6 {
            font-size: 14px;
        }
        
        .info-card p {
            font-size: 13px;
        }
    }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Tab switching functionality
$(document).ready(function() {
    // Tab button click handlers
    $('.tab-btn').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Remove active class from all buttons and panes
        $('.tab-btn').removeClass('active');
        $('.tab-pane').removeClass('active');
        
        // Add active class to clicked button and corresponding pane
        $(this).addClass('active');
        $('#' + tabId + '-tab').addClass('active');
        
        // Clear form fields when switching tabs
        clearCustomerFields();
    });

    // Customer selection handler for existing customers
    $('#drawerCustomerSelect').on('change', function() {
        const customerId = $(this).val();
        if (customerId) {
            // Fetch full customer details via AJAX
            fetchCustomerDetails(customerId);
        } else {
            // Clear all customer fields when no customer is selected
            clearCustomerFields();
        }
    });

    // Function to fetch customer details and populate fields
    function fetchCustomerDetails(customerId) {
        // Show loading state
        $('#customerInfoDisplay').html('<div class="info-card"><i class="fas fa-spinner fa-spin"></i><div><h6>Loading customer details...</h6></div></div>').show();
        
        $.ajax({
            url: '/erp/customer/' + customerId,
            method: 'GET',
            success: function(response) {
                if (response.customer) {
                    const customer = response.customer;
                    
                    // Populate customer info display
                    $('#selectedCustomerName').text(customer.name || '');
                    $('#selectedCustomerEmail').text(customer.email || '');
                    $('#selectedCustomerPhone').text(customer.phone || 'Phone not available');
                    
                    // Populate address fields
                    $('#drawerAddress').val(customer.address || '');
                    $('#drawerCity').val(customer.city || '');
                    $('#drawerState').val(customer.state || '');
                    $('#drawerZipCode').val(customer.zip_code || '');
                    
                    // Show customer info display
                    $('#customerInfoDisplay').show();
                }
            },
            error: function(xhr) {
                console.log('Error fetching customer details:', xhr);
                
                // Show error message
                $('#customerInfoDisplay').html('<div class="info-card"><i class="fas fa-exclamation-triangle text-warning"></i><div><h6>Error loading customer details</h6><p>Please try again or contact support.</p></div></div>').show();
                
                // Fallback to parsing from option text
                const selectedOption = $('#drawerCustomerSelect').find('option:selected');
                const optionText = selectedOption.text();
                
                if (optionText !== 'Search or select customer') {
                    const parts = optionText.split(' - ');
                    const customerName = parts[0];
                    const customerEmail = parts[1] || '';
                    
                    // Restore the original customer info display structure
                    $('#customerInfoDisplay').html(`
                        <div class="info-card">
                            <i class="fas fa-user-check"></i>
                            <div>
                                <h6 id="selectedCustomerName">${customerName}</h6>
                                <p id="selectedCustomerEmail">${customerEmail}</p>
                                <small id="selectedCustomerPhone">Phone not available</small>
                            </div>
                        </div>
                    `).show();
                }
            }
        });
    }

    // Function to clear customer fields
    function clearCustomerFields() {
        $('#drawerFullName').val('');
        $('#drawerEmail').val('');
        $('#drawerPhone').val('');
        $('#drawerCustomerSelect').val('');
        
        // Clear address fields
        $('#drawerAddress').val('');
        $('#drawerCity').val('');
        $('#drawerState').val('');
        $('#drawerZipCode').val('');
        
        // Hide customer info display
        $('#customerInfoDisplay').hide();
    }

    // Load existing customers
    function loadExistingCustomers() {
        $.ajax({
            url: '/erp/customers/search',
            method: 'GET',
            data: { search: '' },
            success: function(response) {
                const select = $('#drawerCustomerSelect');
                select.empty();
                select.append('<option value="">Search or select customer</option>');
                
                if (response && response.length > 0) {
                    response.forEach(function(customer) {
                        select.append(`<option value="${customer.id}">${customer.name} - ${customer.email}</option>`);
                    });
                }
            },
            error: function() {
                console.log('Error loading customers');
            }
        });
    }

    // Load customers on page load
    loadExistingCustomers();
});

// Function to show loading state
function showCheckoutLoading() {
    $('#drawerCheckoutBtn').prop('disabled', true);
    $('#checkoutIcon').hide();
    $('#checkoutSpinner').show();
    $('#checkoutText').text('Processing...');
}

// Function to hide loading state
function hideCheckoutLoading() {
    $('#drawerCheckoutBtn').prop('disabled', false);
    $('#checkoutIcon').show();
    $('#checkoutSpinner').hide();
    $('#checkoutText').text('Complete Secure Checkout');
}

$('#posCheckoutForm').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    
    // Show loading state
    showCheckoutLoading();
    
    // Remove any previous dynamic inputs
    form.find('.dynamic-input').remove();

    // Collect values from the form
    const branchId = $('#hiddenBranchId').val(); // Get branch ID from hidden input
    
    // Determine customer type and get appropriate data
    const activeTab = $('.tab-btn.active').data('tab');
    let customerId = '';
    let customerName = '';
    let customerEmail = '';
    let customerPhone = '';
    
    if (activeTab === 'existing-customer') {
        customerId = $('#drawerCustomerSelect').val();
        customerName = $('#selectedCustomerName').text();
        customerEmail = $('#selectedCustomerEmail').text();
        customerPhone = $('#selectedCustomerPhone').text();
    } else if (activeTab === 'new-customer') {
        customerName = $('#drawerFullName').val();
        customerEmail = $('#drawerEmail').val();
        customerPhone = $('#drawerPhone').val();
    }
    
    // Get address information
    const customerAddress = $('#drawerAddress').val();
    const customerCity = $('#drawerCity').val();
    const customerState = $('#drawerState').val();
    const customerZipCode = $('#drawerZipCode').val();
    // For walk-in customer, all fields remain empty
    
    // Validate new customer fields if new customer tab is active
    if (activeTab === 'new-customer') {
        if (!customerName.trim()) {
            hideCheckoutLoading();
            alert('Please enter customer name for new customer.');
            return;
        }
        if (!customerEmail.trim()) {
            hideCheckoutLoading();
            alert('Please enter customer email for new customer.');
            return;
        }
    }
    
    const employeeId = $('#drawerEmployee').val();
    const saleDate = new Date().toISOString().slice(0, 10); // today
    const estimatedDeliveryDate = $('#drawerEstimatedDate').val();
    const estimatedDeliveryTime = $('#drawerEstimatedTime').val();
    const subTotal = $('#drawerCartSubtotal').text().replace(/[^\d.]/g, '') || 0;
    const discount = $('#drawerDiscountInput').val() || 0;
    const delivery = $('#drawerShippingCharge').val() || 0;
    const totalAmount = $('#drawerCartTotal').text().replace(/[^\d.]/g, '') || 0;
    const paidAmount = $('#drawerPaidAmountInput').val() || 0;
    const paymentMethod = $('#drawerPaymentMethod').val();
    const accountId = $('#drawerBankAccount').val();
    const notes = $('textarea.form-input').val();

    // Add as hidden inputs
    form.append('<input type="hidden" class="dynamic-input" name="branch_id" value="'+branchId+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_id" value="'+customerId+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_type" value="'+activeTab+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_name" value="'+customerName+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_email" value="'+customerEmail+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_phone" value="'+customerPhone+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_address" value="'+customerAddress+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_city" value="'+customerCity+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_state" value="'+customerState+'">');
    form.append('<input type="hidden" class="dynamic-input" name="customer_zip_code" value="'+customerZipCode+'">');
    form.append('<input type="hidden" class="dynamic-input" name="employee_id" value="'+employeeId+'">');
    form.append('<input type="hidden" class="dynamic-input" name="sale_date" value="'+saleDate+'">');
    form.append('<input type="hidden" class="dynamic-input" name="estimated_delivery_date" value="'+estimatedDeliveryDate+'">');
    form.append('<input type="hidden" class="dynamic-input" name="estimated_delivery_time" value="'+estimatedDeliveryTime+'">');
    form.append('<input type="hidden" class="dynamic-input" name="sub_total" value="'+subTotal+'">');
    form.append('<input type="hidden" class="dynamic-input" name="discount" value="'+discount+'">');
    form.append('<input type="hidden" class="dynamic-input" name="delivery" value="'+delivery+'">');
    form.append('<input type="hidden" class="dynamic-input" name="total_amount" value="'+totalAmount+'">');
    form.append('<input type="hidden" class="dynamic-input" name="paid_amount" value="'+paidAmount+'">');
    form.append('<input type="hidden" class="dynamic-input" name="payment_method" value="'+paymentMethod+'">');
    form.append('<input type="hidden" class="dynamic-input" name="account_id" value="'+accountId+'">');
    form.append('<input type="hidden" class="dynamic-input" name="notes" value="'+notes+'">');

    // Read cart from sessionStorage
    let cartData = [];
    const storedCart = sessionStorage.getItem('cartItems');
    if (storedCart) {
        try {
            cartData = JSON.parse(storedCart);
        } catch (e) {
            cartData = [];
        }
    }
    if (Array.isArray(cartData)) {
        cartData.forEach(function(item, idx) {
            const productId = parseInt(item.id, 10);
            const quantity = parseFloat(item.quantity) || 0;
            let unitPrice = 0;
            if (item.discount && Number(item.discount) < Number(item.price)) {
                unitPrice = parseFloat(item.discount);
            } else {
                unitPrice = parseFloat(item.price);
            }
            const totalPrice = unitPrice * quantity;
            form.append('<input type="hidden" class="dynamic-input" name="items['+idx+'][product_id]" value="'+productId+'">');
            form.append('<input type="hidden" class="dynamic-input" name="items['+idx+'][quantity]" value="'+quantity+'">');
            form.append('<input type="hidden" class="dynamic-input" name="items['+idx+'][unit_price]" value="'+unitPrice+'">');
            form.append('<input type="hidden" class="dynamic-input" name="items['+idx+'][total_price]" value="'+totalPrice+'">');
        });
    }

    // Submit via AJAX
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val()
        },
        success: function(response) {
            // Clear cart after successful sale
            sessionStorage.setItem('cartItems', '[]');
            // Optionally, reload the page or update UI
            location.reload();
        },
        error: function(xhr) {
            // Hide loading state on error
            hideCheckoutLoading();
            
            // Optionally, show error messages
            if (xhr.responseJSON && xhr.responseJSON.message) {
                alert('Error: ' + xhr.responseJSON.message);
            } else {
                alert('An error occurred while processing the sale.');
            }
        }
    });
});
</script>