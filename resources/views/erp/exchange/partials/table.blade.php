<!-- Table -->
<div class="premium-card">
    <div class="card-header bg-white border-bottom p-3">
        <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-exchange-alt me-2 text-primary"></i>Product Exchange Registry</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table premium-table compact reporting-table mb-0" id="exchangeTable">
                <thead>
                    <tr>
                        <th class="text-center" style="min-width: 40px;">#</th>
                        <th style="min-width: 120px;">Exchange Invoice</th>
                        <th style="min-width: 120px;">Sale Invoice</th>
                        <th style="min-width: 90px;">Date</th>
                        <th style="min-width: 100px;">Branch</th>
                        <th style="min-width: 120px;">Customer</th>
                        <th class="text-center">Img</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Season</th>
                        <th>Gender</th>
                        <th style="min-width: 140px;">Product Name</th>
                        <th>Style #</th>
                        <th>Color</th>
                        <th>Size</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Exchange</th>
                        <th class="text-end">Refund</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $tExchange = 0; $tRefund = 0; $tDiscount = 0; $tPaid = 0; $tDue = 0;
                    @endphp
                    @forelse($items as $index => $item)
                        @php
                            $exchange = $item->exchange;
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

                            $isFirst = ($index == 0 || $items[$index-1]->pos_exchange_id != $item->pos_exchange_id);
                            if($isFirst) {
                                $tExchange += $exchange->total_new_amount;
                                $tRefund += $exchange->refund_amount;
                                $tDiscount += $exchange->discount_amount;
                                $tPaid += $exchange->extra_payable;
                                $tDue += 0;
                            }
                        @endphp
                        <tr>
                            <td class="text-center text-muted">{{ $items->firstItem() + $index }}</td>
                            <td class="fw-bold text-dark">{{ $exchange?->exchange_number ?? 'N/A' }}</td>
                            <td class="text-primary">{{ $exchange?->originalPos?->sale_number ?? '-' }}</td>
                            <td>{{ $exchange?->exchange_date ? \Carbon\Carbon::parse($exchange->exchange_date)->format('d/m/Y') : '-' }}</td>
                            <td>{{ $exchange?->branch?->name ?? '-' }}</td>
                            <td>{{ $exchange?->customer?->name ?? 'Walk-in' }}</td>
                            <td class="text-center">
                                @if($product && $product->image)
                                    <img src="{{ asset($product->image) }}" width="30" height="30" class="rounded shadow-sm" alt="">
                                @endif
                            </td>
                            <td>{{ $product?->category?->name ?? '-' }}</td>
                            <td>{{ $product?->brand?->name ?? '-' }}</td>
                            <td>{{ $product?->season?->name ?? '-' }}</td>
                            <td>{{ $product?->gender?->name ?? '-' }}</td>
                            <td>{{ $product?->name ?? '-' }}</td>
                            <td>{{ $product?->style_number ?? '-' }}</td>
                            <td>{{ $color }}</td>
                            <td>{{ $size }}</td>
                            <td class="text-center fw-600">{{ $item->quantity }} ({{ ucfirst($item->type) }})</td>
                            <td class="text-end font-monospace">{{ $isFirst ? number_format($exchange->total_new_amount, 2) : '' }}</td>
                            <td class="text-end font-monospace">{{ $isFirst ? number_format($exchange->refund_amount, 2) : '' }}</td>
                            <td class="text-end font-monospace">{{ $isFirst ? number_format($exchange->discount_amount, 2) : '' }}</td>
                            <td class="text-end font-monospace">{{ $isFirst ? number_format($exchange->extra_payable, 2) : '' }}</td>
                            <td class="text-end font-monospace">{{ $isFirst ? '0.00' : '' }}</td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('exchange.show', $exchange->id) }}" class="btn btn-action btn-sm" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('manage exchanges')
                                    <button type="button" class="btn btn-action btn-sm text-danger delete-exchange-btn" data-url="{{ route('exchange.delete', $exchange->id) }}" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="22" class="text-center py-5 text-muted">No records found</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-light">
                    <tr class="fw-bold text-dark text-uppercase">
                        <td colspan="16" class="text-end">Grand Totals</td>
                        <td class="text-end">{{ number_format($tExchange, 2) }}</td>
                        <td class="text-end">{{ number_format($tRefund, 2) }}</td>
                        <td class="text-end">{{ number_format($tDiscount, 2) }}</td>
                        <td class="text-end">{{ number_format($tPaid, 2) }}</td>
                        <td class="text-end">{{ number_format($tDue, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @if($items->hasPages())
    <div class="card-footer bg-white py-2">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} entries</small>
            {{ $items->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
