<div class="table-responsive">
    <table class="table premium-table reporting-table compact table-hover align-middle mb-0" id="transferTable">
        <thead>
            <tr>
                <th class="ps-3" style="width: 40px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="masterCheckbox">
                    </div>
                </th>
                <th>SL</th>
                <th>Invoice No</th>
                <th>Date</th>
                <th>Source</th>
                <th>Destination</th>
                <th>Requested By</th>
                <th class="text-center">Img</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Season</th>
                <th>Gender</th>
                <th style="min-width: 150px;">Product Name</th>
                <th>Style #</th>
                <th>Color</th>
                <th>Size</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Type</th>
                <th class="text-center">Status</th>
                <th class="text-center pe-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transfers as $index => $transfer)
                @php
                    $isReturn = str_starts_with($transfer->invoice_number ?? '', 'RET-');
                    $product = $transfer->product;
                    $variation = $transfer->variation;

                    $color = '-';
                    $size = '-';
                    if ($variation && $variation->attributeValues) {
                        foreach ($variation->attributeValues as $val) {
                            $attrName = strtolower($val->attribute->name ?? '');
                            if (str_contains($attrName, 'color') || (isset($val->attribute) && $val->attribute->is_color))
                                $color = $val->value;
                            elseif (str_contains($attrName, 'size'))
                                $size = $val->value;
                        }
                    }
                @endphp
                <tr class="{{ $isReturn ? 'table-warning' : '' }}">
                    <td class="ps-3">
                        <div class="form-check">
                            <input class="form-check-input row-checkbox" type="checkbox"
                                value="{{ $transfer->invoice_number ?? $transfer->id }}"
                                data-type="{{ $transfer->invoice_number ? 'invoice' : 'single' }}">
                        </div>
                    </td>
                    <td class="text-muted">{{ $transfers->firstItem() + $index }}</td>
                    <td class="fw-bold text-dark">
                        @if($transfer->invoice_number)
                            {{ $transfer->invoice_number }}
                            @if($isReturn)
                                <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;"><i
                                        class="fas fa-undo-alt me-1"></i>RETURN</span>
                            @endif
                        @else
                            <span class="text-muted small">N/A (ID: {{ $transfer->id }})</span>
                        @endif
                    </td>
                    <td>{{ $transfer->requested_at ? \Carbon\Carbon::parse($transfer->requested_at)->format('d/m/Y') : '-' }}
                    </td>
                    <td>{{ $transfer->fromBranch->name ?? ($transfer->fromWarehouse->name ?? 'Unknown') }}</td>
                    <td>{{ $transfer->toBranch->name ?? ($transfer->toWarehouse->name ?? 'Unknown') }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs bg-light rounded-circle text-primary me-2 d-flex align-items-center justify-content-center"
                                style="width:24px;height:24px;font-size:10px;">
                                <i class="fas fa-user"></i>
                            </div>
                            {{ $transfer->requestedPerson->name ?? 'System' }}
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="thumbnail-box" style="width: 30px; height: 30px; margin: 0 auto;">
                            @if($product && $product->image)
                                <img src="{{ asset($product->image) }}" alt=""
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                            @else
                                <i class="fas fa-cube text-muted opacity-50 small"></i>
                            @endif
                        </div>
                    </td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td>{{ $product->brand->name ?? '-' }}</td>
                    <td>{{ $product->season->name ?? '-' }}</td>
                    <td>{{ $product->gender->name ?? '-' }}</td>
                    <td class="fw-bold text-dark">{{ $product->name ?? '-' }}</td>
                    <td>{{ $product->style_number ?? $product->sku ?? '-' }}</td>
                    <td>{{ $color }}</td>
                    <td>{{ $size }}</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">{{ number_format($transfer->quantity, 0) }}</span>
                    </td>
                    <td class="text-center">
                        @if($isReturn)
                            <span class="badge bg-warning text-dark"><i class="fas fa-undo-alt me-1"></i>Return</span>
                        @else
                            <span class="badge bg-light text-dark border"><i class="fas fa-truck me-1"></i>Transfer</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $statusClass = match ($transfer->status) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'delivered' => 'primary',
                                default => 'warning'
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst($transfer->status) }}</span>
                    </td>
                    <td class="pe-3 text-center">
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="{{ route('stocktransfer.show', $transfer->id) }}" class="action-circle"
                                title="View Details">
                                <i class="fas fa-eye text-primary"></i>
                            </a>
                            @if($transfer->status === 'pending' && !$isReturn)
                                @can('approve transfers')
                                    <form action="{{ route('stocktransfer.status', $transfer->id) }}" method="POST" class="d-inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="delivered">
                                        <button type="submit" class="action-circle border-0 bg-transparent" title="Confirm Receipt & Deliver" onclick="return confirm('Confirm receipt and deliver this stock transfer?')">
                                            <i class="fas fa-check-circle text-success"></i>
                                        </button>
                                    </form>
                                @endcan
                            @endif
                            @if($transfer->status === 'delivered' && !$isReturn)
                                @can('reconcile transfers')
                                    <a href="{{ route('stocktransfer.return', $transfer->id) }}" class="action-circle"
                                        title="Return Items" onclick="return confirm('Initiate a return of these items?')">
                                        <i class="fas fa-undo-alt text-warning"></i>
                                    </a>
                                @endcan
                            @endif

                            @can('delete transfers')
                                <button type="button" class="action-circle border-0 bg-transparent btn-delete-transfer"
                                    data-transfer-id="{{ $transfer->id }}"
                                    data-invoice="{{ $transfer->invoice_number ?? 'ID:' . $transfer->id }}"
                                    data-status="{{ $transfer->status }}" data-product="{{ $product->name ?? 'this product' }}"
                                    data-qty="{{ number_format($transfer->quantity, 0) }}"
                                    data-source="{{ $transfer->fromBranch->name ?? ($transfer->fromWarehouse->name ?? 'Unknown') }}"
                                    data-destination="{{ $transfer->toBranch->name ?? ($transfer->toWarehouse->name ?? 'Unknown') }}"
                                    title="Delete Transfer">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="31" class="text-center py-5">
                        <div class="text-muted opacity-50">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p class="fw-bold">No transfer records found.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($transfers->hasPages())
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3">
        <small class="text-muted fw-500">Showing {{ $transfers->firstItem() }} to {{ $transfers->lastItem() }}</small>
        <div class="ajax-pagination">
            {{ $transfers->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
@endif