@php
    // Include helper functions if needed
    // require_once app_path('Helpers/NumberHelper.php');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>POS Receipt - {{ $pos->sale_number }}</title>
    <style>
        /* Base Styles for 80mm Thermal Printer */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace; /* Classic receipt font */
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
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        .header { margin-bottom: 5mm; }
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

        .sale-info {
            margin-bottom: 3mm;
            width: 100%;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 0.5mm 0;
            font-size: 8pt;
        }
        .info-label {
            width: 25mm;
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
            border-bottom: 1px dashed #000;
        }
        .items-table th, .items-table td {
            padding: 1.5mm 0.5mm;
            font-size: 8pt;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        .items-table th { 
            font-weight: bold; 
            text-align: right;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .items-table .text-left { text-align: left; }
        .items-table .text-center { text-align: center; }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1mm;
        }
        .summary-table td {
            padding: 1mm 0;
            font-size: 9pt;
        }
        .summary-label { text-align: right; padding-right: 3mm; width: 70%; }
        .summary-value { text-align: right; width: 30%; font-weight: bold; }
        .currency-symbol { font-size: 7pt; }
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
        .item-returned { font-size: 7pt; display: block; color: #d32f2f; font-weight: bold; margin-top: 0.5mm; }
        .item-exchanged { font-size: 7pt; display: block; color: #856404; font-weight: bold; margin-top: 0.5mm; }
        .adjustment-row { border-bottom: 1px dotted #ccc; padding: 1mm 0; }
        .refund-amount { font-weight: bold; color: #d32f2f; }

        /* QR Code integration */
        .qr-code {
            margin-top: 5mm;
            margin-bottom: 2mm;
        }

        /* PDF specific layout */
        @if(isset($action) && $action == 'download')
        body { width: 80mm; }
        .receipt-container { width: 80mm; }
        @endif

        /* Browser Print specific */
        @media print {
            body { width: 80mm; }
            .receipt-container { width: 80mm; padding: 2mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header text-center">
            <span class="company-name">{{ $general_settings->site_title }}</span>
            <span class="company-info">{{ $general_settings->contact_address }}</span>
            <span class="company-info">Tel: {{ $general_settings->contact_phone }}</span>
            <span class="invoice-title">IN POS SALE INVOICE COPY</span>
        </div>

        @php
            $totalPosDiscount = $pos->discount ?? 0;
        @endphp

        <!-- Sale Details -->
        <div class="sale-info">
            <table class="info-table">
                <tr>
                    <td class="info-label">Date:</td>
                    <td>
                        @php
                            $tz = config('app.timezone', 'Asia/Dhaka');
                            $createdAtLocal = $pos->created_at ? \Carbon\Carbon::parse($pos->created_at)->timezone($tz) : null;
                            if ($pos->sale_date && $createdAtLocal) {
                                $displayDate = \Carbon\Carbon::parse($pos->sale_date)->format('d-m-Y') . ' | ' . $createdAtLocal->format('h:i A');
                            } elseif ($createdAtLocal) {
                                $displayDate = $createdAtLocal->format('d-m-Y | h:i A');
                            } elseif ($pos->sale_date) {
                                $displayDate = \Carbon\Carbon::parse($pos->sale_date)->format('d-m-Y');
                            } else {
                                $displayDate = \Carbon\Carbon::now($tz)->format('d-m-Y | h:i A');
                            }
                        @endphp
                        {{ $displayDate }}
                    </td>
                </tr>
                <tr>
                    <td class="info-label">Invoice No.:</td>
                    <td>{{ $pos->sale_number }}</td>
                </tr>
                <tr>
                    <td class="info-label">Payment Mode:</td>
                    <td>
                        @php
                            $pm = 'Cash In Hand';
                            if($pos->invoice && $pos->invoice->payments->count() > 0) {
                                $pm = $pos->invoice->payments->first()->payment_method ?? 'Cash In Hand';
                            }
                        @endphp
                        {{ $pm }}
                        @if($pos->branch)
                            ({{ strtoupper($pos->branch->name) }})
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="info-label">Customer:</td>
                    <td>{{ $pos->customer ? $pos->customer->name : 'Admin' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Mobile:</td>
                    <td>{{ $pos->customer ? $pos->customer->phone : '' }}</td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left">Item Description</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>VAT</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pos->items as $item)
                @if($item->parent_item_id !== null) @continue @endif
                @php
                    $isCombo = $item->product && $item->product->isCombo();
                    $comboOriginalVal = $isCombo ? ($item->product->combo_original_price * $item->quantity) : 0;
                    $comboSavings = $isCombo ? max(0, $comboOriginalVal - $item->total_price) : 0;
                    $itemRegRetQty = $item->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') !== 'exchange')->sum('returned_qty');
                    $itemExchRetQty = $item->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') === 'exchange')->sum('returned_qty');
                @endphp
                <tr>
                    <td class="text-left">
                        {{ $item->product->name }} <small>#{{ $item->product->style_number ?? $item->product->sku }}</small>
                        @if($isCombo)
                            <span class="item-sku" style="color: #2e7d32; font-weight: bold;">[COMBO OFFER PACKAGE]</span>
                            <span class="item-sku">Regular Value: ৳{{ number_format($comboOriginalVal, 1) }}</span>
                            @if($comboSavings > 0)
                                <span class="item-sku" style="color: #c62828;">(Combo Savings: ৳{{ number_format($comboSavings, 1) }})</span>
                            @endif
                        @elseif($item->variation)
                            @php
                                $vals = [];
                                foreach($item->variation->attributeValues as $val) {
                                    $vals[] = $val->value;
                                }
                                echo '<span class="item-sku">[' . implode(', ', $vals) . ']</span>';
                            @endphp
                        @endif
                        @if(!$isCombo && $item->product && $item->product->price > $item->unit_price)
                            <span class="item-sku">Reg. Price: ৳{{ number_format($item->product->price, 1) }} | Disc. Price: ৳{{ number_format($item->unit_price, 1) }}</span>
                        @endif
                        <span class="item-sku">SKU: {{ $item->product->sku }}</span>
                        @if($itemRegRetQty > 0)
                            <span class="item-returned">↳ Returned: {{ number_format($itemRegRetQty, 0) }} unit(s)</span>
                        @endif
                        @if($itemExchRetQty > 0)
                            <span class="item-exchanged">↳ Exchanged: {{ number_format($itemExchRetQty, 0) }} unit(s)</span>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td>
                        @if($isCombo)
                            <span class="currency-symbol">৳</span>{{ number_format($item->unit_price, 1) }}
                            <small class="item-sku" style="font-size: 6pt;">(Combo Price)</small>
                        @else
                            <span class="currency-symbol">৳</span>{{ number_format($item->unit_price, 1) }}
                        @endif
                    </td>
                    <td>
                        @php
                            $itemVat = $item->total_price * ($pos->vat_rate / 100);
                        @endphp
                        <span class="currency-symbol">৳</span>{{ number_format($itemVat, 1) }}
                    </td>
                    <td><span class="currency-symbol">৳</span>{{ number_format($item->total_price + $itemVat, 1) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Table -->
        <table class="summary-table">
            @php
                // Calculate original gross from items (sum of quantity * unit_price)
                $originalGross = $pos->items->sum(fn($i) => $i->quantity * $i->unit_price);
                // Use VAT from pos if available, otherwise from invoice tax (avoid double counting)
                $vatAmount = $pos->vat_amount ?? $pos->invoice->tax ?? 0;
                $originalTotal = $originalGross - ($totalPosDiscount ?? 0) + ($pos->delivery ?? 0) + $vatAmount - ($pos->exchange_amount ?? 0);
                $returnAdjustment = $pos->invoice ? (($pos->total_amount ?? 0) - ($pos->invoice->total_amount ?? 0)) : 0;
            @endphp
            <tr>
                <td class="summary-label">Sub Total</td>
                <td class="summary-value"><span class="currency-symbol">৳</span>{{ number_format($pos->sub_total ?? 0, 2) }}</td>
            </tr>

            @if($totalPosDiscount > 0)
            @php
                $effectivePercent = ($pos->sub_total > 0) ? round(($totalPosDiscount / $pos->sub_total) * 100, 1) : 0;
            @endphp
            <tr>
                <td class="summary-label">
                    Discount 
                    @if($effectivePercent > 0)
                        <small>({{ $effectivePercent }}%)</small>
                    @endif
                </td>
                <td class="summary-value" style="color: #d32f2f;">-<span class="currency-symbol">৳</span>{{ number_format($totalPosDiscount ?? 0, 2) }}</td>
            </tr>
            @endif

            @if($pos->delivery > 0)
            <tr>
                <td class="summary-label">Delivery</td>
                <td class="summary-value"><span class="currency-symbol">৳</span>{{ number_format($pos->delivery ?? 0, 2) }}</td>
            </tr>
            @endif

            @if($pos->vat_amount > 0)
            <tr>
                <td class="summary-label">VAT @if($pos->vat_rate > 0) <small>({{ $pos->vat_rate }}%)</small> @endif</td>
                <td class="summary-value"><span class="currency-symbol">৳</span>{{ number_format($pos->vat_amount, 2) }}</td>
            </tr>
            @elseif(($pos->invoice->tax ?? 0) > 0)
            <tr>
                <td class="summary-label">VAT & Tax</td>
                <td class="summary-value"><span class="currency-symbol">৳</span>{{ number_format($pos->invoice->tax ?? 0, 2) }}</td>
            </tr>
            @endif

            @if(($pos->exchange_amount ?? 0) > 0)
            <tr>
                <td class="summary-label">Exchange Credit</td>
                <td class="summary-value" style="color: #d32f2f;">-<span class="currency-symbol">৳</span>{{ number_format($pos->exchange_amount, 2) }}</td>
            </tr>
            @endif

            <tr class="total-row">
                <td class="summary-label bold" style="font-size: 10pt;">Original Total</td>
                <td class="summary-value bold" style="font-size: 11pt;"><span class="currency-symbol">৳</span>{{ number_format($originalTotal, 2) }}</td>
            </tr>

            @if($returnAdjustment > 0)
            <tr>
                <td class="summary-label">Less Return</td>
                <td class="summary-value" style="color: #d32f2f;">-<span class="currency-symbol">৳</span>{{ number_format($returnAdjustment, 2) }}</td>
            </tr>
            @endif

            <tr class="total-row">
                <td class="summary-label bold" style="font-size: 10pt;">NET PAYABLE</td>
                <td class="summary-value bold" style="font-size: 12pt;"><span class="currency-symbol">৳</span>{{ number_format($pos->invoice->total_amount ?? $pos->total_amount, 2) }}</td>
            </tr>
            <tr style="border-top: 1px solid #eee;">
                <td class="summary-label" style="padding-top: 2mm;">Cash Received</td>
                <td class="summary-value" style="padding-top: 2mm;"><span class="currency-symbol">৳</span>{{ number_format($pos->invoice->paid_amount ?? 0, 2) }}</td>
            </tr>
            @php
                $paid = $pos->invoice->paid_amount ?? 0;
                $total = $pos->invoice->total_amount ?? $pos->total_amount;
                $diff = $paid - $total;
            @endphp
            @if($diff > 0)
            <tr>
                <td class="summary-label">Change Return</td>
                <td class="summary-value"><span class="currency-symbol">৳</span>{{ number_format($diff, 2) }}</td>
            </tr>
            @elseif($diff < 0)
            <tr>
                <td class="summary-label">Due Amount</td>
                <td class="summary-value" style="color: #d32f2f;"><span class="currency-symbol">৳</span>{{ number_format(abs($diff), 2) }}</td>
            </tr>
            @endif
        </table>

        <!-- Adjustment History (Returns & Exchanges) -->
        @if((isset($saleReturns) && $saleReturns->count() > 0) || (isset($exchanges) && $exchanges->count() > 0))
        <div style="margin-top: 4mm; border-top: 1px dashed #000; padding-top: 2mm;">
            <span class="bold" style="font-size: 8pt; display: block; text-transform: uppercase; margin-bottom: 1.5mm;">Adjustment History:</span>
            
            @if(isset($saleReturns))
                @foreach($saleReturns as $return)
                    <div style="font-size: 8pt; margin-bottom: 1.5mm;">
                        <span class="bold">Return #SR-{{ str_pad($return->id, 5, '0', STR_PAD_LEFT) }}</span> 
                        <span style="font-size: 7pt; color: #555;">({{ \Carbon\Carbon::parse($return->return_date)->format('d-m-Y') }})</span>
                        <table style="width: 100%; font-size: 8pt; margin-top: 0.5mm; border-collapse: collapse;">
                            @foreach($return->items as $rItem)
                                <tr class="adjustment-row">
                                    <td style="text-align: left; padding: 0.5mm 0;">• {{ $rItem->product->name }} x{{ number_format($rItem->returned_qty, 0) }}</td>
                                    <td class="refund-amount" style="text-align: right; padding: 0.5mm 0;">-৳{{ number_format($rItem->total_price, 1) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach
            @endif

            @if(isset($exchanges))
                @foreach($exchanges as $exchange)
                    <div style="font-size: 8pt; margin-bottom: 1.5mm;">
                        <span class="bold">Exchange {{ $exchange->exchange_number }}</span>
                        <span style="font-size: 7pt; color: #555;">({{ \Carbon\Carbon::parse($exchange->exchange_date)->format('d-m-Y') }})</span>
                        <table style="width: 100%; font-size: 8pt; margin-top: 0.5mm; border-collapse: collapse;">
                            {{-- Returned Items --}}
                            @foreach($exchange->returnedItems as $rItem)
                                <tr class="adjustment-row">
                                    <td style="text-align: left; color: #d32f2f; padding: 0.5mm 0;">↳ {{ $rItem->product->name }} x{{ number_format($rItem->quantity, 0) }}</td>
                                    <td style="text-align: right; color: #d32f2f; padding: 0.5mm 0;">-৳{{ number_format($rItem->total_price, 1) }}</td>
                                </tr>
                            @endforeach
                            {{-- New Items --}}
                            @foreach($exchange->newItems as $nItem)
                                <tr class="adjustment-row">
                                    <td style="text-align: left; color: #2e7d32; padding: 0.5mm 0;">+ {{ $nItem->product->name }} x{{ number_format($nItem->quantity, 0) }}</td>
                                    <td style="text-align: right; color: #2e7d32; padding: 0.5mm 0;">+৳{{ number_format($nItem->total_price, 1) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach
            @endif
        </div>
        @endif

        <!-- Footer -->
        <div class="footer text-center">
            @if(isset($qrCodeSvg))
                <div class="qr-code">
                    {!! $qrCodeSvg !!}
                    <div style="font-size: 7pt; margin-top: 2mm;">Scan to verify invoice</div>
                </div>
            @endif
            <div class="bold">Thank you for shopping with us!</div>
            <div>Powered by {{ $general_settings->site_title }}</div>
        </div>
    </div>

    @if(!isset($action) || $action == 'print')
    <script>
        window.onload = () => {
            window.print();
            setTimeout(() => {
                window.close();
            }, 700);
        }
    </script>
    @endif
</body>
</html>
