<?php

namespace App\Http\Controllers;

use App\Models\OnlineAppointment;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index', [
            'totalAppointments' => OnlineAppointment::count(),
            'todayAppointments' => OnlineAppointment::whereDate('appointment_date', now()->toDateString())->count(),
            'pendingAppointments' => OnlineAppointment::where('status', 'Pending')->count(),
            'approvedAppointments' => OnlineAppointment::where('status', 'Approved')->count(),
        ]);
    }
}
