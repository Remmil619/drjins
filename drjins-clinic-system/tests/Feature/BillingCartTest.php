<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_combined_partial_payment_and_duplicate_protection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $patient = Patient::create([
            'patient_no' => 'CART-1',
            'first_name' => 'Test',
            'last_name' => 'Child',
            'birthdate' => '2020-01-01',
            'sex' => 'Female',
            'guardian_name' => 'Guardian',
            'contact_number' => '09123456789',
            'status' => 'Active',
        ]);
        $charges = collect([50000, 35000])->map(fn ($amount) => Charge::create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'description' => 'Test service',
            'charged_on' => today()->toDateString(),
            'amount_centavos' => $amount,
            'request_key' => (string) Str::uuid(),
        ]));
        $this->get(route('patients.show', [
            $patient,
            'tab' => 'billing',
        ]))->assertOk()->assertSee('Services &amp; payments', false)->assertSee('850.00')->assertSee('Record payment')->assertDontSee('+ Record payment');
        $data = [
            'charge_ids' => $charges->pluck('id')->all(),
            'amount' => '600.00',
            'paid_on' => today()->toDateString(),
            'method' => 'Cash',
            'request_key' => (string) Str::uuid(),
        ];
        $this->post(route('billing.checkout', $patient), $data)->assertSessionHasNoErrors();
        $this->assertSame(0, $charges[0]->fresh()->balance_centavos);
        $this->assertSame(25000, $charges[1]->fresh()->balance_centavos);
        $this->post(route('billing.checkout', $patient), $data)->assertSessionHasNoErrors();
        $this->assertSame(2, Payment::count());
        $this->get(route('billing.print', $patient))->assertOk()
            ->assertSee('Print / Save as PDF')->assertSee('CART-1')
            ->assertSee('PHP 850.00')->assertSee('PHP 600.00')->assertSee('PHP 250.00');
        $this->post(route('billing.checkout', $patient), array_merge($data, [
            'amount' => '700.00',
        ]))->assertSessionHasErrors();
        $data['request_key'] = (string) Str::uuid();
        $this->post(route('billing.checkout', $patient), $data)->assertSessionHasErrors('amount');
        $data['amount'] = '250.00';
        $data['method'] = 'GCash';
        $this->post(route('billing.checkout', $patient), $data)->assertSessionHasErrors('reference');
        $data['reference'] = 'GCASH-TEST';
        $this->post(route('billing.checkout', $patient), $data)->assertSessionHasNoErrors();
        $this->assertSame(85000, (int) Payment::sum('amount_centavos'));
        $this->get(route('patients.show', [
            $patient,
            'tab' => 'billing',
        ]))->assertOk()->assertSee('No priced services awaiting payment.');
    }
}
