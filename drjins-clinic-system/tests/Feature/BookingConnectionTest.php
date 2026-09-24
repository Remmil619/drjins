<?php

namespace Tests\Feature;

use App\Models\OnlineAppointment;
use App\Models\User;
use App\Services\BookingSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $overrides = []): array
    {
        return array_replace([
            'appointment_no' => 'APT-CONNECTION-TEST', 'guardian_name' => 'Test Guardian',
            'child_name' => 'Test Child', 'contact_number' => '09123456789',
            'appointment_date' => today()->toDateString(), 'appointment_time' => '10:00',
            'status' => 'Approved', 'guardian_address' => 'Abuyog, Leyte',
            'child_date_of_birth' => '2020-01-15', 'child_sex' => 'Female',
            'guardian_relationship' => 'Mother', 'allergies' => 'Test allergy',
            'current_medications' => 'None', 'medical_notes' => 'Test intake note',
            'emergency_contact_number' => '09987654321',
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.booking_api.url' => 'http://portal.test/api', 'services.booking_api.token' => 'test-token']);
    }

    public function test_intake_details_are_synced_and_available_to_authorized_staff(): void
    {
        Http::fake(['*' => Http::response(['appointments' => [$this->booking()]])]);
        $this->assertTrue(app(BookingSync::class)->sync());
        $this->assertDatabaseHas('online_appointments', [
            'appointment_no' => 'APT-CONNECTION-TEST', 'child_date_of_birth' => '2020-01-15',
            'guardian_address' => 'Abuyog, Leyte', 'medical_notes' => 'Test intake note',
        ]);
        $this->getJson('/appointments/live')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/appointments/live?filter=all')->assertOk()
            ->assertJsonPath('appointments.0.child_date_of_birth', '2020-01-15')
            ->assertJsonPath('appointments.0.allergies', 'Test allergy');
    }

    public function test_failed_push_does_not_block_new_bookings_or_erase_pending_changes(): void
    {
        $pending = OnlineAppointment::create($this->booking(['status' => 'Cancelled']) + ['sync_pending' => true]);
        Http::fake([
            '*/status' => Http::response(['message' => 'Conflict'], 409),
            '*/appointments' => Http::response(['appointments' => [
                $this->booking(), $this->booking(['appointment_no' => 'APT-NEW', 'appointment_time' => '11:00']),
            ]]),
        ]);
        $this->assertFalse(app(BookingSync::class)->sync());
        $this->assertSame('Cancelled', $pending->fresh()->status);
        $this->assertTrue((bool) $pending->fresh()->sync_pending);
        $this->assertDatabaseHas('online_appointments', ['appointment_no' => 'APT-NEW']);
    }

    public function test_cancelled_booking_cannot_be_completed_through_legacy_endpoint(): void
    {
        OnlineAppointment::create($this->booking(['status' => 'Cancelled']));
        Http::fake(['*' => Http::response(['appointments' => []])]);
        $this->actingAs(User::factory()->create())->from('/appointments')->post('/appointments/update-by-reference', [
            'appointment_no' => 'APT-CONNECTION-TEST', 'birthdate' => '2020-01-15', 'sex' => 'Female', 'action' => 'done',
        ])->assertSessionHasErrors('appointment_status');
        $this->assertDatabaseCount('patients', 0);
    }
}
