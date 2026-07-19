<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? "Dr. Jn's Childer Clinic Online Booking" }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f5f9fc;
            font-family: Arial, sans-serif;
        }

        .topbar {
            background: #0d6efd;
            color: white;
            padding: 18px 0;
        }

        .hero {
            background: linear-gradient(135deg, #0d6efd, #6ec6ff);
            color: white;
            padding: 65px 20px;
            border-radius: 0 0 35px 35px;
        }

        .booking-card {
            border: none;
            border-radius: 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .btn-book {
            background: #0d6efd;
            color: white;
            border-radius: 12px;
            padding: 12px 20px;
        }

        .btn-book:hover {
            background: #084db4;
            color: white;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 11px 13px;
        }

        .clinic-note {
            background: #eaf4ff;
            border-left: 5px solid #0d6efd;
            border-radius: 12px;
            padding: 15px;
        }

        .status-box {
            border-radius: 18px;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="container d-flex justify-content-between align-items-center">
        <strong>Dr. Jn's Childer Clinic</strong>

        <div>
            <a href="{{ route('booking.create') }}" class="btn btn-light btn-sm me-2">
                Book Appointment
            </a>

            <a href="{{ route('booking.status') }}" class="btn btn-outline-light btn-sm">
                Check Status
            </a>
        </div>
    </div>
</div>

@yield('content')

<footer class="text-center text-muted py-4 mt-5">
    <small>Dr. Jn's Childer Clinic, Abuyog, Leyte</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
