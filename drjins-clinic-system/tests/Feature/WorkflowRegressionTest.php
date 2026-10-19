<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Consultation;
use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\QueueEntry;
use App\Models\User;
use App\Services\BookingSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkflowRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 24)->startOfDay());
        $this->actingAs(User::factory()->create());
        config([
            'services.booking_api.url' => 'http://portal.test/api',
            'services.booking_api.token' => 'test-token',
        ]);
        Http::preventStrayRequests();
    }

    private function patient(): Patient
    {
        return Patient::create([
            'patient_no' => 'PAT-'.Str::random(8),
            'first_name' => 'Test',
            'last_name' => 'Child',
            'birthdate' => '2020-01-01',
            'sex' => 'Female',
            'guardian_name' => 'Guardian',
            'contact_number' => '09123456789',
            'status' => 'Active',
        ]);
    }

    public function test_missed_appointment_syncs_without_creating_patient_records(): void
    {
        Http::fake([
            '*' => Http::response([]),
        ]);
        $appointment = $this->appointment();
        $this->postJson('/appointments/update-status', [
            'appointment_no' => $appointment->appointment_no,
            'status' => 'No Show',
        ])
            ->assertOk()->assertJsonPath('portal_updated', true);
        $this->assertSame('No Show', $appointment->fresh()->status);
        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('consultations', 0);
        Http::assertSent(fn ($request) => $request->method() === 'PATCH' && $request['status'] === 'No Show');
    }

    public function test_missed_appointment_is_preserved_when_portal_is_offline(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);
        $appointment = $this->appointment();
        $this->postJson('/appointments/update-status', [
            'appointment_no' => $appointment->appointment_no,
            'status' => 'No Show',
        ])
            ->assertOk()->assertJsonPath('portal_updated', false);
        $this->assertSame('No Show', $appointment->fresh()->status);
        $this->assertTrue((bool) $appointment->fresh()->sync_pending);
    }

    public function test_future_cancelled_completed_and_checked_in_visits_cannot_be_marked_absent(): void
    {
        Http::fake();
        foreach ([[
            'appointment_date' => today()->addDay()->toDateString(),
        ], [
            'status' => 'Cancelled',
        ], [
            'status' => 'Completed',
        ]] as $attributes) {
            $appointment = $this->appointment($attributes);
            $this->postJson('/appointments/update-status', [
                'appointment_no' => $appointment->appointment_no,
                'status' => 'No Show',
            ])
                ->assertUnprocessable()->assertJsonValidationErrors('status');
            $this->assertSame($attributes['status'] ?? 'Approved', $appointment->fresh()->status);
        }
        $appointment = $this->appointment();
        QueueEntry::create([
            'patient_id' => $this->patient()->id,
            'online_appointment_id' => $appointment->id,
            'visit_date' => today()->toDateString(),
            'status' => 'waiting',
            'reason' => 'Checkup',
        ]);
        $this->postJson('/appointments/update-status', [
            'appointment_no' => $appointment->appointment_no,
            'status' => 'No Show',
        ])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_portal_missed_appointment_can_be_imported(): void
    {
        $appointment = $this->appointment();
        $incoming = $appointment->toArray();
        $incoming['status'] = 'No Show';
        Http::fake([
            '*' => Http::response([
                'appointments' => [$incoming],
            ]),
        ]);
        $this->assertTrue(app(BookingSync::class)->sync());
        $this->assertSame('No Show', $appointment->fresh()->status);
    }

    private function appointment(array $attributes = []): OnlineAppointment
    {
        return OnlineAppointment::create(array_merge([
            'appointment_no' => 'APT-'.Str::random(8),
            'guardian_name' => 'Guardian',
            'child_name' => 'Test Child',
            'contact_number' => '09123456789',
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'Approved',
        ], $attributes));
    }

    public function test_empty_patient_tab_uses_consultations_and_search_arrays_are_rejected(): void
    {
        $patient = $this->patient();
        $this->get(route('patients.show', $patient).'?tab=')->assertOk()->assertSee('Consultation history');
        $this->getJson('/patients?search[]=invalid')->assertUnprocessable()->assertJsonValidationErrors('search');
    }

    public function test_report_end_date_can_be_supplied_without_start_date(): void
    {
        $this->get('/reports?to=2026-09-24')->assertOk();
    }

    public function test_changed_consultation_retry_is_not_silently_discarded(): void
    {
        $patient = $this->patient();
        $payload = [
            'visit_date' => '2026-09-24',
            'complaint' => 'Checkup',
            'assessment' => 'Initial notes',
            'request_key' => (string) Str::uuid(),
        ];
        $this->post(route('consultations.store', $patient), $payload)->assertSessionHasNoErrors();
        $this->post(route('consultations.store', $patient), array_merge($payload, [
            'assessment' => 'Changed notes',
        ]))->assertSessionHasErrors('request_key');
        $this->assertDatabaseCount('consultations', 1);
        $this->assertSame('Initial notes', Consultation::first()->assessment);
    }

    public function test_changed_payment_reference_is_not_silently_discarded(): void
    {
        $charge = Charge::create([
            'patient_id' => $this->patient()->id,
            'user_id' => auth()->id(),
            'charged_on' => '2026-09-24',
            'description' => 'Fee',
            'amount_centavos' => 50000,
            'request_key' => (string) Str::uuid(),
        ]);
        $payload = [
            'paid_on' => '2026-09-24',
            'amount' => '100',
            'method' => 'GCash',
            'reference' => 'REF-1',
            'request_key' => (string) Str::uuid(),
        ];
        $this->post(route('payments.store', $charge), $payload)->assertSessionHasNoErrors();
        $this->post(route('payments.store', $charge), array_merge($payload, [
            'reference' => 'REF-2',
        ]))->assertSessionHasErrors('request_key');
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('REF-1', Payment::first()->reference);
    }

    public function test_completed_booking_cannot_be_reopened_from_status_menu(): void
    {
        $appointment = $this->appointment([
            'status' => 'Completed',
        ]);
        $this->postJson('/appointments/update-status', [
            'appointment_no' => $appointment->appointment_no,
            'status' => 'Approved',
        ])->assertUnprocessable();
        $this->assertSame('Completed', $appointment->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_checked_in_booking_cannot_be_cancelled_behind_queue(): void
    {
        $patient = $this->patient();
        $appointment = $this->appointment([
            'patient_id' => $patient->id,
        ]);
        $this->post('/queue', [
            'patient_id' => $patient->id,
            'online_appointment_id' => $appointment->id,
            'reason' => 'Checkup',
        ])->assertSessionHasNoErrors();
        $this->postJson('/appointments/update-status', [
            'appointment_no' => $appointment->appointment_no,
            'status' => 'Cancelled',
        ])->assertUnprocessable();
        $this->assertSame('In Queue', $appointment->fresh()->status);
        $this->assertSame('waiting', QueueEntry::first()->status);
    }

    public function test_reference_action_cannot_complete_cancelled_or_future_appointments(): void
    {
        Http::fake([
            '*' => Http::response([
                'appointments' => [],
            ]),
        ]);
        foreach ([[
            'status' => 'Cancelled',
        ], [
            'appointment_date' => '2026-09-25',
        ]] as $attributes) {
            $appointment = $this->appointment($attributes);
            $this->post('/appointments/update-by-reference', [
                'appointment_no' => $appointment->appointment_no,
                'action' => 'done',
                'birthdate' => '2020-01-01',
                'sex' => 'Female',
            ])->assertSessionHasErrors('appointment_no');
            $this->assertSame($attributes['status'] ?? 'Approved', $appointment->fresh()->status);
        }
        $this->assertDatabaseCount('patients', 0);
    }

    public function test_missing_portal_configuration_retains_pending_status_without_http_request(): void
    {
        config([
            'services.booking_api.url' => '',
            'services.booking_api.token' => '',
        ]);
        $appointment = $this->appointment([
            'sync_pending' => true,
        ]);
        $this->assertFalse(app(BookingSync::class)->push($appointment));
        $this->assertTrue((bool) $appointment->fresh()->sync_pending);
        Http::assertNothingSent();
    }

    public function test_failed_pending_update_does_not_block_import_or_other_pending_updates(): void
    {
        $blocked = $this->appointment([
            'status' => 'Cancelled',
            'sync_pending' => true,
        ]);
        $other = $this->appointment([
            'status' => 'Declined',
            'sync_pending' => true,
        ]);
        $incoming = $blocked->toArray();
        $incoming['status'] = 'Approved';
        $new = array_merge($incoming, [
            'appointment_no' => 'APT-NEW',
        ]);
        Http::fake([

            '*/'.$blocked->appointment_no.'/status' => Http::response([], 409),

            '*/'.$other->appointment_no.'/status' => Http::response([]),

            '*/appointments' => Http::response([
                'appointments' => [$incoming, $new],
            ]),

        ]);
        $this->assertFalse(app(BookingSync::class)->sync());
        $this->assertDatabaseHas('online_appointments', [
            'appointment_no' => 'APT-NEW',
        ]);
        $this->assertSame('Cancelled', $blocked->fresh()->status);
        $this->assertTrue((bool) $blocked->fresh()->sync_pending);
        $this->assertFalse((bool) $other->fresh()->sync_pending);
    }

    public function test_linked_patient_is_exposed_as_profile_link_in_live_appointments(): void
    {
        Http::fake([
            '*' => Http::response([
                'appointments' => [],
            ]),
        ]);
        $patient = $this->patient();
        $this->appointment([
            'patient_id' => $patient->id,
        ]);
        $this->getJson('/appointments/live')->assertOk()->assertJsonPath('appointments.0.patient_url', route('patients.show', $patient));
    }

    public function test_json_requests_receive_validation_and_session_errors_as_json(): void
    {
        $this->postJson('/appointments/update-status', [])->assertUnprocessable()->assertJsonValidationErrors(['appointment_no', 'status']);
        auth()->logout();
        $this->getJson('/appointments/live')->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function test_guest_cannot_write_any_clinic_workflow(): void
    {
        auth()->logout();
        foreach (['/patients', '/queue', '/appointments/update-status', '/appointments/save-patient-details', '/appointments/update-by-reference', '/patients/1/consultations', '/patients/1/growth', '/patients/1/immunizations', '/patients/1/alerts', '/patients/1/charges', '/charges/1/payments', '/billing/payment/1/void', '/patients/1/records/consultation/1/void', '/follow-ups/consultation/1/close'] as $url) {
            $this->post($url, [])->assertRedirect('/login');
        }
        $this->patch('/queue/1', [])->assertRedirect('/login');
        $this->put('/patients/1', [])->assertRedirect('/login');
    }

    public function test_populated_billing_filters_reports_and_follow_up_closure(): void
    {
        $patient = $this->patient();
        $this->post(route('charges.store', $patient), [
            'description' => 'Regression consultation fee',
            'charged_on' => '2026-09-24',
            'amount' => '500.10',
            'request_key' => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();
        $charge = Charge::firstOrFail();
        $this->get('/billing?status=unpaid')->assertOk()->assertSee('Regression consultation fee');
        $this->post(route('payments.store', $charge), [
            'paid_on' => '2026-09-24',
            'amount' => '500.10',
            'method' => 'Cash',
            'request_key' => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();
        $this->get('/billing?status=paid')->assertOk()->assertSee('Regression consultation fee');
        $this->get('/billing?status=unpaid')->assertOk()->assertDontSee('Regression consultation fee');
        $this->get('/reports?from=2026-09-01&to=2026-09-24')->assertOk()->assertSee('PHP 500.10')->assertSee('PHP 0.00');
        $this->get(route('patients.show', [
            $patient,
            'tab' => 'billing',
        ]))->assertOk()->assertSee('Regression consultation fee');
        $this->post(route('consultations.store', $patient), [
            'visit_date' => '2026-09-24',
            'complaint' => 'Review',
            'assessment' => 'Follow-up regression notes',
            'follow_up_date' => '2026-10-01',
            'request_key' => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();
        $consultation = Consultation::firstOrFail();
        $this->get('/follow-ups?through=2026-10-01')->assertOk()->assertSee($patient->full_name);
        $this->post(route('reminders.close', ['consultation', $consultation]), [
            'outcome' => 'Follow-up arranged',
        ])->assertSessionHasNoErrors();
        $this->get('/follow-ups?through=2026-10-01')->assertOk()->assertDontSee($patient->full_name);
        $this->post(route('reminders.close', ['consultation', $consultation]), [
            'outcome' => 'Duplicate closure',
        ])->assertSessionHasErrors('outcome');
    }

    public function test_invalid_dates_amounts_and_filters_are_rejected(): void
    {
        $patient = $this->patient();
        foreach (['-1', '1.001', '1e3', '0', '10000000'] as $amount) {
            $this->post(route('charges.store', $patient), [
                'description' => 'Fee',
                'charged_on' => '2026-09-24',
                'amount' => $amount,
                'request_key' => (string) Str::uuid(),
            ])->assertSessionHasErrors('amount');
        }
        foreach (['/queue?date=invalid', '/billing?status=unknown', '/reports?from=2026-09-24&to=2026-09-01', '/follow-ups?through=invalid', '/activity?type=Unknown', route('patients.show', $patient).'?tab=unknown'] as $url) {
            $this->getJson($url)->assertUnprocessable();
        }
        $this->assertDatabaseCount('charges', 0);
    }

    public function test_invalid_success_response_does_not_acknowledge_pending_status(): void
    {
        Http::fake([
            '*' => Http::response('<html>Sign in</html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);
        $appointment = $this->appointment([
            'sync_pending' => true,
        ]);
        $this->assertFalse(app(BookingSync::class)->push($appointment));
        $this->assertTrue((bool) $appointment->fresh()->sync_pending);
    }

    public function test_cancelled_booking_cannot_reenter_waiting_queue(): void
    {
        $patient = $this->patient();
        $appointment = $this->appointment([
            'patient_id' => $patient->id,
        ]);
        $this->post('/queue', [
            'patient_id' => $patient->id,
            'online_appointment_id' => $appointment->id,
            'reason' => 'Checkup',
        ])->assertSessionHasNoErrors();
        $entry = QueueEntry::firstOrFail();
        $this->patch(route('queue.update', $entry), [
            'status' => 'left',
        ])->assertSessionHasNoErrors();
        $appointment->update([
            'status' => 'Cancelled',
        ]);
        $this->patch(route('queue.update', $entry), [
            'status' => 'waiting',
        ])->assertSessionHasErrors('status');
        $this->assertSame('left', $entry->fresh()->status);
    }

    public function test_registration_completion_rolls_back_for_a_future_booking(): void
    {
        $appointment = $this->appointment([
            'appointment_date' => '2026-09-25',
        ]);
        $this->post('/appointments/save-patient-details', [
            'appointment_no' => $appointment->appointment_no,
            'first_name' => 'Child',
            'last_name' => 'Test',
            'guardian_name' => 'Guardian',
            'contact_number' => '09123456789',
            'birthdate' => '2020-01-01',
            'sex' => 'Female',
            'appointment_status' => 'done',
        ])->assertSessionHasErrors('appointment_status');
        $this->assertDatabaseCount('patients', 0);
        $this->assertNull($appointment->fresh()->patient_id);
        $this->assertSame('Approved', $appointment->fresh()->status);
    }
}
