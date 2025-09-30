@extends('erp.master')

@section('title', 'Sale Management')

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
                            <li class="breadcrumb-item active" aria-current="page">Sale List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Sale List</h2>
                    <p class="text-muted mb-0">Manage sale information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        @can('make sale')
                        <a href="{{ route('pos.add') }}" class="btn btn-outline-primary">
                            <i class="fas fa-adjust me-2"></i>Add Sale
                        </a>
                        @endcan
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            <div class="mb-3">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search (ID, Name, Phone, Email)</label>
                        <input type="text" name="search" class="form-control" placeholder="Sale ID or Customer's Name, Phone, Email" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="shipping">Shipping</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estimated Date</label>
                        <input type="date" name="estimated_delivery_date" class="form-control" value="{{ request('estimated_delivery_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bill Status</label>
                        <select name="bill_status" class="form-select">
                            <option value="">All</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('pos.list') }}" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Stock sale Listing Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Sale List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="saleTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">POS ID</th>
                                    <th class="border-0">Due Date</th>
                                    <th class="border-0">Customer</th>
                                    <th class="border-0">Phone</th>
                                    <th class="border-0">Branch</th>
                                    <th class="border-0 text-center">Status</th>
                                    <th class="border-0">Bill Status</th>
                                    <th class="border-0">Subtotal</th>
                                    <th class="border-0">Discount</th>
                                    <th class="border-0">Total</th>
                                    <th class="border-0">Paid</th>
                                    <th class="border-0">Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    <tr>
                                        <td><a href="{{ route('pos.show',$sale->id) }}" class="btn btn-outline-primary">{{ $sale->sale_number ?? '-' }}</a></td>
                                        <td>
                                            {{ $sale->estimated_delivery_date ? \Carbon\Carbon::parse($sale->estimated_delivery_date)->format('d-m-Y') : '-' }}
                                        </td>
                                        <td>{{@$sale->customer->name ?? 'Walk-in-Customer'}}</td>
                                        <td>{{@$sale->customer->phone}}</td>
                                        <td>{{@$sale->branch->name}}</td>
                                        <td class="text-center">
                                            <span class="badge 
                                                @if($sale->status == 'pending') bg-warning text-dark
                                                @elseif($sale->status == 'approved' || $sale->status == 'paid') bg-success
                                                @elseif($sale->status == 'unpaid' || $sale->status == 'rejected') bg-danger
                                                @else bg-secondary
                                                @endif
                                                update-status-btn"
                                                style="cursor:pointer;"
                                                data-id="{{ $sale->id }}"
                                                data-status="{{ $sale->status }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#updateStatusModal"
                                            >
                                                {{ ucfirst($sale->status ?? '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($sale->invoice && $sale->invoice->status == 'unpaid') bg-danger
                                                @elseif($sale->invoice && $sale->invoice->status == 'paid') bg-success
                                                @elseif($sale->invoice && $sale->invoice->status == 'pending') bg-warning text-dark
                                                @else bg-secondary
                                                @endif
                                            ">
                                                {{ ucfirst($sale->invoice->status ?? '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $sale->sub_total }}৳
                                        </td>
                                        <td>
                                            {{ $sale->discount }}৳
                                        </td>
                                        <td>
                                            {{ $sale->total_amount }}৳
                                        </td>
                                        <td>
                                            {{ @$sale->invoice->paid_amount }}৳
                                        </td>
                                        <td>
                                            {{ @$sale->invoice->due_amount }}৳
                                        </td>
                                    </tr>
                                @empty   
                                <tr>
                                    <td colspan="12" class="text-center">No sale Found</td></tr> 
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $sales->firstItem() }} to {{ $sales->lastItem() }} of {{ $sales->total() }} sales
                        </span>
                        {{ $sales->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Modal -->
        <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reportModalLabel">Sales Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Report Filters -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" id="dateFrom" name="date_from">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" id="dateTo" name="date_to">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="statusFilter" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="shipping">Shipping</option>
                                    <option value="received">Received</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Status</label>
                                <select class="form-select" id="paymentStatusFilter" name="payment_status">
                                    <option value="">All Payment Status</option>
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                    <option value="partial">Partial</option>
                                </select>
                            </div>
                        </div>

                        <!-- Column Selection -->
                        <div class="mb-4">
                            <h6>Select Columns to Include:</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="pos_id" id="col_pos_id" checked>
                                        <label class="form-check-label" for="col_pos_id">POS ID</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="sale_date" id="col_sale_date" checked>
                                        <label class="form-check-label" for="col_sale_date">Sale Date</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="customer" id="col_customer" checked>
                                        <label class="form-check-label" for="col_customer">Customer</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="phone" id="col_phone" checked>
                                        <label class="form-check-label" for="col_phone">Phone</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="branch" id="col_branch" checked>
                                        <label class="form-check-label" for="col_branch">Branch</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="status" id="col_status" checked>
                                        <label class="form-check-label" for="col_status">Status</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="payment_status" id="col_payment_status" checked>
                                        <label class="form-check-label" for="col_payment_status">Payment Status</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="subtotal" id="col_subtotal" checked>
                                        <label class="form-check-label" for="col_subtotal">Subtotal</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="discount" id="col_discount" checked>
                                        <label class="form-check-label" for="col_discount">Discount</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="total" id="col_total" checked>
                                        <label class="form-check-label" for="col_total">Total</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="paid_amount" id="col_paid_amount" checked>
                                        <label class="form-check-label" for="col_paid_amount">Paid Amount</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="due_amount" id="col_due_amount" checked>
                                        <label class="form-check-label" for="col_due_amount">Due Amount</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllColumns">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllColumns">Deselect All</button>
                            </div>
                        </div>

                        <!-- Summary Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Sales</h5>
                                        <h3 id="totalSales">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Amount</h5>
                                        <h3 id="totalAmount">৳0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Paid Sales</h5>
                                        <h3 id="paidSales">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Unpaid Sales</h5>
                                        <h3 id="unpaidSales">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Preview -->
                        <div class="mb-4">
                            <h6>Report Preview:</h6>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-bordered" id="reportPreviewTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="col-pos-id">POS ID</th>
                                            <th class="col-sale-date">Sale Date</th>
                                            <th class="col-customer">Customer</th>
                                            <th class="col-phone">Phone</th>
                                            <th class="col-branch">Branch</th>
                                            <th class="col-status">Status</th>
                                            <th class="col-payment-status">Payment Status</th>
                                            <th class="col-subtotal">Subtotal</th>
                                            <th class="col-discount">Discount</th>
                                            <th class="col-total">Total</th>
                                            <th class="col-paid-amount">Paid Amount</th>
                                            <th class="col-due-amount">Due Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportPreviewBody">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Export Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success" id="exportExcel">
                                <i class="fas fa-file-excel me-2"></i>Export to Excel
                            </button>
                            <button type="button" class="btn btn-danger" id="exportPdf">
                                <i class="fas fa-file-pdf me-2"></i>Export to PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    document.getElementById('dateFrom').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('dateTo').value = today.toISOString().split('T')[0];

    // Load initial report data
    loadReportData();

    // Event listeners for filters
    document.getElementById('dateFrom').addEventListener('change', loadReportData);
    document.getElementById('dateTo').addEventListener('change', loadReportData);
    document.getElementById('statusFilter').addEventListener('change', loadReportData);
    document.getElementById('paymentStatusFilter').addEventListener('change', loadReportData);

    // Column selection
    document.getElementById('selectAllColumns').addEventListener('click', function() {
        document.querySelectorAll('.column-selector').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateColumnVisibility();
    });

    document.getElementById('deselectAllColumns').addEventListener('click', function() {
        document.querySelectorAll('.column-selector').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateColumnVisibility();
    });

    document.querySelectorAll('.column-selector').forEach(checkbox => {
        checkbox.addEventListener('change', updateColumnVisibility);
    });

    // Export buttons
    document.getElementById('exportExcel').addEventListener('click', exportToExcel);
    document.getElementById('exportPdf').addEventListener('click', exportToPdf);

    function loadReportData() {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const status = document.getElementById('statusFilter').value;
        const paymentStatus = document.getElementById('paymentStatusFilter').value;

        // Show loading
        document.getElementById('reportPreviewBody').innerHTML = '<tr><td colspan="12" class="text-center">Loading...</td></tr>';

        fetch(`/erp/pos/report-data?date_from=${dateFrom}&date_to=${dateTo}&status=${status}&payment_status=${paymentStatus}`)
            .then(response => response.json())
            .then(data => {
                updateReportPreview(data.sales);
                updateSummaryStats(data.summary);
            })
            .catch(error => {
                console.error('Error loading report data:', error);
                document.getElementById('reportPreviewBody').innerHTML = '<tr><td colspan="12" class="text-center text-danger">Error loading data</td></tr>';
            });
    }

    function updateReportPreview(sales) {
        const tbody = document.getElementById('reportPreviewBody');
        tbody.innerHTML = '';

        if (sales.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" class="text-center">No data found</td></tr>';
            return;
        }

        sales.forEach(sale => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="col-pos-id">${sale.sale_number || '-'}</td>
                <td class="col-sale-date">${sale.sale_date || '-'}</td>
                <td class="col-customer">${sale.customer_name || 'Walk-in Customer'}</td>
                <td class="col-phone">${sale.customer_phone || '-'}</td>
                <td class="col-branch">${sale.branch_name || '-'}</td>
                <td class="col-status"><span class="badge bg-${getStatusBadgeColor(sale.status)}">${sale.status || '-'}</span></td>
                <td class="col-payment-status"><span class="badge bg-${getPaymentStatusBadgeColor(sale.payment_status)}">${sale.payment_status || '-'}</span></td>
                <td class="col-subtotal">৳${sale.sub_total || '0'}</td>
                <td class="col-discount">৳${sale.discount || '0'}</td>
                <td class="col-total">৳${sale.total_amount || '0'}</td>
                <td class="col-paid-amount">৳${sale.paid_amount || '0'}</td>
                <td class="col-due-amount">৳${sale.due_amount || '0'}</td>
            `;
            tbody.appendChild(row);
        });
    }

    function updateSummaryStats(summary) {
        document.getElementById('totalSales').textContent = summary.total_sales || 0;
        document.getElementById('totalAmount').textContent = '৳' + (summary.total_amount || 0);
        document.getElementById('paidSales').textContent = summary.paid_sales || 0;
        document.getElementById('unpaidSales').textContent = summary.unpaid_sales || 0;
    }

    function updateColumnVisibility() {
        const columns = {
            'pos_id': 'col-pos-id',
            'sale_date': 'col-sale-date',
            'customer': 'col-customer',
            'phone': 'col-phone',
            'branch': 'col-branch',
            'status': 'col-status',
            'payment_status': 'col-payment-status',
            'subtotal': 'col-subtotal',
            'discount': 'col-discount',
            'total': 'col-total',
            'paid_amount': 'col-paid-amount',
            'due_amount': 'col-due-amount'
        };

        Object.keys(columns).forEach(key => {
            const checkbox = document.getElementById('col_' + key);
            const columnClass = columns[key];
            const elements = document.querySelectorAll('.' + columnClass);
            
            elements.forEach(element => {
                element.style.display = checkbox.checked ? '' : 'none';
            });
        });
    }

    function getStatusBadgeColor(status) {
        switch(status) {
            case 'pending': return 'warning';
            case 'approved': return 'success';
            case 'shipping': return 'info';
            case 'received': return 'success';
            case 'cancelled': return 'danger';
            default: return 'secondary';
        }
    }

    function getPaymentStatusBadgeColor(status) {
        switch(status) {
            case 'paid': return 'success';
            case 'unpaid': return 'danger';
            case 'partial': return 'warning';
            default: return 'secondary';
        }
    }

    function exportToExcel() {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const status = document.getElementById('statusFilter').value;
        const paymentStatus = document.getElementById('paymentStatusFilter').value;
        const selectedColumns = Array.from(document.querySelectorAll('.column-selector:checked')).map(cb => cb.value);

        if (selectedColumns.length === 0) {
            alert('Please select at least one column to export.');
            return;
        }

        const url = `/erp/pos/export-excel?date_from=${dateFrom}&date_to=${dateTo}&status=${status}&payment_status=${paymentStatus}&columns=${selectedColumns.join(',')}`;
        
        // Show loading state
        const btn = document.getElementById('exportExcel');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating Excel...';
        btn.disabled = true;
        
        // Use fetch to handle potential errors
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Export failed');
                    });
                }
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'sales_report_' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.xlsx';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                alert('Export failed: ' + error.message);
            })
            .finally(() => {
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }

    function exportToPdf() {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const status = document.getElementById('statusFilter').value;
        const paymentStatus = document.getElementById('paymentStatusFilter').value;
        const selectedColumns = Array.from(document.querySelectorAll('.column-selector:checked')).map(cb => cb.value);

        if (selectedColumns.length === 0) {
            alert('Please select at least one column to export.');
            return;
        }

        const url = `/erp/pos/export-pdf?date_from=${dateFrom}&date_to=${dateTo}&status=${status}&payment_status=${paymentStatus}&columns=${selectedColumns.join(',')}`;
        
        // Show loading state
        const btn = document.getElementById('exportPdf');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDF...';
        btn.disabled = true;
        
        // Use fetch to handle potential errors
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Export failed');
                    });
                }
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'sales_report_' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.pdf';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                alert('Export failed: ' + error.message);
            })
            .finally(() => {
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }
});
</script>
@endpush