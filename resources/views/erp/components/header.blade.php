<div class="header">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <button class="btn btn-link d-md-none me-3 p-0" id="sidebarToggle">
                <i class="fas fa-bars fs-5"></i>
            </button>
            <div>
                <h2 class="mb-1">
                    @if(Auth::user()->hasPermissionTo('manage global branches'))
                        Global Branch
                    @else
                        @if(Auth::user()->employee && Auth::user()->employee->branch)
                            {{ Auth::user()->employee->branch->name }} Branch
                        @else
                            No Branch
                        @endif
                    @endif
                </h2>
            </div>
        </div>
        <div class="d-flex align-items-center position-relative">
            <button class="btn btn-link position-relative me-3 p-2">
                <i class="fas fa-bell"></i>
                <span class="notification-badge"></span>
            </button>
            <div class="dropdown">
                <button class="btn p-0 border-0 bg-transparent d-flex align-items-center" id="userDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2"
                        style="width: 32px; height: 32px;">
                        <span class="text-white fw-bold small">
                            {{ strtoupper(substr(Auth::user()->first_name ?? 'U', 0, 1) . substr(Auth::user()->last_name ?? '', 0, 1)) }}
                        </span>
                    </div>
                    <div class="d-none d-md-block text-start">
                        <div class="fw-bold">{{ Auth::user()->first_name ?? 'User' }}
                            {{ Auth::user()->last_name ?? '' }}</div>
                        <small class="text-muted">{{ Auth::user()->roles->first()->name ?? 'No Role' }}</small>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="{{ route('erp.profile') }}">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>