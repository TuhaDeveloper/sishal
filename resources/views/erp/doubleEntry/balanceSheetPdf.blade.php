<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $filename ?? 'Balance Sheet Report' }}</title>
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
        
        .summary-card.primary {
            background-color: #007bff;
            color: white;
        }
        
        .summary-card.danger {
            background-color: #dc3545;
            color: white;
        }
        
        .summary-card.success {
            background-color: #28a745;
            color: white;
        }
        
        .summary-card.info {
            background-color: #17a2b8;
            color: white;
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
        
        .balance-sheet-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .balance-sheet-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            color: #495057;
        }
        
        .balance-sheet-table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            vertical-align: top;
        }
        
        .balance-sheet-table tr:nth-child(even) {
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
            
            .balance-sheet-table {
                font-size: 10px;
            }
            
            .balance-sheet-table th,
            .balance-sheet-table td {
                padding: 4px 6px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">Your Company Name</div>
        <div class="report-title">Balance Sheet</div>
        <div class="report-subtitle">{{ $filename ?? 'Generated on ' . date('Y-m-d H:i:s') }}</div>
    </div>

    <!-- Report Information -->
    <div class="report-info">
        <div class="info-left">
            <strong>As of Date:</strong> {{ date('F d, Y', strtotime($asOfDate)) }}<br>
            <strong>Generated By:</strong> {{ auth()->user()->name ?? 'System' }}<br>
            <strong>Generated On:</strong> {{ date('Y-m-d H:i:s') }}
        </div>
        <div class="info-right">
            <strong>Report Type:</strong> Balance Sheet<br>
            <strong>Page:</strong> 1 of 1<br>
            <strong>Report ID:</strong> BS-{{ date('YmdHis') }}
        </div>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <h3 style="margin-bottom: 15px; color: #007bff;">Financial Summary</h3>
        <div class="summary-grid">
            <div class="summary-card primary">
                <div class="summary-title">Total Assets</div>
                <div class="summary-value">৳{{ $balanceSheetData['totals']['assets_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="summary-card danger">
                <div class="summary-title">Total Liabilities</div>
                <div class="summary-value">৳{{ $balanceSheetData['totals']['liabilities_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="summary-card success">
                <div class="summary-title">Total Equity</div>
                <div class="summary-value">৳{{ $balanceSheetData['totals']['equity_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="summary-card info">
                <div class="summary-title">Net Worth</div>
                <div class="summary-value">৳{{ $balanceSheetData['net_worth_formatted'] ?? '0.00' }}</div>
            </div>
        </div>
    </div>

    <!-- Balance Sheet Sections -->
    <div style="display: flex; gap: 20px;">
        <!-- Assets Section -->
        <div style="flex: 1;">
            <div class="section-header">ASSETS</div>
            <table class="balance-sheet-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Code</th>
                        <th style="width: 50%;">Account Name</th>
                        <th style="width: 30%; text-align: right;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balanceSheetData['assets'] ?? [] as $asset)
                        <tr>
                            <td><span class="badge">{{ $asset['code'] }}</span></td>
                            <td>{{ $asset['name'] }}</td>
                            <td class="text-right">
                                <span class="{{ $asset['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳{{ $asset['formatted_balance'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center" style="padding: 20px; color: #666;">
                                <strong>No asset accounts found.</strong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #343a40; color: white; font-weight: bold;">
                        <td colspan="2">Total Assets</td>
                        <td class="text-right">৳{{ $balanceSheetData['totals']['assets_formatted'] ?? '0.00' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Liabilities & Equity Section -->
        <div style="flex: 1;">
            <!-- Liabilities -->
            <div class="section-header" style="background-color: #dc3545;">LIABILITIES</div>
            <table class="balance-sheet-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Code</th>
                        <th style="width: 50%;">Account Name</th>
                        <th style="width: 30%; text-align: right;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balanceSheetData['liabilities'] ?? [] as $liability)
                        <tr>
                            <td><span class="badge">{{ $liability['code'] }}</span></td>
                            <td>{{ $liability['name'] }}</td>
                            <td class="text-right">
                                <span class="{{ $liability['balance'] >= 0 ? 'text-danger' : 'text-success' }}">
                                    ৳{{ $liability['formatted_balance'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center" style="padding: 20px; color: #666;">
                                <strong>No liability accounts found.</strong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #343a40; color: white; font-weight: bold;">
                        <td colspan="2">Total Liabilities</td>
                        <td class="text-right">৳{{ $balanceSheetData['totals']['liabilities_formatted'] ?? '0.00' }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Equity -->
            <div class="section-header" style="background-color: #ffc107; color: #212529; margin-top: 20px;">EQUITY</div>
            <table class="balance-sheet-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Code</th>
                        <th style="width: 50%;">Account Name</th>
                        <th style="width: 30%; text-align: right;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balanceSheetData['equity'] ?? [] as $equity)
                        <tr>
                            <td><span class="badge">{{ $equity['code'] }}</span></td>
                            <td>{{ $equity['name'] }}</td>
                            <td class="text-right">
                                <span class="{{ $equity['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳{{ $equity['formatted_balance'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center" style="padding: 20px; color: #666;">
                                <strong>No equity accounts found.</strong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #343a40; color: white; font-weight: bold;">
                        <td colspan="2">Total Equity</td>
                        <td class="text-right">৳{{ $balanceSheetData['totals']['equity_formatted'] ?? '0.00' }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Net Worth Summary -->
    <div style="margin-top: 30px;">
        <table class="balance-sheet-table" style="width: 50%; margin-left: auto;">
            <tr style="background-color: #17a2b8; color: white; font-weight: bold;">
                <td style="text-align: center; padding: 15px; font-size: 14px;" colspan="3">
                    NET WORTH SUMMARY
                </td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="text-align: right; padding: 10px;"><strong>Total Assets:</strong></td>
                <td style="text-align: right; padding: 10px; color: #28a745; font-weight: bold;">
                    ৳{{ $balanceSheetData['totals']['assets_formatted'] ?? '0.00' }}
                </td>
                <td></td>
            </tr>
            <tr>
                <td style="text-align: right; padding: 10px;"><strong>Total Liabilities:</strong></td>
                <td style="text-align: right; padding: 10px; color: #dc3545; font-weight: bold;">
                    ৳{{ $balanceSheetData['totals']['liabilities_formatted'] ?? '0.00' }}
                </td>
                <td></td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="text-align: right; padding: 10px;"><strong>Total Equity:</strong></td>
                <td style="text-align: right; padding: 10px; color: #28a745; font-weight: bold;">
                    ৳{{ $balanceSheetData['totals']['equity_formatted'] ?? '0.00' }}
                </td>
                <td></td>
            </tr>
            <tr style="background-color: #343a40; color: white; font-weight: bold;">
                <td style="text-align: right; padding: 15px;"><strong>Net Worth:</strong></td>
                <td style="text-align: right; padding: 15px; font-size: 16px;">
                    <span class="{{ ($balanceSheetData['net_worth'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        ৳{{ $balanceSheetData['net_worth_formatted'] ?? '0.00' }}
                    </span>
                </td>
                <td></td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            <strong>Disclaimer:</strong> This balance sheet is generated automatically by the system. 
            Please verify all amounts and account balances before using this report for official purposes.
        </p>
        <p>
            Generated on {{ date('Y-m-d H:i:s') }} | 
            Page 1 of 1 | 
            Report ID: BS-{{ date('YmdHis') }}
        </p>
    </div>
</body>
</html> 