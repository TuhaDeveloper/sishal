<div class="table-responsive">
    <table class="table premium-table compact reporting-table table-bordered mb-0" id="salesTable">
        <thead>
            <tr>
                @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('delete sales'))
                    <th class="text-center" style="min-width: 40px;">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                    </th>
                @endif
                <th class="text-center" style="min-width: 40px;">#</th>
                <th style="min-width: 100px;">Invoice</th>
                <th style="min-width: 90px;">Date</th>
                <th style="min-width: 120px;">Customer</th>
                <th style="min-width: 100px;">Branch</th>
                <th style="min-width: 100px;">Created By</th>
                <th class="text-center">Img</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Season</th>
                <th>Gender</th>
                <th class="text-center" style="min-width: 150px;">Product Name</th>
                <th>Style #</th>
                <th>Color</th>
                <th>Size</th>
                <th class="text-end">Unit Price</th>

                <!-- Granular Financial Columns -->
                <th class="text-center bg-soft-primary" title="Billed Line Quantity">Sales Qty</th>
                <th class="text-center bg-soft-primary text-primary" title="Total Physical Pieces Sold">Physical Pcs</th>
                <th class="text-center bg-soft-primary">Total S-Qty</th>
                <th class="text-end bg-soft-primary">Sales Amount</th>
                <th class="text-end bg-soft-primary">Total Sales Amount</th>

                <th class="text-center bg-soft-danger">Sales Return Qty</th>
                <th class="text-center bg-soft-danger">Total SR-Qty</th>
                <th class="text-end bg-soft-danger">Sales Return Amount</th>
                <th class="text-end bg-soft-danger">Total Sales Return Amount</th>

                <th class="text-center bg-soft-warning" style="background-color: #fff3cd; color: #856404;">Exchange Qty</th>
                <th class="text-center bg-soft-warning" style="background-color: #fff3cd; color: #856404;">Total Exch-Qty</th>
                <th class="text-end bg-soft-warning" style="background-color: #fff3cd; color: #856404;">Exchange Return Amount</th>
                <th class="text-end bg-soft-warning" style="background-color: #fff3cd; color: #856404;">Total Exchange Return Amount</th>

                <th class="text-center bg-soft-success">Actual Sales Qty</th>
                <th class="text-center bg-soft-success">Total AS-Qty</th>

                <th class="text-end">Delivery Charge Amount</th>
                <th class="text-end">VAT Amount</th>
                <th class="text-end">Discount Amount</th>
                <th class="text-end">Exchange Amount</th>
                <th class="text-end">Refund</th>
                <th class="text-end fw-bold">Gross Amount</th>
                <th class="text-end text-success">Net Amount (Final)</th>
                <th class="text-end text-success fw-bold">Total Received Amount</th>
                <th class="text-end text-danger fw-bold">Total Due Amount</th>
                <th class="text-center">Option</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                    @php
                        $sale = $item->pos;
                        $invoice = $sale->invoice;
                        $product = $item->product;
                        $variation = $item->variation;
                        $isFirst = ($index == 0 || $items[$index - 1]->pos_sale_id != $item->pos_sale_id);

                        $color = '-';
                        $size = '-';
                        if ($variation && $variation->attributeValues) {
                            foreach ($variation->attributeValues as $val) {
                                $attrName = strtolower($val->attribute->name ?? '');
                                if (str_contains($attrName, 'color') || (isset($val->attribute) && $val->attribute->is_color))
                                    $color =
                                        $val->value;
                                elseif (str_contains($attrName, 'size'))
                                    $size = $val->value;
                            }
                        }

                        $regRetItems = $item->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') !== 'exchange');
                        $exchRetItems = $item->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') === 'exchange');

                        $regRetQty = $regRetItems->sum('returned_qty');
                        $regRetAmt = $regRetItems->sum('total_price');

                        $exchRetQty = $exchRetItems->sum('returned_qty');
                        $exchRetAmt = $exchRetItems->sum('total_price');

                        $retQty = $item->returnItems->sum('returned_qty');
                        $retAmt = $item->returnItems->sum('total_price');

                        $grossAmt = $item->quantity * $item->unit_price;
                        $itemExchNewQty = \App\Models\PosExchangeItem::where('type', 'new')
                            ->where('product_id', $item->product_id)
                            ->where('variation_id', $item->variation_id)
                            ->whereHas('exchange', function($q) use ($sale) {
                                $q->where('original_pos_id', $sale->id)->where('status', 'completed');
                            })
                            ->sum('quantity');

                        $actualQty = $item->quantity - $retQty + $itemExchNewQty;
                        $actualAmt = $item->total_price - $retAmt;

                        // Combo & Physical Piece Calculation
                        $isCombo = ($product?->type === 'combo');
                        $childItems = $item->childItems ?? collect();
                        $comboItemsQty = $childItems->sum('quantity');
                        $physicalQty = $isCombo ? ($comboItemsQty > 0 ? $comboItemsQty : $item->quantity) : $item->quantity;

                        // Invoice level (calculated once per invoice change for efficiency)
                        $invItems = $sale->items->whereNull('parent_item_id');
                        $invTotalQty = $invItems->sum('quantity');
                        $invGrossAmt = $invItems->sum(fn($i) => $i->quantity * $i->unit_price);

                        $invPhysicalQty = 0;
                        foreach ($invItems as $invItem) {
                            if ($invItem->product?->type === 'combo') {
                                $cQty = $invItem->childItems ? $invItem->childItems->sum('quantity') : 0;
                                $invPhysicalQty += ($cQty > 0 ? $cQty : $invItem->quantity);
                            } else {
                                $invPhysicalQty += $invItem->quantity;
                            }
                        }

                        $invRegRetQty = $invItems->sum(fn($i) => $i->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') !== 'exchange')->sum('returned_qty'));
                        $invRegRetAmt = $invItems->sum(fn($i) => $i->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') !== 'exchange')->sum('total_price'));

                        $invExchRetQty = $invItems->sum(fn($i) => $i->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') === 'exchange')->sum('returned_qty'));
                        $invExchRetAmt = $invItems->sum(fn($i) => $i->returnItems->filter(fn($ri) => ($ri->saleReturn?->refund_type ?? '') === 'exchange')->sum('total_price'));

                        $invExchNewQty = \App\Models\PosExchangeItem::where('type', 'new')
                            ->whereHas('exchange', function($q) use ($sale) {
                                $q->where('original_pos_id', $sale->id)->where('status', 'completed');
                            })
                            ->sum('quantity');

                        $invRetQty = $invRegRetQty + $invExchRetQty;
                        $invRetAmt = $invRegRetAmt + $invExchRetAmt;
                        $invActualQty = $invTotalQty - $invRetQty + $invExchNewQty;

                        // Calculate proportional returned VAT and discount
                        $invReturnedVat = 0;
                        $invReturnedDiscount = 0;
                        if ($invGrossAmt > 0) {
                            foreach ($invItems as $invItem) {
                                foreach ($invItem->returnItems as $returnItem) {
                                    if (($returnItem->saleReturn?->status ?? '') === 'processed') {
                                        $itemGross = $invItem->quantity * $invItem->unit_price;
                                        $itemProportion = $itemGross / $invGrossAmt;
                                        $qtyProportion = $returnItem->returned_qty / $invItem->quantity;
                                        $invReturnedVat += round($itemProportion * $qtyProportion * ($sale->vat_amount ?? 0), 2);
                                        $invReturnedDiscount += round($itemProportion * $qtyProportion * ($sale->discount ?? 0), 2);
                                    }
                                }
                            }
                        }

                        // Net VAT and Discount after returns
                        $invNetVat = max(0, ($sale->vat_amount ?? 0) - $invReturnedVat);
                        $invNetDiscount = max(0, ($sale->discount ?? 0) - $invReturnedDiscount);

                        // Real-world Gross Amount: Original invoice total before any returns/discounts
                        // Gross = Original Gross (qty × unit_price) + Original VAT + Delivery
                        $invGrossAmount = $invGrossAmt + ($sale->vat_amount ?? 0) + $sale->delivery;
                        // Net Amount: always use invoice->total_amount (correctly reduced on return processing)
                        $invActualAmt = $invoice ? floatval($invoice->total_amount ?? 0) : max(0, $invGrossAmount - $invRetAmt);
                        $invDisplayPaidAmount = $invoice ? min(floatval($invoice->paid_amount ?? 0), $invActualAmt) : 0;
                        $invDisplayDueAmount = max(0, $invActualAmt - $invDisplayPaidAmount);
                    @endphp
                    <tr>
                        <!-- @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('delete sales')) -->

                        <td class="text-center">
                            @if($isFirst)
                                <input class="form-check-input row-checkbox" type="checkbox" value="{{ $sale->id }}">
                            @endif
                        </td>
                        <!-- @endif -->
                        <td class="text-center text-muted">{{ $items->firstItem() + $index }}</td>
                        <td>
                            @if($isFirst)
                                <a href="{{ route('pos.show', $sale->id) }}"
                                    class="text-decoration-none fw-bold text-primary hover-opacity-75">
                                    {{ $sale->sale_number ?? '-' }}
                                </a>
                                @if($sale->original_pos_id)
                                        <div class="mt-1" style="font-size: 10px; line-height: 1.2;">
                                            <span class="text-muted">Exch. from:</span>
                                            <span class="badge bg-info bg-opacity-10 text-info fw-normal border-0 p-0">{{
                                    $sale->originalPos->sale_number ?? '-' }}</span>
                                        </div>
                                @endif
                            @endif
                        </td>
                        <td>{{ $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') : '-' }}</td>
                        <td>{{ $sale->customer->name ?? 'Walk-in' }}</td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{{
                $sale->branch->name ?? '-' }}</span></td>
                        <td>{{ $sale->soldBy ? trim($sale->soldBy->first_name . ' ' . $sale->soldBy->last_name) : '-' }}</td>
                        <td class="text-center">
                            <div class="thumbnail-box" style="width: 30px; height: 30px; margin: 0 auto;">
                                @if($product && $product->image)
                                    <img src="{{ asset($product->image) }}" alt="">
                                @else
                                    <i class="fas fa-cube text-muted opacity-50 small"></i>
                                @endif
                            </div>
                        </td>
                        <td>{{ $product->category->name ?? '-' }}</td>
                        <td>{{ $product->brand->name ?? '-' }}</td>
                        <td>{{ $product->season->name ?? '-' }}</td>
                        <td>{{ $product->gender->name ?? '-' }}</td>
                        <td class="fw-bold text-dark">
                            {{ $product->name ?? '-' }}
                            @if($isCombo)
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 ms-1"
                                    style="font-size: 0.65rem;">
                                    <i class="fas fa-boxes me-1"></i>COMBO ({{ $physicalQty }} pcs)
                                </span>
                            @endif
                        </td>
                        <td>{{ $product->style_number ?? $product->sku ?? '-' }}</td>
                        <td>{{ $color }}</td>
                        <td>{{ $size }}</td>

                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>

                        <!-- Sales Qty (Billed) -->
                        <td class="text-center bg-light">
                            <span>{{ $item->quantity }}</span>
                        </td>

                        <!-- Physical Pcs (Total Pieces) -->
                        <td class="text-center bg-light fw-bold text-primary">
                            @if($isCombo)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" title="Physical pieces inside this combo">
                                    <i class="fas fa-cube me-1"></i>{{ $physicalQty }} pcs
                                </span>
                            @else
                                <span>{{ $physicalQty }} pcs</span>
                            @endif
                        </td>

                        <!-- Total S-Qty (Invoice Level) -->
                        <td class="text-center bg-light">
                            @if($isFirst)
                                <span class="fw-bold">{{ $invTotalQty }}</span>
                            @endif
                        </td>

                        <!-- Sales Amount & Total Sales Amount -->
                        <td class="text-end bg-light">{{ number_format($grossAmt, 2) }}</td>
                        <td class="text-end bg-light">
                            @if($isFirst) <span class="fw-bold">{{ number_format($invGrossAmt, 2) }}</span> @endif
                        </td>

                        <!-- Returns -->
                        <td class="text-center text-danger">{{ $regRetQty ?: '-' }}</td>
                        <td class="text-center text-danger">
                            @if($isFirst) <span class="fw-bold">{{ $invRegRetQty ?: '-' }}</span> @endif
                        </td>
                        <td class="text-end text-danger">{{ $regRetQty ? number_format($regRetAmt, 2) : '-' }}</td>
                        <td class="text-end text-danger">
                            @if($isFirst) <span class="fw-bold">{{ $invRegRetAmt ? number_format($invRegRetAmt, 2) : '-' }}</span>
                            @endif
                        </td>

                        <!-- Exchanges -->
                        <td class="text-center text-warning" style="background-color: #fffdf5;">{{ $exchRetQty ?: '-' }}</td>
                        <td class="text-center text-warning" style="background-color: #fffdf5;">
                            @if($isFirst) <span class="fw-bold">{{ $invExchRetQty ?: '-' }}</span> @endif
                        </td>
                        <td class="text-end text-warning" style="background-color: #fffdf5;">{{ $exchRetQty ? number_format($exchRetAmt, 2) : '-' }}</td>
                        <td class="text-end text-warning" style="background-color: #fffdf5;">
                            @if($isFirst) <span class="fw-bold">{{ $invExchRetAmt ? number_format($invExchRetAmt, 2) : '-' }}</span>
                            @endif
                        </td>

                        <td class="text-center text-success fw-bold">
                            {{ (int)$actualQty }}
                            @if($itemExchNewQty > 0)
                                <div class="text-muted fw-normal" style="font-size: 0.65rem;">(+{{ (int)$itemExchNewQty }} exch.)</div>
                            @endif
                        </td>
                        <td class="text-center text-success fw-bold">
                            @if($isFirst) 
                                <span>{{ (int)$invActualQty }}</span> 
                                @if($invExchNewQty > 0)
                                    <div class="text-muted" style="font-size: 0.65rem;">(+{{ (int)$invExchNewQty }} exch.)</div>
                                @endif
                            @endif
                        </td>

                        <td class="text-end">
                            @if($isFirst) {{ number_format($sale->delivery, 2) }} @endif
                        </td>
                        <td class="text-end">
                            @if($isFirst) {{ number_format($invNetVat, 2) }} @endif
                        </td>
                        <td class="text-end text-danger">
                            @if($isFirst) {{ number_format($invNetDiscount, 2) }} @endif
                        </td>
                        <td class="text-end">
                            @if($isFirst) {{ number_format($sale->exchange_amount ?? 0, 2) }} @endif
                        </td>
                        <td class="text-end text-danger">
                            @if($isFirst) {{ number_format($sale->refund_amount ?? 0, 2) }} @endif
                        </td>

                        <td class="text-end fw-bold">
                            @if($isFirst) {{ number_format($invGrossAmount, 2) }} @endif
                        </td>
                        <td class="text-end fw-bold text-success">
                            @if($isFirst) {{ number_format($invActualAmt, 2) }} @endif
                        </td>
                        <td class="text-end text-success fw-bold">
                            @if($isFirst) {{ number_format($invDisplayPaidAmount, 2) }} @endif
                        </td>
                        <td class="text-end text-danger fw-bold">
                            @if($isFirst) {{ number_format($invDisplayDueAmount, 2) }} @endif
                        </td>
                        <td class="text-center">
                            @if($isFirst)
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('pos.show', $sale->id) }}" class="btn btn-action btn-sm" title="View Details"
                                        style="background: #e2e8f0; color: #475569;">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if(auth()->user()->can('edit sales') && $sale->status !== 'cancelled')
                                        <a href="{{ route('pos.edit', $sale->id) }}" class="btn btn-action btn-sm" title="Edit Sale"
                                            style="background: #fef3c7; color: #92400e;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    @if(($invoice->due_amount ?? 0) > 0 && auth()->user()->can('manage money receipts'))
                                        @php
                                            // Walk-in: no customer_id, route to invoice-based mode
                                            // Named customer: pass customer_id for customer-based mode
                                            $mrParams = [
                                                'invoice_id' => $invoice->id,
                                                'invoice_number' => $invoice->invoice_number,
                                                'due_amount' => $invoice->due_amount,
                                            ];
                                            if ($sale->customer_id) {
                                                $mrParams['customer_id'] = $sale->customer_id;
                                                $mrParams['customer_name'] = $sale->customer->name ?? '';
                                            }
                                        @endphp
                                        <a href="{{ route('money-receipt.create', $mrParams) }}" class="btn btn-action btn-sm"
                                            title="Receive Payment" style="background: #dcfce7; color: #166534;">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                    @endif



                                    <form action="{{ route('pos.delete', $sale->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete Sale {{ $sale->sale_number }}? This will also delete its invoice and payments.')">
                                        @csrf @method('DELETE')
                                        @can('delete sales')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                style="padding: 4px 8px; font-size: 0.75rem;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endcan
                                    </form>

                                </div>
                            @endif
                        </td>
                    </tr>
            @empty
                <tr>
                    <td colspan="40" class="text-center py-5 text-muted">No sales records found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="bg-light fw-bold">
            <tr class="bg-soft-primary border-top-2">
                @php
                    $colBeforeQty = (auth()->user()->hasRole('Super Admin') || auth()->user()->can('delete sales')) ? 17 : 16;
                @endphp
                <td colspan="{{ $colBeforeQty }}" class="text-end text-uppercase py-3">Grand Total (All Records)</td>

                <!-- Billed Qty -->
                <td class="text-center py-3">
                    <span class="fw-bold fs-6">{{ $reportTotals['sell_qty'] }}</span>
                </td>

                <!-- Physical Pcs -->
                <td class="text-center py-3 bg-primary-subtle text-primary">
                    <span class="fw-extrabold fs-6">{{ $reportTotals['total_physical_qty'] ?? $reportTotals['sell_qty'] }} pcs</span>
                    @if(($reportTotals['combo_qty'] ?? 0) > 0)
                        <div class="text-muted small" style="font-size: 0.65rem;">
                            ({{ $reportTotals['single_qty'] ?? 0 }} single + {{ $reportTotals['combo_child_qty'] ?? 0 }} combo pcs)
                        </div>
                    @endif
                </td>

                <td></td> <!-- Total S-Qty footer empty -->

                <td class="text-end py-3">{{ number_format($reportTotals['gross_amt'], 2) }}</td>
                <td></td>
                <!-- Total Sales Amount footer empty -->

                <!-- Returns -->
                <td></td>
                <td class="text-center text-danger">{{ $reportTotals['reg_ret_qty'] ?: '-' }}</td>
                <td></td>
                <td></td>

                <!-- Exchanges -->
                <td></td>
                <td class="text-center text-warning">{{ $reportTotals['exch_ret_qty'] ?: '-' }}</td>
                <td></td>
                <td></td>

                <!-- Actual Qty -->
                <td></td>
                <td class="text-center text-success fw-bold">
                    <span>{{ $reportTotals['act_qty'] }}</span>
                </td>

                <td class="text-end py-3">{{ number_format($reportTotals['delivery'], 2) }}</td>
                <td class="text-end py-3">{{ number_format($reportTotals['vat_amt'], 2) }}</td>
                <td class="text-end py-3 text-danger">{{ number_format($reportTotals['discount'], 2) }}</td>
                <td class="text-end fw-bold">{{ number_format($reportTotals['exchange'], 2) }}</td>
                <td class="text-end fw-bold text-danger">{{ number_format($reportTotals['refund'], 2) }}</td>

                <td class="text-end fw-bold">{{ number_format($reportTotals['gross_amt'] + $reportTotals['vat_amt'] + $reportTotals['delivery'], 2) }}</td>

                <td class="text-end text-dark py-3">{{ number_format($reportTotals['final_total'], 2) }}</td>
                <td class="text-end text-success py-3">{{ number_format($reportTotals['paid'], 2) }}</td>
                <td class="text-end text-danger py-3">{{ number_format($reportTotals['due'], 2) }}</td>
                <td colspan="1"></td> <!-- Option -->
            </tr>
        </tfoot>
    </table>
</div>

@if($items->hasPages())
    <div class="card-footer bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing {{ $items->firstItem() }} - {{ $items->lastItem() }} of {{ $items->total()
                                                            }}</small>
            {{ $items->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
@endif