<?php
namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_can_mark_booking_in_queue_then_complete_it(): void
    {
        config(['services.booking_api_token' => 'test-token']);
        $booking = Appointment::create([
            'appointment_no' => 'APT-QUEUE', 'guardian_name' => 'Guardian',
            'child_name' => 'Child', 'contact_number' => '09123456789',
            'appointment_date' => today()->toDateString(), 'appointment_time' => '08:00',
            'reason_for_visit' => 'Checkup', 'status' => 'Approved',
        ]);
        foreach (['In Queue', 'Completed'] as $status) {
            $this->withHeader('X-Booking-Token', 'test-token')
                ->patchJson('/api/appointments/APT-QUEUE/status', ['status' => $status])
                ->assertOk()->assertJsonPath('appointment.status', $status);
            $this->assertSame($status, $booking->fresh()->status);
        }
    }
}
