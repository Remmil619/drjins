<nav class="navbar navbar-expand-lg clinic-navbar workspace-sidebar" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand clinic-brand" href="{{ route('dashboard') }}">
            <span class="brand-symbol" aria-hidden="true">+</span>
            <span>Dr. Jn's<small>CHILDREN'S CLINIC</small>
            </span>
        </a>
        <button
            class="navbar-toggler bg-light"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
            aria-controls="navbarMenu"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon">
            </span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav workspace-navigation">
                <li class="navigation-heading">Overview</li>
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    >
                        Clinic Dashboard
                    </a>
                </li>
                <li class="navigation-heading">Patient care</li>
                <li class="nav-item">
                    <a href="{{ route('appointments.index') }}"
                        class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}"
                    >
                        Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('consultations.index') }}" class="nav-link {{ request()->routeIs('consultations.*') ? 'active' : '' }}">Consultation</a>
                </li>
                @php($managementOpen = request()->routeIs('patients.*', 'queue.*', 'vaccines.*', 'clinic-vaccines.*', 'billing.*', 'charges.*', 'payments.*'))
                <li class="nav-item">
                    <button type="button" class="nav-link management-toggle w-100 {{ $managementOpen ? 'active' : '' }}"
                        data-bs-toggle="collapse" data-bs-target="#managementMenu"
                        aria-expanded="{{ $managementOpen ? 'true' : 'false' }}" aria-controls="managementMenu">
                        <span>Management</span><span class="management-chevron" aria-hidden="true">&#8964;</span>
                    </button>
                    <ul id="managementMenu" class="collapse management-links list-unstyled {{ $managementOpen ? 'show' : '' }}">
                        @foreach([
                            ['patients.index', 'patients.*', 'Patients'],
                            ['queue.index', 'queue.*', 'Queue'],
                            ['vaccines.index', 'vaccines.*', 'Vaccines'],
                            ['billing.index', 'billing.*', 'Fees'],
                        ] as [$destination, $pattern, $label])
                            <li><a class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($destination) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inquiries.index') }}"
                        class="nav-link {{ request()->routeIs('inquiries.*') ? 'active' : '' }}"
                    >Inquiries</a>
                </li>
                <li class="navigation-heading">Administration</li>
                <li class="nav-item">
                    <a href="{{ route('sales.index') }}"
                        class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}"
                    >Sales</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">Reports</a>
                </li>
                @php($settingsOpen = request()->routeIs('settings.*', 'users.*'))
                <li class="nav-item">
                    <button type="button" class="nav-link management-toggle w-100 {{ $settingsOpen ? 'active' : '' }}" data-bs-toggle="collapse" data-bs-target="#settingsMenu" aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}" aria-controls="settingsMenu">
                        <span>Settings</span><span class="management-chevron" aria-hidden="true">&#8964;</span>
                    </button>
                    <ul id="settingsMenu" class="collapse management-links list-unstyled {{ $settingsOpen ? 'show' : '' }}">
                        @can('register-users')<li><a href="{{ route('users.create') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">Accounts</a></li>@endcan
                        <li><a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">Theme</a></li>
                        <li><a href="{{ route('settings.availability') }}" class="nav-link {{ request()->routeIs('settings.availability*') ? 'active' : '' }}">Doctors availability</a></li>
                    </ul>
                </li>
            </ul>
                <div class="dropdown workspace-account">
                    <button
                        class="btn account-menu-button dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Open account menu"
                    >
                        <span class="account-avatar" aria-hidden="true">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="account-details">
                            <span class="account-name">{{ auth()->user()->name }}</span>
                            <span class="account-role">{{ match (auth()->user()->role) { 'admin' => 'Administrator', 'doctor' => 'Doctor', default => 'Authorized Staff' } }}</span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end account-dropdown">
                        <div class="account-dropdown-header">
                            <div class="fw-semibold">Signed in as</div>
                            <div class="small text-muted account-email">{{ auth()->user()->email }}</div>
                        </div>
                        <div class="dropdown-divider">
                        </div>
                        <a class="dropdown-item" href="{{ route('password.edit') }}">Change password</a>
                        <div class="dropdown-divider"></div>
                        <button
                            type="button"
                            class="logout-button"
                            data-bs-toggle="modal"
                            data-bs-target="#logoutConfirmationModal"
                        >
                            Sign out securely
                        </button>
                    </div>
                </div>
        </div>
    </div>
</nav>
<div
    class="modal fade"
    id="logoutConfirmationModal"
    tabindex="-1"
    aria-labelledby="logoutConfirmationTitle"
    aria-describedby="logoutConfirmationMessage"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content logout-confirmation-card">
            <div class="modal-body text-center">
                <div class="logout-confirmation-icon" aria-hidden="true">
                    <svg
                        width="28"
                        height="28"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <path
                            d="M10 17l5-5-5-5M15 12H3M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
                <h2 class="logout-confirmation-title" id="logoutConfirmationTitle">
                    Confirm logout
                </h2>
                <p class="logout-confirmation-message" id="logoutConfirmationMessage">
                    Are you sure you want to sign out of your clinic account?
                </p>
                <div class="logout-confirmation-actions">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            Yes, log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
