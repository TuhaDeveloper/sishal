<!DOCTYPE html>
<html>
<head>
    <title>Top Products Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f8f9fa; padding: 8px; border: 1px solid #ddd; text-align: left; }
        td { padding: 8px; border: 1px solid #ddd; }
        .text-end { text-align: right; }
        .section-title { background: #eee; padding: 10px; font-weight: bold; margin-top: 20px; }
        .profit-pos { color: #28a745; }
        .profit-neg { color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Top Products Report</h1>
        <p>Period: {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}</p>
    </div>

    <div class="section-title">TOP {{ $limit }} PRODUCTS BY REVENUE</div>
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th>Product</th>
                <th>Style No.</th>
                <th>Category</th>
                <th class="text-end">Qty Sold</th>
                <th class="text-end">Revenue</th>
                <th class="text-end">Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topByRevenue as $index => $data)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $data['display_name'] ?? $data['product']->name }}</td>
                    <td>{{ $data['product']->style_number ?? $data['product']->sku ?? 'N/A' }}</td>
                    <td>{{ $data['product']->category->name ?? 'N/A' }}</td>
                    <td class="text-end">{{ $data['quantity_sold'] }}</td>
                    <td class="text-end fw-bold">{{ number_format($data['revenue'], 2) }}</td>
                    <td class="text-end {{ $data['profit'] >= 0 ? 'profit-pos' : 'profit-neg' }}">{{ number_format($data['profit'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">TOP {{ $limit }} PRODUCTS BY PROFIT</div>
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th>Product</th>
                <th>Style No.</th>
                <th class="text-end">Cost</th>
                <th class="text-end">Revenue</th>
                <th class="text-end">Profit</th>
                <th class="text-end">Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topByProfit as $index => $data)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $data['display_name'] ?? $data['product']->name }}</td>
                    <td>{{ $data['product']->style_number ?? $data['product']->sku ?? 'N/A' }}</td>
                    <td class="text-end">{{ number_format($data['cost'], 2) }}</td>
                    <td class="text-end">{{ number_format($data['revenue'], 2) }}</td>
                    <td class="text-end fw-bold {{ $data['profit'] >= 0 ? 'profit-pos' : 'profit-neg' }}">{{ number_format($data['profit'], 2) }}</td>
                    <td class="text-end">
                        {{ $data['revenue'] > 0 ? number_format(($data['profit'] / $data['revenue']) * 100, 1) : 0 }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: center; color: #999; font-size: 9px;">
        Generated on {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
