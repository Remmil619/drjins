@extends('layouts.app')

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
            grid-template-columns: repeat(4, 1fr);
        }

        .time-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 575px) {
        .calendar-header-grid,
        .calendar-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .time-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .calendar-empty {
            display: none;
        }
    }
</style>

<div class="hero text-center">
    <div class="container">
        <h1 class="fw-bold">Online Appointment Booking</h1>
        <p class="mb-0">Book an appointment for Dr. Jn's Childer Clinic, Abuyog, Leyte.</p>
    </div>
</div>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">

            <div class="clinic-note mb-4">
                <strong>Reminder:</strong>
                Green dates and time slots are available. Red dates or time slots are already closed or fully booked.
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please check the form.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card booking-card">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3">Appointment Request Form</h4>

                    <form action="{{ route('booking.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="appointment_date" id="appointment_date" value="{{ old('appointment_date') }}">
                        <input type="hidden" name="appointment_time" id="appointment_time" value="{{ old('appointment_time') }}">

                        <div class="mb-3">
                            <label class="form-label">Guardian / Parent Name</label>
                            <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Child's Name</label>
                            <input type="text" name="child_name" class="form-control" value="{{ old('child_name') }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number') }}" placeholder="Example: 09XXXXXXXXXX" required>
                        </div>

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

                            <div id="selectedDateText" class="text-muted mb-3">
                                Please select an available date first.
                            </div>

                            <div class="time-grid" id="timeSlots"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Visit</label>
                            <textarea name="reason_for_visit" class="form-control" rows="4" placeholder="Example: Check-up, fever, cough, follow-up consultation">{{ old('reason_for_visit') }}</textarea>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" name="consent" value="1" class="form-check-input" required>
                            <label class="form-check-label">
                                I consent to the use of this information for appointment processing only.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-book w-100">
                            Submit Appointment Request
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

    function clearDateSelection() {
        document.querySelectorAll('.date-btn').forEach(button => {
            button.classList.remove('selected');
        });
    }

    function clearTimeSelection() {
        document.querySelectorAll('.time-btn').forEach(button => {
            button.classList.remove('selected');
        });
    }

    function showTimeSlots(dateValue) {
        const selectedDate = calendarDates.find(item => item.date === dateValue);

        appointmentDateInput.value = dateValue;
        appointmentTimeInput.value = '';

        clearDateSelection();

        const selectedDateButton = document.querySelector('.date-btn[data-date="' + dateValue + '"]');
        if (selectedDateButton) {
            selectedDateButton.classList.add('selected');
        }

        selectedDateText.innerHTML = 'Selected date: <strong>' + selectedDate.full_label + '</strong>';
        timeSlotsContainer.innerHTML = '';

        selectedDate.slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'time-chip time-btn';
            button.textContent = slot.label;
            button.dataset.time = slot.time;

            if (slot.available) {
                button.addEventListener('click', function () {
                    appointmentTimeInput.value = slot.time;
                    clearTimeSelection();
                    button.classList.add('selected');
                });
            } else {
                button.classList.add('booked');
                button.disabled = true;
                button.textContent = slot.label + ' - Booked';
            }

            timeSlotsContainer.appendChild(button);
        });

        if (oldTime) {
            const oldTimeButton = document.querySelector('.time-btn[data-time="' + oldTime + '"]');

            if (oldTimeButton && !oldTimeButton.disabled) {
                oldTimeButton.classList.add('selected');
                appointmentTimeInput.value = oldTime;
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
