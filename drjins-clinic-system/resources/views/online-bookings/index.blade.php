@extends('layouts.app')

@section('content')

<div class="page-header">
    <div class="container">
        <h1 class="fw-bold">Appointments</h1>
        <p class="mb-0">Automatically updated list of patients who booked an appointment online.</p>
    </div>
</div>

<div class="container">

    @if (session('success'))
        <div class="alert alert-success mt-4">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mt-4">
            <strong>Please check the form.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card clinic-card mb-4 mt-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Booked Appointments</h4>
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

            <form class="row g-2 align-items-end" onsubmit="return false;">
                <div class="col-md-5">
                    <label for="appointmentFilter" class="form-label fw-semibold">
                        Appointment Schedule
                    </label>
                    <select id="appointmentFilter" class="form-select">
                        <option value="upcoming" @selected($selectedFilter === 'upcoming')>Upcoming Appointments</option>
                        <option value="today" @selected($selectedFilter === 'today')>Today's Appointments</option>
                        <option value="all" @selected($selectedFilter === 'all')>All Appointments</option>
                        <option value="past" @selected($selectedFilter === 'past')>Past Appointments</option>
                    </select>
                    <div class="form-text">
                        Select a schedule to automatically update the appointment list.
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card clinic-card">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0" id="bookingTitle">
                        Upcoming Appointments
                    </h5>
                    <small class="text-muted">
                        Total appointments: <span id="bookingTotal">0</span>
                    </small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Date</th>
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
                            <td colspan="9" class="text-center text-muted py-4">
                                Loading appointments...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<div class="modal fade" id="patientDetailsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content patient-modal">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Complete Patient Details</h5>

            <button 
                type="button" 
                class="btn-close btn-close-white" 
                data-bs-dismiss="modal" 
                aria-label="Close">
            </button>
        </div>

            <form method="POST" action="{{ route('appointments.savePatientDetails') }}">
                @csrf

                <div class="modal-body">
                    <input type="hidden" name="appointment_no" id="modalAppointmentNo">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Patient Name</label>
                            <input type="text" name="child_name" id="modalChildName" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Parent / Guardian</label>
                            <input type="text" name="guardian_name" id="modalGuardianName" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" id="modalContactNumber" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Birthdate</label>
                            <input type="date" name="birthdate" class="form-control" max="{{ now()->toDateString() }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Sex</label>
                            <select name="sex" class="form-select" required>
                                <option value="">Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Height</label>
                            <input type="text" name="height" class="form-control" placeholder="Example: 100 cm">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Weight</label>
                            <input type="text" name="weight" class="form-control" placeholder="Example: 20 kg">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Appointment Status After Saving</label>
                            <select name="appointment_status" class="form-select">
                                <option value="done">Done</option>
                                <option value="follow_up">Suggest Follow-up</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        After saving, this patient will be added to the Patients page and the appointment status will be updated.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        Save Patient Details
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    const liveUrl = "{{ route('appointments.live') }}";
    const appointmentFilter = document.getElementById('appointmentFilter');
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

    function escapeAttribute(value) {
        if (value === null || value === undefined || value === '') {
            return '';
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

    function statusLabel(status) {
        if (status === 'Approved') {
            return 'Booked';
        }

        if (status === 'Completed') {
            return 'Done';
        }

        if (status === 'Rescheduled') {
            return 'For Follow-up';
        }

        if (status === 'Cancelled') {
            return 'Cancelled';
        }

        return status;
    }

    function statusClass(status) {
        if (status === 'Approved') {
            return 'status-badge status-booked';
        }

        if (status === 'Completed') {
            return 'status-badge status-done';
        }

        if (status === 'Rescheduled') {
            return 'status-badge status-followup';
        }

        if (status === 'Cancelled') {
            return 'status-badge status-cancelled';
        }

        return 'status-badge status-booked';
    }

    function renderAppointments(data) {
        bookingTotal.textContent = data.total;
        bookingTitle.textContent = data.filter_label;
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
                    <td colspan="9" class="text-center text-muted py-4">
                        No appointments found for the selected schedule.
                    </td>
                </tr>
            `;

            return;
        }

        let rows = '';

        data.appointments.forEach(function (appointment) {
            rows += `
                <tr>
                    <td>
                        <button
                            type="button"
                            class="action-dots-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#patientDetailsModal"
                            data-appointment-no="${escapeAttribute(appointment.appointment_no)}"
                            data-child-name="${escapeAttribute(appointment.child_name)}"
                            data-guardian-name="${escapeAttribute(appointment.guardian_name)}"
                            data-contact-number="${escapeAttribute(appointment.contact_number)}"
                        >
                            ⋮
                        </button>
                    </td>
                    <td>${formatDate(appointment.appointment_date)}</td>
                    <td class="fw-bold">${formatTime(appointment.appointment_time)}</td>
                    <td>${escapeHtml(appointment.appointment_no)}</td>
                    <td>${escapeHtml(appointment.child_name)}</td>
                    <td>${escapeHtml(appointment.guardian_name)}</td>
                    <td>${escapeHtml(appointment.contact_number)}</td>
                    <td>${escapeHtml(appointment.reason_for_visit)}</td>
                    <td>
                        <span class="${statusClass(appointment.status)}">
                            ${statusLabel(appointment.status)}
                        </span>
                    </td>
                </tr>
            `;
        });

        bookingTableBody.innerHTML = rows;
    }

    function loadBookings() {
        fetch(liveUrl + '?filter=' + encodeURIComponent(appointmentFilter.value))
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

    appointmentFilter.addEventListener('change', function () {
        loadBookings();
    });

    const patientDetailsModal = document.getElementById('patientDetailsModal');

    patientDetailsModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        document.getElementById('modalAppointmentNo').value = button.getAttribute('data-appointment-no');
        document.getElementById('modalChildName').value = button.getAttribute('data-child-name');
        document.getElementById('modalGuardianName').value = button.getAttribute('data-guardian-name');
        document.getElementById('modalContactNumber').value = button.getAttribute('data-contact-number');
    });

    loadBookings();

    setInterval(loadBookings, 5000);
</script>

@endsection
