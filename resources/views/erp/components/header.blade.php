<div class="header">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <button class="btn btn-link d-md-none me-3 p-0" id="sidebarToggle">
                <i class="fas fa-bars fs-5"></i>
            </button>
            @if(Auth::user()->employee && Auth::user()->employee->branch)
                <div>
                    <h2 class="mb-1">{{ Auth::user()->employee->branch->name }} Branch</h2>
                </div>
            @endif
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    // Create overlay for mobile close behavior
    var overlay = document.getElementById('sidebarOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebarOverlay';
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
    }

    function openSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('active');
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    // Close when clicking overlay or pressing ESC
    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keyup', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    // Close after navigating via sidebar links (mobile UX),
    // but DO NOT close when toggling collapsible parent menus
    sidebar.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var isCollapseToggle = this.getAttribute('data-bs-toggle') === 'collapse' || this.getAttribute('data-bs-target');
            var href = this.getAttribute('href') || '';
            if (isCollapseToggle || href === '#' || href.startsWith('#')) {
                // keep sidebar open for menu expansion
                return;
            }
            closeSidebar();
        });
    });
});
</script>
</div>