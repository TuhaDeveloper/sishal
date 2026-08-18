<!-- 4. Branch Sales Statement Component -->
<div class="col-lg-7">
    <div class="sa-card h-100 mb-0" id="cardBranchStatement">
        <div class="sa-card-overlay"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="sa-card-header">
            <h2 class="sa-card-title">
                <i class="fas fa-table sa-icon-statement"></i>
                Branch Sales Statement
            </h2>
            <span class="badge bg-light text-dark border">6 Months Qty Overview</span>
        </div>

        <div class="table-container-scroll">
            <table class="sa-table">
                <thead>
                    <tr id="branchStatementHead">
                        <th>Branch</th>
                        @foreach($branchSalesStatement['months'] as $m)
                            <th class="text-end">{{ $m }}</th>
                        @endforeach
                        <th class="text-end">Year Qty</th>
                        <th class="text-end">Total Value</th>
                        <th class="text-end">Total Profit</th>
                        <th class="text-end">Profit %</th>
                    </tr>
                </thead>
                <tbody id="branchStatementBody">
                    @foreach($branchSalesStatement['rows'] as $row)
                    <tr>
                        <td class="fw-bold text-dark">{{ $row['branch'] }}</td>
                        @foreach($row['months'] as $mVal)
                            <td class="text-end">{{ number_format($mVal) }} pcs</td>
                        @endforeach
                        <td class="text-end fw-bold text-dark">{{ number_format($row['year_total']) }} pcs</td>
                        <td class="text-end fw-bold text-primary">৳{{ number_format($row['total_value'], 2) }}</td>
                        <td class="text-end fw-bold text-success">৳{{ number_format($row['total_profit'], 2) }}</td>
                        <td class="text-end fw-bold">
                            <span class="badge @if($row['profit_pct'] >= 25) bg-success-subtle text-success border border-success-subtle @elseif($row['profit_pct'] >= 10) bg-warning-subtle text-warning-emphasis border border-warning-subtle @else bg-danger-subtle text-danger border border-danger-subtle @endif px-2 py-1">
                                {{ number_format($row['profit_pct'], 1) }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot id="branchStatementFoot">
                    <tr>
                        <td>TOTAL</td>
                        @foreach($branchSalesStatement['totals']['months'] as $tVal)
                            <td class="text-end text-dark">{{ number_format($tVal) }} pcs</td>
                        @endforeach
                        <td class="text-end text-dark fw-bold">{{ number_format($branchSalesStatement['totals']['year_total']) }} pcs</td>
                        <td class="text-end text-primary fw-bold fs-6">৳{{ number_format($branchSalesStatement['totals']['total_value'], 2) }}</td>
                        <td class="text-end text-success fw-bold fs-6">৳{{ number_format($branchSalesStatement['totals']['total_profit'], 2) }}</td>
                        <td class="text-end fw-bold fs-6">
                            <span class="badge bg-primary px-2 py-1">
                                {{ number_format($branchSalesStatement['totals']['profit_pct'], 1) }}%
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
