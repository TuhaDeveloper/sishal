@extends('ecommerce.master')

@section('main-section')
    <div class="container py-5">
        <!-- Profile Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="mb-1">{{$user->first_name}} {{$user->last_name}}</h2>
                        <p class="text-muted mb-2">{{$user->email}}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="card shadow-sm">
            <div class="card-header p-0">
                <ul class="nav nav-tabs nav-fill" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile"
                            type="button" role="tab" aria-controls="profile" aria-selected="true">
                            <i class="fas fa-user me-2"></i>My Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security"
                            type="button" role="tab" aria-controls="security" aria-selected="false">
                            <i class="fas fa-lock me-2"></i>Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button"
                            role="tab" aria-controls="orders" aria-selected="false">
                            <i class="fas fa-shopping-bag me-2"></i>My Orders
                        </button>
                    </li>
                    
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="card-body">
                <div class="tab-content" id="profileTabContent">
                    <!-- My Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <form class="row" action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="col-md-6">
                                <h5 class="mb-3">Personal Information</h5>
                                <div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="firstName" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="firstName" name="first_name" value="{{ auth()->user()->first_name }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lastName" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="lastName" name="last_name" value="{{ auth()->user()->last_name }}">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="{{ auth()->user()->email }}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="{{ optional(auth()->user()->customer)->phone }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">Address Information</h5>
                                <div>
                                    <div class="mb-3">
                                        <label for="street" class="form-label">Street Address</label>
                                        <input type="text" class="form-control" id="street" name="address_1" value="{{ optional(auth()->user()->customer)->address_1 }}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="address_2" class="form-label">Address 2</label>
                                        <input type="text" class="form-control" id="address_2" name="address_2" value="{{ optional(auth()->user()->customer)->address_2 }}">
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="city" class="form-label">City</label>
                                                <input type="text" class="form-control" id="city" name="city" value="{{ optional(auth()->user()->customer)->city }}">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="state" class="form-label">State</label>
                                                <input type="text" class="form-control" id="state" name="state" value="{{ optional(auth()->user()->customer)->state }}">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="zip" class="form-label">ZIP Code</label>
                                                <input type="text" class="form-control" id="zip" name="zip_code" value="{{ optional(auth()->user()->customer)->zip_code }}">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="country" class="form-label">Country</label>
                                                <input type="text" class="form-control" id="country" name="country" value="{{ optional(auth()->user()->customer)->country }}">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn mt-3"
                                        style="background-color: #0da2e7; border-color: #0da2e7; color: white;">
                                        Update Profile
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <div class="row">
                            <div class="col-lg-8">
                                <h5 class="mb-4">Security Settings</h5>

                                <!-- Change Password -->
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <h6 class="card-title">Change Password</h6>
                                        <form class="row g-3" action="{{ route('password.store') }}" method="POST">
                                            @csrf
                                            <div class="col-12">
                                                <label for="currentPassword" class="form-label">Current Password</label>
                                                <input type="password" class="form-control" id="currentPassword" name="current_password" required autocomplete="current-password">
                                            </div>
                                            <div class="col-12">
                                                <label for="newPassword" class="form-label">New Password</label>
                                                <input type="password" class="form-control" id="newPassword" name="password" required autocomplete="new-password">
                                            </div>
                                            <div class="col-12">
                                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" required autocomplete="new-password">
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn"
                                                    style="background-color: #0da2e7; border-color: #0da2e7; color: white;">
                                                    Update Password
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My Orders Tab -->
                    <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Order History</h5>
                            <select class="form-select w-auto">
                                <option>All Orders</option>
                                <option>Completed</option>
                                <option>Pending</option>
                                <option>Cancelled</option>
                            </select>
                        </div>

                        @forelse ($orders as $order)
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1">{{$order->order_number}}</h6>
                                        <small class="text-muted">Placed on {{$order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('M d, Y') : '-'}}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="h6 mb-1">{{$order->total}}৳</div>
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
                                </div>
                                <div class="row">
                                    @foreach ($order->items as $item)
                                    <div class="col-md-4 row align-items-center mb-3">
                                        <div class="col-auto">
                                            <img src="{{ asset($item->product->image) }}" alt="Product" class="rounded"
                                                style="width: 60px; height: 60px; object-fit: cover;">
                                        </div>
                                        <div class="col">
                                            <a href={{ route('product.details',@$item->product->slug) }} class="d-block fw-semibold text-black" style="text-decoration: none;">{{$item->product->name}}</a>
                                            <small class="text-muted">Qty: {{number_format($item->quantity,0)}}</small>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <a href="{{ route('order.details',$order->order_number) }}" class="btn btn-link p-0" style="color: #0da2e7;">View Details</a>
                                    @if($order->status != 'pending' && $order->status != 'cancelled')
                                    <button class="btn btn-sm"
                                        style="background-color: #0da2e7; border-color: #0da2e7; color: white;">
                                        Reorder
                                    </button>
                                    @elseif($order->status != 'cancelled')
                                    <form action="{{ route('order.cancel', $order->id) }}" method="POST" class="d-inline cancel-order-form">
                                        @csrf
                                        <button type="button" class="btn btn-outline-secondary btn-cancel-order" data-bs-toggle="modal" data-bs-target="#cancelOrderModal" data-order-id="{{$order->id}}">
                                            Cancel
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                            <p class="text-center">No Orders Found</p>
                        @endforelse

                        <div class="d-flex justify-content-center mt-4">
                            {{ $orders->links('vendor.pagination.bootstrap-5') }}
                        </div>
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
                    Are you sure you want to cancel this order?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Order</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelOrderBtn">Yes, Cancel Order</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
        }

        .nav-tabs .nav-link:hover {
            color: #0da2e7;
            border-color: transparent;
        }

        .nav-tabs .nav-link.active {
            color: #0da2e7;
            background-color: transparent;
            border-bottom: 3px solid #0da2e7;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0da2e7;
            box-shadow: 0 0 0 0.25rem rgba(13, 162, 231, 0.25);
        }

        .btn-outline {
            border: 1px solid;
        }

        .btn-outline:hover {
            background-color: #0da2e7;
            border-color: #0da2e7;
            color: white !important;
        }
    </style>
@endsection

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let formToSubmit = null;
            document.querySelectorAll('.btn-cancel-order').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    formToSubmit = this.closest('form');
                });
            });
            document.getElementById('confirmCancelOrderBtn').addEventListener('click', function() {
                if(formToSubmit) {
                    formToSubmit.submit();
                }
            });
        });
    </script>