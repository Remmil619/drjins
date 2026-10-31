<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Models\ClinicUnavailableDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'theme' => ClinicSetting::theme(),
            'unavailableDates' => ClinicUnavailableDate::whereDate('date', '>=', today())->orderBy('date')->get(),
        ]);
    }

    public function availability()
    {
        return view('settings.availability', [
            'unavailableDates' => ClinicUnavailableDate::whereDate('date', '>=', today())->orderBy('date')->get(),
        ]);
    }

    public function storeUnavailableDate(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        ClinicUnavailableDate::updateOrCreate(
            ['date' => $data['date']],
            ['reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null, 'created_by' => $request->user()->id],
        );

        return to_route('settings.availability')->with('success', 'Clinic unavailability saved. The online booking calendar will reflect this date when it refreshes.');
    }

    public function destroyUnavailableDate(ClinicUnavailableDate $unavailableDate)
    {
        $unavailableDate->delete();
        return to_route('settings.availability')->with('success', 'The clinic is available for bookings on that date again.');
    }

    public function updateTheme(Request $request)
    {
        $data = $request->validate(['theme' => ['required', 'in:green,blue,violet']]);
        DB::table('clinic_settings')->updateOrInsert(
            ['key' => 'theme'],
            ['value' => $data['theme'], 'updated_at' => now(), 'created_at' => now()],
        );
        return to_route('settings.index')->with('success', 'System theme updated.');
    }
}
