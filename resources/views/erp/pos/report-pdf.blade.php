<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            font-size: 11px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .filters h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .filter-item {
            display: inline-block;
            margin-right: 20px;
            font-size: 11px;
        }
        .summary-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .summary-row {
            width: 100%;
            margin-bottom: 0;
        }
        .summary-item {
            width: 25%;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 3px;
            border: 1px solid #dee2e6;
            text-align: center;
            vertical-align: top;
        }
        .summary-item .label {
            display: block;
            font-size: 11px;
            color: #666;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-item .value {
            display: block;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
            font-size: 8px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .totals-row {
            background-color: #e8f4fd !important;
            font-weight: bold;
        }
        .totals-row td {
            border-top: 2px solid #007bff;
        }
        .status-badge {
            padding: 2px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-shipping { background-color: #cce5ff; color: #004085; }
        .status-received { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-unpaid { background-color: #f8d7da; color: #721c24; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sales Report</h1>
        <p>Generated on: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <table class="summary-row" style="width: 100%; border-collapse: separate; border-spacing: 5px;">
            <tr>
                <td class="summary-item">
                    <span class="label">Total Sales:</span>
                    <span class="value">{{ $summary['total_sales'] }}</span>
                </td>
                <td class="summary-item">
                    <span class="label">Total Amount:</span>
                    <span class="value">{{ $summary['total_amount'] }} taka</span>
                </td>
                <td class="summary-item">
                    <span class="label">Paid Sales:</span>
                    <span class="value">{{ $summary['paid_sales'] }}</span>
                </td>
                <td class="summary-item">
                    <span class="label">Unpaid Sales:</span>
                    <span class="value">{{ $summary['unpaid_sales'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['status']) || !empty($filters['payment_status']))
    <div class="filters">
        <h3>Applied Filters:</h3>
        @if(!empty($filters['date_from']))
            <span class="filter-item">From: {{ $filters['date_from'] }}</span>
        @endif
        @if(!empty($filters['date_to']))
            <span class="filter-item">To: {{ $filters['date_to'] }}</span>
        @endif
        @if(!empty($filters['status']))
            <span class="filter-item">Status: {{ ucfirst($filters['status']) }}</span>
        @endif
        @if(!empty($filters['payment_status']))
            <span class="filter-item">Payment Status: {{ ucfirst($filters['payment_status']) }}</span>
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    @foreach($selectedColumns as $column)
                        @switch($column)
                            @case('pos_id')
                                <td>{{ $sale->sale_number ?? '-' }}</td>
                                @break
                            @case('sale_date')
                                <td>{{ $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d-m-Y') : '-' }}</td>
                                @break
                            @case('customer')
                                <td>{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</td>
                                @break
                            @case('phone')
                                <td>{{ $sale->customer ? $sale->customer->phone : '-' }}</td>
                                @break
                            @case('branch')
                                <td>{{ $sale->branch ? $sale->branch->name : '-' }}</td>
                                @break
                            @case('status')
                                <td>
                                    <span class="status-badge status-{{ $sale->status ?? 'unknown' }}">
                                        {{ ucfirst($sale->status ?? '-') }}
                                    </span>
                                </td>
                                @break
                            @case('payment_status')
                                <td>
                                    @if($sale->invoice)
                                        <span class="status-badge status-{{ $sale->invoice->status ?? 'unknown' }}">
                                            {{ ucfirst($sale->invoice->status ?? '-') }}
                                        </span>
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                                @break
                            @case('subtotal')
                                <td>{{ number_format($sale->sub_total, 2) }} taka</td>
                                @break
                            @case('discount')
                                <td>{{ number_format($sale->discount, 2) }} taka</td>
                                @break
                            @case('total')
                                <td>{{ number_format($sale->total_amount, 2) }} taka</td>
                                @break
                            @case('paid_amount')
                                <td>{{ $sale->invoice ? number_format($sale->invoice->paid_amount, 2) : '0.00' }} taka</td>
                                @break
                            @case('due_amount')
                                <td>{{ $sale->invoice ? number_format($sale->invoice->due_amount, 2) : '0.00' }} taka</td>
                                @break
                            @default
                                <td>-</td>
                        @endswitch
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align: center;">No data found</td>
                </tr>
            @endforelse
            
            <!-- Totals Row -->
            @if($sales->count() > 0)
                @php
                    $totalAmount = $sales->sum('total_amount');
                    $totalPaidAmount = $sales->sum(function($sale) {
                        return $sale->invoice ? $sale->invoice->paid_amount : 0;
                    });
                    $totalDueAmount = $sales->sum(function($sale) {
                        return $sale->invoice ? $sale->invoice->due_amount : 0;
                    });
                @endphp
                <tr class="totals-row">
                    @foreach($selectedColumns as $column)
                        @switch($column)
                            @case('pos_id')
                                <td>{{ $sales->count() }} Sales</td>
                                @break
                            @case('sale_date')
                                <td>-</td>
                                @break
                            @case('customer')
                                <td>-</td>
                                @break
                            @case('phone')
                                <td>-</td>
                                @break
                            @case('branch')
                                <td>-</td>
                                @break
                            @case('status')
                                <td>-</td>
                                @break
                            @case('payment_status')
                                <td>-</td>
                                @break
                            @case('subtotal')
                                <td>-</td>
                                @break
                            @case('discount')
                                <td>-</td>
                                @break
                            @case('total')
                                <td>{{ number_format($totalAmount, 2) }} taka</td>
                                @break
                            @case('paid_amount')
                                <td>{{ number_format($totalPaidAmount, 2) }} taka</td>
                                @break
                            @case('due_amount')
                                <td>{{ number_format($totalDueAmount, 2) }} taka</td>
                                @break
                            @default
                                <td>-</td>
                        @endswitch
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated automatically by the system on {{ date('d-m-Y H:i:s') }}</p>
        <p>Total records: {{ $sales->count() }} | Generated by: {{ Auth::user()->name ?? 'System' }}</p>
    </div>
</body>
</html> 