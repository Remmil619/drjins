<nav class="navbar navbar-expand-xl clinic-navbar" aria-label="Main navigation">
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
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    >
                        Clinic Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('appointments.index') }}"
                        class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}"
                    >
                        Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('patients.index') }}"
                        class="nav-link {{ request()->routeIs('patients.*') ? 'active' : '' }}"
                    >
                        Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('queue.index') }}"
                        class="nav-link {{ request()->routeIs('queue.*') ? 'active' : '' }}"
                    >Queue</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inquiries.index') }}"
                        class="nav-link {{ request()->routeIs('inquiries.*') ? 'active' : '' }}"
                    >Inquiries</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('sales.index') }}"
                        class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}"
                    >Sales</a>
                </li>
                @can('register-users')
                    <li class="nav-item">
                        <a href="{{ route('users.create') }}"
                            class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        >Register User</a>
                    </li>
                @endcan
                <li class="nav-item dropdown ms-lg-2">
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
                        <button
                            type="button"
                            class="logout-button"
                            data-bs-toggle="modal"
                            data-bs-target="#logoutConfirmationModal"
                        >
                            Sign out securely
                        </button>
                    </div>
                </li>
            </ul>
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
