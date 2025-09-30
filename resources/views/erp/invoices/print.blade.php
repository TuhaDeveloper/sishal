<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice Print</title>
    <style>
        *{
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            color: #222;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .invoice-box {
            max-width: 800px;
            margin: 20px auto;
            border: 1px solid #222;
            padding: 24px 32px;
            background: #fff;
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .header .logo {
            width: 120px;
        }

        .header .company-info {
            flex: 1;
            margin-left: 16px;
        }

        .header .invoice-title {
            text-align: right;
            color: #007bff;
            font-size: 2rem;
            font-weight: bold;
            font-weight: 400;
        }

        .barcode {
            text-align: right;
            margin-top: 8px;
        }

        .barcode img {
            height: 40px;
        }

        .info-table,
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .info-table td {
            padding: 2px 0;
            font-size: 15px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #222;
            padding: 6px 8px;
            text-align: left;
            font-size: 15px;
        }

        .items-table th {
            background: #f5f5f5;
            text-align: center;
            font-size: 10px;
        }

        .items-table td {
            text-align: center;
            font-size: 10px;
        }

        .summary-table {
            width: 40%;
            float: right;
            margin-top: 16px;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 8px;
            font-size: 10px;
        }

        .summary-table tr td:first-child {
            text-align: right;
        }

        .summary-table tr td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .inword,
        .bill-note,
        .footer,
        .service-note {
            margin-top: 16px;
            font-size: 15px;
        }

        .footer {
            font-size: 13px;
            color: #444;
            margin-top: 24px;
        }

        .sign-row {
            margin-top: 48px;
            display: flex;
            justify-content: space-between;
            font-size: 15px;
        }

        .sign-row .sales-person {
            text-align: right;
        }

        @media print {
            .invoice-box {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }

            .header,
            .barcode,
            .info-table,
            .items-table,
            .summary-table,
            .inword,
            .bill-note,
            .footer,
            .sign-row {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="header">
            <div>
                <img src="{{ asset($general_settings->site_logo) }}" alt="{{ $general_settings->site_title }}"
                    class="logo" style="width: 60px;">
            </div>
            <div class="company-info">
                <div style="font-size: 22px; font-weight: bold; text-transform: uppercase;">
                    {{ $general_settings->site_title }}</div>
                <div style="font-size: 13px; color: #007bff;">SK Corporation is a trusted name in pure water.</div>
            </div>
            <div>
                <div class="invoice-title">INVOICE</div>

            </div>
        </div>
        <div class="header">
            <div style="font-size: 14px; margin-top: 4px; max-width: 300px;">
                {{ $general_settings->contact_address }} <br>
                Phone: <a style="color: black; text-decoration: none;"
                    href="callto:{{ $general_settings->contact_phone }}">{{ $general_settings->contact_phone }}</a> <br>
                Email: <a style="color: black; text-decoration: none;"
                    href="mailto:{{ $general_settings->contact_email }}">{{ $general_settings->contact_email }}</a> <br>
                Website: <a style="color: black; text-decoration: none;" target="_blank"
                    href="https://www.skcorporationbd.com">www.skcorporationbd.com</a>
            </div>
            <div class="barcode">
                {!! $qrCodeSvg !!}
            </div>
        </div>
        <hr style="margin: 16px 0;">
        <table class="info-table">
            <tr>
                <td><strong>Customer Info.</strong></td>
                <td style="text-align:right;"><strong>INVOICE #</strong> B SKC{{$invoice->invoice_number}}</td>
            </tr>
            <tr>
                <td>{{@$invoice->customer->name}}</td>
                <td style="text-align:right;">DATE:
                    {{$invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d M,Y, h:i A') : ''}}
                </td>
            </tr>
            <tr>
                <td>Phone: {{@$invoice->customer->phone}}</td>
            </tr>
            @php
                $addressParts = [];
                if (!empty($invoice->invoiceAddress->billing_address_1))
                    $addressParts[] = $invoice->invoiceAddress->billing_address_1;
                if (!empty($invoice->invoiceAddress->billing_address_2))
                    $addressParts[] = $invoice->invoiceAddress->billing_address_2;
                if (!empty($invoice->invoiceAddress->billing_city))
                    $addressParts[] = $invoice->invoiceAddress->billing_city;
                if (!empty($invoice->invoiceAddress->billing_state))
                    $addressParts[] = $invoice->invoiceAddress->billing_state;
                if (!empty($invoice->invoiceAddress->billing_zip_code))
                    $addressParts[] = $invoice->invoiceAddress->billing_zip_code;
            @endphp
            <tr>
                <td>Address: {{ implode(', ', $addressParts) }}</td>
            </tr>
        </table>
        <table class="items-table" style="margin-top: 18px;">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>DESCRIPTION</th>
                    <th>QUANTITY</th>
                    <th>UNIT</th>
                    <th>PRICE</th>
                    <th>DISCOUNT</th>
                    <th>AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                @if(@$item->product->type == 'product')
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td style="text-align:left;">{{@$item->product->name}}</td>
                        <td>{{number_format($item->quantity, 0)}}</td>
                        <td>PCS</td>
                        <td>{{@$item->unit_price}}৳</td>
                        @php
                            $originalPrice = @$item->product->discount ?? @$item->product->price;
                            $unitPrice = @$item->unit_price;
                            $discountPercent = ($originalPrice && $originalPrice > 0 && $unitPrice < $originalPrice)
                                ? number_format((($originalPrice - $unitPrice) / $originalPrice) * 100, 2)
                                : '0.00';
                        @endphp
                        <td>{{ $discountPercent }}%</td>
                        <td>{{@$item->total_price}}৳</td>
                    </tr>
                @endif
                @endforeach
                
                <tr>
                    <td>{{$invoice->items->count() + 1}}</td>
                    <td style="text-align:left;">Service Charge {{$invoice->service ? ': ('.@$invoice->service->productService->name.')' : ''}}</td>
                    <td>1</td>
                    <td>PCS</td>
                    <td> {{$invoice->service ? @$invoice->service->service_fee : ''}}৳</td>
                    <td>-</td>
                    <td>{{$invoice->service ? @$invoice->service->service_fee : ''}}৳</td>
                </tr>

                @if(@$invoice->service->travel_fee > 0)
                <tr>
                    <td>{{$invoice->items->count() + 2}}</td>
                    <td style="text-align:left;">Travel fee</td>
                    <td>1</td>
                    <td>PCS</td>
                    <td> {{$invoice->service ? @$invoice->service->travel_fee : ''}}৳</td>
                    <td>-</td>
                    <td>{{$invoice->service ? @$invoice->service->travel_fee : ''}}৳</td>
                </tr>
                @endif
            </tbody>
        </table>
        <table class="summary-table">
            <tr>
                <td>SUB TOTAL :</td>
                <td>{{$invoice->subtotal}}৳</td>
            </tr>
            <tr>
                <td>DIS. AMOUNT :</td>
                <td>{{$invoice->discount_apply}}৳</td>
            </tr>
            <tr>
                <td>Vat :</td>
                <td>{{$invoice->tax}}৳</td>
            </tr>
            <tr>
                <td>NET BILL :</td>
                <td>{{$invoice->total_amount}}৳</td>
            </tr>
            <tr>
                <td>ADVANCE :</td>
                <td>{{$invoice->paid_amount}}৳</td>
            </tr>
            <tr>
                <td>DUE :</td>
                <td>{{$invoice->due_amount}}৳</td>
            </tr>
        </table>
        <div style="clear:both;"></div>
        <div class="inword" style="font-size: 12px;"><strong>IN-WORD :</strong></div>
        <div class="bill-note" style="font-size: 12px;"><strong>BILL NOTE :</strong> {{$invoice->note}}</div>
        <div class="service-note" style="margin-top: 10px; font-size: 12px; font-weight: 700;">
            Service Charge 300 Taka (In Dhaka City)..... Service Charge 500 Taka (Out of Dhaka City)........ For Any
            Service Needs, Call : +8801958394757<br>
            বিশেষ দ্রষ্টব্য: সকল যাতায়াত খরচ কাস্টমার বহন করবে ।
        </div>
        <br>
        <p style="margin-bottom: 0; font-size: 12px;"><b>- Payment Method :</b> {{@$invoice->payments->first()->payment_method}}</p>
        <div class="footer">
            {!! $invoice->footer_text ?? $template->footer_note !!}
        </div>
        <div class="sign-row">
            <div style="border-top: 1px dotted black; padding-top: 10px; font-size: 12px;">
                Received By
            </div>
            <div style="border-top: 1px dotted black; padding-top: 10px; font-size: 12px;">
                Sales Person : {{ @$invoice->salesman->first_name }} {{ @$invoice->salesman->last_name }}
            </div>
        </div>
    </div>
</body>

</html>



@if($action == 'print' || (isset($action) && $action == 'print'))
<script>
    console.log('print');
    
    window.onload = function() {
        window.print();
    };
    window.onafterprint = function() {
        window.close();
    };
</script>
@endif

@if($action == 'download' || (isset($action) && $action == 'download'))
<script>
    window.onload = function() {
        window.print();
    };
    window.onafterprint = function() {
        window.close();
    };
</script>
@endif