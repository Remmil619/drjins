<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Consultation;
use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClinicFormsTest extends TestCase
{
    use RefreshDatabase;

    private function details(): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'Child',
            'birthdate' => '2020-01-01',
            'sex' => 'Female',

            'guardian_name' => 'Guardian',
            'contact_number' => '09123456789',
            'status' => 'Active',
        ];
    }

    private function patient(): Patient
    {
        return Patient::create($this->details() + [
            'patient_no' => 'PAT-'.Str::random(8),
            'address' => 'Existing address',
        ]);
    }

    public function test_structured_address_saves_and_survives_editing(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/patients', $this->details() + [
            'barangay' => 'San Jose',
            'municipality' => 'Tacloban',
            'province' => 'Leyte',
        ])
            ->assertSessionHasNoErrors();
        $patient = Patient::firstOrFail();
        $this->assertSame('San Jose, Tacloban, Leyte', $patient->address);
        $this->get(route('patients.edit', $patient))->assertOk()->assertSee('San Jose')->assertSee('Height (cm)')->assertSee('Weight (kg)');
        $this->put(route('patients.update', $patient), $this->details() + [
            'barangay' => 'San Pedro',
            'municipality' => 'Tacloban',
            'province' => 'Leyte',
        ])->assertSessionHasNoErrors();
        $this->assertSame('San Pedro, Tacloban, Leyte', $patient->fresh()->address);
        $this->put(route('patients.update', $patient), $this->details() + [
            'barangay' => 'Incomplete',
        ])->assertSessionHasErrors(['municipality', 'province']);
    }

    public function test_legacy_address_is_preserved_when_new_fields_are_empty(): void
    {
        $this->actingAs(User::factory()->create());
        $patient = $this->patient();
        $this->put(route('patients.update', $patient), $this->details() + [
            'barangay' => '',
            'municipality' => '',
            'province' => '',
            'address' => 'Existing address',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Existing address', $patient->fresh()->address);
    }

    public function test_prescription_print_is_scoped_and_only_prints_saved_prescription(): void
    {
        $user = User::factory()->create();
        $patient = $this->patient();
        $visit = Consultation::create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'visit_date' => today()->toDateString(),

            'complaint' => 'Private complaint',
            'assessment' => 'Private assessment',
            'prescription' => 'Test prescription text',
        ]);
        $url = route('prescriptions.print', [$patient, $visit]);
        $this->get($url)->assertRedirect('/login');
        $this->actingAs($user)->get($url)->assertOk()->assertSee('Test prescription text')->assertDontSee('Private assessment')->assertDontSee('Private complaint');
        $this->get(route('prescriptions.print', [$this->patient(), $visit]))->assertNotFound();
        $this->get(route('patients.show', $patient))->assertOk()->assertSee('Print prescription')->assertSee('Patient details')->assertDontSee('Print this section');
        $visit->update([
            'voided_at' => now(),
        ]);
        $this->get($url)->assertNotFound();
        $visit->update([
            'voided_at' => null,
            'prescription' => null,
        ]);
        $this->get($url)->assertNotFound();
    }

    public function test_billing_summary_and_vaccine_suggestions_render(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $patient = $this->patient();
        $charge = Charge::create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'charged_on' => today()->toDateString(),

            'description' => 'Consultation fee',
            'amount_centavos' => 50000,
            'request_key' => (string) Str::uuid(),
        ]);
        Payment::create([
            'charge_id' => $charge->id,
            'user_id' => $user->id,
            'paid_on' => today()->toDateString(),

            'amount_centavos' => 20000,
            'method' => 'Cash',
            'request_key' => (string) Str::uuid(),
        ]);
        $this->get(route('patients.show', [
            $patient,
            'tab' => 'billing',
        ]))->assertOk()->assertSee('Billing &amp; Fees', false)
            ->assertSee('PHP 500.00')->assertSee('PHP 200.00')->assertSee('PHP 300.00')->assertSee('common-fees');
        $this->get(route('patients.show', [
            $patient,
            'tab' => 'immunizations',
        ]))->assertOk()->assertSee('common-vaccines')->assertSee('BCG')->assertSee('Hepatitis B');
    }

    public function test_complete_patient_details_saves_address_and_measurements(): void
    {
        $this->actingAs(User::factory()->create());
        config([
            'services.booking_api.url' => '',
            'services.booking_api.token' => '',
        ]);
        $booking = OnlineAppointment::create([
            'appointment_no' => 'APT-FORM',
            'guardian_name' => 'Guardian',

            'child_name' => 'Test Child',
            'contact_number' => '09123456789',
            'appointment_date' => today()->toDateString(),

            'appointment_time' => '10:00:00',
            'status' => 'Approved',
        ]);
        $this->get('/appointments')->assertOk()->assertSee('Brgy.')->assertSee('Municipality')->assertSee('Province')->assertSee('modalReferenceDetails');
        $this->post('/appointments/save-patient-details', $this->details() + [
            'appointment_no' => $booking->appointment_no,

            'appointment_status' => 'done',
            'height' => '100',
            'weight' => '20',

            'barangay' => 'San Jose',
            'municipality' => 'Tacloban',
            'province' => 'Leyte',
        ])->assertSessionHasNoErrors();
        $patient = Patient::firstOrFail();
        $this->assertSame('San Jose, Tacloban, Leyte', $patient->address);
        $this->assertEquals(100, $patient->height);
        $this->assertEquals(20, $patient->weight);
        $this->assertSame('Completed', $booking->fresh()->status);
    }
}
