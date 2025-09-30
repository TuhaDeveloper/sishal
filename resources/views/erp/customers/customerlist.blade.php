@extends('erp.master')

@section('title', 'Customer Management')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Customer List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Customer List</h2>
                    <p class="text-muted mb-0">Manage Customer information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button class="btn btn-outline-primary" id="addCustomerBtn">
                            <i class="fas fa-adjust me-2"></i>Add Customer
                        </button>
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-10">
                            <label class="form-label fw-medium">Search</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0"
                                    placeholder="Customer Name, Phone, Email..." name="search"
                                    value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary flex-fill" type="submit">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                                <a href="{{ route('customers.list') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stock Listing Table -->
        <div class="card border-0 shadow-sm m-2">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Customer List</h5>
                    <div class="text-muted">
                        <small>Total: {{ $customers->total() }} returns</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="stockTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="border-0">ID</th>
                                <th class="border-0">Name</th>
                                <th class="border-0">Email</th>
                                <th class="border-0 text-center">Phone</th>
                                <th class="border-0 text-center">Address</th>
                                <th class="border-0 text-center">Status</th>
                                <th class="border-0 text-center">Added By</th>
                                <th class="border-0">Action</th>
                            </tr>
                        </thead>
                        <tbody id="stockTableBody">
                            @forelse ($customers as $customer)
                                <tr>
                                    <td>
                                        <h6 class="mb-0 fw-medium">#{{ $customer->id ?? 'N/A' }}</h6>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $customer->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $customer->email }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $customer->phone }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium">{{ $customer->address_1 }}, {{$customer->city}},
                                            {{$customer->state}}, {{$customer->country}} {{$customer->zip_code}}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium">{{ $customer->addedBy->first_name ?? 'N/A' }}
                                            {{ $customer->addedBy->last_name ?? '' }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2" role="group">
                                            <a href="{{ route('customer.show', $customer->id) }}"
                                                class="btn btn-sm btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('customers.edit', $customer->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <form action="{{ route('customers.destroy',$customer->id) }}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Edit">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-2x mb-3"></i>
                                            <p class="mb-0">No Customers found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">
                        Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }}
                        Customers
                    </span>
                    {{ $customers->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addCustomerForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="customer_email" name="email">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="register_as_user"
                                name="register_as_user">
                            <label class="form-check-label" for="register_as_user">Also register as user</label>
                        </div>
                        <div id="userFields" style="display:none;">
                            <div class="mb-3">
                                <label for="user_password" class="form-label">Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="user_password" name="user_password"
                                    minlength="6">
                            </div>
                            <div class="mb-3">
                                <label for="user_password_confirmation" class="form-label">Confirm Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="user_password_confirmation"
                                    name="user_password_confirmation" minlength="6">
                            </div>
                        </div>
                        <div id="customerFormError" class="alert alert-danger d-none"></div>
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="customer_tax_number" class="form-label">Tax Number</label>
                            <input type="text" class="form-control" id="customer_tax_number" name="tax_number">
                        </div>
                        <div class="mb-3">
                            <label for="customer_address_1" class="form-label">Address 1</label>
                            <input type="text" class="form-control" id="customer_address_1" name="address_1">
                        </div>
                        <div class="mb-3">
                            <label for="customer_address_2" class="form-label">Address 2</label>
                            <input type="text" class="form-control" id="customer_address_2" name="address_2">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="customer_city" name="city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_state" class="form-label">State</label>
                                <input type="text" class="form-control" id="customer_state" name="state">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="customer_country" name="country">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_zip_code" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="customer_zip_code" name="zip_code">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-text">Add Customer</span>
                            <span class="btn-loading" style="display:none;"><i class="fas fa-spinner fa-spin"></i>
                                Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function () {
            // Show modal on button click
            $('#addCustomerBtn').on('click', function () {
                $('#addCustomerModal').modal('show');
            });

            // Show/hide user fields
            $('#register_as_user').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#userFields').slideDown();
                    $('#user_password, #user_password_confirmation').prop('required', true);
                } else {
                    $('#userFields').slideUp();
                    $('#user_password, #user_password_confirmation').prop('required', false);
                }
            });

            // Handle form submit
            $('#addCustomerForm').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find('button[type="submit"]');
                var errorBox = $('#customerFormError');
                errorBox.addClass('d-none').text('');
                btn.prop('disabled', true);
                form.find('.btn-text').hide();
                form.find('.btn-loading').show();

                $.ajax({
                    url: '{{ route('customers.store') }}',
                    method: 'POST',
                    data: form.serialize() + '&_token={{ csrf_token() }}',
                    success: function (res) {
                        // Success: reload or update list
                        location.reload();
                    },
                    error: function (xhr) {
                        let msg = 'An error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).map(function (arr) { return arr.join(' '); }).join(' ');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        errorBox.removeClass('d-none').text(msg);
                        btn.prop('disabled', false);
                        form.find('.btn-text').show();
                        form.find('.btn-loading').hide();
                    }
                });
            });

            // Reset modal on close
            $('#addCustomerModal').on('hidden.bs.modal', function () {
                $('#addCustomerForm')[0].reset();
                $('#customerFormError').addClass('d-none').text('');
                $('#addCustomerForm button[type="submit"]').prop('disabled', false);
                $('#addCustomerForm .btn-text').show();
                $('#addCustomerForm .btn-loading').hide();
            });
        });
    </script>
@endsection