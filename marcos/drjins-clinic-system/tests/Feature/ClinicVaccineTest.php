<?php

namespace Tests\Feature;

use App\Models\ClinicVaccine;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicVaccineTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): Patient
    {
        return Patient::create(['patient_no'=>'PAT-VACCINE','first_name'=>'Test','last_name'=>'Child','birthdate'=>'2020-01-01','sex'=>'Female','guardian_name'=>'Guardian','contact_number'=>'09123456789','status'=>'Active']);
    }

    public function test_staff_can_add_and_change_shared_vaccine_suggestions(): void
    {
        $patient = $this->patient();
        $this->assertSame(5, ClinicVaccine::count());
        $this->assertSame(0, ClinicVaccine::where('available', true)->count());
        $this->actingAs(User::factory()->create());
        $this->post(route('clinic-vaccines.store', $patient), ['vaccine_name'=>'  Test vaccine  ', 'available'=>1])->assertSessionHasNoErrors();
        $vaccine = ClinicVaccine::where('name', 'Test vaccine')->firstOrFail();
        $page = $this->get(route('patients.show', [$patient, 'tab'=>'immunizations']))->assertOk();
        $page->assertSee('<option value="Test vaccine">', false)->assertDontSee('<option value="BCG">', false);
        $this->post(route('clinic-vaccines.store', $patient), ['vaccine_name'=>'test vaccine', 'available'=>1])->assertSessionHasErrors('vaccine_name');
        $this->patch(route('clinic-vaccines.update', [$patient, $vaccine]), ['available'=>0])->assertSessionHasNoErrors();
        $this->get(route('patients.show', [$patient, 'tab'=>'immunizations']))->assertOk()->assertDontSee('<option value="Test vaccine">', false);
        $this->assertFalse($vaccine->fresh()->available);
    }

    public function test_guests_cannot_manage_vaccines(): void
    {
        $patient = $this->patient();
        $this->post(route('clinic-vaccines.store', $patient), ['vaccine_name'=>'Test vaccine', 'available'=>1])->assertRedirect('/login');
        $this->patch(route('clinic-vaccines.update', [$patient, ClinicVaccine::first()]), ['available'=>1])->assertRedirect('/login');
    }
}
