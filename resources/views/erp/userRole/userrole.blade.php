@extends('erp.master')

@section('title', 'User Role')

@push('styles')
<style>
.permission-tabs .nav-tabs {
    border-bottom: 2px solid #dee2e6;
}

.permission-tabs .nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1rem;
    margin-right: 0.5rem;
    border-radius: 0;
}

.permission-tabs .nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
    background-color: #f8f9fa;
}

.permission-tabs .nav-tabs .nav-link.active {
    color: #0d6efd;
    background-color: transparent;
    border-bottom: 3px solid #0d6efd;
}

.permission-tabs .tab-content {
    padding: 1.5rem 0;
}

.permission-tabs .form-check {
    margin-bottom: 0.5rem;
}

.permission-tabs .form-check-label {
    font-size: 0.9rem;
    cursor: pointer;
}

.permission-tabs .btn-sm {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endpush



@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">User Role</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">User Role</h2>
                    <p class="text-muted mb-0">Manage user roles and permissions.</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#addUserRoleModal">
                            <i class="fas fa-plus me-2"></i>Add User Role
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- User Role Table -->
        <div class="container-fluid px-4 py-4">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Permission</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr>
                                        <td>{{ $role->name }}</td>
                                        <td>
                                            @foreach ($role->permissions as $permission)
                                                <span class="badge text-primary mb-1" style="font-size: 14px; text-transform: capitalize; background-color:rgba(13, 109, 253, 0.20); border: 1px solid #0d6efd;">{{ $permission->name }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary" onclick="openEditModal({{ $role->id }}, '{{ $role->name }}', [{{ $role->permissions->pluck('id')->implode(',') }}])">Edit</button>
                                            <a href="#" class="btn btn-danger">Delete</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Add User Role Modal -->
            <div class="modal fade" id="addUserRoleModal" tabindex="-1" aria-labelledby="addUserRoleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserRoleModalLabel">Add User Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('userRole.store') }}" method="post">
                                @csrf
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                                                    <div class="mb-3">
                                    <label class="form-label">Permissions</label>
                                    
                                    <!-- Permission Tabs -->
                                    <div class="permission-tabs">
                                        <ul class="nav nav-tabs" id="permissionTabs" role="tablist">
                                            @foreach($permissionsByCategory as $category => $categoryPermissions)
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ $loop->first ? 'active' : '' }} text-capitalize" 
                                                            id="tab-{{ Str::slug($category) }}" 
                                                            data-bs-toggle="tab" 
                                                            data-bs-target="#content-{{ Str::slug($category) }}" 
                                                            type="button" 
                                                            role="tab" 
                                                            aria-controls="content-{{ Str::slug($category) }}" 
                                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                        {{ $category }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                        
                                        <div class="tab-content mt-3" id="permissionTabsContent">
                                            @foreach($permissionsByCategory as $category => $categoryPermissions)
                                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                                     id="content-{{ Str::slug($category) }}" 
                                                     role="tabpanel" 
                                                     aria-labelledby="tab-{{ Str::slug($category) }}">
                                                    
                                                    <div class="row">
                                                        @foreach($categoryPermissions as $permission)
                                                            <div class="col-md-6 col-lg-4 mb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input permission-checkbox" 
                                                                           type="checkbox" 
                                                                           name="permissions[]" 
                                                                           value="{{ $permission->name }}" 
                                                                           id="permission_{{ $permission->id }}">
                                                                    <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                                        {{ ucwords(str_replace('-', ' ', $permission->name)) }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    
                                                    <!-- Select All for this category -->
                                                    <div class="mt-3">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary select-all-category" 
                                                                data-category="{{ Str::slug($category) }}">
                                                            Select All {{ $category }}
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-secondary deselect-all-category" 
                                                                data-category="{{ Str::slug($category) }}">
                                                            Deselect All {{ $category }}
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        
                                        <!-- Global Select All -->
                                        <div class="mt-3 border-top pt-3">
                                            <button type="button" class="btn btn-primary select-all-permissions">
                                                Select All Permissions
                                            </button>
                                            <button type="button" class="btn btn-secondary deselect-all-permissions">
                                                Deselect All Permissions
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Add</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit User Role Modal -->
            <div class="modal fade" id="editUserRoleModal" tabindex="-1" aria-labelledby="editUserRoleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editUserRoleModalLabel">Edit User Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editRoleForm" action="#" method="post">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Permissions</label>
                                    <div class="permission-tabs">
                                        <ul class="nav nav-tabs" id="editPermissionTabs" role="tablist">
                                            @foreach($permissionsByCategory as $category => $categoryPermissions)
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ $loop->first ? 'active' : '' }} text-capitalize" 
                                                            id="edit-tab-{{ Str::slug($category) }}" 
                                                            data-bs-toggle="tab" 
                                                            data-bs-target="#edit-content-{{ Str::slug($category) }}" 
                                                            type="button" 
                                                            role="tab" 
                                                            aria-controls="edit-content-{{ Str::slug($category) }}" 
                                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                        {{ $category }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                        
                                        <div class="tab-content mt-3" id="editPermissionTabsContent">
                                            @foreach($permissionsByCategory as $category => $categoryPermissions)
                                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                                     id="edit-content-{{ Str::slug($category) }}" 
                                                     role="tabpanel" 
                                                     aria-labelledby="edit-tab-{{ Str::slug($category) }}">
                                                    
                                                    <div class="row">
                                                        @foreach($categoryPermissions as $permission)
                                                            <div class="col-md-6 col-lg-4 mb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input edit-permission-checkbox" 
                                                                           type="checkbox" 
                                                                           name="permissions[]" 
                                                                           value="{{ $permission->id }}" 
                                                                           id="edit_permission_{{ $permission->id }}">
                                                                    <label class="form-check-label" for="edit_permission_{{ $permission->id }}">
                                                                        {{ ucwords(str_replace('-', ' ', $permission->name)) }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    
                                                    <!-- Select All for this category -->
                                                    <div class="mt-3">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary edit-select-all-category" 
                                                                data-category="{{ Str::slug($category) }}">
                                                            Select All {{ $category }}
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-secondary edit-deselect-all-category" 
                                                                data-category="{{ Str::slug($category) }}">
                                                            Deselect All {{ $category }}
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        
                                        <!-- Global Select All -->
                                        <div class="mt-3 border-top pt-3">
                                            <button type="button" class="btn btn-primary edit-select-all-permissions">
                                                Select All Permissions
                                            </button>
                                            <button type="button" class="btn btn-secondary edit-deselect-all-permissions">
                                                Deselect All Permissions
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Update</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .nav-tabs .nav-link.active {
                color: #0d6efd !important;
                background-color:rgba(13, 109, 253, 0.25)  !important;
                border-bottom: 3px solid #0d6efd !important;
            }
            .nav-tabs .nav-link:hover {
                color: #0d6efd !important;
                border-color: #0d6efd !important;
                border-bottom: 3px solid #0d6efd !important;
            }
        </style>

@endsection

@push('scripts')
<script>
// Global function for opening edit modal
function openEditModal(roleId, roleName, permissions) {
    const modal = document.getElementById('editUserRoleModal');
    const form = document.getElementById('editRoleForm');
    const nameInput = form.querySelector('#name');
    
    // Set form action
    form.action = `/erp/user-role/${roleId}`;
    
    // Set role name
    nameInput.value = roleName;
    
    // Clear all checkboxes first
    document.querySelectorAll('.edit-permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Check permissions that the role has
    if (permissions && permissions.length > 0) {
        permissions.forEach(permissionId => {
            const checkbox = document.getElementById(`edit_permission_${permissionId}`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
    
    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Select All Permissions
    document.querySelector('.select-all-permissions').addEventListener('click', function() {
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
    });

    // Deselect All Permissions
    document.querySelector('.deselect-all-permissions').addEventListener('click', function() {
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
    });

    // Select All for specific category
    document.querySelectorAll('.select-all-category').forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const tabContent = document.getElementById('content-' + category);
            const checkboxes = tabContent.querySelectorAll('.permission-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });
    });

    // Deselect All for specific category
    document.querySelectorAll('.deselect-all-category').forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const tabContent = document.getElementById('content-' + category);
            const checkboxes = tabContent.querySelectorAll('.permission-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    });

    // Edit Modal Functionality
    // Select All Permissions for Edit Modal
    document.querySelector('.edit-select-all-permissions').addEventListener('click', function() {
        document.querySelectorAll('.edit-permission-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
    });

    // Deselect All Permissions for Edit Modal
    document.querySelector('.edit-deselect-all-permissions').addEventListener('click', function() {
        document.querySelectorAll('.edit-permission-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
    });

    // Select All for specific category in Edit Modal
    document.querySelectorAll('.edit-select-all-category').forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const tabContent = document.getElementById('edit-content-' + category);
            const checkboxes = tabContent.querySelectorAll('.edit-permission-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });
    });

    // Deselect All for specific category in Edit Modal
    document.querySelectorAll('.edit-deselect-all-category').forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const tabContent = document.getElementById('edit-content-' + category);
            const checkboxes = tabContent.querySelectorAll('.edit-permission-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    });
});
</script>
@endpush