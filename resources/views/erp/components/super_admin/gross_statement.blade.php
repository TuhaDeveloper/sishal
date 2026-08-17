<!-- 5. Gross Sales Statement Component -->
<div class="sa-card mb-4" id="cardGrossStatement">
    <div class="sa-card-overlay"><div class="spinner-border text-primary" role="status"></div></div>
    <div class="sa-card-header">
        <h2 class="sa-card-title">
            <i class="fas fa-chart-line sa-icon-gross"></i>
            Gross Sales Statement
        </h2>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="fas fa-check-circle me-1"></i> Profit Engine Calculated</span>
        </div>
    </div>

    <div class="table-container-scroll">
        <table class="sa-table">
            <thead>
                <tr id="grossStatementHead">
                    <th>Financial Metric</th>
                    @foreach($grossSalesStatement['months'] as $m)
                        <th class="text-end">{{ $m }}</th>
                    @endforeach
                    <th class="text-end">Year Total</th>
                </tr>
            </thead>
            <tbody id="grossStatementBody">
                @foreach($grossSalesStatement['rows'] as $key => $row)
                <tr class="{{ $key === 'gross_profit' ? 'table-light fw-bold' : '' }}">
                    <td class="fw-bold {{ $key === 'gross_profit' ? 'text-primary' : 'text-dark' }}">
                        {{ $row['label'] }}
                    </td>
                    @foreach($row['values'] as $val)
                        <td class="text-end">
                            @if($row['format'] === 'qty')
                                <span class="fw-semibold text-secondary">{{ number_format($val) }} pcs</span>
                            @elseif($row['format'] === 'currency')
                                ৳{{ number_format($val) }}
                            @elseif($row['format'] === 'currency_highlight')
                                <span class="amount-positive">৳{{ number_format($val) }}</span>
                            @elseif($row['format'] === 'percent')
                                <span class="profit-pill {{ $val >= 40 ? 'profit-pill-high' : ($val >= 25 ? 'profit-pill-mid' : 'profit-pill-low') }}">
                                    {{ $val }}%
                                </span>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-end fw-bold">
                        @if($row['format'] === 'qty')
                            <span class="fw-bold text-dark">{{ number_format($row['year_total']) }} pcs</span>
                        @elseif($row['format'] === 'currency')
                            ৳{{ number_format($row['year_total']) }}
                        @elseif($row['format'] === 'currency_highlight')
                            <span class="amount-positive fs-6">৳{{ number_format($row['year_total']) }}</span>
                        @elseif($row['format'] === 'percent')
                            <span class="profit-pill profit-pill-high fs-6">
                                {{ $row['year_total'] }}%
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
