<?php

namespace Tests\Feature;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppointmentDoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        config([
            'services.booking_api.url' => 'http://portal.test/api',
            'services.booking_api.token' => 'test',
        ]);
        Http::preventStrayRequests();
    }

    private function booking(array $attributes = []): OnlineAppointment
    {
        return OnlineAppointment::create(array_merge([

            'appointment_no' => 'APT-DONE',
            'guardian_name' => 'Test Guardian',

            'child_name' => 'Test Child',
            'contact_number' => '09123456789',

            'appointment_date' => today()->toDateString(),
            'appointment_time' => '10:00:00',

            'status' => 'Approved',
            'child_date_of_birth' => '2020-01-01',
            'child_sex' => 'Female',

            'guardian_address' => 'Test Address',
            'emergency_contact_number' => '09987654321',

        ], $attributes));
    }

    public function test_done_creates_patient_and_repeated_click_reuses_record(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);
        $booking = $this->booking();
        $payload = [
            'appointment_no' => $booking->appointment_no,
        ];
        $this->postJson('/appointments/done', $payload)->assertOk()->assertJsonPath('success', true);
        $patient = Patient::firstOrFail();
        $this->assertSame('Test Address', $patient->address);
        $this->assertSame('09987654321', $patient->emergency_contact);
        $this->assertSame('Completed', $booking->fresh()->status);
        $this->assertEquals($patient->id, $booking->fresh()->patient_id);
        $this->postJson('/appointments/done', $payload)->assertOk()->assertJsonPath('patient_url', route('patients.show', $patient));
        $this->assertDatabaseCount('patients', 1);
        $this->get('/patients')->assertOk()->assertSee('Child');
    }

    public function test_done_saves_locally_when_portal_is_offline(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);
        $booking = $this->booking();
        $this->postJson('/appointments/done', [
            'appointment_no' => $booking->appointment_no,
        ])->assertOk()->assertJsonPath('portal_updated', false);
        $this->assertDatabaseCount('patients', 1);
        $this->assertTrue((bool) $booking->fresh()->sync_pending);
    }

    public function test_missing_demographics_do_not_create_partial_patient(): void
    {
        $booking = $this->booking([
            'child_date_of_birth' => null,
        ]);
        $this->postJson('/appointments/done', [
            'appointment_no' => $booking->appointment_no,
        ])->assertUnprocessable()->assertJsonValidationErrors('birthdate');
        $this->assertDatabaseCount('patients', 0);
        $this->assertSame('Approved', $booking->fresh()->status);
    }

    public function test_future_visit_cannot_be_completed(): void
    {
        $booking = $this->booking([
            'appointment_date' => today()->addDay()->toDateString(),
        ]);
        $this->postJson('/appointments/done', [
            'appointment_no' => $booking->appointment_no,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('patients', 0);
    }

    public function test_menu_only_offers_done(): void
    {
        Http::fake([
            '*' => Http::response([
                'appointments' => [],
            ]),
        ]);
        $this->get('/appointments')->assertOk()->assertSee('data-status="Completed"', false)
            ->assertDontSee('Approve booking')->assertDontSee('Decline booking')->assertDontSee('Add Patient Details')
            ->assertDontSee('data-status="Cancelled"', false)->assertDontSee('data-status="Rescheduled"', false);
    }
}
