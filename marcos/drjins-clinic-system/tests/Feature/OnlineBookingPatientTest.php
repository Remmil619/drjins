<?php

namespace Tests\Feature;

use App\Models\OnlineAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnlineBookingPatientTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_patient_details_are_validated_before_import(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/online-bookings')
            ->post('/appointments/update-by-reference', [
                'appointment_no' => 'APT-20260716-ABCDE',
                'action' => 'done',
            ])
            ->assertRedirect('/online-bookings')
            ->assertSessionHasErrors(['birthdate', 'sex']);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_online_appointment_can_be_imported_as_a_complete_patient(): void
    {
        Http::fake([
            '*' => Http::response(['appointments' => []]),
        ]);

        $user = User::factory()->create();

        OnlineAppointment::create([
            'appointment_no' => 'APT-20260716-ABCDE',
            'guardian_name' => 'Sample Guardian',
            'child_name' => 'Mark A. Villones',
            'contact_number' => '09069622888',
            'appointment_date' => '2026-07-16',
            'appointment_time' => '10:00:00',
            'reason_for_visit' => 'Checkup',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($user)
            ->post('/appointments/update-by-reference', [
                'appointment_no' => 'APT-20260716-ABCDE',
                'birthdate' => '2020-05-12',
                'sex' => 'Male',
                'action' => 'done',
            ]);

        $patient = \App\Models\Patient::firstOrFail();

        $response->assertRedirect(route('patients.show', $patient));

        $this->assertDatabaseHas('patients', [
            'online_appointment_no' => 'APT-20260716-ABCDE',
            'first_name' => 'Mark',
            'last_name' => 'Villones',
            'birthdate' => '2020-05-12',
            'sex' => 'Male',
        ]);

        $this->assertDatabaseHas('online_appointments', [
            'appointment_no' => 'APT-20260716-ABCDE',
            'status' => 'Completed',
        ]);
    }
}
