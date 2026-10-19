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
            'guardian_first_name' => 'required|string|max:80',
            'guardian_middle_name' => 'nullable|string|max:80',
            'guardian_surname' => 'required|string|max:80',
            'guardian_relationship' => 'required|string|max:100',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_barangay' => 'required|string|max:150',
            'guardian_municipality' => 'required|string|max:150',
            'guardian_province' => 'required|string|max:150',
            'child_first_name' => 'required|string|max:80',
            'child_middle_name' => 'nullable|string|max:80',
            'child_surname' => 'required|string|max:80',
            'child_date_of_birth' => 'required|date_format:Y-m-d|before_or_equal:today',
            'child_sex' => 'required|in:Male,Female',
            'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'allergies' => 'nullable|string|max:2000',
            'current_medications' => 'nullable|string|max:2000',
            'medical_notes' => 'nullable|string|max:2000',
            'appointment_date' => 'required|date_format:Y-m-d|after_or_equal:today|before_or_equal:'.now()->addDays(13)->toDateString(),
            'appointment_time' => 'required|date_format:H:i',
            'reason_for_visit' => 'nullable|string|max:1000',
            'consent' => 'accepted',
        ]);

        foreach (['guardian', 'child'] as $person) {
            $validated[$person.'_name'] = implode(' ', array_filter([
                $validated[$person.'_first_name'],
                $validated[$person.'_middle_name'] ?? null,
                $validated[$person.'_surname'],
            ], fn ($part) => $part !== null && $part !== ''));
        }

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
                    'guardian_address' => implode(', ', [
                        $validated['guardian_barangay'],
                        $validated['guardian_municipality'],
                        $validated['guardian_province'],
                    ]),
                    'child_name' => $validated['child_name'],
                    'child_date_of_birth' => $validated['child_date_of_birth'],
                    'child_sex' => $validated['child_sex'],
                    'contact_number' => $validated['contact_number'],
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
                    ->with('booking_success', 'Your appointment has been booked successfully. Please remember your appointment date and time.');
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
            return redirect()->route('booking.status', []);
        }
        $appointment = Appointment::where('appointment_no', $appointment_no)->firstOrFail();

        return view('booking.success', compact('appointment'));
    }

    public function status(Request $request)
    {
        $appointments = collect();

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'child_name' => 'required|string|max:255',
                'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            ]);
            $localNumber = preg_replace('/^\+63/', '0', $data['contact_number']);
            $appointments = Appointment::whereRaw('LOWER(child_name) = ?', [mb_strtolower(preg_replace('/\\s+/u', ' ', trim($data['child_name'])))])
                ->whereIn('contact_number', [$localNumber, '+63'.substr($localNumber, 1)])->orderByDesc('appointment_date')->orderByDesc('appointment_time')->get();
        }

        return view('booking.status', compact('appointments'));
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
                ->whereNotIn('status', ['Declined', 'Cancelled', 'No Show'])
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
            ->whereNotIn('status', ['Declined', 'Cancelled', 'No Show'])
            ->exists();
    }
}
