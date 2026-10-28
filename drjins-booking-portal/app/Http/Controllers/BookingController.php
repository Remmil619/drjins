<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        $birthDate = $request->input('child_date_of_birth');
        if (is_string($birthDate) && preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $birthDate, $parts)) {
            $year = (int) $parts[3] + ((int) $parts[3] <= 69 ? 2000 : 1900);
            $request->merge(['child_date_of_birth' => sprintf('%04d-%s-%s', $year, $parts[1], $parts[2])]);
        }

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
            'child_date_of_birth' => 'bail|required|date_format:Y-m-d|before_or_equal:'.now('Asia/Manila')->toDateString().'|after:'.now('Asia/Manila')->subYearsNoOverflow(13)->toDateString(),
            'child_sex' => 'required|in:Male,Female',
            'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'allergies' => 'nullable|string|max:2000',
            'current_medications' => 'nullable|string|max:2000',
            'medical_notes' => 'nullable|string|max:2000',
            'appointment_date' => 'required|date_format:Y-m-d|after_or_equal:today|before_or_equal:'.now()->addDays(27)->toDateString(),
            'appointment_time' => 'required|date_format:H:i',
            'reason_for_visit' => 'nullable|string|max:1000',
            'consent' => 'accepted',
        ], [
            'child_date_of_birth.required' => "Please enter the child's date of birth.",
            'child_date_of_birth.date_format' => 'Please enter a valid date of birth in MM/DD/YY format.',
            'child_date_of_birth.before_or_equal' => 'Date of birth cannot be in the future. The child must already be born.',
            'child_date_of_birth.after' => 'The clinic only accepts children aged 12 and below.',
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
        $inquiries = collect();
        $lookup = $request->session()->pull('status_lookup', []);
        if ($request->isMethod('post')) {
            $lookup = $request->validate([
                'child_name' => 'required|string|max:255',
                'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            ]);
        }

        if (is_array($lookup) && isset($lookup['child_name'], $lookup['contact_number'])) {
            $localNumber = preg_replace('/^\+63/', '0', $lookup['contact_number']);
            $appointments = Appointment::whereRaw('LOWER(child_name) = ?', [mb_strtolower(preg_replace('/\\s+/u', ' ', trim($lookup['child_name'])))])
                ->whereIn('contact_number', [$localNumber, '+63'.substr($localNumber, 1)])->orderByDesc('appointment_date')->orderByDesc('appointment_time')->get();
            $appointments->each(function ($appointment) {
                $appointment->setRelation('inquiryReplies', collect());
            });
            $inquiries = \Illuminate\Support\Facades\DB::table('inquiries')
                ->where(function ($query) use ($localNumber) {
                    $query->where('contact_number', $localNumber)->orWhere('contact_number', '+63'.substr($localNumber, 1));
                })->get();
            foreach ($inquiries as $inquiry) {
                $inquiry->replies = \Illuminate\Support\Facades\DB::table('inquiry_replies')
                    ->where('inquiry_reference', $inquiry->reference)->orderBy('id')->get();
            }
        }

        return view('booking.status', compact('appointments', 'inquiries', 'lookup'));
    }

    private function clinicSchedule()
    {
        return config('clinic.schedule');
    }

    private function buildCalendarDates()
    {
        $dates = [];
        $schedule = $this->clinicSchedule();

        for ($i = 0; $i < 28; $i++) {
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
