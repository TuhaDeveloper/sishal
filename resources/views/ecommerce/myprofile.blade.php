@extends('ecommerce.master')

@section('main-section')
<style>
    .profile-page {
        background: #f8f9fa;
        min-height: 100vh;
    }
    
    .profile-header {
        background: white;
        border-bottom: 1px solid #e9ecef;
        padding: 2rem 20px; /* 20px left/right padding */
        margin-bottom: 2rem;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #00512C;
    }
    
    .card-simple {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
    }
    
    .card-header-simple {
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
        font-weight: 600;
        color: #333;
    }
    
    .card-body-simple {
        padding: 1.5rem;
    }
    
    .form-control-simple {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 14px;
        width: 100%;
        transition: all 0.3s ease;
        background: #fff;
    }
    
    .form-control-simple:hover {
        border-color: #cbd5e1;
    }
    
    .form-control-simple:focus {
        border-color: #00512C;
        box-shadow: 0 0 0 0.2rem rgba(0, 81, 44, 0.25);
        outline: none;
    }
    
    .btn-simple {
        background: #00512C;
        border: 1px solid #00512C;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-simple:hover {
        background: #004124;
        border-color: #004124;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 81, 44, 0.3);
    }
    
    .btn-outline-simple {
        background: transparent;
        border: 1px solid #6c757d;
        color: #6c757d;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .btn-outline-simple:hover {
        background: #6c757d;
        color: white;
    }

    /* Action buttons on orders row */
    .btn-view {
        background: #ffffff;
        color: #374151;
        border: 1px solid #e5e7eb;
        padding: 0.5rem 0.9rem;
        border-radius: 8px;
        font-weight: 600;
    }
    .btn-view:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    .btn-cancel {
        background: #ffffff;
        color: #6b7280;
        border: 1px solid #d1d5db;
        padding: 0.5rem 0.9rem;
        border-radius: 8px;
        font-weight: 600;
    }
    .btn-cancel:hover { background: #f9fafb; }
    .btn-delete-strong { background: #ef4444; color: #fff; border: 1px solid #ef4444; padding: 0.5rem 0.9rem; border-radius: 8px; font-weight: 700; }
    .btn-delete-strong:hover { background: #dc2626; border-color: #dc2626; }

    /* Ensure modals overlay everything on this page */
    /* Force highest stacking to fix backdrop under header issue */
    .modal.show { z-index: 4010 !important; }
    .modal-backdrop.show { z-index: 4000 !important; }
    
    .form-label-simple {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 14px;
        display: block;
    }
    
    .nav-tabs-simple {
        border: none;
        background: #f8f9fa;
    }
    
    .nav-tabs-simple .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        padding: 1rem 1.5rem;
        background: transparent;
    }
    
    .nav-tabs-simple .nav-link:hover {
        color: #00512C;
        background: rgba(0, 81, 44, 0.1);
    }
    
    .nav-tabs-simple .nav-link.active {
        color: #00512C;
        background: white;
        border-bottom: 2px solid #00512C;
    }
    
    .order-card-simple {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Order item styling improvements */
    .order-item {
        gap: 0.75rem;
        border: 1px solid #eef2f7;
        background: #f9fafb;
    }
    .order-item:hover {
        background: #f3f4f6;
        border-color: #e5e7eb;
    }
    .order-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }
    
    .text-muted-simple {
        color: #6c757d;
        font-size: 13px;
    }
    
    /* Form Section Styling */
    .form-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .form-section-title {
        color: #00512C;
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group:last-child {
        margin-bottom: 0;
    }
    
    /* Improved spacing and alignment */
    .row.g-4 > * {
        padding: 0.75rem;
    }
    
    .row.g-3 > * {
        padding: 0.5rem;
    }
    
    /* Better visual hierarchy */
    .card-body-simple {
        padding: 2rem;
    }
    
    /* Enhanced card styling */
    .card-simple {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    /* Profile header improvements */
    .profile-header {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-bottom: 1px solid #e2e8f0;
        padding: 2.5rem 20px; /* 20px left/right padding */
        margin-bottom: 2rem;
    }
    
    .btn-delete {
        background: #dc2626;
        border: 1px solid #dc2626;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .btn-delete:hover {
        background: #b91c1c;
        border-color: #b91c1c;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    
    .btn-delete:disabled {
        background: #9ca3af;
        border-color: #9ca3af;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* Fix Bootstrap badge positioning inside order cards (override global .badge) */
    .order-card-simple .badge {
        position: static;
        top: auto;
        right: auto;
        min-width: auto;
        height: auto;
        border-radius: 0.375rem;
        padding: 0.35rem 0.6rem;
        line-height: 1;
        display: inline-block;
    }
</style>

<div class="container py-4">
    <!-- Simple Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <img src="{{ $user->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->first_name . ' ' . $user->last_name) . '&background=00512C&color=fff&size=80' }}" 
                         alt="Profile Avatar" class="profile-avatar me-3">
                    <div>
                        <h3 class="mb-1">{{$user->first_name}} {{$user->last_name}}</h3>
                        <p class="mb-1 text-muted-simple">{{$user->email}}</p>
                        <small class="text-muted-simple">Member since {{ $user->created_at->format('M Y') }} • {{ $orders->count() }} Orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <small class="text-muted-simple">{{ $orders->where('status', 'delivered')->count() }} completed orders</small>
            </div>
        </div>
    </div>

        <!-- Simple Tab Navigation -->
        <div class="card-simple">
            <div class="card-header p-0">
                <ul class="nav nav-tabs-simple nav-fill" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request()->get('tab') == 'profile' || !request()->has('tab') ? 'active' : '' }}" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile"
                            type="button" role="tab" aria-controls="profile" aria-selected="{{ request()->get('tab') == 'profile' || !request()->has('tab') ? 'true' : 'false' }}">
                            My Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request()->get('tab') == 'security' ? 'active' : '' }}" id="security-tab" data-bs-toggle="tab" data-bs-target="#security"
                            type="button" role="tab" aria-controls="security" aria-selected="{{ request()->get('tab') == 'security' ? 'true' : 'false' }}">
                            Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ request()->get('tab') == 'orders' ? 'active' : '' }}" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button"
                            role="tab" aria-controls="orders" aria-selected="{{ request()->get('tab') == 'orders' ? 'true' : 'false' }}">
                            My Orders
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="card-body-simple">
                <div class="tab-content" id="profileTabContent">
                    <!-- My Profile Tab -->
                    <div class="tab-pane fade {{ request()->get('tab') == 'profile' || !request()->has('tab') ? 'show active' : '' }}" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <form class="row g-4" action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="col-lg-6">
                                <div class="form-section">
                                    <h6 class="form-section-title">Personal Information</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="firstName" class="form-label-simple">First Name</label>
                                                <input type="text" class="form-control-simple" id="firstName" name="first_name" value="{{ auth()->user()->first_name }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="lastName" class="form-label-simple">Last Name</label>
                                                <input type="text" class="form-control-simple" id="lastName" name="last_name" value="{{ auth()->user()->last_name }}">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="email" class="form-label-simple">Email Address</label>
                                                <input type="email" class="form-control-simple" id="email" name="email" value="{{ auth()->user()->email }}">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="phone" class="form-label-simple">Phone Number</label>
                                                <input type="tel" class="form-control-simple" id="phone" name="phone" value="{{ optional(auth()->user()->customer)->phone }}" placeholder="+1 (555) 123-4567">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-section">
                                    <h6 class="form-section-title">Address Information</h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="street" class="form-label-simple">Street Address</label>
                                                <input type="text" class="form-control-simple" id="street" name="address_1" value="{{ optional(auth()->user()->customer)->address_1 }}" placeholder="123 Main Street">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="address_2" class="form-label-simple">Address Line 2</label>
                                                <input type="text" class="form-control-simple" id="address_2" name="address_2" value="{{ optional(auth()->user()->customer)->address_2 }}" placeholder="Apartment, suite, unit, etc.">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="city" class="form-label-simple">City</label>
                                                <input type="text" class="form-control-simple" id="city" name="city" value="{{ optional(auth()->user()->customer)->city }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="state" class="form-label-simple">State/Province</label>
                                                <input type="text" class="form-control-simple" id="state" name="state" value="{{ optional(auth()->user()->customer)->state }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="zip" class="form-label-simple">ZIP/Postal Code</label>
                                                <input type="text" class="form-control-simple" id="zip" name="zip_code" value="{{ optional(auth()->user()->customer)->zip_code }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="country" class="form-label-simple">Country</label>
                                                <input type="text" class="form-control-simple" id="country" name="country" value="{{ optional(auth()->user()->customer)->country }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 text-center pt-4">
                                <button type="submit" class="btn-simple">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-pane fade {{ request()->get('tab') == 'security' ? 'show active' : '' }}" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <h6 class="mb-3">Change Password</h6>
                                <form class="row g-3" action="{{ route('password.store') }}" method="POST">
                                    @csrf
                                    <div class="col-12">
                                        <label for="currentPassword" class="form-label-simple">Current Password</label>
                                        <input type="password" class="form-control-simple" id="currentPassword" name="current_password" required autocomplete="current-password">
                                    </div>
                                    <div class="col-12">
                                        <label for="newPassword" class="form-label-simple">New Password</label>
                                        <input type="password" class="form-control-simple" id="newPassword" name="password" required autocomplete="new-password">
                                    </div>
                                    <div class="col-12">
                                        <label for="confirmPassword" class="form-label-simple">Confirm New Password</label>
                                        <input type="password" class="form-control-simple" id="confirmPassword" name="password_confirmation" required autocomplete="new-password">
                                    </div>
                                    <div class="col-12 text-center pt-2">
                                        <button type="submit" class="btn-simple">
                                            Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- My Orders Tab -->
                    <div class="tab-pane fade {{ request()->get('tab') == 'orders' ? 'show active' : '' }}" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Order History</h6>
                            <select class="form-control-simple w-auto">
                                <option>All Orders</option>
                                <option>Completed</option>
                                <option>Pending</option>
                                <option>Cancelled</option>
                            </select>
                        </div>

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
                                <a href="{{ route('order.details', urlencode($order->order_number)) }}" class="btn-view btn-sm">
                                    View Details
                                </a>
                                <div class="d-flex gap-2">
                                    @if($order->status == 'pending')
                                    <form action="{{ route('order.cancel', $order->id) }}?tab=orders" method="POST" class="d-inline cancel-order-form">
                                        @csrf
                                        <button type="button" class="btn-cancel btn-sm btn-cancel-order" data-bs-toggle="modal" data-bs-target="#cancelOrderModal" data-order-id="{{$order->id}}">
                                            Cancel
                                        </button>
                                    </form>
                                    @endif
                                    
                                    @if(in_array($order->status, ['cancelled', 'pending']))
                                    <form action="{{ route('order.delete', $order->id) }}?tab=orders" method="POST" class="d-inline delete-order-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn-delete-strong btn-sm btn-delete-order" data-bs-toggle="modal" data-bs-target="#deleteOrderModal" data-order-id="{{$order->id}}" data-order-number="{{$order->order_number}}">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                            <div class="text-center py-4">
                                <h6 class="text-muted mb-2">No Orders Found</h6>
                                <p class="text-muted-simple">You haven't placed any orders yet.</p>
                                <a href="{{ route('ecommerce.home') }}" class="btn-simple">
                                    Start Shopping
                                </a>
                            </div>
                        @endforelse

                        @if($orders->count() > 0)
                        <div class="d-flex justify-content-center mt-3">
                            {{ $orders->appends(['tab' => 'orders'])->links('vendor.pagination.bootstrap-5') }}
                        </div>
                        @endif
                    </div>

                    
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-simple" data-bs-dismiss="modal">No, Keep Order</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmCancelOrderBtn">Yes, Cancel Order</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteOrderModalLabel">Delete Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-center">Are you sure you want to delete order <strong id="deleteOrderNumber"></strong>?</p>
                    <p class="text-center text-muted small">This action cannot be undone and will permanently remove the order from your history.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-simple" data-bs-dismiss="modal">No, Keep Order</button>
                    <button type="button" class="btn-delete" id="confirmDeleteOrderBtn">Yes, Delete Order</button>
                </div>
            </div>
        </div>
    </div>

@endsection

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing order buttons...');
            let formToSubmit = null;
            
            // Function to activate tab
            function activateTab(tabId) {
                // Remove active class from all tabs
                document.querySelectorAll('.nav-link').forEach(function(tab) {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });
                
                // Remove active class from all tab panes
                document.querySelectorAll('.tab-pane').forEach(function(pane) {
                    pane.classList.remove('show', 'active');
                });
                
                // Activate the specified tab
                const targetTab = document.getElementById(tabId + '-tab');
                const targetPane = document.getElementById(tabId);
                
                if (targetTab && targetPane) {
                    targetTab.classList.add('active');
                    targetTab.setAttribute('aria-selected', 'true');
                    targetPane.classList.add('show', 'active');
                }
            }
            
            // Handle tab parameter from URL - with delay to ensure DOM is ready
            setTimeout(function() {
                const urlParams = new URLSearchParams(window.location.search);
                const activeTab = urlParams.get('tab');
                
                if (activeTab) {
                    activateTab(activeTab);
                }
            }, 100);
            
            // Handle tab clicks to update URL
            document.querySelectorAll('.nav-link').forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    const tabId = this.getAttribute('data-bs-target').replace('#', '');
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.pushState({}, '', url);
                });
            });
            
            // Cancel Order Modal
            const cancelButtons = document.querySelectorAll('.btn-cancel-order');
            console.log('Found cancel buttons:', cancelButtons.length);
            
            cancelButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    console.log('Cancel button clicked');
                    e.preventDefault();
                    formToSubmit = this.closest('form');
                    console.log('Form to submit:', formToSubmit);
                });
            });
            
            const confirmCancelBtn = document.getElementById('confirmCancelOrderBtn');
            if (confirmCancelBtn) {
                confirmCancelBtn.addEventListener('click', function() {
                    console.log('Confirm cancel clicked');
                    if(formToSubmit) {
                        console.log('Submitting cancel form');
                        formToSubmit.submit();
                    }
                });
            }
            
            // Delete Order Modal
            const deleteButtons = document.querySelectorAll('.btn-delete-order');
            console.log('Found delete buttons:', deleteButtons.length);
            
            deleteButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    console.log('Delete button clicked');
                    e.preventDefault();
                    formToSubmit = this.closest('form');
                    const orderNumber = this.getAttribute('data-order-number');
                    const deleteOrderNumber = document.getElementById('deleteOrderNumber');
                    if (deleteOrderNumber) {
                        deleteOrderNumber.textContent = orderNumber;
                    }
                    console.log('Form to submit:', formToSubmit);
                });
            });
            
            const confirmDeleteBtn = document.getElementById('confirmDeleteOrderBtn');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    console.log('Confirm delete clicked');
                    if(formToSubmit) {
                        console.log('Submitting delete form');
                        formToSubmit.submit();
                    }
                });
            }

            // Ensure Bootstrap modals are appended to body to avoid clipping/z-index issues
            const cancelModal = document.getElementById('cancelOrderModal');
            const deleteModal = document.getElementById('deleteOrderModal');
            ['cancelOrderModal','deleteOrderModal'].forEach(id => {
                const modalEl = document.getElementById(id);
                if (!modalEl) return;
                modalEl.addEventListener('show.bs.modal', function (event) {
                    document.body.appendChild(modalEl);
                    // Make sure z-index is above any sticky headers
                    modalEl.style.zIndex = '2000';
                    const backdrops = document.getElementsByClassName('modal-backdrop');
                    if (backdrops.length) {
                        backdrops[backdrops.length - 1].style.zIndex = '1990';
                    }
                });
            });
        });
    </script>