@extends('erp.master')

@section('title', 'Stock Management')

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
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Supplier List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Supplier List</h2>
                    <p class="text-muted mb-0">Manage supplier information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stocksupplierModal">
                            <i class="fas fa-adjust me-2"></i>Add Supplier
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-10">
                                <label class="form-label fw-medium">Search Supplier</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" placeholder="Supplier name, phone..." name="search" value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary flex-fill" type="submit">
                                        <i class="fas fa-filter me-2"></i>Filter
                                    </button>
                                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stock supplier Listing Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Supplier List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="supplierTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Name</th>
                                    <th class="border-0">Phone</th>
                                    <th class="border-0">Email</th>
                                    <th class="border-0 text-center">Address</th>
                                    <th class="border-0">Balance</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($suppliers as $supplier)
                                    <tr>
                                        <td>{{ $supplier->name ?? '-' }}</td>
                                        <td>
                                            {{ $supplier->phone ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $supplier->email ?? '-' }}
                                        </td>
                                        <td class="text-center">{{ $supplier->address.', '.$supplier->city.', '.$supplier->country. ' '.$supplier->zip_code }}</td>
                                        <td>
                                            0 ৳
                                        </td>
                                        <td>
                                            <a href="{{ route('supplier.show', $supplier->id) }}" class="text-info me-2"><i class="fas fa-eye"></i></a>
                                            <a href="#" class="text-primary me-2 edit-supplier-btn"
                                               data-bs-toggle="modal" data-bs-target="#editSupplierModal"
                                               data-id="{{ $supplier->id ?? '' }}"
                                               data-name="{{ $supplier->name ?? '' }}"
                                               data-phone="{{ $supplier->phone ?? '' }}"
                                               data-email="{{ $supplier->email ?? '' }}"
                                               data-address="{{ $supplier->address ?? '' }}"
                                               data-city="{{ $supplier->city ?? '' }}"
                                               data-country="{{ $supplier->country ?? '' }}"
                                               data-zip_code="{{ $supplier->zip_code ?? '' }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="text-danger delete-supplier-btn" data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}" data-bs-toggle="modal" data-bs-target="#deleteSupplierModal"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                @empty   
                                <tr>
                                    <td colspan="6" class="text-center">No Supplier Found</td></tr> 
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $suppliers->firstItem() }} to {{ $suppliers->lastItem() }} of {{ $suppliers->total() }} suppliers
                        </span>
                        {{ $suppliers->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="stocksupplierModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('supplier.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control" name="country">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zip Code</label>
                        <input type="text" class="form-control" name="zip_code">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" id="editSupplierForm">
                @csrf
                @method('PATCH')
                <input type="hidden" name="id" id="editSupplierId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="editSupplierName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="editSupplierPhone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editSupplierEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" id="editSupplierAddress">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" id="editSupplierCity">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control" name="country" id="editSupplierCountry">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zip Code</label>
                        <input type="text" class="form-control" name="zip_code" id="editSupplierZipCode">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Supplier Modal -->
    <div class="modal fade" id="deleteSupplierModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" id="deleteSupplierForm">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Delete Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete supplier <strong id="deleteSupplierName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Use event delegation for dynamically loaded buttons
        $(document).on('click', '.edit-supplier-btn', function() {

            var btn = $(this);
            $('#editSupplierId').val(btn.data('id') || '');
            $('#editSupplierName').val(btn.data('name') || '');
            $('#editSupplierPhone').val(btn.data('phone') || '');
            $('#editSupplierEmail').val(btn.data('email') || '');
            $('#editSupplierAddress').val(btn.data('address') || '');
            $('#editSupplierCity').val(btn.data('city') || '');
            $('#editSupplierCountry').val(btn.data('country') || '');
            $('#editSupplierZipCode').val(btn.data('zip_code') || '');
            var action = "{{ route('supplier.update', ['id' => 'SUPPLIER_ID']) }}".replace('SUPPLIER_ID', btn.data('id'));
            $('#editSupplierForm').attr('action', action);
        });
        // Clear modal fields on close
        $('#editSupplierModal').on('hidden.bs.modal', function () {
            $(this).find('input').val('');
        });
        $(document).on('click', '.delete-supplier-btn', function() {
            var btn = $(this);
            var supplierId = btn.data('id');
            var supplierName = btn.data('name');
            $('#deleteSupplierName').text(supplierName);
            var action = "{{ route('supplier.delete', ['id' => 'SUPPLIER_ID']) }}".replace('SUPPLIER_ID', supplierId);
            $('#deleteSupplierForm').attr('action', action);
        });
    });
    </script>
@endsection    