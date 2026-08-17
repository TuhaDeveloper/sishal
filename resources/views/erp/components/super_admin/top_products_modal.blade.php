<!-- Modal for View All Top Products -->
<div class="modal fade" id="allTopProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-trophy text-warning"></i> Top Performing Products Breakdown
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-container-scroll" style="max-height: 450px;">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th class="text-center">Rank</th>
                                <th>Product Name</th>
                                <th>Branch</th>
                                <th class="text-center">Units Sold</th>
                                <th class="text-end">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="topSellingModalBody">
                            @foreach($topSellingProducts as $tp)
                            <tr>
                                <td class="text-center">
                                    <span class="rank-badge {{ $tp['rank'] == 1 ? 'rank-1' : ($tp['rank'] == 2 ? 'rank-2' : ($tp['rank'] == 3 ? 'rank-3' : 'rank-other')) }}">
                                        {{ $tp['rank'] }}
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">{{ $tp['product'] }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $tp['branch'] }}</span></td>
                                <td class="text-center fw-semibold">{{ number_format($tp['sold_qty']) }} pcs</td>
                                <td class="text-end fw-bold text-success">৳{{ number_format($tp['sales_amount']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
