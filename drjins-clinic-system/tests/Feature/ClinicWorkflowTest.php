<?php

namespace Tests\Feature;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\BookingSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClinicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(): OnlineAppointment
    {
        return OnlineAppointment::create([
            'appointment_no' => 'APT-TEST',
            'guardian_name' => 'Guardian',
            'child_name' => 'Child Test',
            'contact_number' => '09123456789',
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'Approved',
        ]);
    }

    public function test_offline_status_update_is_retained_and_retried(): void
    {
        config([
            'services.booking_api.url' => 'http://portal.test/api',
            'services.booking_api.token' => 'test-token',
        ]);
        $appointment = $this->appointment();
        Http::fake([
            '*/status' => Http::sequence()->push([], 503)->push([
                'message' => 'Updated',
            ]),
            '*/appointments' => Http::response([
                'appointments' => [array_merge($appointment->toArray(), [
                    'status' => 'Cancelled',
                ])],
            ]),
        ]);
        $this->actingAs(User::factory()->create())->postJson('/appointments/update-status', [
            'appointment_no' => $appointment->appointment_no,
            'status' => 'Cancelled',
        ])->assertOk()->assertJson([
            'portal_updated' => false,
        ]);
        $this->assertTrue((bool) $appointment->fresh()->sync_pending);
        Http::fake([

            '*/status' => Http::response([
                'message' => 'Updated',
            ]),

            '*/appointments' => Http::response([
                'appointments' => [array_merge($appointment->fresh()->toArray(), [
                    'status' => 'Cancelled',
                ])],
            ]),

        ]);
        $this->assertTrue(app(BookingSync::class)->sync());
        $this->assertFalse((bool) $appointment->fresh()->sync_pending);
        $this->assertSame('Cancelled', $appointment->fresh()->status);
    }

    public function test_invalid_portal_payload_does_not_overwrite_local_records(): void
    {
        config([
            'services.booking_api.url' => 'http://portal.test/api',
            'services.booking_api.token' => 'test-token',
        ]);
        $appointment = $this->appointment();
        Http::fake([
            '*' => Http::response([
                'appointments' => [[
                    'appointment_no' => 'APT-TEST',
                ]],
            ]),
        ]);
        $this->assertFalse(app(BookingSync::class)->sync());
        $this->assertSame('Approved', $appointment->fresh()->status);
    }

    public function test_patient_consultation_and_edit_workflow(): void
    {
        $user = User::factory()->create();
        $values = [
            'first_name' => 'Child',
            'last_name' => 'Test',
            'birthdate' => '2020-01-01',
            'sex' => 'Female',
            'guardian_name' => 'Guardian',
            'contact_number' => '09123456789',
            'status' => 'Active',
        ];
        $this->actingAs($user)->post('/patients', $values)->assertSessionHasNoErrors();
        $patient = Patient::firstOrFail();
        $this->get(route('patients.edit', $patient))->assertOk()->assertSee('Child');
        $this->put(route('patients.update', $patient), array_merge($values, [
            'first_name' => 'Updated',
        ]))->assertSessionHasNoErrors();
        $this->post(route('consultations.store', $patient), [
            'visit_date' => today()->toDateString(),
            'complaint' => 'Review',
            'assessment' => 'Recorded assessment',
            'plan' => 'Recorded plan',
            'prescription' => 'Recorded notes',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('consultations', [
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'complaint' => 'Review',
        ]);
        $this->get(route('patients.show', $patient))->assertOk()->assertSee('Recorded assessment')->assertSee('Updated');
        $this->get('/dashboard')->assertOk();
        $this->post(route('consultations.store', $patient), [
            'visit_date' => today()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['visit_date', 'complaint', 'assessment']);
    }

    public function test_guest_cannot_write_consultation(): void
    {
        $this->post('/patients/1/consultations', [])->assertRedirect('/login');
    }
}
