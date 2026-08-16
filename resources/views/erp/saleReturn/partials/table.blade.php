<!-- Table -->
<div class="premium-card">
    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-list me-2 text-primary"></i>Return Data List</h6>
        <div class="search-wrapper-premium">
            <input type="text" id="returnSearch" class="form-control rounded-pill search-input-premium" placeholder="Quick find in this registry...">
            <i class="fas fa-search search-icon-premium"></i>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table premium-table compact reporting-table mb-0" id="returnTable">
                <thead>
                    <tr>
                        <th class="text-center">SL</th>
                        <th>Date</th>
                        <th>R-Inv No</th>
                        <th>S-Inv No</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th>Branch</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Season</th>
                        <th>Gender</th>
                        <th style="min-width: 140px;">Product Name</th>
                        <th>Style #</th>
                        <th>Color</th>
                        <th>Size</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">T.Qty</th>
                        <th class="text-end">T.Amount</th>
                        <th class="text-end">Charge</th>
                        <th class="text-end">Paid</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $gQty = 0; $gAmt = 0;
                    @endphp
                    @forelse($items as $index => $item)
                        @php
                            $return = $item->saleReturn;
                            $product = $item->product;
                            $variation = $item->variation;
                            if (!$return) continue;

                            $color = '-'; $size = '-';
                            if ($variation && $variation->attributeValues) {
                                foreach($variation->attributeValues as $val) {
                                    $attrName = strtolower($val->attribute->name ?? '');
                                    if (str_contains($attrName, 'color')) $color = $val->value;
                                    elseif (str_contains($attrName, 'size')) $size = $val->value;
                                }
                            }

                            $gQty += $item->returned_qty;
                            $gAmt += $item->total_price;
                        @endphp
                        <tr>
                            <td class="text-center text-muted">{{ $items->firstItem() + $index }}</td>
                            <td class="text-center">{{ $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('d/m/Y') : '-' }}</td>
                            <td class="fw-bold text-dark">#SR-{{ str_pad($return->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                 <a href="{{ route('pos.show', $return->pos_sale_id) }}" class="text-decoration-none text-primary fw-600">
                                    {{ $return->posSale->sale_number ?? '-' }}
                                 </a>
                            </td>
                            <td>{{ $return->customer->name ?? 'Walk-in' }}</td>
                            <td>{{ $return->customer->phone ?? '-' }}</td>
                            <td>{{ $return->branch->name ?? '-' }}</td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>{{ $product->brand->name ?? '-' }}</td>
                            <td>{{ $product->season->name ?? '-' }}</td>
                            <td>{{ $product->gender->name ?? '-' }}</td>
                            <td>{{ $product->name ?? '-' }}</td>
                            <td>{{ $product->style_number ?? '-' }}</td>
                            <td>{{ $color }}</td>
                            <td>{{ $size }}</td>
                            <td class="text-center">{{ $item->returned_qty }}</td>
                            <td class="text-center fw-bold">{{ $item->returned_qty }}</td>
                            <td class="text-end fw-bold">{{ number_format($item->total_price, 2) }}</td>
                            <td class="text-end">0.00</td>
                            <td class="text-end">0.00</td>
                             <td class="text-center">
                                 <div class="d-flex gap-1 justify-content-center">
                                     <a href="{{ route('saleReturn.show', $return->id) }}" class="btn btn-action btn-sm" title="View">
                                         <i class="fas fa-eye"></i>
                                     </a>
                                     @can('delete sale returns')
                                     <button type="button" class="btn btn-action btn-sm text-danger delete-return-btn" data-url="{{ route('saleReturn.delete', $return->id) }}" title="Delete">
                                         <i class="fas fa-trash"></i>
                                     </button>
                                     @endcan
                                 </div>
                             </td>
                        </tr>
                    @empty
                        <tr><td colspan="21" class="text-center py-5 text-muted">No records found</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-light">
                    <tr class="fw-bold text-dark text-uppercase">
                        <td colspan="15" class="text-end">Grand Totals</td>
                        <td class="text-center">{{ $gQty }}</td>
                        <td class="text-center">{{ $gQty }}</td>
                        <td class="text-end">{{ number_format($gAmt, 2) }}</td>
                        <td class="text-end">0.00</td>
                        <td class="text-end">0.00</td>
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
