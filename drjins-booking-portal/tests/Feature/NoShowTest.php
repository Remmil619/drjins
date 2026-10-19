<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_saves_and_returns_missed_appointment(): void
    {
        config(['services.booking_api_token' => 'test-token']);
        $appointment = Appointment::create([
            'appointment_no' => 'APT-NOSHOW', 'guardian_name' => 'Guardian',
            'child_name' => 'Child', 'contact_number' => '09123456789',
            'appointment_date' => today()->toDateString(), 'appointment_time' => '08:00',
            'reason_for_visit' => 'Checkup', 'status' => 'Approved',
        ]);
        $this->withHeader('X-Booking-Token', 'test-token')
            ->patchJson('/api/appointments/APT-NOSHOW/status', ['status' => 'No Show', 'staff_response' => 'Patient did not attend.'])
            ->assertOk()->assertJsonPath('appointment.status', 'No Show');
        $this->assertSame('No Show', $appointment->fresh()->status);
        $this->withHeader('X-Booking-Token', 'test-token')->getJson('/api/appointments')
            ->assertOk()->assertJsonPath('appointments.0.status', 'No Show');
    }
}
