@extends('erp.master')

@section('title', 'Bill Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Bill List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Bill List</h2>
                    <p class="text-muted mb-0">Manage billing information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="{{ route('bill.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-adjust me-2"></i>Add Bill
                        </a>
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
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Bill Number</label>
                                <input type="text" class="form-control" name="bill_number"
                                    value="{{ request('bill_number') }}" placeholder="Bill Number">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Bill Date</label>
                                <input type="date" class="form-control" name="bill_date"
                                    value="{{ request('bill_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Due Date</label>
                                <input type="date" class="form-control" name="due_date"
                                    value="{{ request('due_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Supplier</label>
                                <select name="supplier_id" id="supplier_id" class="form-select"></select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid
                                    </option>
                                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial
                                    </option>
                                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button class="btn btn-primary flex-fill" type="submit">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                                <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stock bill Listing Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Bill List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="billTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Bill No</th>
                                    <th class="border-0">Vendor</th>
                                    <th class="border-0">Bill Date</th>
                                    <th class="border-0">Due Date</th>
                                    <th class="border-0 text-center">Status</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bills as $bill)
                                    <tr>
                                        <td>{{ $bill->bill_number ?? '-' }}</td>
                                        <td>{{ $bill->vendor->name }}</td>
                                        <td>
                                            {{ $bill->bill_date ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $bill->due_date ?? '-' }}
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge
                                                @if ($bill->status == 'unpaid') bg-danger
                                                @elseif($bill->status == 'paid') bg-success
                                                @elseif($bill->status == 'partial') bg-warning text-dark
                                                @elseif($bill->status == 'cancelled' || $bill->status == 'rejected') bg-secondary
                                                @else bg-secondary @endif
                                            ">
                                                {{ ucfirst($bill->status) }}
                                            </span>
                                        </td>

                                        <td>
                                            <a href="{{ route('bill.show', $bill->id) }}" class="text-info me-2"><i
                                                    class="fas fa-eye"></i></a>
                                            <a href="{{ route('bill.edit',$bill->id) }}" class="text-primary me-2 edit-bill-btn">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('bill.delete', $bill->id) }}" method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Are you sure you want to delete this bill?');">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-link text-danger p-0 m-0 align-baseline"><i
                                                        class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No bill Found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $bills->firstItem() }} to {{ $bills->lastItem() }} of {{ $bills->total() }} bills
                        </span>
                        {{ $bills->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            padding: 6px 12px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            background-color: #fff;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 10px;
        }

        .select2-container--default .select2-selection--single:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25);
        }
    </style>
@endpush
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#supplier_id').select2({
                placeholder: 'Select Supplier',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route('supplier.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });
        });
    </script>
@endpush
