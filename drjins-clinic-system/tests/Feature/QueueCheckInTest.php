<?php

namespace Tests\Feature;

use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueueCheckInTest extends TestCase
{
    use RefreshDatabase;

    private bool $portalOffline = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        config(['services.booking_api.url' => 'http://portal.test/api', 'services.booking_api.token' => 'test']);
        Http::fake(fn () => $this->portalOffline
            ? Http::response([], 503)
            : Http::response(['appointments' => []]));
    }

    private function booking(array $values = []): OnlineAppointment
    {
        return OnlineAppointment::create(array_merge([
            'appointment_no' => 'APT-TEST', 'guardian_name' => 'Guardian',
            'child_name' => 'ANA DELA CRUZ', 'contact_number' => '09123456789',
            'appointment_date' => today()->toDateString(), 'appointment_time' => '09:00',
            'child_date_of_birth' => '2020-01-01', 'child_sex' => 'Female', 'status' => 'Approved',
        ], $values));
    }

    public function test_online_patient_is_registered_only_when_queue_visit_is_done(): void
    {
        $booking = $this->booking();
        $this->getJson('/queue/search?search=CRUZ')->assertOk()->assertJsonPath('results.0.online_appointment_id', $booking->id);
        $this->post('/queue', ['online_appointment_id' => $booking->id])->assertSessionHasNoErrors();
        $this->assertSame('In Queue', $booking->fresh()->status);
        $this->assertDatabaseCount('patients', 0);
        $entry = QueueEntry::firstOrFail();
        $this->assertNull($entry->patient_id);
        $this->get('/queue')->assertOk()->assertSee('ANA DELA CRUZ');
        $this->post('/queue', ['online_appointment_id' => $booking->id])->assertSessionHasErrors();
        $this->post(route('queue.done', $entry))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('patients', 1);
        $this->assertSame('completed', $entry->fresh()->status);
        $this->assertSame('Completed', $booking->fresh()->status);
        $this->assertSame($booking->fresh()->patient_id, $entry->fresh()->patient_id);
        $this->get('/queue')->assertOk()->assertDontSee('ANA DELA CRUZ');
        $this->post(route('queue.done', $entry))->assertSessionHasErrors('status');
        $this->assertDatabaseCount('patients', 1);
        Http::assertSent(fn ($request) => $request->method() === 'PATCH' && $request['status'] === 'In Queue');
        Http::assertSent(fn ($request) => $request->method() === 'PATCH' && $request['status'] === 'Completed');
    }

    public function test_missing_demographics_keep_visit_waiting_until_staff_supplies_them(): void
    {
        $booking = $this->booking(['child_date_of_birth' => null, 'child_sex' => null]);
        $this->post('/queue', ['online_appointment_id' => $booking->id]);
        $entry = QueueEntry::firstOrFail();
        $this->post(route('queue.done', $entry))->assertSessionHasErrors(['birthdate', 'sex']);
        $this->assertSame('waiting', $entry->fresh()->status);
        $this->assertDatabaseCount('patients', 0);
        $this->post(route('queue.done', $entry), ['birthdate' => '2020-01-01', 'sex' => 'Female'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('patients', 1);
    }

    public function test_unknown_and_future_names_are_not_selectable_and_offline_updates_are_retained(): void
    {
        $booking = $this->booking(['appointment_date' => today()->addDay()->toDateString()]);
        $this->getJson('/queue/search?search=CRUZ')->assertJsonCount(0, 'results');
        $this->getJson('/queue/search?search=NOBODY')->assertJsonCount(0, 'results');
        $this->getJson('/queue/search')->assertJsonCount(0, 'results');
        $this->post('/queue', ['online_appointment_id' => $booking->id])->assertSessionHasErrors();
        $booking->update(['appointment_date' => today()->toDateString()]);
        $this->portalOffline = true;
        $this->post('/queue', ['online_appointment_id' => $booking->id])->assertSessionHasNoErrors();
        $this->assertTrue((bool) $booking->fresh()->sync_pending);
        $this->assertSame('In Queue', $booking->fresh()->status);
    }

    public function test_registered_patient_checkin_and_completion_do_not_duplicate_records(): void
    {
        $patient = Patient::create([
            'patient_no' => 'P-1', 'first_name' => 'ANA', 'last_name' => 'CRUZ',
            'birthdate' => '2020-01-01', 'sex' => 'Female', 'guardian_name' => 'Parent',
            'contact_number' => '09123456789', 'status' => 'Active',
        ]);
        $this->getJson('/queue/search?search=ANA%20CRUZ')->assertJsonPath('results.0.patient_id', $patient->id);
        $this->post('/queue', ['patient_id' => $patient->id])->assertSessionHasNoErrors();
        $this->post('/queue', ['patient_id' => $patient->id])->assertSessionHasErrors();
        $this->getJson('/queue/search?search=CRUZ')->assertJsonCount(0, 'results');
        $this->post(route('queue.done', QueueEntry::firstOrFail()))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('consultations', 0);
    }
}
