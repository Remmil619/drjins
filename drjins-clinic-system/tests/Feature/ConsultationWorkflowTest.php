<?php
namespace Tests\Feature;

use App\Models\{Patient, User, OnlineAppointment, QueueEntry, Consultation, Immunization, Charge};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsultationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['request_key' => (string) Str::uuid(), 'visit_date' => today()->toDateString(),
            'complaint' => 'CHECK UP', 'assessment' => 'WELL', 'consultation_fee' => '500.00',
            'include_vaccination' => '1', 'vaccination_fee' => '750.50',
            'vaccination' => ['vaccine' => 'TEST VACCINE', 'dose' => 'FIRST', 'provider' => 'CLINIC',
                'source' => 'clinic', 'administered_on' => today()->toDateString()]];
    }
    private function patient(): Patient
    {
        return Patient::create(['patient_no' => 'TEST-1', 'first_name' => 'ANA', 'last_name' => 'TEST',
            'birthdate' => '2020-01-01', 'sex' => 'Female', 'guardian_name' => 'GUARDIAN',
            'contact_number' => '09123456789', 'status' => 'Active']);
    }
    private function entry(): QueueEntry
    {
        $booking = OnlineAppointment::create(['appointment_no' => 'BOOKING-1', 'child_name' => 'ANA TEST',
            'guardian_name' => 'GUARDIAN', 'contact_number' => '09123456789',
            'child_date_of_birth' => '2020-01-01', 'child_sex' => 'Female',
            'appointment_date' => today()->toDateString(), 'appointment_time' => '09:00', 'status' => 'In Queue']);
        return QueueEntry::create(['online_appointment_id' => $booking->id, 'visit_date' => today()->toDateString(),
            'reason' => 'CHECK UP', 'status' => 'waiting']);
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'doctor']));
    }
    public function test_saves_all_steps_once_and_rejects_changed_retries(): void
    {
        $patient = $this->patient(); $data = $this->payload();
        $this->get(route('patients.show', $patient))->assertOk()->assertSee('data-open-workflow="2"', false)->assertSee('Save consultation');
        $url = route('consultation-workflow.store', $patient);
        $this->post($url, $data)->assertSessionHasNoErrors()->assertRedirect(route('patients.show', $patient));
        $this->post($url, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultations', 1);
        $this->assertDatabaseCount('immunizations', 1);
        $this->assertDatabaseCount('charges', 2);
        $this->assertSame(125050, (int) Charge::sum('amount_centavos'));
        $this->post($url, array_replace($data, ['vaccination_fee' => '800.00']))->assertSessionHasErrors('request_key');
    }
    public function test_online_booking_is_registered_only_on_final_save(): void
    {
        $entry = $this->entry();
        $this->patch(route('queue.update', $entry), ['status' => 'in_consultation'])
            ->assertSessionHasNoErrors()->assertRedirect(route('consultation-workflow.booking', $entry));
        $this->get(route('consultation-workflow.booking', $entry))->assertOk()->assertSee('Save consultation');
        $this->assertDatabaseCount('patients', 0);
        $this->post(route('consultation-workflow.booking.store', $entry), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('patients', 1);
        $this->assertSame(Patient::first()->id, $entry->fresh()->patient_id);
        $this->assertSame(Patient::first()->id, $entry->appointment->fresh()->patient_id);
        $this->assertSame($entry->id, Consultation::first()->queue_entry_id);
    }
    public function test_invalid_vaccination_rolls_back_new_patient_and_entire_visit(): void
    {
        $entry = $this->entry(); $data = $this->payload(); unset($data['vaccination']['dose']);
        $this->post(route('consultation-workflow.booking.store', $entry), $data)->assertSessionHasErrors('vaccination.dose');
        foreach (['patients', 'consultations', 'immunizations', 'charges'] as $table) $this->assertDatabaseCount($table, 0);
        $this->assertNull($entry->fresh()->patient_id);
        $this->assertNull($entry->appointment->fresh()->patient_id);
    }
    public function test_visit_without_vaccination_saves_only_consultation_fee(): void
    {
        $data = $this->payload(); unset($data['vaccination'], $data['vaccination_fee']); $data['include_vaccination'] = '0';
        $this->post(route('consultation-workflow.store', $this->patient()), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('immunizations', 0);
        $this->assertDatabaseCount('consultations', 1);
        $this->assertDatabaseCount('charges', 1);
    }
    public function test_invalid_consultation_does_not_register_booking_patient(): void
    {
        $data = $this->payload(); unset($data['complaint']);
        $this->post(route('consultation-workflow.booking.store', $this->entry()), $data)->assertSessionHasErrors('complaint');
        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('consultations', 0);
    }
    public function test_staff_cannot_register_booking_through_consultation(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $entry = $this->entry();
        $this->get(route('consultation-workflow.booking', $entry))->assertForbidden();
        $this->post(route('consultation-workflow.booking.store', $entry), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('patients', 0);
    }
}
