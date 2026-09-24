@extends('layouts.app')
@section('content')
<section class="page-header"><div class="container d-flex flex-wrap justify-content-between align-items-center gap-3"><div><span class="eyebrow">YOUR CLINIC, AT A GLANCE</span><h1>Care starts with a well-planned day.</h1><p class="mb-0">{{ now()->format('l, F j, Y') }} · Abuyog, Leyte</p></div><a class="btn btn-primary" href="{{ route('patients.create') }}">+ Register patient</a></div></section>
<div class="container">
<div class="operations-links mb-4">
<a href="{{ route('queue.index') }}"><strong>Clinic queue</strong><small>{{ \App\Models\QueueEntry::whereDate('visit_date',today())->whereIn('status',['waiting','in_consultation'])->count() }} active visits &rarr;</small></a>
<a href="{{ route('reminders.index') }}"><strong>Follow-ups & vaccines</strong><small>Review due dates &rarr;</small></a>
<a href="{{ route('billing.index') }}"><strong>Billing & payments</strong><small>Manage outstanding balances &rarr;</small></a>
<a href="{{ route('reports.index') }}"><strong>Clinic reports</strong><small>Visits and collections &rarr;</small></a>
</div>
<div class="row g-3 mb-4">
@foreach([["Today's appointments",$todayAppointments],['Pending review',$pendingAppointments],['Approved bookings',$approvedAppointments],['Registered patients',\App\Models\Patient::count()]] as [$label,$value])
<div class="col-6 col-lg-3"><div class="card clinic-card h-100"><div class="card-body p-4"><p class="text-muted small mb-2">{{ $label }}</p><div class="metric-value">{{ $value }}</div></div></div></div>
@endforeach
</div>
<div class="row g-4"><div class="col-lg-8"><div class="card clinic-card"><div class="card-body p-4"><div class="d-flex justify-content-between gap-3 mb-4"><div><h2 class="h5">Today's appointments</h2><p class="small text-muted mb-0">Latest records received from the booking portal.</p></div><a href="{{ route('appointments.index') }}">View all &rarr;</a></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Time</th><th>Patient</th><th>Reference</th><th>Status</th></tr></thead><tbody>
@forelse(\App\Models\OnlineAppointment::whereDate('appointment_date',today())->orderBy('appointment_time')->get() as $appointment)
<tr><td>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</td><td><strong>{{ $appointment->child_name }}</strong><small class="d-block text-muted">{{ $appointment->guardian_name }}</small></td><td>{{ $appointment->appointment_no }}</td><td><span class="badge text-bg-light">{{ $appointment->status }}</span></td></tr>
@empty
<tr><td colspan="4" class="text-center py-5 text-muted">No appointments in the local schedule today.<br><a href="{{ route('appointments.index') }}">Open appointments to refresh the portal connection.</a></td></tr>
@endforelse
</tbody></table></div></div></div></div>
<div class="col-lg-4"><div class="card clinic-card mint-card mb-4"><div class="card-body p-4"><span class="eyebrow">CONNECTED CARE</span><h2 class="h4">From online booking to clinic visit.</h2><p>Review appointments, save patient details, and send status updates back to families.</p><a class="btn btn-primary w-100" href="{{ route('appointments.index') }}">Manage appointments &rarr;</a><p class="small mt-3 mb-0">{{ \App\Models\OnlineAppointment::where('sync_pending',true)->count() }} status updates awaiting synchronization.</p></div></div><div class="card clinic-card"><div class="card-body p-4"><h2 class="h5">Patient care records</h2><p>Find a patient to record a consultation, prescription, or follow-up plan.</p><a href="{{ route('patients.index') }}">Browse patient records &rarr;</a></div></div></div></div>
</div>
@endsection

