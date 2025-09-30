<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $filename ?? 'Profit & Loss Report' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .report-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .info-left, .info-right {
            flex: 1;
        }
        
        .info-right {
            text-align: right;
        }
        
        .summary-section {
            margin-bottom: 30px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
        }
        
        .summary-card.success {
            background-color: #28a745;
            color: white;
        }
        
        .summary-card.danger {
            background-color: #dc3545;
            color: white;
        }
        
        .summary-card.info {
            background-color: #17a2b8;
            color: white;
        }
        
        .summary-card.warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .summary-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 18px;
            font-weight: bold;
        }
        
        .pl-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .pl-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            color: #495057;
        }
        
        .pl-table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            vertical-align: top;
        }
        
        .pl-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }
        
        .text-success {
            color: #28a745;
            font-weight: bold;
        }
        
        .badge {
            background-color: #6c757d;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .section-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .pl-table {
                font-size: 10px;
            }
            
            .pl-table th,
            .pl-table td {
                padding: 4px 6px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">Your Company Name</div>
        <div class="report-title">Profit & Loss Statement</div>
        <div class="report-subtitle">{{ $filename ?? 'Generated on ' . date('Y-m-d H:i:s') }}</div>
    </div>

    <!-- Report Information -->
    <div class="report-info">
        <div class="info-left">
            <strong>Period:</strong> {{ date('F d, Y', strtotime($startDate)) }} to {{ date('F d, Y', strtotime($endDate)) }}<br>
            <strong>Generated By:</strong> {{ auth()->user()->name ?? 'System' }}<br>
            <strong>Generated On:</strong> {{ date('Y-m-d H:i:s') }}
        </div>
        <div class="info-right">
            <strong>Report Type:</strong> Profit & Loss Statement<br>
            <strong>Page:</strong> 1 of 1<br>
            <strong>Report ID:</strong> PL-{{ date('YmdHis') }}
        </div>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <h3 style="margin-bottom: 15px; color: #007bff;">Financial Summary</h3>
        <div class="summary-grid">
            <div class="summary-card success">
                <div class="summary-title">Total Revenue</div>
                <div class="summary-value">৳{{ $profitLossData['totals']['revenue_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="summary-card danger">
                <div class="summary-title">Total Expenses</div>
                <div class="summary-value">৳{{ $profitLossData['totals']['expenses_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="summary-card info">
                <div class="summary-title">Net Profit/Loss</div>
                <div class="summary-value">৳{{ $profitLossData['totals']['net_profit_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="summary-card warning">
                <div class="summary-title">Profit Margin</div>
                <div class="summary-value">{{ $profitLossData['totals']['profit_percentage'] ?? '0.0' }}%</div>
            </div>
        </div>
    </div>

    <!-- Profit & Loss Statement -->
    <div class="pl-section">
        <h3 style="margin-bottom: 15px; color: #007bff;">Profit & Loss Statement</h3>
        <table class="pl-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Account Code</th>
                    <th style="width: 50%;">Account Name</th>
                    <th style="width: 20%; text-align: right;">Amount</th>
                    <th style="width: 15%; text-align: center;">Type</th>
                </tr>
            </thead>
            <tbody>
                <!-- Revenue Section -->
                <tr style="background-color: #28a745; color: white; font-weight: bold;">
                    <td colspan="4">
                        <i class="fas fa-arrow-up me-2"></i>REVENUE
                    </td>
                </tr>
                @forelse($profitLossData['revenue'] ?? [] as $revenue)
                    <tr>
                        <td><span class="badge" style="background-color: #28a745;">{{ $revenue['code'] }}</span></td>
                        <td>{{ $revenue['name'] }}</td>
                        <td class="text-right">
                            <span class="text-success">৳{{ $revenue['formatted_balance'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge" style="background-color: #28a745;">Income</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px; color: #666;">
                            <strong>No revenue accounts found for this period.</strong>
                        </td>
                    </tr>
                @endforelse
                <tr style="background-color: #28a745; color: white; font-weight: bold;">
                    <td colspan="2">Total Revenue</td>
                    <td class="text-right">৳{{ $profitLossData['totals']['revenue_formatted'] ?? '0.00' }}</td>
                    <td></td>
                </tr>

                <!-- Expenses Section -->
                <tr style="background-color: #dc3545; color: white; font-weight: bold;">
                    <td colspan="4">
                        <i class="fas fa-arrow-down me-2"></i>EXPENSES
                    </td>
                </tr>
                @forelse($profitLossData['expenses'] ?? [] as $expense)
                    <tr>
                        <td><span class="badge" style="background-color: #dc3545;">{{ $expense['code'] }}</span></td>
                        <td>{{ $expense['name'] }}</td>
                        <td class="text-right">
                            <span class="text-danger">৳{{ $expense['formatted_balance'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge" style="background-color: #dc3545;">Expense</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px; color: #666;">
                            <strong>No expense accounts found for this period.</strong>
                        </td>
                    </tr>
                @endforelse
                <tr style="background-color: #dc3545; color: white; font-weight: bold;">
                    <td colspan="2">Total Expenses</td>
                    <td class="text-right">৳{{ $profitLossData['totals']['expenses_formatted'] ?? '0.00' }}</td>
                    <td></td>
                </tr>

                <!-- Net Profit/Loss Section -->
                <tr style="background-color: #17a2b8; color: white; font-weight: bold;">
                    <td colspan="2">
                        <i class="fas fa-calculator me-2"></i>NET PROFIT/LOSS
                    </td>
                    <td class="text-right">
                        @php
                            $netProfit = $profitLossData['totals']['net_profit'] ?? 0;
                            $profitClass = $netProfit >= 0 ? 'text-success' : 'text-danger';
                        @endphp
                        <span class="{{ $profitClass }}">৳{{ $profitLossData['totals']['net_profit_formatted'] ?? '0.00' }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge" style="background-color: {{ $netProfit >= 0 ? '#28a745' : '#dc3545' }};">
                            {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Financial Metrics Summary -->
    <div style="margin-top: 30px;">
        <table class="pl-table" style="width: 50%; margin-left: auto;">
            <tr style="background-color: #17a2b8; color: white; font-weight: bold;">
                <td style="text-align: center; padding: 15px; font-size: 14px;" colspan="2">
                    FINANCIAL METRICS
                </td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="text-align: right; padding: 10px;"><strong>Total Revenue:</strong></td>
                <td style="text-align: right; padding: 10px; color: #28a745; font-weight: bold;">
                    ৳{{ $profitLossData['totals']['revenue_formatted'] ?? '0.00' }}
                </td>
            </tr>
            <tr>
                <td style="text-align: right; padding: 10px;"><strong>Total Expenses:</strong></td>
                <td style="text-align: right; padding: 10px; color: #dc3545; font-weight: bold;">
                    ৳{{ $profitLossData['totals']['expenses_formatted'] ?? '0.00' }}
                </td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="text-align: right; padding: 10px;"><strong>Net Profit/Loss:</strong></td>
                <td style="text-align: right; padding: 10px; font-weight: bold;">
                    @php
                        $netProfit = $profitLossData['totals']['net_profit'] ?? 0;
                        $profitClass = $netProfit >= 0 ? 'text-success' : 'text-danger';
                    @endphp
                    <span class="{{ $profitClass }}">৳{{ $profitLossData['totals']['net_profit_formatted'] ?? '0.00' }}</span>
                </td>
            </tr>
            <tr style="background-color: #343a40; color: white; font-weight: bold;">
                <td style="text-align: right; padding: 15px;"><strong>Profit Margin:</strong></td>
                <td style="text-align: right; padding: 15px; font-size: 16px;">
                    <span class="text-info">{{ $profitLossData['totals']['profit_percentage'] ?? '0.0' }}%</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            <strong>Disclaimer:</strong> This profit & loss statement is generated automatically by the system. 
            Please verify all amounts and calculations before using this report for official purposes.
        </p>
        <p>
            Generated on {{ date('Y-m-d H:i:s') }} | 
            Page 1 of 1 | 
            Report ID: PL-{{ date('YmdHis') }}
        </p>
    </div>
</body>
</html> 