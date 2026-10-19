<?php

namespace App\Http\Controllers;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\QueueEntry;

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

            'patientsInQueue' => QueueEntry::whereDate('visit_date', today())
                ->whereIn('status', ['waiting', 'in_consultation'])->count(),

            'registeredPatients' => Patient::count(),

        ]);
    }
}
