@extends('layouts.app')
@section('title', 'Book an appointment')

@section('content')

@php
    use Carbon\Carbon;

    $firstDayOffset = count($calendarDates) ? Carbon::parse($calendarDates[0]['date'])->dayOfWeekIso : 1;
@endphp

<style>
    .calendar-wrapper {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 18px;
    }

    .calendar-header-grid,
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }

    .calendar-weekday {
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        color: #6b7280;
        padding: 6px 0;
    }

    .calendar-empty {
        min-height: 92px;
    }

    .calendar-day {
        border: 1px solid #d1d5db;
        border-radius: 14px;
        background: #fff;
        min-height: 92px;
        padding: 8px 6px;
        text-align: center;
        transition: 0.2s ease;
        width: 100%;
    }

    .calendar-day .day-name {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .calendar-day .day-number {
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 4px;
    }

    .calendar-day .month-label {
        font-size: 11px;
        margin-bottom: 4px;
    }

    .calendar-day .status-label {
        font-size: 11px;
        line-height: 1.2;
    }

    .calendar-day.available {
        border-color: #198754;
        color: #198754;
    }

    .calendar-day.available:hover {
        background: #ecfdf3;
        transform: translateY(-1px);
    }

    .calendar-day.closed {
        border-color: #f199a0;
        color: #dc3545;
        background: #fffafb;
    }

    .calendar-day.selected {
        background: #198754;
        color: #fff !important;
        border-color: #198754;
        box-shadow: 0 6px 18px rgba(25, 135, 84, 0.20);
    }

    .calendar-day.selected .status-label,
    .calendar-day.selected .month-label,
    .calendar-day.selected .day-name,
    .calendar-day.selected .day-number {
        color: #fff !important;
    }

    .time-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .time-chip {
        border: 1px solid #198754;
        background: #fff;
        color: #198754;
        border-radius: 12px;
        padding: 10px 8px;
        font-weight: 600;
        font-size: 14px;
        transition: 0.2s ease;
        width: 100%;
    }

    .time-chip:hover {
        background: #ecfdf3;
    }

    .time-chip.booked {
        border-color: #dc3545;
        color: #dc3545;
        background: #fff5f5;
    }

    .time-chip.selected {
        background: #198754;
        color: #fff;
        border-color: #198754;
    }

    @media (max-width: 991px) {
        .calendar-header-grid,
        .calendar-grid {
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .time-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 575px) {
        .calendar-header-grid,
        .calendar-grid {
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .time-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .calendar-empty {
            min-height: 65px;
        }
        .calendar-grid, .calendar-header-grid { gap: 3px; }
        .calendar-day { min-height: 65px; border-radius: 7px; padding: 6px 1px; }
        .calendar-day .day-number { font-size: 17px; }
        .calendar-day .day-name, .calendar-day .status-label { display: none; }
        .calendar-day .month-label, .calendar-weekday { font-size: 10px; }
        .calendar-wrapper { padding: 8px; }
    }
</style>

<div class="hero text-center">
    <div class="container">
        <h1 class="fw-bold">Online Appointment Booking</h1>
        <p class="mb-0">Plan your child's checkup at {{ config('clinic.name') }}, Abuyog, Leyte.</p>
    </div>
</div>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">

            <div class="clinic-note mb-4">
                <strong>Reminder:</strong>
                Choose from the next 14 days. Green dates and times are available; red ones are unavailable. All times are Philippine time.
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" id="serverBookingErrors" role="alert" tabindex="-1">
                    <strong>Please check the form.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card booking-card" id="appointment-form" tabindex="-1">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-2">Appointment Request Form</h4>
                    <p class="small">Child checkup · No online payment required. Fees are confirmed by the clinic.</p>

                    <form id="bookingForm" action="{{ route('booking.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="appointment_date" id="appointment_date" value="{{ old('appointment_date') }}">
                        <input type="hidden" name="appointment_time" id="appointment_time" value="{{ old('appointment_time') }}">

                        <p class="small text-muted mb-4">Please provide parent or guardian details separately from the child’s. No password is needed.</p>

                        <fieldset class="mb-4"><legend class="h5 fw-bold border-bottom pb-2 mb-3">Parent or Guardian Information</legend><div class="row g-3">
                            <div class="col-12"><label class="form-label" for="guardian_first_name">First Name</label><input type="text" id="guardian_first_name" name="guardian_first_name" placeholder="First Name (Ex. DAVID JR, JOHN III)" class="form-control" value="{{ old('guardian_first_name') }}" autocomplete="section-guardian given-name" maxlength="80" required></div>
                            <div class="col-12"><label class="form-label" for="guardian_middle_name">Middle Name <span class="text-muted">(optional)</span></label><input type="text" id="guardian_middle_name" name="guardian_middle_name" placeholder="Middle Name" class="form-control" value="{{ old('guardian_middle_name') }}" autocomplete="section-guardian additional-name" maxlength="80"></div>
                            <div class="col-12"><label class="form-label" for="guardian_surname">Last Name</label><input type="text" id="guardian_surname" name="guardian_surname" placeholder="Surname" class="form-control" value="{{ old('guardian_surname') }}" autocomplete="section-guardian family-name" maxlength="80" required></div>
                            <div class="col-12"><label class="form-label" for="guardian_relationship">Relationship to child</label><select id="guardian_relationship" name="guardian_relationship" class="form-select" required><option value="">Choose one</option>@foreach(['Mother', 'Father', 'Legal guardian', 'Other relative', 'Other'] as $relationship)<option value="{{ $relationship }}" @selected(old('guardian_relationship') === $relationship)>{{ $relationship }}</option>@endforeach</select></div>
                            <div class="col-12"><label class="form-label" for="contact_number">Mobile Number</label><input type="tel" id="contact_number" name="contact_number" class="form-control" value="{{ old('contact_number') }}" placeholder="Mobile Number (09XXXXXXXXX)" pattern="(09[0-9]{9}|\+639[0-9]{9})" autocomplete="tel" required><div class="form-text">Used for appointment updates and status checks.</div></div>
                            <div class="col-12"><label class="form-label" for="guardian_email">Email address <span class="text-muted">(optional)</span></label><input type="email" id="guardian_email" name="guardian_email" placeholder="Enter Email Address" aria-describedby="guardianEmailHelp" class="form-control" value="{{ old('guardian_email') }}" autocomplete="email" maxlength="255"><div id="guardianEmailHelp" class="form-text">Please use an active email address.</div></div>
                            <div class="col-12"><label class="form-label" for="guardian_address">Home address</label><input type="text" id="guardian_address" name="guardian_address" class="form-control" value="{{ old('guardian_address') }}" autocomplete="street-address" maxlength="500" placeholder="Barangay, municipality, province" required></div>
                        </div></fieldset>

                        <fieldset class="mb-4"><legend class="h5 fw-bold border-bottom pb-2 mb-3">Child’s Information</legend><div class="row g-3">
                            <div class="col-12"><label class="form-label" for="child_first_name">First Name</label><input type="text" id="child_first_name" name="child_first_name" placeholder="First Name (Ex. DAVID JR, JOHN III)" class="form-control" value="{{ old('child_first_name') }}" autocomplete="section-child given-name" maxlength="80" required></div>
                            <div class="col-12"><label class="form-label" for="child_middle_name">Middle Name <span class="text-muted">(optional)</span></label><input type="text" id="child_middle_name" name="child_middle_name" placeholder="Middle Name" class="form-control" value="{{ old('child_middle_name') }}" autocomplete="section-child additional-name" maxlength="80"></div>
                            <div class="col-12"><label class="form-label" for="child_surname">Last Name</label><input type="text" id="child_surname" name="child_surname" placeholder="Surname" class="form-control" value="{{ old('child_surname') }}" autocomplete="section-child family-name" maxlength="80" required></div>
                            <div class="col-12"><label class="form-label" for="child_date_of_birth">Date of birth</label><input type="date" id="child_date_of_birth" name="child_date_of_birth" class="form-control" value="{{ old('child_date_of_birth') }}" max="{{ now()->toDateString() }}" required></div>
                            <div class="col-12"><label class="form-label" for="child_sex">Sex</label><select id="child_sex" name="child_sex" class="form-select" required><option value="">Choose one</option>@foreach(['Male', 'Female'] as $sex)<option value="{{ $sex }}" @selected(old('child_sex') === $sex)>{{ $sex }}</option>@endforeach</select></div>
                            <div class="col-12"><label class="form-label" for="allergies">Known allergies <span class="text-muted">(optional)</span></label><textarea id="allergies" name="allergies" class="form-control" rows="2" maxlength="2000" placeholder="List allergies, or write ‘None known’">{{ old('allergies') }}</textarea></div>
                            <div class="col-12"><label class="form-label" for="current_medications">Current medicines or supplements <span class="text-muted">(optional)</span></label><textarea id="current_medications" name="current_medications" class="form-control" rows="2" maxlength="2000" placeholder="Include name and dose if known">{{ old('current_medications') }}</textarea></div>
                            <div class="col-12"><label class="form-label" for="medical_notes">Relevant health history or notes <span class="text-muted">(optional)</span></label><textarea id="medical_notes" name="medical_notes" class="form-control" rows="3" maxlength="2000" placeholder="Briefly note important past conditions or care needs. You may bring detailed records to the visit.">{{ old('medical_notes') }}</textarea></div>
                        </div></fieldset>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Choose Available Date</label>

                            <div class="calendar-wrapper">
                                <div class="calendar-header-grid mb-2">
                                    <div class="calendar-weekday">Mon</div>
                                    <div class="calendar-weekday">Tue</div>
                                    <div class="calendar-weekday">Wed</div>
                                    <div class="calendar-weekday">Thu</div>
                                    <div class="calendar-weekday">Fri</div>
                                    <div class="calendar-weekday">Sat</div>
                                    <div class="calendar-weekday">Sun</div>
                                </div>

                                <div class="calendar-grid" id="dateButtons">
                                    @for ($i = 1; $i < $firstDayOffset; $i++)
                                        <div class="calendar-empty"></div>
                                    @endfor

                                    @foreach ($calendarDates as $calendarDate)
                                        @php
                                            $dateObject = Carbon::parse($calendarDate['date']);
                                        @endphp

                                        <button
                                            type="button"
                                            class="calendar-day date-btn {{ $calendarDate['clickable'] ? 'available' : 'closed' }}"
                                            data-date="{{ $calendarDate['date'] }}"
                                            aria-label="{{ $calendarDate['full_label'] }}, {{ $calendarDate['status'] }}, {{ $calendarDate['available_count'] }} available slots"
                                            aria-pressed="false"
                                            {{ $calendarDate['clickable'] ? '' : 'disabled' }}
                                        >
                                            <div class="day-name">{{ $dateObject->format('D') }}</div>
                                            <div class="day-number">{{ $dateObject->format('d') }}</div>
                                            <div class="month-label">{{ $dateObject->format('M') }}</div>
                                            <div class="status-label">
                                                {{ $calendarDate['status'] }}
                                                @if ($calendarDate['clickable'])
                                                    <br>{{ $calendarDate['available_count'] }} slots
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Choose Preferred Time</label>

                            <div id="selectedDateText" class="text-muted mb-3" aria-live="polite">
                                Please select an available date first.
                            </div>

                            <div class="time-grid" id="timeSlots"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="reason_for_visit">Reason for Visit (optional)</label>
                            <textarea id="reason_for_visit" name="reason_for_visit" class="form-control" rows="4" maxlength="1000" placeholder="Tell us what you would like to discuss during the checkup">{{ old('reason_for_visit', request('service') === 'child-checkup' ? 'Child checkup' : '') }}</textarea>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" id="consent" name="consent" value="1" class="form-check-input" required @checked(old('consent'))>
                            <label class="form-check-label" for="consent">
                                I consent to the use of the details provided, including the child’s health notes, to arrange and manage this appointment. <a href="{{ route('privacy') }}">Read the privacy notice</a>.
                            </label>
                        </div>

                        <div id="bookingSummary" class="booking-summary" aria-live="polite">Select a date and time to continue.</div>
                        <div id="bookingFeedback" class="alert alert-danger" role="alert" tabindex="-1" hidden></div>
                        <noscript><div class="alert alert-warning">Please enable JavaScript to select your appointment date and time.</div></noscript>
                        <button type="submit" id="submitBooking" class="btn btn-book w-100">
                            Confirm Appointment
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const calendarDates = @json($calendarDates);
    const oldDate = @json(old('appointment_date'));
    const oldTime = @json(old('appointment_time'));

    const appointmentDateInput = document.getElementById('appointment_date');
    const appointmentTimeInput = document.getElementById('appointment_time');
    const selectedDateText = document.getElementById('selectedDateText');
    const timeSlotsContainer = document.getElementById('timeSlots');
    const dateButtons = document.querySelectorAll('.date-btn');
    const bookingForm = document.getElementById('bookingForm');
    const bookingFeedback = document.getElementById('bookingFeedback');
    const submitBooking = document.getElementById('submitBooking');
    bookingForm.noValidate = true;

    function showBookingError(message) {
        bookingFeedback.textContent = message;
        bookingFeedback.hidden = false;
        bookingFeedback.focus();
    }

    bookingForm.addEventListener('submit', function (event) {
        bookingFeedback.hidden = true;
        if (!bookingForm.checkValidity()) {
            event.preventDefault();
            const invalidField = bookingForm.querySelector('input:invalid, select:invalid, textarea:invalid');
            const label = invalidField.labels?.[0]?.textContent.trim() || 'Required information';
            showBookingError('Please check ' + label + ': ' + invalidField.validationMessage);
            invalidField.focus();
            bookingForm.reportValidity();
            return;
        }
        const selectedDate = calendarDates.find(item => item.date === appointmentDateInput.value);
        const selectedSlot = selectedDate?.slots.find(slot => slot.time === appointmentTimeInput.value && slot.available);
        if (!selectedSlot) {
            event.preventDefault();
            showBookingError('Please select an available appointment date and time before confirming.');
            return;
        }
        submitBooking.disabled = true;
        submitBooking.textContent = 'Booking your appointment…';
        bookingForm.setAttribute('aria-busy', 'true');
    });

    window.addEventListener('pageshow', function () {
        submitBooking.disabled = false;
        submitBooking.textContent = 'Confirm Appointment';
        bookingForm.removeAttribute('aria-busy');
    });
    document.getElementById('serverBookingErrors')?.focus();

    function clearDateSelection() {
        document.querySelectorAll('.date-btn').forEach(button => {
            button.classList.remove('selected');
            button.setAttribute('aria-pressed', 'false');
        });
    }

    function clearTimeSelection() {
        document.querySelectorAll('.time-btn').forEach(button => {
            button.classList.remove('selected');
            button.setAttribute('aria-pressed', 'false');
        });
    }

    function showTimeSlots(dateValue) {
        const selectedDate = calendarDates.find(item => item.date === dateValue);
        if (!selectedDate) return;
        bookingFeedback.hidden = true;
        document.getElementById('bookingSummary').textContent = 'Choose an available time for ' + selectedDate.full_label + '.';

        appointmentDateInput.value = dateValue;
        appointmentTimeInput.value = '';

        clearDateSelection();

        const selectedDateButton = document.querySelector('.date-btn[data-date="' + dateValue + '"]');
        if (selectedDateButton) {
            selectedDateButton.classList.add('selected');
            selectedDateButton.setAttribute('aria-pressed', 'true');
        }

        selectedDateText.innerHTML = 'Selected date: <strong>' + selectedDate.full_label + '</strong>';
        timeSlotsContainer.innerHTML = '';

        selectedDate.slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'time-chip time-btn';
            button.textContent = slot.label;
            button.dataset.time = slot.time;
            button.setAttribute('aria-pressed', 'false');

            if (slot.available) {
                button.addEventListener('click', function () {
                    appointmentTimeInput.value = slot.time;
                    clearTimeSelection();
                    button.classList.add('selected');
                    button.setAttribute('aria-pressed', 'true');
                    document.getElementById('submitBooking').disabled = false;
                    document.getElementById('bookingSummary').textContent = 'Your visit: ' + selectedDate.full_label + ' at ' + slot.label + ' (Philippine time).';
                });
            } else {
                button.classList.add('booked');
                button.disabled = true;
                button.textContent = slot.label + ' - Unavailable';
            }

            timeSlotsContainer.appendChild(button);
        });

        if (oldTime && dateValue === oldDate) {
            const oldTimeButton = document.querySelector('.time-btn[data-time="' + oldTime + '"]');

            if (oldTimeButton && !oldTimeButton.disabled) {
                oldTimeButton.click();
            }
        }
    }

    dateButtons.forEach(button => {
        button.addEventListener('click', function () {
            showTimeSlots(this.dataset.date);
        });
    });

    if (oldDate) {
        const oldDateButton = document.querySelector('.date-btn[data-date="' + oldDate + '"]');

        if (oldDateButton && !oldDateButton.disabled) {
            showTimeSlots(oldDate);
        }
    }
</script>

@endsection
