@extends('erp.master')

@section('title', 'Pay-on-Sale (Consignment) Settlement')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content" id="mainContent">
        @include('erp.components.header')
        
        <div class="glass-header">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 breadcrumb-premium">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('supplier-payments.index') }}" class="text-decoration-none text-muted">Supplier Payments</a></li>
                            <li class="breadcrumb-item active text-primary fw-600">Pay-on-Sale Settlement</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm bg-primary text-white d-flex align-items-center justify-content-center rounded-circle fw-bold">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark">Pay-on-Sale (Consignment) Settlement</h4>
                            <p class="text-muted small mb-0">Pay suppliers exclusively for goods that have been sold (Sold Qty × Unit Cost)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
                    <a href="{{ route('supplier-payments.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fas fa-history me-1"></i>All Payments
                    </a>
                    <a href="{{ route('supplier-payments.create') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-plus-circle me-1"></i>Bill Payment
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4 fw-bold rounded-3">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 fw-bold rounded-3">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                </div>
            @endif

            <!-- Filter & Supplier Selection Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <form method="GET" action="{{ route('supplier-payments.pay-on-sale') }}" id="payOnSaleFilterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Select Supplier <span class="text-danger">*</span></label>
                                <select class="form-select select2-supplier" name="supplier_id" id="supplierSelect" required>
                                    <option value="">-- Choose Supplier --</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}" {{ $supplierId == $sup->id ? 'selected' : '' }}>
                                            {{ $sup->name }} {{ $sup->phone ? '('.$sup->phone.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">From Date</label>
                                <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">To Date</label>
                                <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="fas fa-filter"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($supplierId)
                <!-- Metric Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white h-100">
                            <div class="small text-muted fw-bold text-uppercase">Purchased Qty</div>
                            <div class="fs-4 fw-bold text-primary mt-1" id="sumPurchasedQty">{{ number_format($summary['totals']['purchased_qty']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white h-100">
                            <div class="small text-muted fw-bold text-uppercase">Sold Qty</div>
                            <div class="fs-4 fw-bold text-success mt-1" id="sumSoldQty">{{ number_format($summary['totals']['sold_qty']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white h-100">
                            <div class="small text-muted fw-bold text-uppercase">In-Stock Qty</div>
                            <div class="fs-4 fw-bold text-warning mt-1" id="sumInStockQty">{{ number_format($summary['totals']['in_stock_qty']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white h-100">
                            <div class="small text-muted fw-bold text-uppercase">Total Sold Cost</div>
                            <div class="fs-4 fw-bold text-dark mt-1">৳<span id="sumSoldCost">{{ number_format($summary['totals']['sold_cost_payable'], 2) }}</span></div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white h-100">
                            <div class="small text-muted fw-bold text-uppercase">Already Paid</div>
                            <div class="fs-4 fw-bold text-info mt-1">৳<span id="sumTotalPaid">{{ number_format($summary['totals']['total_paid'], 2) }}</span></div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-success bg-opacity-10 border border-success border-opacity-25 h-100">
                            <div class="small text-success fw-bold text-uppercase">Net Due Payable</div>
                            <div class="fs-4 fw-bold text-success mt-1">৳<span id="sumNetDue">{{ number_format($summary['totals']['net_due_payable'], 2) }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Products Breakdown Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-list me-2 text-primary"></i>Supplied Products & Sales Settlement Status</h6>
                            <div class="extra-small text-muted mt-1">Select items below to calculate partial settlement payment</div>
                        </div>
                        @can('create payments')
                            <button type="button" class="btn btn-success btn-sm px-4 rounded-pill shadow-sm fw-bold no-loader" id="settleModalBtn" data-bs-toggle="modal" data-bs-target="#payOnSaleModal" {{ $summary['totals']['net_due_payable'] <= 0 ? 'disabled' : '' }}>
                                <i class="fas fa-check-double me-2"></i>Settle Selected (<span id="settleBtnLabel">৳{{ number_format($summary['totals']['net_due_payable'], 2) }}</span>)
                            </button>
                        @endcan
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="ps-3 py-3" width="4%">
                                            <input type="checkbox" class="form-check-input" id="selectAllItems" checked title="Select/Deselect All Items">
                                        </th>
                                        <th class="small text-muted text-uppercase py-3" width="4%">#</th>
                                        <th class="small text-muted text-uppercase py-3">Product Name</th>
                                        <th class="small text-muted text-uppercase py-3">SKU / Style</th>
                                        <th class="text-center small text-muted text-uppercase py-3">Purchased</th>
                                        <th class="text-center small text-muted text-uppercase py-3">Sold Qty</th>
                                        <th class="text-center small text-muted text-uppercase py-3">In-Stock</th>
                                        <th class="text-end small text-muted text-uppercase py-3">Unit Cost (৳)</th>
                                        <th class="text-end small text-muted text-uppercase py-3">Sold Cost (৳)</th>
                                        <th class="text-end pe-3 small text-muted text-uppercase py-3">Net Due (৳)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($summary['items'] as $index => $item)
                                        <tr class="{{ ($item['net_due'] ?? $item['sold_cost_payable']) <= 0 ? 'bg-light bg-opacity-25 opacity-75' : '' }}">
                                            <td class="ps-3">
                                                <input type="checkbox" class="form-check-input item-checkbox" data-name="{{ $item['product_name'] }}" data-payable="{{ $item['net_due'] ?? $item['sold_cost_payable'] }}" {{ ($item['net_due'] ?? $item['sold_cost_payable']) > 0 ? 'checked' : 'disabled' }}>
                                            </td>
                                            <td><span class="badge bg-light text-dark border">{{ $loop->iteration }}</span></td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $item['product_name'] }}</div>
                                                @if(($item['net_due'] ?? 0) <= 0 && $item['sold_cost_payable'] > 0)
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 extra-small mt-1"><i class="fas fa-check-circle me-1"></i>Fully Settled</span>
                                                @elseif($item['sold_cost_payable'] <= 0)
                                                    <span class="badge bg-light text-muted border extra-small mt-1">No Sales Yet</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="small text-muted">SKU: {{ $item['sku'] }}</div>
                                                <div class="extra-small text-muted">Style: {{ $item['style_number'] }}</div>
                                            </td>
                                            <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3">{{ number_format($item['purchased_qty']) }}</span></td>
                                            <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">{{ number_format($item['sold_qty']) }}</span></td>
                                            <td class="text-center"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">{{ number_format($item['in_stock_qty']) }}</span></td>
                                            <td class="text-end fw-bold">৳{{ number_format($item['unit_cost'], 2) }}</td>
                                            <td class="text-end fw-bold text-dark">৳{{ number_format($item['sold_cost_payable'], 2) }}</td>
                                            <td class="text-end pe-3 fw-bold {{ ($item['net_due'] ?? 0) > 0 ? 'text-success' : 'text-muted' }}">৳{{ number_format($item['net_due'] ?? 0, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-5 text-muted">
                                                <i class="fas fa-info-circle fa-2x mb-2 text-muted opacity-50 d-block"></i>
                                                No purchase or sales data found for the selected supplier.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Settlement Payment Modal -->
                @can('create payments')
                    <div class="modal fade" id="payOnSaleModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <div class="modal-header bg-primary text-white border-0 py-3">
                                    <h6 class="modal-title fw-bold"><i class="fas fa-money-check-alt me-2"></i>Process Pay-on-Sale Settlement</h6>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="{{ route('supplier-payments.store-pay-on-sale') }}">
                                    @csrf
                                    <input type="hidden" name="supplier_id" value="{{ $supplierId }}">
                                    
                                    <div class="modal-body p-4">
                                        <div class="alert alert-info border-0 rounded-3 mb-3">
                                            <div class="small fw-bold"><i class="fas fa-calculator me-1"></i>Pay-on-Sale Summary</div>
                                            <div class="d-flex justify-content-between mt-1 extra-small">
                                                <span>Selected Items Sold Cost: <strong id="modalSelectedCostText">৳{{ number_format($summary['totals']['sold_cost_payable'], 2) }}</strong></span>
                                                <span>Already Paid: <strong>৳{{ number_format($summary['totals']['total_paid'], 2) }}</strong></span>
                                            </div>
                                            <div class="fw-bold text-success mt-1">
                                                Net Due for Selected Items: <span id="modalNetDueText">৳{{ number_format($summary['totals']['net_due_payable'], 2) }}</span>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Settlement Payment Amount (৳) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">৳</span>
                                                <input type="number" step="0.01" class="form-control fw-bold fs-5 text-success" name="amount" id="settleModalAmountInput" value="{{ $summary['totals']['net_due_payable'] }}" max="{{ $summary['totals']['net_due_payable'] }}" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Payment Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Payment Account <span class="text-danger">*</span></label>
                                            <select class="form-select" name="account_id" required>
                                                <option value="">-- Choose Account --</option>
                                                @foreach($accounts as $acc)
                                                    <option value="{{ $acc->id }}">{{ $acc->account_name ?? $acc->provider_name }} (Bal: ৳{{ number_format($acc->balance, 2) }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Payment Method <span class="text-danger">*</span></label>
                                            <select class="form-select" name="payment_method" required>
                                                <option value="cash">Cash</option>
                                                <option value="bank_transfer">Bank Transfer</option>
                                                <option value="check">Check</option>
                                                <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Reference / Transaction ID</label>
                                            <input type="text" class="form-control" name="reference" placeholder="Check no, Txn ID, etc.">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Note / Remarks</label>
                                            <textarea class="form-control" name="note" rows="2" placeholder="Pay-on-sale settlement for sold goods..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light border-0 py-3">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold"><i class="fas fa-check-circle me-1"></i>Confirm Settlement Payment</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endcan

            @else
                <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                    <div class="card-body py-5">
                        <i class="fas fa-truck-loading fa-3x text-muted opacity-50 mb-3"></i>
                        <h5 class="fw-bold text-dark mb-1">Select a Supplier to Begin Pay-on-Sale Settlement</h5>
                        <p class="text-muted mb-0">Choose a supplier above to view total purchased quantity, sold quantity, and calculate sold item payable amounts.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.select2-supplier').select2({
                    width: '100%',
                    dropdownParent: $('body')
                });

                function updateSelectedPayableSummary() {
                    let totalSelectedCost = 0;
                    let selectedCount = 0;

                    $('.item-checkbox:checked').each(function() {
                        totalSelectedCost += parseFloat($(this).data('payable')) || 0;
                        selectedCount++;
                    });

                    const overallNetDue = parseFloat("{{ $summary['totals']['net_due_payable'] ?? 0 }}") || 0;
                    let netDueForSelected = totalSelectedCost;
                    if (overallNetDue > 0 && totalSelectedCost > 0) {
                        netDueForSelected = Math.min(totalSelectedCost, overallNetDue);
                    }
                    netDueForSelected = Math.round(netDueForSelected * 100) / 100;

                    $('#settleBtnLabel').text('৳' + netDueForSelected.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#settleModalAmountInput').val(netDueForSelected);
                    $('#modalSelectedCostText').text('৳' + totalSelectedCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#modalNetDueText').text('৳' + netDueForSelected.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                    if (netDueForSelected <= 0 || selectedCount === 0) {
                        $('#settleModalBtn').prop('disabled', true);
                    } else {
                        $('#settleModalBtn').prop('disabled', false);
                    }
                }

                $('#selectAllItems').on('change', function() {
                    $('.item-checkbox:not(:disabled)').prop('checked', $(this).is(':checked'));
                    updateSelectedPayableSummary();
                });

                $(document).on('change', '.item-checkbox', function() {
                    const activeCount = $('.item-checkbox:not(:disabled)').length;
                    const checkedCount = $('.item-checkbox:checked').length;
                    $('#selectAllItems').prop('checked', activeCount > 0 && activeCount === checkedCount);
                    updateSelectedPayableSummary();
                });

                // Initial calculation run
                updateSelectedPayableSummary();
            });
        </script>
    @endpush
@endsection
