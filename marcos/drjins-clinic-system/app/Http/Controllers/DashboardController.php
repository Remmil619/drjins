<?php

namespace App\Http\Controllers;

use App\Models\OnlineAppointment;
use App\Models\Patient;

class DashboardController extends Controller
{
    public function index()
    {
        $appointments = OnlineAppointment::whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get();

        return view('dashboard.index', [
            'appointments' => $appointments,
            'todayAppointments' => $appointments->count(),
            'pendingAppointments' => OnlineAppointment::where('status', 'Pending')->count(),
            'registeredPatients' => Patient::count(),
        ]);
    }
}
