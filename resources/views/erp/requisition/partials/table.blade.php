<div class="table-responsive">
    <table class="table premium-table align-middle mb-0">
        <thead>
            <tr>
                <th class="ps-4">Req #</th>
                <th>Branch</th>
                <th>Target Warehouse</th>
                <th>Date</th>
                <th>Status</th>
                <th>Created By</th>
                <th class="text-center pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requisitions as $req)
                <tr>
                    <td class="ps-4 fw-bold text-dark">{{ $req->requisition_number }}</td>
                    <td>
                        <div class="fw-bold">{{ optional($req->branch)->name ?? '—' }}</div>
                        <div class="extra-small text-muted text-uppercase">{{ optional($req->branch)->location }}</div>
                    </td>
                    <td>
                        <div class="badge bg-light text-dark border fw-bold px-3 py-2">
                            <i class="fas fa-warehouse me-1 text-info"></i>{{ optional($req->warehouse)->name ?? '—' }}
                        </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($req->requisition_date)->format('M d, Y') }}</td>
                    <td>
                        @php
                            $dispStatus = $req->display_status;
                            $statusClass = match ($dispStatus) {
                                'dispatched' => 'bg-info text-white',
                                'partially_dispatched' => 'bg-info text-white',
                                'fulfilled' => 'bg-success text-white',
                                'partially_fulfilled' => 'bg-primary text-white',
                                'rejected' => 'bg-danger text-white',
                                default => 'bg-warning text-dark'
                            };
                            $statusLabel = match ($dispStatus) {
                                'dispatched' => 'Dispatched',
                                'partially_dispatched' => 'Partially Dispatched',
                                'fulfilled' => 'Fulfilled',
                                'partially_fulfilled' => 'Partially Fulfilled',
                                'rejected' => 'Rejected',
                                default => 'Pending'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }} px-3 py-2 rounded-pill text-uppercase" style="font-size: 0.7rem;">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td>
                        <div class="small fw-bold">{{ optional($req->creator)->name ?? '—' }}</div>
                        <div class="extra-small text-muted">{{ $req->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="text-center pe-4">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('requisition.show', $req->id) }}" class="btn btn-sm btn-light border action-circle shadow-none" title="View Details">
                                <i class="fas fa-eye text-primary"></i>
                            </a>
                            @can('manage requisitions')
                                <form action="{{ route('requisition.destroy', $req->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $req->status === 'pending' ? 'Delete this requisition?' : 'WARNING: This requisition is ' . strtoupper(str_replace('_', ' ', $req->status)) . '. Deleting it will reverse all associated stock transfers and restore inventory. Are you sure you want to proceed?' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border action-circle shadow-none" title="Delete">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted opacity-50">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p class="fw-bold mb-0">No requisitions found.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card-footer bg-white border-top p-4">
    {{ $requisitions->links() }}
</div>
