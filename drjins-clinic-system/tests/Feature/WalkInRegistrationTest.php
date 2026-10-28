<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function details(): array
    {
        return [
            'first_name' => 'ANA', 'last_name' => 'CRUZ', 'birthdate' => '01/02/20',
            'sex' => 'Female', 'status' => 'Active', 'guardian_first_name' => 'MARIA',
            'guardian_middle_name' => 'DELA', 'guardian_surname' => 'CRUZ',
            'guardian_relationship' => 'Mother', 'contact_number' => '09123456789',
            'province' => 'LEYTE', 'municipality' => 'ABUYOG', 'barangay' => 'BUNGA',
            'height' => '100', 'weight' => '20', 'temperature' => '36.5',
        ];
    }

    public function test_walkin_form_saves_name_parts_address_birthdate_and_dated_vitals(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/patients/create')->assertOk()->assertSee('MM/DD/YY')
            ->assertSee('guardian_first_name')->assertSee('patient-address.js')
            ->assertDontSee('name="emergency_contact"', false);
        $this->post('/patients', $this->details())->assertSessionHasNoErrors();
        $patient = Patient::firstOrFail();
        $this->assertSame('2020-01-02', $patient->birthdate);
        $this->assertSame('MARIA DELA CRUZ', $patient->guardian_name);
        $this->assertSame('DELA', $patient->guardian_middle_name);
        $this->assertSame('BUNGA, ABUYOG, LEYTE', $patient->address);
        $this->assertDatabaseHas('growth_records', [
            'patient_id' => $patient->id, 'measured_on' => today()->toDateString(),
            'height_cm' => 100, 'weight_kg' => 20, 'temperature_c' => 36.5,
        ]);
        $this->get(route('patients.show', $patient))->assertOk()->assertSee('Height: 100')->assertSee('36.5');
        $this->get(route('patients.show', [$patient, 'tab' => 'growth']))->assertOk()
            ->assertSee('value="100"', false)->assertSee('value="20"', false)->assertSee('value="36.5"', false);
        $this->get(route('patients.edit', $patient))->assertOk()->assertSee('01/02/20')->assertSee('DELA');
        $this->put(route('patients.update', $patient), $this->details())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('growth_records', 1);
    }

    public function test_invalid_date_or_incomplete_vitals_do_not_create_patient(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/patients', array_replace($this->details(), ['birthdate' => '02/30/20']))
            ->assertSessionHasErrors('birthdate');
        $this->post('/patients', array_replace($this->details(), ['height' => '', 'weight' => '-1']))
            ->assertSessionHasErrors(['height', 'weight']);
        $this->assertDatabaseCount('patients', 0);
    }

    public function test_measurements_can_be_edited_without_creating_a_duplicate(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/patients', $this->details())->assertSessionHasNoErrors();
        $patient = Patient::firstOrFail();
        $record = \App\Models\GrowthRecord::firstOrFail();
        $data = ['measured_on' => today()->toDateString(), 'height_cm' => 110, 'weight_kg' => 22, 'temperature_c' => 37];
        $this->patch(route('growth.update', [$patient, $record]), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('growth_records', 1);
        $this->assertEquals(22, $record->fresh()->weight_kg);
        $this->assertEquals(22, $patient->fresh()->weight);
        $this->patch(route('growth.update', [$patient, $record]), array_replace($data, ['weight_kg' => -2]))->assertSessionHasErrors('weight_kg');
        $this->post('/patients', $this->details())->assertSessionHasNoErrors();
        $other = Patient::latest('id')->firstOrFail();
        $this->patch(route('growth.update', [$other, $record]), $data)->assertNotFound();
        $record->update(['voided_at' => now(), 'void_reason' => 'Incorrect record']);
        $this->patch(route('growth.update', [$patient, $record]), $data)->assertNotFound();
    }

    public function test_vaccination_edit_updates_record_and_rejects_invalid_changes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/patients', $this->details());
        $patient = Patient::firstOrFail();
        $data = ['vaccine' => 'IPV', 'dose' => 'FIRST', 'provider' => 'CLINIC DOCTOR',
            'source' => 'clinic', 'administered_on' => today()->toDateString()];
        $this->post(route('immunizations.store', $patient), $data)->assertSessionHasNoErrors();
        $record = \App\Models\Immunization::firstOrFail();
        $this->patch(route('immunizations.update', [$patient, $record]), array_replace($data, ['dose' => 'SECOND']))->assertSessionHasNoErrors();
        $this->assertSame('SECOND', $record->fresh()->dose);
        $this->assertDatabaseCount('immunizations', 1);
        $this->get(route('patients.show', [$patient, 'tab' => 'immunizations']))->assertOk()
            ->assertSee('Edit vaccination')->assertSee('Given at this clinic');
        $this->patch(route('immunizations.update', [$patient, $record]), array_replace($data, ['administered_on' => today()->addDay()->toDateString()]))->assertSessionHasErrors('administered_on');
        $this->post('/patients', $this->details());
        $other = Patient::latest('id')->firstOrFail();
        $this->patch(route('immunizations.update', [$other, $record]), $data)->assertNotFound();
        $this->post(route('immunizations.store', $patient), $data)->assertSessionHasNoErrors();
        $this->patch(route('immunizations.update', [$patient, $record]), $data)->assertSessionHasErrors('vaccine');
        $record->update(['voided_at' => now()]);
        $this->patch(route('immunizations.update', [$patient, $record]), $data)->assertNotFound();
    }

    public function test_combined_consultation_saves_vitals_and_prints_only_notes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/patients', $this->details())->assertSessionHasNoErrors();
        $patient = Patient::firstOrFail();
        $input = [
            'visit_date' => today()->toDateString(), 'complaint' => 'ROUTINE CHECKUP',
            'assessment' => 'DOING WELL', 'prescription' => 'FOLLOW INSTRUCTIONS',
            'height_cm' => '100', 'weight_kg' => '20', 'temperature_c' => '36.5',
            'request_key' => (string) \Illuminate\Support\Str::uuid(),
        ];
        $this->post(route('consultations.store', $patient), $input)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('growth_records', 1);
        $this->post(route('consultations.store', $patient), $input)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultations', 1);
        $this->post(route('consultations.store', $patient), array_replace($input, ['weight_kg' => '21']))->assertSessionHasErrors('request_key');
        $visit = $patient->consultations()->firstOrFail();
        $this->get(route('consultations.print', [$patient, $visit]))->assertOk()
            ->assertSee('ROUTINE CHECKUP')->assertSee('DOING WELL')->assertDontSee('36.5')->assertDontSee('Height');
        $this->post(route('consultations.store', $patient), array_replace($input, [
            'request_key' => (string) \Illuminate\Support\Str::uuid(), 'weight_kg' => '21',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('growth_records', 2);
        $this->get(route('patients.show', $patient))->assertOk()->assertDontSee('id="panel-growth"', false)
            ->assertSee('consultation-height_cm')->assertSee('Print consultation');
    }

    public function test_services_are_billed_automatically_using_consultation_and_vaccine_prices(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/patients', $this->details());
        $patient = Patient::firstOrFail();
        $visit = ['visit_date' => today()->toDateString(), 'complaint' => 'CHECKUP', 'assessment' => 'WELL'];
        $this->post(route('consultations.store', $patient), $visit)->assertSessionHasNoErrors();
        $this->post(route('consultations.store', $patient), $visit)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('charges', 1);
        $this->assertDatabaseHas('charges', ['description' => 'CONSULTATION FEE', 'amount_centavos' => 50000, 'price_pending' => false]);
        $data = ['vaccine' => 'IPV', 'dose' => 'FIRST', 'provider' => 'DOCTOR', 'source' => 'clinic', 'administered_on' => today()->toDateString()];
        $this->post(route('immunizations.store', $patient), $data)->assertSessionHasNoErrors();
        $charge = \App\Models\Charge::where('price_pending', true)->firstOrFail();
        $this->post(route('payments.store', $charge), ['amount' => '100', 'paid_on' => today()->toDateString(), 'method' => 'Cash', 'request_key' => (string) \Illuminate\Support\Str::uuid()])->assertSessionHasErrors('amount');
        $this->post(route('clinic-vaccines.store', $patient), ['vaccine_name' => 'IPV', 'available' => 1, 'price' => '1200.50'])->assertSessionHasNoErrors();
        $this->assertEquals(120050, $charge->fresh()->amount_centavos);
        $this->assertFalse((bool) $charge->fresh()->price_pending);
        $vaccine = \App\Models\ClinicVaccine::where('name', 'IPV')->firstOrFail();
        $this->patch(route('clinic-vaccines.update', [$patient, $vaccine]), ['price' => '1300'])->assertSessionHasNoErrors();
        $this->assertEquals(120050, $charge->fresh()->amount_centavos);
        $this->post(route('immunizations.store', $patient), array_replace($data, ['dose' => 'SECOND']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('charges', ['amount_centavos' => 130000]);
        $this->post(route('immunizations.store', $patient), array_replace($data, ['dose' => 'THIRD', 'source' => 'external_record']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('charges', 3);
        $record = \App\Models\Immunization::where('dose', 'FIRST')->firstOrFail();
        $this->patch(route('immunizations.update', [$patient, $record]), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('charges', 3);
        $this->assertEquals(120050, $charge->fresh()->amount_centavos);
        $this->get(route('patients.show', [$patient, 'tab' => 'billing']))->assertOk()->assertDontSee('Add to cart')->assertSee('Save price');
    }
}
