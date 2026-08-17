<!-- 6. Expense Statement Component -->
<div class="sa-card mb-4" id="cardExpenseStatement">
    <div class="sa-card-overlay"><div class="spinner-border text-primary" role="status"></div></div>
    <div class="sa-card-header">
        <h2 class="sa-card-title">
            <i class="fas fa-wallet sa-icon-expense"></i>
            Expense Statement (Chart of Accounts)
        </h2>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('vouchers.index') }}" class="btn btn-sm btn-outline-danger font-semibold">
                <i class="fas fa-receipt me-1"></i> View All Expenses
            </a>
        </div>
    </div>

    <div class="table-container-scroll">
        <table class="sa-table">
            <thead>
                <tr id="expenseStatementHead">
                    <th>Expense Category</th>
                    @foreach($expenseStatement['months'] as $m)
                        <th class="text-end">{{ $m }}</th>
                    @endforeach
                    <th class="text-end">Year Total</th>
                </tr>
            </thead>
            <tbody id="expenseStatementBody">
                @foreach($expenseStatement['categories'] as $cat)
                <tr>
                    <td class="fw-bold text-dark">
                        <i class="fas fa-tag text-muted me-2 opacity-50"></i>{{ $cat['category'] }}
                    </td>
                    @foreach($cat['months'] as $mVal)
                        <td class="text-end">৳{{ number_format($mVal) }}</td>
                    @endforeach
                    <td class="text-end fw-bold text-danger">৳{{ number_format($cat['year_total']) }}</td>
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
