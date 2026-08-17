<!-- 4. Branch Sales Statement Component -->
<div class="col-lg-7">
    <div class="sa-card h-100 mb-0" id="cardBranchStatement">
        <div class="sa-card-overlay"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="sa-card-header">
            <h2 class="sa-card-title">
                <i class="fas fa-table sa-icon-statement"></i>
                Branch Sales Statement
            </h2>
            <span class="badge bg-light text-dark border">6 Months Overview</span>
        </div>

        <div class="table-container-scroll">
            <table class="sa-table">
                <thead>
                    <tr id="branchStatementHead">
                        <th>Branch</th>
                        @foreach($branchSalesStatement['months'] as $m)
                            <th class="text-end">{{ $m }}</th>
                        @endforeach
                        <th class="text-end">Year Total</th>
                    </tr>
                </thead>
                <tbody id="branchStatementBody">
                    @foreach($branchSalesStatement['rows'] as $row)
                    <tr>
                        <td class="fw-bold text-dark">{{ $row['branch'] }}</td>
                        @foreach($row['months'] as $mVal)
                            <td class="text-end">৳{{ number_format($mVal) }}</td>
                        @endforeach
                        <td class="text-end fw-bold text-primary">৳{{ number_format($row['year_total']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot id="branchStatementFoot">
                    <tr>
                        <td>TOTAL</td>
                        @foreach($branchSalesStatement['totals']['months'] as $tVal)
                            <td class="text-end text-dark">৳{{ number_format($tVal) }}</td>
                        @endforeach
                        <td class="text-end text-success fs-6">৳{{ number_format($branchSalesStatement['totals']['year_total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
