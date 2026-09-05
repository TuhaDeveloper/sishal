<!DOCTYPE html>
<html>
<head>
    <title>POS Sales Report</title>
    <style>
        body { font-family: sans-serif; font-size: 8px; margin: 0; padding: 5px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; color: #2d5a4c; font-size: 14px; }
        .header p { margin: 2px 0; color: #666; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; table-layout: fixed; }
        th, td { border: 1px solid #ddd; padding: 3px 2px; text-align: left; word-wrap: break-word; }
        th { background: #2d5a4c; color: #fff; font-size: 6px; text-transform: uppercase; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        @page { margin: 0.3cm; }
    </style>
</head>
<body>
    <div class="header">
        <h2>POS Sales Report</h2>
        <p>Report Type: {{ ucfirst($reportType) }} | Period: 
            {{ $startDate ? $startDate->format('d/m/Y') : 'All Time' }} - {{ $endDate ? $endDate->format('d/m/Y') : 'Now' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15px;">SN</th>
                <th style="width: 35px;">Invoice</th>
                <th style="width: 35px;">Date</th>
                <th style="width: 40px;">Customer</th>
                <th style="width: 45px;">Product/Style</th>
                <th style="width: 25px;">Price</th>
                <th style="width: 15px;">S-Q</th>
                <th style="width: 20px;">T S-Q</th>
                <th style="width: 30px;">S-A</th>
                <th style="width: 35px;">T S-A</th>
                <th style="width: 15px;">SR-Q</th>
                <th style="width: 20px;">T SR-Q</th>
                <th style="width: 25px;">SR-A</th>
                <th style="width: 30px;">T SR-A</th>
                <th style="width: 15px;">AS-Q</th>
                <th style="width: 20px;">T AS-Q</th>
                <th style="width: 25px;">Del.</th>
                <th style="width: 25px;">VAT</th>
                <th style="width: 25px;">Disc.</th>
                <th style="width: 25px;">Exch.</th>
                <th style="width: 25px;">Refund</th>
                <th style="width: 40px;">Gross Amt (with vat)</th>
                <th style="width: 35px;">Net Amt (without vat)</th>
                <th style="width: 35px;">Recvd Amt</th>
                <th style="width: 35px;">Due Amt</th>
            </tr>
        </thead>
        <tbody>
            @php
                $gSellQty = 0; $gTSQty = 0; $gSellAmt = 0; $gTSAmt = 0;
                $gRetQty = 0; $gTSRQty = 0; $gRetAmt = 0; $gTSRAmt = 0;
                $gActQty = 0; $gTASQty = 0; $gActAmt = 0;
                $gDelivery = 0; $gVat = 0; $gDiscount = 0; $gExchange = 0; $gRefund = 0;
                $gFinalTotal = 0; $gReceived = 0; $gDue = 0;
            @endphp
            @foreach($items as $index => $item)
                @php
                    $sale = $item->pos;
                    $invoice = $sale->invoice;
                    $product = $item->product;
                    $variation = $item->variation;
                    
                    $color = '-'; $size = '-';
                    if ($variation && $variation->attributeValues) {
                        foreach($variation->attributeValues as $val) {
                            $attrName = strtolower($val->attribute->name ?? '');
                            if (str_contains($attrName, 'color')) $color = $val->value;
                            elseif (str_contains($attrName, 'size')) $size = $val->value;
                        }
                    }

                    $isFirst = ($index == 0 || $items[$index-1]->pos_sale_id != $item->pos_sale_id);
                    
                    // Item Level
                    $isCombo = ($product?->type === 'combo');
                    $childItems = $item->childItems ?? collect();
                    $comboItemsQty = $childItems->sum('quantity');
                    $physicalQty = $isCombo ? ($comboItemsQty > 0 ? $comboItemsQty : $item->quantity) : $item->quantity;

                    $itemGrossAmt = $item->quantity * $item->unit_price;
                    $regRetItems = $item->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') !== 'exchange');
                    $exchRetItems = $item->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') === 'exchange');
                    $regRetQty = $regRetItems->sum('returned_qty');
                    $regRetAmt = $regRetItems->sum('total_price');
                    $exchRetQty = $exchRetItems->sum('returned_qty');
                    $exchRetAmt = $exchRetItems->sum('total_price');
                    $retQty = $regRetQty + $exchRetQty;
                    $retAmt = $regRetAmt + $exchRetAmt;
                    $actualQty = $physicalQty - $retQty;

                    $gSellQty += $item->quantity; 
                    $gSellAmt += $itemGrossAmt;
                    $gRetQty += $retQty; 
                    $gRetAmt += $retAmt;
                    $gActQty += $actualQty;

                    // Invoice Level
                    $invTSQty = '-'; $invTSAmt = '-'; $invTSRQty = '-'; $invTSRAmt = '-';
                    $invTASQty = '-'; $invNetTotal = '-'; $invActualAmt = '-';
                    $invDel = '-'; $invVat = '-'; $invDisc = '-'; $invExch = '-';
                    $invPaid = '-'; $invDue = '-';

                    if ($isFirst) {
                        $invItems = $sale->items->whereNull('parent_item_id');
                        $i_TotalQty = $invItems->sum('quantity');
                        $i_GrossAmt = $invItems->sum(fn($i) => $i->quantity * $i->unit_price);
                        
                        $i_PhysicalQty = 0;
                        foreach ($invItems as $invIt) {
                            $itIsCombo = ($invIt->product?->type === 'combo');
                            $itChilds = $invIt->childItems ?? collect();
                            $itComboChildsQty = $itChilds->sum('quantity');
                            $itPhys = $itIsCombo ? ($itComboChildsQty > 0 ? $itComboChildsQty : $invIt->quantity) : $invIt->quantity;
                            $i_PhysicalQty += $itPhys;
                        }

                        $i_RetQty = $invItems->sum(fn($i) => $i->returnItems->sum('returned_qty'));
                        $i_RetAmt = $invItems->sum(fn($i) => $i->returnItems->sum('total_price'));
                        $i_ActualQty = $i_PhysicalQty - $i_RetQty;
                        
                        $i_TotalSalesAmt = $i_GrossAmt;
                        $i_NetTotalValue = $i_TotalSalesAmt - $sale->discount;
                        $i_ActualAmtValue = $i_NetTotalValue - $i_RetAmt;

                        $invTSQty = $i_TotalQty;
                        $invTSAmt = number_format($i_TotalSalesAmt, 2);
                        $invTSRQty = $i_RetQty;
                        $invTSRAmt = number_format($i_RetAmt, 2);
                        $invTASQty = $i_ActualQty;
                        $invNetTotal = number_format($i_NetTotalValue, 2);
                        $invActualAmt = number_format($i_ActualAmtValue, 2);
                        $invDel = number_format($sale->delivery, 2);
                        $invVat = number_format($sale->vat_amount ?? 0, 2);
                        $invDisc = number_format($sale->discount, 2);
                        $invExch = number_format($sale->exchange_amount ?? 0, 2);
                        $invPaid = number_format($invoice->paid_amount ?? 0, 2);
                        $invDue = number_format($invoice->due_amount ?? 0, 2);

                        // Accumulate Globals
                        $gTSQty += $i_TotalQty;
                        $gTSAmt += $i_TotalSalesAmt;
                        $gTSRQty += $i_RetQty;
                        $gTSRAmt += $i_RetAmt;
                        $gTASQty += $i_ActualQty;
                        $gDelivery += $sale->delivery;
                        $gVat += ($sale->vat_amount ?? 0);
                        $gDiscount += $sale->discount;
                        $gExchange += ($sale->exchange_amount ?? 0);
                        $gRefund += ($sale->refund_amount ?? 0);
                        $gActAmt += $i_ActualAmtValue;
                        $gFinalTotal += $i_NetTotalValue;
                        $gReceived += ($invoice->paid_amount ?? 0);
                        $gDue += ($invoice->due_amount ?? 0);
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="fw-bold">{{ $sale->sale_number ?? '-' }}</td>
                    <td class="text-center">{{ $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d/m/y') : '-' }}</td>
                    <td>{{ substr($sale->customer->name ?? 'Walk-in', 0, 10) }}</td>
                    <td>
                        {{ substr($product->name ?? '-', 0, 15) }}
                        @if(($product->type ?? '') === 'combo')
                            <br><strong style="color: #0284c7; font-size: 5px;">[COMBO: {{ $item->childItems?->sum('quantity') ?: $item->quantity }} pcs]</strong>
                        @endif
                        <br><small>{{ $product->style_number ?? '-' }}</small>
                    </td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center fw-bold">{{ $invTSQty }}</td>
                    <td class="text-right">{{ number_format($itemGrossAmt, 2) }}</td>
                    <td class="text-right fw-bold">{{ $invTSAmt }}</td>
                    <td class="text-center">{{ $retQty ?: 0 }}</td>
                    <td class="text-center fw-bold">{{ $invTSRQty }}</td>
                    <td class="text-right">{{ number_format($retAmt, 2) }}</td>
                    <td class="text-right fw-bold">{{ $invTSRAmt }}</td>
                    <td class="text-center">{{ $actualQty }}</td>
                    <td class="text-center fw-bold">{{ $invTASQty }}</td>
                    <td class="text-right">{{ $invDel }}</td>
                    <td class="text-right">{{ $invVat }}</td>
                    <td class="text-right">{{ $invDisc }}</td>
                    <td class="text-right">{{ $invExch }}</td>
                    <td class="text-right">{{ $isFirst ? number_format($sale->refund_amount ?? 0, 2) : '' }}</td>
                    <td class="text-right fw-bold">{{ $invNetTotal }}</td>
                    <td class="text-right fw-bold">{{ $invActualAmt }}</td>
                    <td class="text-right">{{ $invPaid }}</td>
                    <td class="text-right">{{ $invDue }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background: #f8f9fa;">
                <td colspan="6" class="text-right">TOTAL</td>
                <td class="text-center">{{ $gSellQty }}</td>
                <td class="text-center">{{ $gTSQty }}</td>
                <td class="text-right">{{ number_format($gSellAmt, 2) }}</td>
                <td class="text-right">{{ number_format($gTSAmt, 2) }}</td>
                <td class="text-center">{{ $gRetQty }}</td>
                <td class="text-center">{{ $gTSRQty }}</td>
                <td class="text-right">{{ number_format($gRetAmt, 2) }}</td>
                <td class="text-right">{{ number_format($gTSRAmt, 2) }}</td>
                <td class="text-center">{{ $gActQty }}</td>
                <td class="text-center">{{ $gTASQty }}</td>
                <td class="text-right">{{ number_format($gDelivery, 2) }}</td>
                <td class="text-right">{{ number_format($gVat, 2) }}</td>
                <td class="text-right">{{ number_format($gDiscount, 2) }}</td>
                <td class="text-right">{{ number_format($gExchange, 2) }}</td>
                <td class="text-right">{{ number_format($gRefund, 2) }}</td>
                <td class="text-right">{{ number_format($gFinalTotal, 2) }}</td>
                <td class="text-right">{{ number_format($gActAmt, 2) }}</td>
                <td class="text-right">{{ number_format($gReceived, 2) }}</td>
                <td class="text-right">{{ number_format($gDue, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
