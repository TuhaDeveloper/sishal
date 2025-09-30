@extends('erp.master')

@section('title', 'Supplier Details')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h4 class="mb-0">Supplier Details</h4>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Name</dt>
                                <dd class="col-sm-8">{{ $supplier->name ?? '-' }}</dd>
                                <dt class="col-sm-4">Phone</dt>
                                <dd class="col-sm-8">{{ $supplier->phone ?? '-' }}</dd>
                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8">{{ $supplier->email ?? '-' }}</dd>
                                <dt class="col-sm-4">Address</dt>
                                <dd class="col-sm-8">{{ implode(', ', array_filter([$supplier->address, $supplier->city, $supplier->country, $supplier->zip_code])) ?: '-' }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">Bills</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Bill Number</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2024-07-01</td>
                                            <td>INV-1001</td>
                                            <td>5,000 ৳</td>
                                            <td><span class="badge bg-success">Paid</span></td>
                                        </tr>
                                        <tr>
                                            <td>2024-06-15</td>
                                            <td>INV-0998</td>
                                            <td>2,500 ৳</td>
                                            <td><span class="badge bg-warning">Pending</span></td>
                                        </tr>
                                        <tr>
                                            <td>2024-06-01</td>
                                            <td>INV-0990</td>
                                            <td>3,200 ৳</td>
                                            <td><span class="badge bg-danger">Overdue</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection