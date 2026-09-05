<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Exchange Receipt - {{ $exchange->exchange_number }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9pt;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
            -webkit-print-color-adjust: exact;
        }
        .receipt-container {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 5mm 3mm;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .bold        { font-weight: bold; }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            display: block;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }
        .company-info {
            font-size: 8pt;
            display: block;
            margin-bottom: 1mm;
        }
        .invoice-title {
            margin-top: 3mm;
            font-size: 10pt;
            font-weight: bold;
            display: block;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 2mm 0;
            margin-bottom: 3mm;
            text-transform: uppercase;
        }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td {
            vertical-align: top;
            padding: 0.5mm 0;
            font-size: 8pt;
        }
        .info-label { width: 28mm; font-weight: bold; }

        .section-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 1mm 0;
            margin: 3mm 0 1mm;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }
        .items-table th, .items-table td {
            padding: 1mm 0.5mm;
            font-size: 8pt;
            text-align: right;
        }
        .items-table th {
            font-weight: bold;
            border-bottom: 1px dashed #000;
        }
        .items-table .text-left  { text-align: left; }
        .items-table .text-center { text-align: center; }

        .summary-table { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        .summary-table td { padding: 1mm 0; font-size: 9pt; }
        .summary-label { text-align: right; padding-right: 3mm; width: 70%; }
        .summary-value { text-align: right; width: 30%; font-weight: bold; }
        .total-row td {
            border-top: 1px dashed #000;
            font-size: 11pt;
            padding-top: 2mm;
        }
        .footer {
            margin-top: 5mm;
            font-size: 8pt;
            border-top: 1px dashed #000;
            padding-top: 3mm;
            padding-bottom: 10mm;
        }
        .item-sku { font-size: 7pt; display: block; color: #444; }
        .badge-return { background: #000; color: #fff; padding: 0mm 1mm; font-size: 7pt; }
        .badge-new    { border: 1px solid #000; padding: 0mm 1mm; font-size: 7pt; }

        @media print {
            body { width: 80mm; }
            .receipt-container { width: 80mm; padding: 2mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="receipt-container">

    {{-- Header --}}
    <div class="text-center" style="margin-bottom: 4mm;">
        <span class="company-name">{{ $general_settings->site_title }}</span>
        <span class="company-info">{{ $general_settings->contact_address }}</span>
        <span class="company-info">Tel: {{ $general_settings->contact_phone }}</span>
        <span class="invoice-title">EXCHANGE RECEIPT</span>
    </div>

    {{-- Exchange Info --}}
    <table class="info-table" style="margin-bottom: 3mm;">
        <tr>
            <td class="info-label">Exc. No.:</td>
            <td>{{ $exchange->exchange_number }}</td>
        </tr>
        <tr>
            <td class="info-label">Date:</td>
            <td>
                @php
                    $tz = config('app.timezone', 'Asia/Dhaka');
                    $createdAtLocal = $exchange->created_at ? \Carbon\Carbon::parse($exchange->created_at)->timezone($tz) : null;
                    if ($exchange->exchange_date && $createdAtLocal) {
                        $displayExcDate = \Carbon\Carbon::parse($exchange->exchange_date)->format('d-m-Y') . ' | ' . $createdAtLocal->format('h:i A');
                    } elseif ($createdAtLocal) {
                        $displayExcDate = $createdAtLocal->format('d-m-Y | h:i A');
                    } elseif ($exchange->exchange_date) {
                        $displayExcDate = \Carbon\Carbon::parse($exchange->exchange_date)->format('d-m-Y');
                    } else {
                        $displayExcDate = \Carbon\Carbon::now($tz)->format('d-m-Y | h:i A');
                    }
                @endphp
                {{ $displayExcDate }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Original Sale:</td>
            <td>{{ $exchange->originalPos->sale_number ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Customer:</td>
            <td>{{ $exchange->customer->name ?? 'Walk-in' }}</td>
        </tr>
        @if($exchange->customer?->phone)
        <tr>
            <td class="info-label">Mobile:</td>
            <td>{{ $exchange->customer->phone }}</td>
        </tr>
        @endif
        <tr>
            <td class="info-label">Branch:</td>
            <td>{{ $exchange->branch->name ?? '-' }}</td>
        </tr>
    </table>

    {{-- Returned Items --}}
    @php
        $returnedItems = $exchange->items->where('type', 'returned');
        $newItems      = $exchange->items->where('type', 'new');
    @endphp

    @if($returnedItems->count())
    <div class="section-title text-center">(-) Items Returned by Customer</div>
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left">Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($returnedItems as $item)
            <tr>
                <td class="text-left">
                    {{ $item->product->name ?? '-' }}
                    @if($item->variation)
                        @php
                            $vals = [];
                            foreach($item->variation->attributeValues as $av) { $vals[] = $av->value; }
                        @endphp
                        <span class="item-sku">[{{ implode(', ', $vals) }}]</span>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- New Items --}}
    @if($newItems->count())
    <div class="section-title text-center">(+) New Items Given to Customer</div>
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left">Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($newItems as $item)
            <tr>
                <td class="text-left">
                    {{ $item->product->name ?? '-' }}
                    @if($item->variation)
                        @php
                            $vals = [];
                            foreach($item->variation->attributeValues as $av) { $vals[] = $av->value; }
                        @endphp
                        <span class="item-sku">[{{ implode(', ', $vals) }}]</span>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Financial Summary --}}
    <table class="summary-table">
        <tr>
            <td class="summary-label">Returned Value</td>
            <td class="summary-value">{{ number_format($exchange->total_return_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label">New Items Value</td>
            <td class="summary-value">{{ number_format($exchange->total_new_amount, 2) }}</td>
        </tr>
        @if($exchange->discount_amount > 0)
        <tr>
            <td class="summary-label">Discount (-)</td>
            <td class="summary-value">{{ number_format($exchange->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($exchange->delivery_charge > 0)
        <tr>
            <td class="summary-label">Delivery (+)</td>
            <td class="summary-value">{{ number_format($exchange->delivery_charge, 2) }}</td>
        </tr>
        @endif

        @if($exchange->extra_payable > 0)
        <tr class="total-row">
            <td class="summary-label bold">EXTRA PAID BY CUSTOMER</td>
            <td class="summary-value bold" style="font-size: 12pt;">{{ number_format($exchange->extra_payable, 2) }}</td>
        </tr>
        @elseif($exchange->refund_amount > 0)
        <tr class="total-row">
            <td class="summary-label bold">REFUNDED TO CUSTOMER</td>
            <td class="summary-value bold" style="font-size: 12pt;">{{ number_format($exchange->refund_amount, 2) }}</td>
        </tr>
        @else
        <tr class="total-row">
            <td class="summary-label bold">BALANCE (No Extra Charge)</td>
            <td class="summary-value bold" style="font-size: 12pt;">0.00</td>
        </tr>
        @endif
    </table>

    {{-- Footer --}}
    <div class="footer text-center">
        <div class="bold">Thank you for shopping with us!</div>
        <div>This is your Exchange Slip — Please keep it safe.</div>
        <div style="margin-top: 2mm;">{{ $general_settings->site_title }}</div>
    </div>
</div>

@if(!isset($action) || $action == 'print')
<script>
    window.onload = () => {
        window.print();
        setTimeout(() => { window.close(); }, 700);
    }
</script>
@endif
</body>
</html>
