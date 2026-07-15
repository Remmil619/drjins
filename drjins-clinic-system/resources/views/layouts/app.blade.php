<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? "Dr. Jin's Children Clinic" }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CDN for easy capstone UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f4f8fb;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #0d6efd;
        }

        .navbar-brand,
        .nav-link {
            color: white !important;
        }

        .clinic-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        .page-header {
            background: linear-gradient(135deg, #0d6efd, #56b4ff);
            color: white;
            padding: 50px 20px;
            border-radius: 0 0 30px 30px;
            margin-bottom: 30px;
        }

        .btn-clinic {
            background: #0d6efd;
            color: white;
            border-radius: 10px;
            padding: 10px 20px;
        }

        .btn-clinic:hover {
            background: #084db4;
            color: white;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-declined {
            background: #f8d7da;
            color: #842029;
        }

        .status-completed {
            background: #cfe2ff;
            color: #084298;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">
            Dr. Jin's Children Clinic
        </a>

        <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link">Clinic Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('appointments.index') }}" class="nav-link">Appointments</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('patients.index') }}" class="nav-link">Patients</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main>
    @yield('content')
</main>

<footer class="text-center py-4 mt-5 text-muted">
    <small>
        &copy; {{ date('Y') }} Dr. Jin's Children Clinic, Abuyog, Leyte
    </small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
