@extends('layouts.app')

@section('content')

<div class="page-header">
    <div class="container">
        <h1 class="fw-bold">Booked Patients</h1>
        <p class="mb-0">Automatically updated list of patients who booked an appointment online.</p>
    </div>
</div>

<div class="container">

    <div class="card clinic-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Online Booked Appointments</h4>
                    <p class="text-muted mb-0">
                        This page automatically checks the online booking website every 5 seconds.
                    </p>
                </div>

                <div class="text-end">
                    <span id="connectionStatus" class="badge bg-secondary">Checking connection...</span>
                    <div class="small text-muted mt-1">
                        Last checked: <span id="lastChecked">---</span>
                    </div>
                </div>
            </div>

            <hr>

            <form id="dateForm" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Select Appointment Date</label>
                    <input type="date" id="selectedDate" class="form-control" value="{{ $selectedDate }}">
                </div>

                <div class="col-md-3">
                    <button type="button" id="viewDateButton" class="btn btn-primary w-100">
                        View Bookings
                    </button>
                </div>

                <div class="col-md-3">
                    <button type="button" id="todayButton" class="btn btn-outline-secondary w-100">
                        Today
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card clinic-card">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0" id="bookingTitle">
                        Bookings for {{ date('F d, Y', strtotime($selectedDate)) }}
                    </h5>
                    <small class="text-muted">
                        Total booked patients: <span id="bookingTotal">0</span>
                    </small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Reference No.</th>
                            <th>Child / Patient</th>
                            <th>Guardian</th>
                            <th>Contact</th>
                            <th>Reason for Visit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="bookingTableBody">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Loading booked patients...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
    const liveUrl = "{{ route('online-bookings.live') }}";
    const selectedDateInput = document.getElementById('selectedDate');
    const viewDateButton = document.getElementById('viewDateButton');
    const todayButton = document.getElementById('todayButton');
    const bookingTableBody = document.getElementById('bookingTableBody');
    const bookingTotal = document.getElementById('bookingTotal');
    const bookingTitle = document.getElementById('bookingTitle');
    const connectionStatus = document.getElementById('connectionStatus');
    const lastChecked = document.getElementById('lastChecked');

    function escapeHtml(value) {
        if (value === null || value === undefined || value === '') {
            return 'Not provided';
        }

        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatTime(time) {
        const parts = time.split(':');
        let hour = parseInt(parts[0]);
        const minute = parts[1];
        const suffix = hour >= 12 ? 'PM' : 'AM';

        hour = hour % 12;
        hour = hour ? hour : 12;

        return hour + ':' + minute + ' ' + suffix;
    }

    function formatDate(dateValue) {
        const date = new Date(dateValue + 'T00:00:00');

        return date.toLocaleDateString('en-US', {
            month: 'long',
            day: '2-digit',
            year: 'numeric'
        });
    }

    function renderAppointments(data) {
        bookingTotal.textContent = data.total;
        bookingTitle.textContent = 'Bookings for ' + formatDate(data.date);
        lastChecked.textContent = data.last_checked;

        if (data.connected) {
            connectionStatus.className = 'badge bg-success';
            connectionStatus.textContent = 'Connected to Online Booking';
        } else {
            connectionStatus.className = 'badge bg-danger';
            connectionStatus.textContent = 'Booking Portal Offline';
        }

        if (data.appointments.length === 0) {
            bookingTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No booked patients found for this date.
                    </td>
                </tr>
            `;

            return;
        }

        let rows = '';

        data.appointments.forEach(function (appointment) {
            rows += `
                <tr>
                    <td class="fw-bold">${formatTime(appointment.appointment_time)}</td>
                    <td>${escapeHtml(appointment.appointment_no)}</td>
                    <td>${escapeHtml(appointment.child_name)}</td>
                    <td>${escapeHtml(appointment.guardian_name)}</td>
                    <td>${escapeHtml(appointment.contact_number)}</td>
                    <td>${escapeHtml(appointment.reason_for_visit)}</td>
                    <td>
                        <span class="status-badge status-approved">Booked</span>
                    </td>
                </tr>
            `;
        });

        bookingTableBody.innerHTML = rows;
    }

    function loadBookings() {
        const dateValue = selectedDateInput.value;

        fetch(liveUrl + '?date=' + encodeURIComponent(dateValue))
            .then(response => response.json())
            .then(data => {
                renderAppointments(data);
            })
            .catch(() => {
                connectionStatus.className = 'badge bg-danger';
                connectionStatus.textContent = 'Connection Error';
                lastChecked.textContent = 'Failed';
            });
    }

    viewDateButton.addEventListener('click', function () {
        loadBookings();
    });

    todayButton.addEventListener('click', function () {
        const today = new Date().toISOString().slice(0, 10);
        selectedDateInput.value = today;
        loadBookings();
    });

    selectedDateInput.addEventListener('change', function () {
        loadBookings();
    });

    loadBookings();

    setInterval(loadBookings, 5000);
</script>

@endsection