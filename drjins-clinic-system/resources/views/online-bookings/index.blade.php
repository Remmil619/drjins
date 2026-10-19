@extends('layouts.app')

@section('content')

    <div class="page-header">
        <div class="container">
            <h1 class="fw-bold">Appointments</h1>
            <p class="mb-0">Automatically updated list of patients who booked an appointment online.</p>
        </div>
    </div>
    <div class="container">
        <a class="btn btn-primary mb-3" href="{{ route('queue.index') }}">Check in an existing patient &rarr;</a>
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
                            This page automatically checks the online booking website every 15 seconds.
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
                            {{ match ($selectedFilter) {
                                'all' => 'All Appointments',
                                'upcoming' => 'Upcoming Appointments',
                                default => "Today's Appointments",
                            } }}
                        </h5>
                        <small class="text-muted">
                            Total appointments: <span id="bookingTotal">0</span>
                        </small>
                    </div>
                </div>
                <div class="table-responsive appointments-table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Child's full name</th>
                                <th>Guardian</th>
                                <th>Contact</th>
                                <th>Reason for Visit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="bookingTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Loading appointments...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div
        class="modal fade"
        id="patientDetailsModal"
        tabindex="-1"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content patient-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Complete Patient Details</h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    >
                    </button>
                </div>

                <form method="POST" action="{{ route('appointments.savePatientDetails') }}">
                    @csrf

                    <div class="modal-body">
                        <div class="alert alert-light">
                            <strong>Child's full name:</strong>
                            <span id="modalReferenceDetails">
                            </span>
                        </div>
                        <div
                            id="portalIntakeDetails"
                            class="alert alert-info"
                            style="white-space: pre-wrap"
                            hidden
                        >
                        </div>
                        <input
                            type="hidden"
                            name="appointment_no"
                            id="modalAppointmentNo"
                        >
                        <div class="row g-3 patient-details-fields">
                            <div class="col-12">
                                <label class="form-label">First Name</label>
                                <input
                                    type="text"
                                    name="first_name"
                                    id="modalFirstName"
                                    class="form-control"
                                    required
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Middle Name <span class="text-muted">(Optional)</span>
                                </label>
                                <input
                                    type="text"
                                    name="middle_name"
                                    id="modalMiddleName"
                                    class="form-control"
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Last Name / Surname</label>
                                <input
                                    type="text"
                                    name="last_name"
                                    id="modalLastName"
                                    class="form-control"
                                    required
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Suffix <span class="text-muted">(Optional)</span>
                                </label>
                                <input
                                    type="text"
                                    name="suffix"
                                    id="modalSuffix"
                                    class="form-control"
                                    placeholder="Example: Jr., Sr., III"
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Parent / Guardian</label>
                                <input
                                    type="text"
                                    name="guardian_name"
                                    id="modalGuardianName"
                                    class="form-control"
                                    required
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Contact Number</label>
                                <input
                                    type="text"
                                    name="contact_number"
                                    id="modalContactNumber"
                                    class="form-control"
                                    required
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Birthdate</label>
                                <input
                                    type="date"
                                    name="birthdate"
                                    class="form-control"
                                    max="{{ now()->toDateString() }}"
                                    required
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Sex</label>
                                <select
                                    name="sex"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Select Sex</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Height (cm)</label>
                                <input
                                    type="text"
                                    name="height"
                                    class="form-control"
                                    placeholder="Example: 100 cm"
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Weight (kg)</label>
                                <input
                                    type="text"
                                    name="weight"
                                    class="form-control"
                                    placeholder="Example: 20 kg"
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Temperature (&deg;C)</label>
                                <input
                                    type="number"
                                    name="temperature"
                                    class="form-control"
                                    min="25"
                                    max="45"
                                    step="0.1"
                                    placeholder="Example: 36.5"
                                >
                            </div>
                            <div class="col-12">
                                <label class="form-label">Emergency Contact</label>
                                <input
                                    type="text"
                                    name="emergency_contact"
                                    class="form-control"
                                >
                            </div>
                            <div class="col-12">
                                @include('patients.address-fields')
                            </div>
                            <div class="col-12">
                                <input
                                    type="hidden"
                                    name="appointment_status"
                                    value="done"
                                >
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            Complete the missing patient details to mark this appointment Done and add the patient to
                            Patient Records.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">
                            Done — Add to Patient Records
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div
        class="modal fade"
        id="noShowConfirmation"
        tabindex="-1"
        aria-labelledby="noShowTitle"
        aria-describedby="noShowMessage"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content logout-confirmation-card">
                <div class="modal-body text-center p-4">
                    <div class="logout-confirmation-icon" aria-hidden="true">
                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="M8 2v4m8-4v4M3 10h18M9 14l6 6m0-6-6 6M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <h2 class="logout-confirmation-title" id="noShowTitle">Mark as Didn't Show?</h2>
                    <p class="logout-confirmation-message" id="noShowMessage">Confirm that the patient did not attend this
                        appointment.</p>
                    <p class="small fw-semibold text-muted" id="noShowReference">
                    </p>
                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <button
                            type="button"
                            class="btn btn-light px-4"
                            data-bs-dismiss="modal"
                        >Cancel</button>
                        <button
                            type="button"
                            class="btn btn-primary px-4"
                            id="confirmNoShow"
                        >Yes, Didn't Show</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div
        class="modal fade"
        id="appointmentMessageModal"
        tabindex="-1"
        aria-labelledby="appointmentMessageTitle"
        aria-describedby="appointmentMessageText"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content logout-confirmation-card">
                <div class="modal-body text-center p-4">
                    <div class="logout-confirmation-icon" aria-hidden="true">
                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />
                            <path
                                d="M12 7v6m0 4h.01"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>
                    <h2 class="logout-confirmation-title" id="appointmentMessageTitle">Unable to update appointment</h2>
                    <p class="logout-confirmation-message" id="appointmentMessageText">
                    </p>
                    <button
                        type="button"
                        class="btn btn-primary px-4 mt-2"
                        data-bs-dismiss="modal"
                    >Got it</button>
                </div>
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
            if (status === 'No Show') return "Didn't Show";
            if (status === 'Approved') {
                return 'Booked';
            }

            if (status === 'Completed') {
                return 'Done';
            }

            if (status === 'Rescheduled') {
                return 'For Reschedule';
            }

            if (status === 'Cancelled') {
                return 'Cancelled';
            }

            return status;
        }

        function statusClass(status) {
            if (status === 'No Show') return 'status-badge status-cancelled';
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
            bookingTableBody.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(button => {
                bootstrap.Dropdown.getInstance(button)?.dispose();
            });
            bookingTotal.textContent = data.total;
            bookingTitle.textContent = data.filter_label;
            lastChecked.textContent = data.last_checked;

            if (data.connected) {
                connectionStatus.className = 'badge bg-success';
                connectionStatus.textContent = 'Connected to Online Booking';
            } else {
                connectionStatus.className = 'badge bg-danger';
                connectionStatus.textContent = 'Portal sync incomplete - showing saved bookings';
            }

            if (data.appointments.length === 0) {
                bookingTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No appointments found for the selected schedule.
                    </td>
                </tr>
            `;

                return;
            }

            let rows = '';

            data.appointments.forEach(function(appointment) {
                rows += `
                <tr>
                    <td>${formatDate(appointment.appointment_date)}</td>
                    <td class="fw-bold">${formatTime(appointment.appointment_time)}</td>
                    <td>${escapeHtml(appointment.child_name)}</td>
                    <td>${escapeHtml(appointment.guardian_name)}</td>
                    <td>${escapeHtml(appointment.contact_number)}</td>
                    <td>${escapeHtml(appointment.reason_for_visit)}</td>

                    <td>
                        <div class="dropdown">
                            <button
                                class="${statusClass(appointment.status)} dropdown-toggle status-dropdown-btn"
                                type="button"
                                data-bs-toggle="dropdown"
                                data-bs-config='{"popperConfig":{"strategy":"fixed"}}'
                                data-bs-boundary="viewport"
                                aria-expanded="false"
                            >
                                ${escapeHtml(statusLabel(appointment.status))}
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end status-dropdown-menu">
<li><button type="button" class="dropdown-item update-status-option"
${appointment.status_locked || ['Cancelled', 'Declined', 'No Show'].includes(appointment.status) ? 'disabled' : ''}
data-appointment-no="${escapeAttribute(appointment.appointment_no)}"
data-child-name="${escapeAttribute(appointment.child_name)}"
data-guardian-name="${escapeAttribute(appointment.guardian_name)}"
data-contact-number="${escapeAttribute(appointment.contact_number)}"
data-intake="${escapeAttribute(JSON.stringify({birthdate: appointment.child_date_of_birth, sex: appointment.child_sex, address: appointment.guardian_address, emergency: appointment.emergency_contact_number, relationship: appointment.guardian_relationship, email: appointment.guardian_email, emergencyName: appointment.emergency_contact_name, allergies: appointment.allergies, medicines: appointment.current_medications, notes: appointment.medical_notes}))}"
data-status="Completed">Done</button></li>
<li><button type="button" class="dropdown-item update-status-option"
${!appointment.can_mark_no_show ? 'disabled' : ''}
data-appointment-no="${escapeAttribute(appointment.appointment_no)}"
data-child-name="${escapeAttribute(appointment.child_name)}"
data-status="No Show">Didn't Show</button></li>
                            </ul>
                        </div>
                    </td>




                </tr>
            `;
            });

            bookingTableBody.innerHTML = rows;
        }

        function openPatientModal(button) {
            const modalElement = document.getElementById('patientDetailsModal');
            modalElement.querySelector('form').reset();
            const intake = JSON.parse(button.getAttribute('data-intake') || '{}');
            modalElement.querySelector('[name="birthdate"]').value = intake.birthdate || '';
            modalElement.querySelector('[name="sex"]').value = ['Male', 'Female'].includes(intake.sex) ? intake.sex : '';
            modalElement.querySelector('[name="address"]').value = intake.address || '';
            modalElement.querySelector('[name="emergency_contact"]').value = intake.emergency || '';
            const notes = document.getElementById('portalIntakeDetails');
            notes.textContent = Object.entries({
                    Relationship: intake.relationship,
                    Email: intake.email,
                    'Emergency contact': intake.emergencyName,
                    'Reported sex': intake.sex,
                    Allergies: intake.allergies,
                    Medicines: intake.medicines,
                    'Health notes': intake.notes
                })
                .filter(([, value]) => value).map(([label, value]) => label + ': ' + value).join('\n');
            notes.hidden = !notes.textContent;
            document.getElementById('modalAppointmentNo').value = button.getAttribute('data-appointment-no');
            populatePatientName(button.getAttribute('data-child-name'));
            document.getElementById('modalReferenceDetails').textContent = button.getAttribute('data-child-name');
            document.getElementById('modalGuardianName').value = button.getAttribute('data-guardian-name');
            document.getElementById('modalContactNumber').value = button.getAttribute('data-contact-number');

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

            modal.show();
        }

        function populatePatientName(fullName) {
            const parts = String(fullName || '').trim().split(/\s+/).filter(Boolean);
            const knownSuffixes = ['jr', 'jr.', 'sr', 'sr.', 'ii', 'iii', 'iv'];
            let suffix = '';

            if (parts.length > 1 && knownSuffixes.includes(parts[parts.length - 1].toLowerCase())) {
                suffix = parts.pop();
            }

            const firstName = parts.shift() || '';
            const lastName = parts.length > 0 ? parts.pop() : '';

            document.getElementById('modalFirstName').value = firstName;
            document.getElementById('modalMiddleName').value = parts.join(' ');
            document.getElementById('modalLastName').value = lastName;
            document.getElementById('modalSuffix').value = suffix;
        }

        let updatingStatus = false;

        function showAppointmentMessage(message, title = 'Unable to update appointment') {
            return new Promise(resolve => {
                const element = document.getElementById('appointmentMessageModal');
                document.getElementById('appointmentMessageTitle').textContent = title;
                document.getElementById('appointmentMessageText').textContent = message;
                element.addEventListener('shown.bs.modal', () => element.querySelector('button').focus(), {
                    once: true
                });
                element.addEventListener('hidden.bs.modal', resolve, {
                    once: true
                });
                bootstrap.Modal.getOrCreateInstance(element).show();
            });
        }

        function confirmNoShow(childName) {
            return new Promise(resolve => {
                const element = document.getElementById('noShowConfirmation');
                const confirmButton = document.getElementById('confirmNoShow');
                const modal = bootstrap.Modal.getOrCreateInstance(element);
                let confirmed = false;
                document.getElementById('noShowReference').textContent = childName;
                const accept = () => {
                    confirmed = true;
                    modal.hide();
                };
                confirmButton.addEventListener('click', accept);
                element.addEventListener('shown.bs.modal', () => element.querySelector('[data-bs-dismiss="modal"]')
                    .focus(), {
                        once: true
                    });
                element.addEventListener('hidden.bs.modal', () => {
                    confirmButton.removeEventListener('click', accept);
                    resolve(confirmed);
                }, {
                    once: true
                });
                modal.show();
            });
        }
        async function updateAppointmentStatus(appointmentNo, status, button) {
            if (updatingStatus) return;
            updatingStatus = true;
            try {
                if (status === 'No Show' && !await confirmNoShow(button.getAttribute('data-child-name'))) return;
                const response = await fetch(status === 'Completed' ? "{{ route('appointments.done') }}" :
                    "{{ route('appointments.updateStatus') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            appointment_no: appointmentNo,
                            status: status
                        })
                    });
                const data = await response.json();
                if (!response.ok && (data.errors?.birthdate || data.errors?.sex)) {
                    openPatientModal(button);
                    return;
                }
                if (!response.ok) throw new Error(data.message || 'Unable to mark appointment Done.');
                if (data.portal_updated === false) await showAppointmentMessage(data.message, 'Appointment saved');
                if (data.patient_url) window.location.assign(data.patient_url);
            } catch (error) {
                await showAppointmentMessage(error.message || 'Unable to update appointment status.');
            } finally {
                updatingStatus = false;
                loadBookings(true);
            }
        }

        bookingTableBody.addEventListener('click', function(event) {
            if (event.target.classList.contains('add-patient-option')) {
                openPatientModal(event.target);
            }

            if (event.target.classList.contains('update-status-option')) {
                const appointmentNo = event.target.getAttribute('data-appointment-no');
                const status = event.target.getAttribute('data-status');

                updateAppointmentStatus(appointmentNo, status, event.target);
            }
        });





        let activeRequest = null;

        function isEditingBooking() {
            return document.querySelector(
                '#patientDetailsModal.show, #noShowConfirmation.show, #appointmentMessageModal.show, .status-dropdown-menu.show'
            ) !== null;
        }

        function loadBookings(force = false) {
            if (updatingStatus || (!force && (activeRequest || isEditingBooking()))) return;
            activeRequest?.abort();
            const controller = new AbortController();
            activeRequest = controller;
            const filter = appointmentFilter.value;
            fetch(liveUrl + '?filter=' + encodeURIComponent(filter), {
                    signal: controller.signal,
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Request failed');
                    return response.json();
                })
                .then(data => {
                    if (activeRequest === controller && filter === appointmentFilter.value && !isEditingBooking())
                        renderAppointments(data);
                })
                .catch(error => {
                    if (error.name === 'AbortError') return;
                    connectionStatus.className = 'badge bg-danger';
                    connectionStatus.textContent = 'Connection Error';
                    lastChecked.textContent = 'Failed';
                }).finally(() => {
                    if (activeRequest === controller) activeRequest = null;
                });
        }

        appointmentFilter.addEventListener('change', function() {
            loadBookings(true);
        });

        window.addEventListener('DOMContentLoaded', () => {
            const previousInput = {{ \Illuminate\Support\Js::from(session()->getOldInput()) }};
            if (previousInput.appointment_no) {
                const modal = document.getElementById('patientDetailsModal');
                const form = modal.querySelector('form');
                document.getElementById('modalReferenceDetails').textContent = [previousInput.first_name,
                    previousInput.middle_name, previousInput.last_name, previousInput.suffix
                ].filter(Boolean).join(' ');
                Object.entries(previousInput).forEach(([name, value]) => {
                    const field = form.elements.namedItem(name);
                    if (field && name !== '_token') field.value = value ?? '';
                });
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
            loadBookings();
        });

        setInterval(() => loadBookings(), 15000);
    </script>

@endsection
