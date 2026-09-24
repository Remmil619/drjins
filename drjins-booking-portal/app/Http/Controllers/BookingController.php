<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'guardian_relationship' => 'required|string|max:100',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_address' => 'required|string|max:500',
            'child_name' => 'required|string|max:255',
            'child_date_of_birth' => 'required|date_format:Y-m-d|before_or_equal:today',
            'child_sex' => 'required|in:Female,Male,Prefer not to say',
            'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => ['nullable', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'allergies' => 'nullable|string|max:2000',
            'current_medications' => 'nullable|string|max:2000',
            'medical_notes' => 'nullable|string|max:2000',
            'appointment_date' => 'required|date_format:Y-m-d|after_or_equal:today|before_or_equal:'.now()->addDays(13)->toDateString(),
            'appointment_time' => 'required|date_format:H:i',
            'reason_for_visit' => 'nullable|string|max:1000',
            'consent' => 'accepted',
        ]);

        try {
            return Cache::lock('appointment-'.$validated['appointment_date'].'-'.$validated['appointment_time'], 15)->block(5, function () use ($validated, $request) {
                if (! $this->isSlotAvailable($validated['appointment_date'], $validated['appointment_time'])) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'appointment_time' => 'The selected date or time is no longer available. Please choose another schedule.',
                        ]);
                }

                $appointmentNo = 'APT-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

                $appointment = Appointment::create([
                    'appointment_no' => $appointmentNo,
                    'guardian_name' => $validated['guardian_name'],
                    'guardian_relationship' => $validated['guardian_relationship'],
                    'guardian_email' => $validated['guardian_email'] ?? null,
                    'guardian_address' => $validated['guardian_address'],
                    'child_name' => $validated['child_name'],
                    'child_date_of_birth' => $validated['child_date_of_birth'],
                    'child_sex' => $validated['child_sex'],
                    'contact_number' => $validated['contact_number'],
                    'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                    'emergency_contact_number' => $validated['emergency_contact_number'] ?? null,
                    'allergies' => $validated['allergies'] ?? null,
                    'current_medications' => $validated['current_medications'] ?? null,
                    'medical_notes' => $validated['medical_notes'] ?? null,
                    'appointment_date' => $validated['appointment_date'],
                    'appointment_time' => $validated['appointment_time'],
                    'reason_for_visit' => $validated['reason_for_visit'] ?? null,
                    'status' => 'Approved',
                    'staff_response' => 'Your appointment has been booked successfully. Please arrive 10 minutes before your scheduled time.',
                    'responded_at' => now(),
                ]);

                $references = $request->session()->get('booking_references', []);
                $references[] = $appointment->appointment_no;
                $request->session()->put('booking_references', array_slice($references, -10));

                return redirect()->route('booking.success', $appointment->appointment_no)
                    ->with('booking_success', 'Your appointment has been booked successfully. Save your reference number below.');
            });
        } catch (LockTimeoutException $e) {
            return back()->withInput()->withErrors([
                'appointment_time' => 'This slot is being booked by another visitor. Please try again or choose another time.',
            ]);
        }
    }

    public function success($appointment_no)
    {
        if (! in_array($appointment_no, session('booking_references', []), true)) {
            return redirect()->route('booking.status', ['appointment_no' => $appointment_no]);
        }
        $appointment = Appointment::where('appointment_no', $appointment_no)->firstOrFail();

        return view('booking.success', compact('appointment'));
    }

    public function status(Request $request)
    {
        $appointment = null;

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'appointment_no' => 'required|string|max:40',
                'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            ]);
            $localNumber = preg_replace('/^\+63/', '0', $data['contact_number']);
            $appointment = Appointment::where('appointment_no', strtoupper(trim($data['appointment_no'])))
                ->whereIn('contact_number', [$localNumber, '+63'.substr($localNumber, 1)])->first();
        }

        return view('booking.status', compact('appointment'));
    }

    private function clinicSchedule()
    {
        return config('clinic.schedule');
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

            $timeSlots = collect($slots)->map(function ($slot) use ($bookedTimes, $dateString) {
                return [
                    'time' => $slot,
                    'label' => Carbon::createFromFormat('H:i', $slot)->format('h:i A'),
                    'available' => ! in_array($slot, $bookedTimes) && Carbon::parse($dateString.' '.$slot)->isFuture(),
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
        if (! Carbon::parse($date.' '.$time)->isFuture()) {
            return false;
        }
        $dateObject = Carbon::parse($date);
        $dayOfWeek = $dateObject->dayOfWeekIso;
        $schedule = $this->clinicSchedule();
        $time = Carbon::parse($time)->format('H:i');

        if (! isset($schedule[$dayOfWeek])) {
            return false;
        }

        if (! in_array($time, $schedule[$dayOfWeek])) {
            return false;
        }

        return ! Appointment::whereDate('appointment_date', $dateObject->toDateString())
            ->whereIn('appointment_time', [$time, $time.':00'])
            ->whereNotIn('status', ['Declined', 'Cancelled'])
            ->exists();
    }
}
