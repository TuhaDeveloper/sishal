<!-- 6. Expense Statement Component -->
<div class="sa-card mb-4" id="cardExpenseStatement">
    <div class="sa-card-overlay"><div class="spinner-border text-primary" role="status"></div></div>
    <div class="sa-card-header">
        <h2 class="sa-card-title">
            <i class="fas fa-wallet sa-icon-expense"></i>
            Expense Statement (Branch Wise)
        </h2>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('vouchers.index') }}" class="btn btn-sm btn-outline-danger font-semibold">
                <i class="fas fa-receipt me-1"></i> View All Expenses
            </a>
        </div>
    </div>

    <div class="table-container-scroll">
        <table class="sa-table sa-table-sticky-col">
            <thead>
                <tr id="expenseStatementHead">
                    <th>Branch</th>
                    @foreach($expenseStatement['months'] as $m)
                        <th class="text-end">{{ $m }}</th>
                    @endforeach
                    <th class="text-end">Year Total</th>
                </tr>
            </thead>
            <tbody id="expenseStatementBody">
                @foreach(($expenseStatement['branches'] ?? $expenseStatement['categories'] ?? []) as $bRow)
                <tr>
                    <td class="fw-bold text-dark">
                        <i class="fas fa-building text-danger me-2 opacity-75"></i>{{ $bRow['branch'] ?? $bRow['category'] ?? '' }}
                    </td>
                    @foreach($bRow['months'] as $mVal)
                        <td class="text-end">৳{{ number_format($mVal) }}</td>
                    @endforeach
                    <td class="text-end fw-bold text-danger">৳{{ number_format($bRow['year_total']) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot id="expenseStatementFoot">
                <tr>
                    <td>TOTAL EXPENSE</td>
                    @foreach($expenseStatement['total']['months'] as $tVal)
                        <td class="text-end text-danger fw-bold">৳{{ number_format($tVal) }}</td>
                    @endforeach
                    <td class="text-end text-danger fs-6 fw-extrabold">৳{{ number_format($expenseStatement['total']['year_total']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
