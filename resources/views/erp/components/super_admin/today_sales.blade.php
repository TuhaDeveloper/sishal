<!-- 1. Today's Sales — Branch Wise Component -->
<div class="col-lg-7">
    <div class="sa-card h-100 mb-0" id="cardTodaySales">
        <div class="sa-card-overlay"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="sa-card-header">
            <h2 class="sa-card-title">
                <i class="fas fa-store sa-icon-sales"></i>
                Today's Sales — Branch Wise
            </h2>
            <div class="dropdown">
                <button id="branchFilterLabel" class="btn btn-sm btn-light border dropdown-toggle fw-semibold text-secondary" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-filter me-1"></i> {{ $selectedBranchId === 'all' ? 'All Branches' : 'Selected Branch' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item fw-medium" href="#" onclick="selectBranchFilter('all'); return false;">All Branches</a></li>
                    @foreach($branches as $b)
                        <li><a class="dropdown-item" href="#" onclick="selectBranchFilter('{{ $b->id }}'); return false;">{{ $b->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="table-container-scroll">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Branch Name</th>
                        <th class="text-center">Today's Qty</th>
                        <th class="text-end">Today's Sales</th>
                        <th class="text-center">Month Qty</th>
                        <th class="text-end">Month Total</th>
                    </tr>
                </thead>
                <tbody id="todaySalesTableBody">
                    @foreach($todaySalesBranchWise['branches'] as $bw)
                    <tr>
                        <td class="fw-bold text-dark">
                            <i class="fas fa-building text-primary me-2 opacity-75"></i>{{ $bw['branch_name'] }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1">{{ number_format($bw['today_qty']) }} pcs</span>
                        </td>
                        <td class="text-end fw-bold text-dark">৳{{ number_format($bw['today_amount']) }}</td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1">{{ number_format($bw['month_qty']) }} pcs</span>
                        </td>
                        <td class="text-end fw-bold text-success">৳{{ number_format($bw['month_amount']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot id="todaySalesTableFoot">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-center">{{ number_format($todaySalesBranchWise['total']['today_qty']) }} pcs</td>
                        <td class="text-end text-primary">৳{{ number_format($todaySalesBranchWise['total']['today_amount']) }}</td>
                        <td class="text-center">{{ number_format($todaySalesBranchWise['total']['month_qty']) }} pcs</td>
                        <td class="text-end text-success">৳{{ number_format($todaySalesBranchWise['total']['month_amount']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
