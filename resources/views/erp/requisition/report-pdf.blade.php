<!DOCTYPE html>
<html>
<head>
    <title>Requisition Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Requisition Report</h2>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Req #</th>
                <th>Branch</th>
                <th>Warehouse</th>
                <th>Date</th>
                <th>Status</th>
                <th>Requested By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requisitions as $req)
                <tr>
                    <td>{{ $req->requisition_number }}</td>
                    <td>{{ optional($req->branch)->name ?? '—' }}</td>
                    <td>{{ optional($req->warehouse)->name ?? '—' }}</td>
                    <td>{{ $req->requisition_date }}</td>
                    <td>{{ strtoupper(str_replace('_', ' ', $req->display_status)) }}</td>
                    <td>{{ optional($req->creator)->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
