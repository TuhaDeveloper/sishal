<!DOCTYPE html>
<html>
<head>
    <title>Detailed Inventory Movement Report</title>
    <style>
        @page { size: A4 landscape; margin: 20px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 8px; color: #333; line-height: 1.2; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 4px 2px; text-align: center; }
        th { background-color: #2d5a4c; color: white; text-transform: uppercase; font-size: 7px; }
        .header { text-align: center; margin-bottom: 10px; }
        .text-left { text-align: left; padding-left: 5px; }
        .text-right { text-align: right; padding-right: 5px; }
        .fw-bold { font-weight: bold; }
        .bg-light { background-color: #f9f9f9; }
        .footer { position: fixed; bottom: -10px; width: 100%; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin-bottom: 5px; color: #2d5a4c;">Stock Movement Details</h2>
        <p style="margin-top: 0; color: #666; font-size: 9px;">
            Inventory Report | Generated: {{ date('d M, Y h:i A') }}
        </p>
    </div>

    @php
        $sl = 1;
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width: 2%;">No.</th>
                <th style="width: 11%;">Product Name</th>
                <th style="width: 7%;">Style</th>
                <th style="width: 4%;">Color</th>
                <th style="width: 3%;">Size</th>
                <th style="width: 7%;">Outlet</th>
                <th style="width: 4%;">Open</th>
                <th style="width: 3%;">P-Qnt</th>
                <th style="width: 3%;">PR-Q</th>
                <th style="width: 3%; color: #00e5ff;">Net-P</th>
                <th style="width: 3%;">S-Qnt</th>
                <th style="width: 3%;">SR-Q</th>
                <th style="width: 3%; color: #00e5ff;">Net-S</th>
                <th style="width: 4%;">Adj</th>
                <th style="width: 4%;">E-To</th>
                <th style="width: 4%;">E-Fr</th>
                <th style="width: 4%;">T-Fr</th>
                <th style="width: 4%;">T-To</th>
                <th style="width: 5%;">STOCK</th>
                <th style="width: 8%;">Cost Val</th>
                <th style="width: 8%;">Rev</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                @php
                    $productLocations = [];
                    foreach($product->branchStock as $bs) { $productLocations['branch_' . $bs->branch_id] = ['type' => 'branch', 'id' => $bs->branch_id, 'name' => $bs->branch->name ?? 'Unknown']; }
                    foreach($product->warehouseStock as $ws) { $productLocations['warehouse_' . $ws->warehouse_id] = ['type' => 'warehouse', 'id' => $ws->warehouse_id, 'name' => $ws->warehouse->name ?? 'Unknown']; }
                    foreach($product->variationStocks as $vs) {
                        $lkey = $vs->branch_id ? 'branch_' . $vs->branch_id : 'warehouse_' . $vs->warehouse_id;
                        if(!isset($productLocations[$lkey])) {
                            $productLocations[$lkey] = ['type' => $vs->branch_id ? 'branch' : 'warehouse', 'id' => $vs->branch_id ?: $vs->warehouse_id, 'name' => ($vs->branch->name ?? $vs->warehouse->name) ?? 'Unknown'];
                        }
                    }
                @endphp

                @foreach ($productLocations as $location)
                    @php
                        $lid = $location['id']; $ltype = $location['type'];
                        $isProductSummary = (request('group_by') === 'product');
                        $vars = ($product->has_variations && !$isProductSummary) ? $product->variations : [null];

                        // PRE-AGGREGATE MOVEMENTS FOR O(1) LOOKUP PERFORMANCE
                        if (!isset($product->agg)) {
                            $agg = [ 'p' => [], 'pr' => [], 's' => [], 'sr' => [], 'adj' => [], 'et' => [], 'ef' => [], 'tf' => [], 'tt' => [], 'rev' => [] ];
                            
                            foreach($product->purchaseItems as $m) {
                                if($m->purchase) {
                                    $k = ($m->variation_id ?: 0) . '_' . $m->purchase->ship_location_type . '_' . $m->purchase->location_id;
                                    $agg['p'][$k] = ($agg['p'][$k] ?? 0) + $m->quantity;
                                }
                            }
                            foreach($product->purchaseReturnItems as $m) {
                                $k = ($m->variation_id ?: 0) . '_' . $m->return_from_type . '_' . $m->return_from_id;
                                $agg['pr'][$k] = ($agg['pr'][$k] ?? 0) + $m->returned_qty;
                            }
                            foreach($product->saleItems as $m) {
                                if($m->pos) {
                                    $k = ($m->variation_id ?: 0) . '_branch_' . $m->pos->branch_id;
                                    if ($m->pos->sale_type != 'exchange') $agg['s'][$k] = ($agg['s'][$k] ?? 0) + $m->quantity;
                                    else $agg['et'][$k] = ($agg['et'][$k] ?? 0) + $m->quantity;
                                    $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) + $m->total_price;
                                }
                            }
                            foreach($product->invoiceItems as $m) {
                                $k = ($m->variation_id ?: 0) . '_warehouse_0';
                                $agg['s'][$k] = ($agg['s'][$k] ?? 0) + $m->quantity;
                                $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) + $m->total_price;
                            }
                            foreach($product->orderItems as $m) {
                                if($m->order) {
                                    $k = ($m->variation_id ?: 0) . '_branch_' . $m->order->branch_id;
                                    $agg['s'][$k] = ($agg['s'][$k] ?? 0) + $m->quantity;
                                    $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) + $m->total_price;
                                }
                            }
                            foreach($product->saleReturnItems as $m) {
                                if($m->saleReturn) {
                                    $k = ($m->variation_id ?: 0) . '_' . $m->saleReturn->return_to_type . '_' . $m->saleReturn->return_to_id;
                                    if ($m->saleReturn->refund_type != 'exchange') $agg['sr'][$k] = ($agg['sr'][$k] ?? 0) + $m->returned_qty;
                                    else $agg['ef'][$k] = ($agg['ef'][$k] ?? 0) + $m->returned_qty;
                                    $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) - $m->total_price;
                                }
                            }
                            foreach($product->orderReturnItems as $m) {
                                if($m->orderReturn) {
                                    $k = ($m->variation_id ?: 0) . '_' . $m->orderReturn->return_to_type . '_' . $m->orderReturn->return_to_id;
                                    $agg['sr'][$k] = ($agg['sr'][$k] ?? 0) + $m->returned_qty;
                                    $agg['rev'][$k] = ($agg['rev'][$k] ?? 0) - $m->total_price;
                                }
                            }
                            foreach($product->stockAdjustmentItems as $m) {
                                if($m->adjustment) {
                                    $ltype_adj = $m->adjustment->branch_id ? 'branch' : 'warehouse';
                                    $lid_adj = $m->adjustment->branch_id ?: $m->adjustment->warehouse_id;
                                    $k = ($m->variation_id ?: 0) . '_' . $ltype_adj . '_' . $lid_adj;
                                    $agg['adj'][$k] = ($agg['adj'][$k] ?? 0) + ($m->new_quantity - $m->old_quantity);
                                }
                            }
                            foreach($product->stockTransfers as $m) {
                                if($m->status == 'delivered') {
                                    $k_from = ($m->variation_id ?: 0) . '_' . $m->from_type . '_' . $m->from_id;
                                    $k_to = ($m->variation_id ?: 0) . '_' . $m->to_type . '_' . $m->to_id;
                                    $agg['tf'][$k_from] = ($agg['tf'][$k_from] ?? 0) + $m->quantity;
                                    $agg['tt'][$k_to] = ($agg['tt'][$k_to] ?? 0) + $m->quantity;
                                }
                            }
                            $product->agg = $agg;
                        }
                    @endphp

                    @foreach ($vars as $var)
                        @php
                            if ($isProductSummary && $product->has_variations) {
                                $p_qnt = 0; $pr_qnt = 0; $s_qnt = 0; $sr_qnt = 0; $adjust = 0;
                                $exc_to = 0; $exc_from = 0; $tr_from = 0; $tr_to = 0;
                                $stock_qty = 0; $cost_val = 0; $actual_rev = 0;

                                foreach ($product->variations as $v) {
                                    $vid = $v->id;
                                    $key = $vid . '_' . $ltype . '_' . $lid;
                                    $wh_key = $vid . '_warehouse_0';

                                    $p_qnt += $product->agg['p'][$key] ?? 0;
                                    $pr_qnt += $product->agg['pr'][$key] ?? 0;

                                    $v_s = $product->agg['s'][$key] ?? 0;
                                    if ($ltype == 'warehouse') $v_s += $product->agg['s'][$wh_key] ?? 0;
                                    $s_qnt += $v_s;

                                    $sr_qnt += $product->agg['sr'][$key] ?? 0;
                                    $adjust += $product->agg['adj'][$key] ?? 0;
                                    $exc_to += $product->agg['et'][$key] ?? 0;
                                    $exc_from += $product->agg['ef'][$key] ?? 0;
                                    $tr_from += $product->agg['tf'][$key] ?? 0;
                                    $tr_to += $product->agg['tt'][$key] ?? 0;

                                    $v_stk = $v->stocks->where($ltype == 'branch' ? 'branch_id' : 'warehouse_id', $lid)->sum('quantity');
                                    $stock_qty += $v_stk;
                                    $v_cost = $v->cost ?: $product->cost;
                                    $cost_val += ($v_stk * $v_cost);

                                    $v_rev = $product->agg['rev'][$key] ?? 0;
                                    if ($ltype == 'warehouse') $v_rev += $product->agg['rev'][$wh_key] ?? 0;
                                    $actual_rev += $v_rev;
                                }

                                $inflows = $p_qnt + $sr_qnt + ($adjust > 0 ? $adjust : 0) + $tr_to + $exc_from;
                                $outflows = $s_qnt + $pr_qnt + ($adjust < 0 ? abs($adjust) : 0) + $tr_from + $exc_to;
                                $opening_stock = $stock_qty - ($inflows - $outflows);
                                $color = 'All'; $size = 'All';
                            } else {
                                $vid = $var ? $var->id : 0;
                                $key = $vid . '_' . $ltype . '_' . $lid;
                                $wh_key = $vid . '_warehouse_0';

                                $p_qnt = $product->agg['p'][$key] ?? 0;
                                $pr_qnt = $product->agg['pr'][$key] ?? 0;
                                
                                $s_qnt = $product->agg['s'][$key] ?? 0;
                                if ($ltype == 'warehouse') $s_qnt += $product->agg['s'][$wh_key] ?? 0;

                                $sr_qnt = $product->agg['sr'][$key] ?? 0;
                                $adjust = $product->agg['adj'][$key] ?? 0;
                                $exc_to = $product->agg['et'][$key] ?? 0;
                                $exc_from = $product->agg['ef'][$key] ?? 0;
                                $tr_from = $product->agg['tf'][$key] ?? 0;
                                $tr_to = $product->agg['tt'][$key] ?? 0;

                                $stock_qty = 0;
                                if ($product->has_variations) {
                                    $stock_qty = $var->stocks->where($ltype == 'branch' ? 'branch_id' : 'warehouse_id', $lid)->sum('quantity');
                                } else {
                                    $stock_qty = ($ltype == 'branch' ? $product->branchStock->where('branch_id', $lid)->sum('quantity') : $product->warehouseStock->where('warehouse_id', $lid)->sum('quantity'));
                                }

                                $inflows = $p_qnt + $sr_qnt + ($adjust > 0 ? $adjust : 0) + $tr_to + $exc_from;
                                $outflows = $s_qnt + $pr_qnt + ($adjust < 0 ? abs($adjust) : 0) + $tr_from + $exc_to;
                                $opening_stock = $stock_qty - ($inflows - $outflows);

                                $color = '-'; $size = '-';
                                if ($var) {
                                    foreach($var->attributeValues as $av) {
                                        $attr = strtolower($av->attribute->name ?? '');
                                        if(str_contains($attr, 'color')) $color = $av->value;
                                        elseif(str_contains($attr, 'size')) $size = $av->value;
                                    }
                                }
                                $cost = $var ? ($var->cost ?: $product->cost) : $product->cost;
                                $price = $var ? ($var->price ?: $product->price) : $product->price;
                                $cost_val = $stock_qty * $cost;

                                $actual_rev = $product->agg['rev'][$key] ?? 0;
                                if ($ltype == 'warehouse') $actual_rev += $product->agg['rev'][$wh_key] ?? 0;
                            }
                        @endphp
                        <tr>
                            <td>{{ $sl++ }}</td>
                            <td class="text-left fw-bold">{{ $product->name }}</td>
                            <td>{{ $product->style_number ?: ($product->sku ?: '-') }}</td>
                            <td>{{ $color }}</td>
                            <td>{{ $size }}</td>
                            <td>{{ $location['name'] }}</td>
                            <td>{{ $opening_stock }}</td>
                            <td>{{ $p_qnt ?: '' }}</td>
                            <td>{{ $pr_qnt ?: '' }}</td>
                            <td style="background: rgba(0,229,255,0.05); font-weight: bold;">{{ ($p_qnt - $pr_qnt) ?: '' }}</td>
                            <td>{{ $s_qnt ?: '' }}</td>
                            <td>{{ $sr_qnt ?: '' }}</td>
                            <td style="background: rgba(0,229,255,0.05); font-weight: bold;">{{ ($s_qnt - $sr_qnt) ?: '' }}</td>
                            <td>{{ $adjust ?: '' }}</td>
                            <td>{{ $exc_to ?: '' }}</td>
                            <td>{{ $exc_from ?: '' }}</td>
                            <td>{{ $tr_from ?: '' }}</td>
                            <td>{{ $tr_to ?: '' }}</td>
                            <td class="fw-bold bg-light">{{ $stock_qty }}</td>
                            <td class="text-right">{{ number_format($stock_qty * $cost, 2) }}</td>
                            <td class="text-right">{{ number_format($actual_rev, 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Automated Inventory Movement Report - {{ date('Y-m-d') }}
    </div>
</body>
</html>
