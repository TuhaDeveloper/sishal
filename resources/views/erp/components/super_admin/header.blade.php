<!-- Dashboard Executive Header Component -->
<div class="sa-dashboard-header">
    <div class="row align-items-center g-3">
        <div class="col-lg-6">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="sa-badge"><i class="fas fa-crown me-1"></i> Multi-Branch ERP</span>
                <span class="badge bg-light text-secondary border">Executive View</span>
            </div>
            <h1 class="sa-title mb-0">Super Admin Dashboard</h1>
            <p class="text-muted small mb-0 mt-1">Real-time consolidated operational and financial overview across all branches.</p>
        </div>
        <div class="col-lg-6">
            <form id="globalFilterForm" onsubmit="event.preventDefault(); triggerAjaxFilter();">
                <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                    <div>
                        <label class="form-label small text-muted mb-1 fw-bold">Branch Scope</label>
                        <select id="branchSelect" name="branch_id" class="form-select sa-filter-select" onchange="triggerAjaxFilter()">
                            <option value="all" {{ $selectedBranchId === 'all' ? 'selected' : '' }}>All Branches (Consolidated)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (string)$selectedBranchId === (string)$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1 fw-bold">Timeframe</label>
                        <select id="rangeSelect" name="range" class="form-select sa-filter-select" onchange="triggerAjaxFilter()">
                            <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="this_quarter" {{ $dateRange === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                            <option value="this_year" {{ $dateRange === 'this_year' ? 'selected' : '' }}>This Year</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
