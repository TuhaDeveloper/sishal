@extends('erp.master')

@section('title', 'Branch Management')

@section('body')
@include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
    @include('erp.components.header')
        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Branch List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Branch List</h2>
                    <p class="text-muted mb-0">Manage branch information, locations, and staff efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
            @can('create branch')
                        <a href="{{ route('branches.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Add Branch
            </a>
            @endcan
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-4 py-4">

            <div class="mb-3">
                <form id="filterForm" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search (Name, Location, Manager)</label>
                        <input type="text" class="form-control" name="name" placeholder="Branch Name, Location, or Manager" value="{{ request('name') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="City or Area" value="{{ request('location') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Manager</label>
                        <input type="text" class="form-control" name="manager" placeholder="Manager Name" value="{{ request('manager') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-secondary w-100" id="resetFilter">Reset</button>
                    </div>
                </form>
            </div>

            <!-- Branch Listing Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Branch List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="branchesTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">ID</th>
                                    <th class="border-0">Branch Name</th>
                                    <th class="border-0">Location</th>
                                    <th class="border-0">Contact Info</th>
                                    <th class="border-0">Manager</th>
                                    <th class="border-0 text-center">Status</th>
                                    <th class="border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded by JS -->
                        </tbody>
                    </table>
                </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted" id="branchCount">
                            Loading branches...
                        </span>
                <nav>
                            <ul class="pagination justify-content-center mb-0" id="branchesPagination">
                        <!-- Pagination will be loaded by JS -->
                    </ul>
                </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Modal -->
        <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reportModalLabel">Branch Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Report Filters -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="statusFilter" name="status">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" id="locationFilter" name="location" placeholder="City or Area">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Manager</label>
                                <input type="text" class="form-control" id="managerFilter" name="manager" placeholder="Manager Name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" id="searchFilter" name="search" placeholder="Branch Name">
                            </div>
                        </div>

                        <!-- Column Selection -->
                        <div class="mb-4">
                            <h6>Select Columns to Include:</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="id" id="col_id" checked>
                                        <label class="form-check-label" for="col_id">ID</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="name" id="col_name" checked>
                                        <label class="form-check-label" for="col_name">Branch Name</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="location" id="col_location" checked>
                                        <label class="form-check-label" for="col_location">Location</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="contact_info" id="col_contact_info" checked>
                                        <label class="form-check-label" for="col_contact_info">Contact Info</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="manager" id="col_manager" checked>
                                        <label class="form-check-label" for="col_manager">Manager</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input column-selector" type="checkbox" value="status" id="col_status" checked>
                                        <label class="form-check-label" for="col_status">Status</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllColumns">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllColumns">Deselect All</button>
                            </div>
                        </div>

                        <!-- Summary Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Branches</h5>
                                        <h3 id="totalBranches">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Active Branches</h5>
                                        <h3 id="activeBranches">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Inactive Branches</h5>
                                        <h3 id="inactiveBranches">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Preview -->
                        <div class="mb-4">
                            <h6>Report Preview:</h6>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-bordered" id="reportPreviewTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="col-id">ID</th>
                                            <th class="col-name">Branch Name</th>
                                            <th class="col-location">Location</th>
                                            <th class="col-contact-info">Contact Info</th>
                                            <th class="col-manager">Manager</th>
                                            <th class="col-status">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportPreviewBody">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Export Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success" id="exportExcel">
                                <i class="fas fa-file-excel me-2"></i>Export to Excel
                            </button>
                            <button type="button" class="btn btn-danger" id="exportPdf">
                                <i class="fas fa-file-pdf me-2"></i>Export to PDF
                            </button>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load initial branch data
    fetchBranches();

    // Event listeners for filters
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        fetchBranches();
    });

    document.getElementById('resetFilter').addEventListener('click', function() {
        document.getElementById('filterForm').reset();
        fetchBranches();
    });

    // Report modal functionality
    document.getElementById('statusFilter').addEventListener('change', loadReportData);
    document.getElementById('locationFilter').addEventListener('change', loadReportData);
    document.getElementById('managerFilter').addEventListener('change', loadReportData);
    document.getElementById('searchFilter').addEventListener('change', loadReportData);

    // Column selection
    document.getElementById('selectAllColumns').addEventListener('click', function() {
        document.querySelectorAll('.column-selector').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateColumnVisibility();
    });

    document.getElementById('deselectAllColumns').addEventListener('click', function() {
        document.querySelectorAll('.column-selector').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateColumnVisibility();
    });

    document.querySelectorAll('.column-selector').forEach(checkbox => {
        checkbox.addEventListener('change', updateColumnVisibility);
    });

    // Export buttons
    document.getElementById('exportExcel').addEventListener('click', exportToExcel);
    document.getElementById('exportPdf').addEventListener('click', exportToPdf);

    function renderBranchesTable(branches) {
        const tbody = document.querySelector('#branchesTable tbody');
        tbody.innerHTML = '';
        
        if (branches.data && branches.data.length > 0) {
        branches.data.forEach(function(branch) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${branch.id}</td>
                    <td><a href="/erp/branches/${branch.id}" class="fw-bold text-decoration-underline">${branch.name}</a></td>
                    <td>${branch.location || '-'}</td>
                    <td>${branch.contact_info || '-'}</td>
                    <td>${branch.manager ? branch.manager.first_name + ' ' + branch.manager.last_name : 'N/A'}</td>
                    <td class="text-center">
                        <span class="badge ${getStatusBadgeClass(branch.status)}">
                            ${branch.status ? branch.status.charAt(0).toUpperCase() + branch.status.slice(1) : 'Active'}
                        </span>
                    </td>
                    <td>
                        @can('edit branch')
                        <a href="/erp/branches/${branch.id}/edit" class="btn btn-sm btn-warning me-1">Edit</a>
                        @endcan
                        @can('delete branch')
                        <form action="/erp/branches/${branch.id}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this branch?')">Delete</button>
                        </form>
                        @endcan
                    </td>
                `;
                tbody.appendChild(row);
        });
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No branches found</td></tr>';
        }
        
        renderPagination(branches);
        updateBranchCount(branches);
    }

    function renderPagination(branches) {
        const pagination = document.getElementById('branchesPagination');
        pagination.innerHTML = '';
        
        if (branches.last_page <= 1) return;
        
        let html = '';
        if (branches.current_page > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${branches.current_page - 1}">Previous</a></li>`;
        }
        
        for (let i = 1; i <= branches.last_page; i++) {
            html += `<li class="page-item${i === branches.current_page ? ' active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        if (branches.current_page < branches.last_page) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${branches.current_page + 1}">Next</a></li>`;
        }
        
        pagination.innerHTML = html;
    }

    function updateBranchCount(branches) {
        const countElement = document.getElementById('branchCount');
        if (branches.total) {
            countElement.textContent = `Showing ${branches.from || 0} to ${branches.to || 0} of ${branches.total} branches`;
        } else {
            countElement.textContent = 'No branches found';
        }
    }

    function getStatusBadgeClass(status) {
        switch(status) {
            case 'active': return 'bg-success';
            case 'inactive': return 'bg-danger';
            default: return 'bg-success';
        }
    }

    function fetchBranches(page = 1) {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }
        params.append('page', page);
        
        fetch(`{{ url('/erp/branches/fetch') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
            renderBranchesTable(data);
            })
            .catch(error => {
                console.error('Error fetching branches:', error);
                document.querySelector('#branchesTable tbody').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>';
            });
    }

    // Pagination click handler
    document.addEventListener('click', function(e) {
        if (e.target.matches('#branchesPagination .page-link')) {
            e.preventDefault();
            const page = e.target.getAttribute('data-page');
            if (page) fetchBranches(page);
        }
    });

    // Report functionality
    function loadReportData() {
        const status = document.getElementById('statusFilter').value;
        const location = document.getElementById('locationFilter').value;
        const manager = document.getElementById('managerFilter').value;
        const search = document.getElementById('searchFilter').value;

        // Show loading
        document.getElementById('reportPreviewBody').innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (location) params.append('location', location);
        if (manager) params.append('manager', manager);
        if (search) params.append('search', search);

        fetch(`/erp/branches/report-data?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                updateReportPreview(data.branches);
                updateSummaryStats(data.summary);
            })
            .catch(error => {
                console.error('Error loading report data:', error);
                document.getElementById('reportPreviewBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading data</td></tr>';
            });
    }

    function updateReportPreview(branches) {
        const tbody = document.getElementById('reportPreviewBody');
        tbody.innerHTML = '';

        if (branches.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data found</td></tr>';
            return;
        }

        branches.forEach(branch => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="col-id">${branch.id}</td>
                <td class="col-name">${branch.name || '-'}</td>
                <td class="col-location">${branch.location || '-'}</td>
                <td class="col-contact-info">${branch.contact_info || '-'}</td>
                <td class="col-manager">${branch.manager_name || 'N/A'}</td>
                <td class="col-status"><span class="badge ${getStatusBadgeClass(branch.status)}">${branch.status || 'Active'}</span></td>
            `;
            tbody.appendChild(row);
        });
    }

    function updateSummaryStats(summary) {
        document.getElementById('totalBranches').textContent = summary.total_branches || 0;
        document.getElementById('activeBranches').textContent = summary.active_branches || 0;
        document.getElementById('inactiveBranches').textContent = summary.inactive_branches || 0;
    }

    function updateColumnVisibility() {
        const columns = {
            'id': 'col-id',
            'name': 'col-name',
            'location': 'col-location',
            'contact_info': 'col-contact-info',
            'manager': 'col-manager',
            'status': 'col-status'
        };

        Object.keys(columns).forEach(key => {
            const checkbox = document.getElementById('col_' + key);
            const columnClass = columns[key];
            const elements = document.querySelectorAll('.' + columnClass);
            
            elements.forEach(element => {
                element.style.display = checkbox.checked ? '' : 'none';
            });
        });
    }

    function exportToExcel() {
        const status = document.getElementById('statusFilter').value;
        const location = document.getElementById('locationFilter').value;
        const manager = document.getElementById('managerFilter').value;
        const search = document.getElementById('searchFilter').value;
        const selectedColumns = Array.from(document.querySelectorAll('.column-selector:checked')).map(cb => cb.value);

        if (selectedColumns.length === 0) {
            alert('Please select at least one column to export.');
            return;
        }

        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (location) params.append('location', location);
        if (manager) params.append('manager', manager);
        if (search) params.append('search', search);
        params.append('columns', selectedColumns.join(','));

        const url = `/erp/branches/export-excel?${params.toString()}`;
        
        // Show loading state
        const btn = document.getElementById('exportExcel');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating Excel...';
        btn.disabled = true;
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Export failed');
                    });
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'branches_report_' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.xlsx';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                alert('Export failed: ' + error.message);
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }

    function exportToPdf() {
        const status = document.getElementById('statusFilter').value;
        const location = document.getElementById('locationFilter').value;
        const manager = document.getElementById('managerFilter').value;
        const search = document.getElementById('searchFilter').value;
        const selectedColumns = Array.from(document.querySelectorAll('.column-selector:checked')).map(cb => cb.value);

        if (selectedColumns.length === 0) {
            alert('Please select at least one column to export.');
            return;
        }

        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (location) params.append('location', location);
        if (manager) params.append('manager', manager);
        if (search) params.append('search', search);
        params.append('columns', selectedColumns.join(','));

        const url = `/erp/branches/export-pdf?${params.toString()}`;
        
        // Show loading state
        const btn = document.getElementById('exportPdf');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDF...';
        btn.disabled = true;
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Export failed');
                    });
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'branches_report_' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.pdf';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                alert('Export failed: ' + error.message);
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }
    });
</script>
@endpush
@endsection
