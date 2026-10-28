<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_statement_requires_login_and_excludes_voided_and_other_patient_records(): void
    {
        $user = User::factory()->create();
        $patient = Patient::create([
            'patient_no' => 'PRINT-1', 'first_name' => 'Test', 'last_name' => 'Child',
            'birthdate' => '2020-01-01', 'sex' => 'Female', 'guardian_name' => 'Guardian',
            'contact_number' => '09123456789', 'status' => 'Active',
        ]);
        $url = route('billing.print', $patient);
        $this->get($url)->assertRedirect('/login');
        $this->actingAs($user)->get($url)->assertOk()->assertSee('No charges recorded.')->assertSee('PHP 0.00');
        for ($i = 0; $i < 16; $i++) {
            $charge = Charge::create([
                'patient_id' => $patient->id, 'user_id' => $user->id,
                'description' => 'Service '.$i, 'charged_on' => '2026-01-01',
                'amount_centavos' => 10000, 'request_key' => (string) Str::uuid(),
            ]);
        }
        Payment::create([
            'charge_id' => $charge->id, 'user_id' => $user->id, 'paid_on' => '2026-01-01',
            'amount_centavos' => 5000, 'method' => 'Cash', 'reference' => 'VOID-PAYMENT',
            'voided_at' => now(), 'request_key' => (string) Str::uuid(),
        ]);
        $void = $charge->replicate();
        $void->description = 'VOID-CHARGE';
        $void->request_key = (string) Str::uuid();
        $void->voided_at = now();
        $void->save();
        $other = $patient->replicate();
        $other->patient_no = 'PRINT-2';
        $other->save();
        $otherCharge = $charge->replicate();
        $otherCharge->patient_id = $other->id;
        $otherCharge->description = 'OTHER-PATIENT';
        $otherCharge->request_key = (string) Str::uuid();
        $otherCharge->save();
        $this->get($url)->assertOk()->assertSee('Service 0')->assertSee('Service 15')
            ->assertSee('PHP 1,600.00')->assertSee('No payments recorded.')
            ->assertDontSee('VOID-CHARGE')->assertDontSee('VOID-PAYMENT')->assertDontSee('OTHER-PATIENT');
        $this->get(route('patients.show', [$patient, 'tab' => 'billing']))->assertOk()->assertSee($url, false);
    }
}
