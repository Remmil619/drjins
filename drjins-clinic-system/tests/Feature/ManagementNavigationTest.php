<?php

namespace Tests\Feature;

use App\Models\{ClinicVaccine, Consultation, Patient, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function patient(string $name, string $number): Patient
    {
        return Patient::create(['patient_no' => $number, 'first_name' => $name, 'last_name' => 'TEST',
            'birthdate' => '2020-01-01', 'sex' => 'Female', 'guardian_name' => 'GUARDIAN',
            'contact_number' => '09123456789', 'status' => 'Active']);
    }

    public function test_consultation_history_lists_all_patients_and_filters_records(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        $ana = $this->patient('ANA', 'PAT-ANA'); $bea = $this->patient('BEA', 'PAT-BEA');
        foreach ([[$ana, '2026-01-02', null], [$bea, '2026-02-03', now()]] as [$patient, $date, $voided]) {
            Consultation::create(['patient_id' => $patient->id, 'user_id' => $user->id, 'visit_date' => $date,
                'complaint' => 'CHECK UP', 'assessment' => 'WELL', 'voided_at' => $voided, 'void_reason' => $voided ? 'Duplicate record' : null]);
        }
        $this->get(route('consultations.index'))->assertOk()->assertSee('ANA TEST')->assertSee('BEA TEST')->assertSee('Voided');
        $this->get(route('consultations.index', ['patient' => $ana->id]))
            ->assertOk()->assertSee('ANA TEST')->assertDontSee('BEA TEST')
            ->assertSee(route('consultations.index', ['patient' => $ana->id]))
            ->assertSee("All patients' consultations", false);
        $this->get(route('consultations.index', ['search' => 'ANA TEST']))->assertOk()->assertSee('PAT-ANA')->assertDontSee('PAT-BEA');
        $this->get(route('consultations.index', ['search' => 'PAT-BEA']))->assertOk()->assertSee('BEA TEST')->assertDontSee('ANA TEST');
        $this->get(route('consultations.index', ['from' => '2026-03-01', 'status' => 'active']))
            ->assertOk()->assertSee('ANA TEST')->assertSee('BEA TEST')
            ->assertDontSee('id="history-from"', false)->assertDontSee('id="history-to"', false)
            ->assertDontSee('id="history-status"', false)->assertSee('id="consultation-search"', false);

    }

    public function test_shared_vaccine_management_updates_both_patient_forms(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $patient = $this->patient('ANA', 'PAT-ANA');
        $this->post(route('vaccines.store'), ['vaccine_name' => 'STAFF VACCINE', 'available' => '1', 'price' => '1234.50'])
            ->assertSessionHasNoErrors()->assertRedirect(route('vaccines.index'));
        $vaccine = ClinicVaccine::where('name', 'STAFF VACCINE')->firstOrFail();
        $this->assertSame(123450, (int) $vaccine->price_centavos);
        $this->get(route('vaccines.index'))->assertOk()->assertSee('STAFF VACCINE');
        $this->get(route('patients.show', $patient))->assertOk()
            ->assertSee('<option value="STAFF VACCINE" ', false)
            ->assertSee('<option value="STAFF VACCINE" ', false);
        $this->patch(route('vaccines.update', $vaccine), ['available' => '0'])->assertSessionHasNoErrors();
        $this->get(route('patients.show', $patient))->assertOk()
            ->assertDontSee('<option value="STAFF VACCINE" ', false)
            ->assertDontSee('<option value="STAFF VACCINE" ', false);
        $this->get(route('vaccines.index', ['availability' => 'available']))->assertOk()->assertDontSee('STAFF VACCINE');
        $this->get(route('vaccines.index', ['availability' => 'unavailable', 'search' => 'STAFF']))->assertOk()->assertSee('STAFF VACCINE');
        $this->patch(route('vaccines.update', $vaccine), ['price' => '900.25'])->assertSessionHasNoErrors();
        $this->assertSame(90025, (int) $vaccine->fresh()->price_centavos);
        $this->post(route('vaccines.store'), ['vaccine_name' => 'staff vaccine', 'available' => '1'])->assertSessionHasErrors('vaccine_name');
        $this->patch(route('vaccines.update', $vaccine), ['available' => 'invalid', 'price' => '-1'])->assertSessionHasErrors(['available', 'price']);
    }

    public function test_navigation_has_management_children_and_correct_active_state(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('vaccines.index'))->assertOk()->assertSee('Management')->assertSee('Consultation')
            ->assertSee('id="managementMenu" class="collapse management-links list-unstyled show"', false)
            ->assertSee(route('patients.index'))->assertSee(route('queue.index'))->assertSee(route('billing.index'));
        $this->get(route('consultations.index'))->assertOk()->assertSee('No consultation records found');
    }

    public function test_guests_cannot_read_or_change_management_data(): void
    {
        $this->get(route('consultations.index'))->assertRedirect('/login');
        $this->get(route('vaccines.index'))->assertRedirect('/login');
        $this->post(route('vaccines.store'), ['vaccine_name' => 'TEST', 'available' => '1'])->assertRedirect('/login');
        $this->patch(route('vaccines.update', ClinicVaccine::first()), ['available' => '1'])->assertRedirect('/login');
    }
}
