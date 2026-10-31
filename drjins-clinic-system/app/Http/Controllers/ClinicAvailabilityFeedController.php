<?php

namespace App\Http\Controllers;

use App\Models\ClinicUnavailableDate;
use Illuminate\Http\Request;

class ClinicAvailabilityFeedController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = (string) config('services.booking_api.token');
        abort_unless($token !== '' && hash_equals($token, (string) $request->header('X-Booking-Token')), 403);

        return response()->json([
            'unavailable_dates' => ClinicUnavailableDate::whereDate('date', '>=', today())
                ->orderBy('date')->get()->map(fn ($entry) => [
                    'date' => $entry->date->toDateString(),
                    'reason' => $entry->reason,
                ])->values(),
        ])->header('Cache-Control', 'no-store');
    }
}
