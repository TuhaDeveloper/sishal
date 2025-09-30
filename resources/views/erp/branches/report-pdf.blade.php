<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Report</title>
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
            width: 33.33%;
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
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
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
        <h1>Branch Report</h1>
        <p>Generated on: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <table class="summary-row" style="width: 100%; border-collapse: separate; border-spacing: 5px;">
            <tr>
                <td class="summary-item">
                    <span class="label">Total Branches:</span>
                    <span class="value">{{ $summary['total_branches'] }}</span>
                </td>
                <td class="summary-item">
                    <span class="label">Active Branches:</span>
                    <span class="value">{{ $summary['active_branches'] }}</span>
                </td>
                <td class="summary-item">
                    <span class="label">Inactive Branches:</span>
                    <span class="value">{{ $summary['inactive_branches'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($filters['status']) || !empty($filters['location']) || !empty($filters['manager']) || !empty($filters['search']))
    <div class="filters">
        <h3>Applied Filters:</h3>
        @if(!empty($filters['status']))
            <span class="filter-item">Status: {{ ucfirst($filters['status']) }}</span>
        @endif
        @if(!empty($filters['location']))
            <span class="filter-item">Location: {{ $filters['location'] }}</span>
        @endif
        @if(!empty($filters['manager']))
            <span class="filter-item">Manager: {{ $filters['manager'] }}</span>
        @endif
        @if(!empty($filters['search']))
            <span class="filter-item">Search: {{ $filters['search'] }}</span>
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
            @forelse($branches as $branch)
                <tr>
                    @foreach($selectedColumns as $column)
                        @switch($column)
                            @case('id')
                                <td>{{ $branch->id }}</td>
                                @break
                            @case('name')
                                <td>{{ $branch->name }}</td>
                                @break
                            @case('location')
                                <td>{{ $branch->location ?? '-' }}</td>
                                @break
                            @case('contact_info')
                                <td>{{ $branch->contact_info ?? '-' }}</td>
                                @break
                            @case('manager')
                                <td>{{ $branch->manager ? $branch->manager->first_name . ' ' . $branch->manager->last_name : 'N/A' }}</td>
                                @break
                            @case('status')
                                <td>
                                    <span class="status-badge status-{{ $branch->status ?? 'active' }}">
                                        {{ ucfirst($branch->status ?? 'Active') }}
                                    </span>
                                </td>
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
            @if($branches->count() > 0)
                <tr class="totals-row">
                    @foreach($selectedColumns as $column)
                        @switch($column)
                            @case('id')
                                <td>{{ $branches->count() }} Branches</td>
                                @break
                            @case('name')
                                <td>-</td>
                                @break
                            @case('location')
                                <td>-</td>
                                @break
                            @case('contact_info')
                                <td>-</td>
                                @break
                            @case('manager')
                                <td>-</td>
                                @break
                            @case('status')
                                <td>-</td>
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
        <p>Total records: {{ $branches->count() }} | Generated by: {{ Auth::user()->name ?? 'System' }}</p>
    </div>
</body>
</html>
