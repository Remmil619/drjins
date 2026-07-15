<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function create()
    {
        $calendarDates = $this->buildCalendarDates();

        return view('booking.create', compact('calendarDates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guardian_name' => 'required|string|max:255',
            'child_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'reason_for_visit' => 'nullable|string|max:1000',
            'consent' => 'required',
        ]);

        if (!$this->isSlotAvailable($validated['appointment_date'], $validated['appointment_time'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'appointment_time' => 'The selected date or time is no longer available. Please choose another schedule.',
                ]);
        }

        $appointmentNo = 'APT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $appointment = Appointment::create([
            'appointment_no' => $appointmentNo,
            'guardian_name' => $validated['guardian_name'],
            'child_name' => $validated['child_name'],
            'contact_number' => $validated['contact_number'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'reason_for_visit' => $validated['reason_for_visit'] ?? null,
            'status' => 'Approved',
            'staff_response' => 'Your appointment has been booked successfully. Please arrive 10 minutes before your scheduled time.',
            'responded_at' => now(),
        ]);

        return redirect()->route('booking.success', $appointment->appointment_no);
    }

    public function success($appointment_no)
    {
        $appointment = Appointment::where('appointment_no', $appointment_no)->firstOrFail();

        return view('booking.success', compact('appointment'));
    }

    public function status(Request $request)
    {
        $appointment = null;

        if ($request->appointment_no) {
            $appointment = Appointment::where('appointment_no', $request->appointment_no)->first();
        }

        return view('booking.status', compact('appointment'));
    }

    private function clinicSchedule()
    {
        return [
            1 => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'],
            2 => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'],
            3 => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'],
            4 => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'],
            5 => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'],
            6 => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00'],
        ];
    }

    private function buildCalendarDates()
    {
        $dates = [];
        $schedule = $this->clinicSchedule();

        for ($i = 0; $i < 14; $i++) {
            $date = now()->addDays($i);
            $dateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeekIso;
            $slots = $schedule[$dayOfWeek] ?? [];

            $bookedTimes = Appointment::whereDate('appointment_date', $dateString)
                ->whereNotIn('status', ['Declined', 'Cancelled'])
                ->pluck('appointment_time')
                ->map(function ($time) {
                    return Carbon::parse($time)->format('H:i');
                })
                ->toArray();

            $timeSlots = collect($slots)->map(function ($slot) use ($bookedTimes) {
                return [
                    'time' => $slot,
                    'label' => Carbon::createFromFormat('H:i', $slot)->format('h:i A'),
                    'available' => !in_array($slot, $bookedTimes),
                ];
            })->values()->toArray();

            $availableCount = collect($timeSlots)->where('available', true)->count();

            if (count($slots) === 0) {
                $status = 'Closed';
                $clickable = false;
            } elseif ($availableCount === 0) {
                $status = 'Fully Booked';
                $clickable = false;
            } else {
                $status = 'Available';
                $clickable = true;
            }

            $dates[] = [
                'date' => $dateString,
                'day' => $date->format('D'),
                'label' => $date->format('M d'),
                'full_label' => $date->format('F d, Y'),
                'status' => $status,
                'clickable' => $clickable,
                'available_count' => $availableCount,
                'slots' => $timeSlots,
            ];
        }

        return $dates;
    }

    private function isSlotAvailable($date, $time)
    {
        $dateObject = Carbon::parse($date);
        $dayOfWeek = $dateObject->dayOfWeekIso;
        $schedule = $this->clinicSchedule();
        $time = Carbon::parse($time)->format('H:i');

        if (!isset($schedule[$dayOfWeek])) {
            return false;
        }

        if (!in_array($time, $schedule[$dayOfWeek])) {
            return false;
        }

        return !Appointment::whereDate('appointment_date', $dateObject->toDateString())
            ->whereTime('appointment_time', $time)
            ->whereNotIn('status', ['Declined', 'Cancelled'])
            ->exists();
    }
}