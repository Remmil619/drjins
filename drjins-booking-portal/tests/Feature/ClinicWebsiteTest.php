<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ClinicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-24 07:00:00', 'Asia/Manila'));
    }

    private function booking(array $overrides = []): array
    {
        return array_replace([
            'guardian_first_name' => 'Test', 'guardian_surname' => 'Guardian', 'child_first_name' => 'Test', 'child_surname' => 'Child',
            'guardian_relationship' => 'Mother', 'guardian_address' => 'Abuyog, Leyte',
            'child_date_of_birth' => '2020-01-15', 'child_sex' => 'Female',
            'contact_number' => '09123456789', 'appointment_date' => '2026-09-24',
            'appointment_time' => '08:00', 'reason_for_visit' => 'Child checkup', 'consent' => '1',
        ], $overrides);
    }

    public function test_public_pages_and_navigation_are_available(): void
    {
        foreach (['/', '/services', '/products', '/patient-hub', '/about', '/contact', '/privacy', '/book-appointment', '/check-status'] as $path) {
            $this->get($path)->assertOk()->assertSee('Book Now')->assertSee('Privacy notice');
        }
        $this->get('/')->assertSee('clinic-care.svg')->assertDontSee('name="guardian_name"', false);
    }

    public function test_booking_confirmation_and_duplicate_slot_protection(): void
    {
        $response = $this->post('/book-appointment', $this->booking());
        $appointment = Appointment::firstOrFail();
        $response->assertRedirect(route('booking.success', $appointment->appointment_no))->assertSessionHas('booking_success');
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Appointment Booked')->assertSee('Test Child');
        $this->post('/book-appointment', $this->booking())->assertSessionHasErrors('appointment_time');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_separate_names_are_validated_and_saved_without_emergency_contact(): void
    {
        $this->get('/book-appointment')->assertOk()
            ->assertSee('name="guardian_first_name"', false)
            ->assertSee('name="child_surname"', false)
            ->assertDontSee('Emergency Contact');

        $this->post('/book-appointment', $this->booking([
            'guardian_surname' => '', 'child_first_name' => '',
        ]))->assertSessionHasErrors(['guardian_surname', 'child_first_name']);

        $this->post('/book-appointment', $this->booking([
            'guardian_middle_name' => 'Maria', 'child_middle_name' => 'Santos',
            'emergency_contact_name' => 'Unused Contact',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'guardian_name' => 'Test Maria Guardian',
            'child_name' => 'Test Santos Child',
            'emergency_contact_name' => null,
        ]);
    }

    public function test_invalid_past_closed_and_out_of_window_slots_are_rejected(): void
    {
        $this->post('/book-appointment', $this->booking(['appointment_time' => 'nonsense']))->assertSessionHasErrors('appointment_time');
        $this->post('/book-appointment', $this->booking(['appointment_date' => '2026-10-15']))->assertSessionHasErrors('appointment_date');
        $this->post('/book-appointment', $this->booking(['appointment_date' => '2026-09-27']))->assertSessionHasErrors('appointment_time');
        $this->post('/book-appointment', $this->booking(['consent' => '0']))->assertSessionHasErrors('consent');
        $this->post('/book-appointment', $this->booking(['contact_number' => 'abc']))->assertSessionHasErrors('contact_number');
        $this->travelTo(Carbon::parse('2026-09-24 09:00:00', 'Asia/Manila'));
        $this->post('/book-appointment', $this->booking())->assertSessionHasErrors('appointment_time');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_status_requires_matching_contact_and_success_page_requires_session(): void
    {
        $this->post('/book-appointment', $this->booking());
        $appointment = Appointment::firstOrFail();
        $this->flushSession();
        $this->get(route('booking.success', $appointment->appointment_no))->assertRedirect();
        $this->get('/check-status?appointment_no='.$appointment->appointment_no)->assertDontSee('Test Child');
        $this->post('/check-status', ['appointment_no' => $appointment->appointment_no, 'contact_number' => '09999999999'])->assertOk()->assertDontSee('Test Child')->assertSee('No matching appointment');
        $this->post('/check-status', ['appointment_no' => $appointment->appointment_no, 'contact_number' => '+639123456789'])->assertOk()->assertSee('Test Child')->assertSee('Booked');
    }

    public function test_inquiries_are_validated_and_saved_with_reference(): void
    {
        $this->post('/contact', [])->assertSessionHasErrors(['name', 'message', 'consent']);
        $this->post('/contact', ['name' => 'Test Parent', 'contact_number' => '09123456789', 'topic' => 'Product availability', 'message' => 'Please confirm product availability.', 'consent' => '1'])
            ->assertRedirect(route('contact'))->assertSessionHas('inquiry_reference');
        $this->assertDatabaseHas('inquiries', ['name' => 'Test Parent', 'topic' => 'Product availability']);
    }

    public function test_clinic_api_requires_a_configured_matching_token(): void
    {
        config(['services.booking_api_token' => null]);
        $this->getJson('/api/appointments')->assertForbidden();
        config(['services.booking_api_token' => 'test-clinic-token']);
        $this->getJson('/api/appointments')->assertForbidden();
        $this->getJson('/api/appointments', ['X-Booking-Token' => 'test-clinic-token'])->assertOk();
    }

    public function test_booking_is_available_to_clinic_and_staff_changes_reach_patient(): void
    {
        config(['services.booking_api_token' => 'test-clinic-token']);
        $headers = ['X-Booking-Token' => 'test-clinic-token'];

        $this->post('/book-appointment', $this->booking())->assertSessionHasNoErrors();
        $appointment = Appointment::firstOrFail();

        $this->getJson('/api/appointments', $headers)->assertOk()
            ->assertJsonPath('appointments.0.appointment_no', $appointment->appointment_no)
            ->assertJsonPath('appointments.0.child_date_of_birth', '2020-01-15')
            ->assertJsonPath('appointments.0.status', 'Approved');

        $endpoint = '/api/appointments/'.$appointment->appointment_no.'/status';
        $this->patchJson($endpoint, ['status' => 'Cancelled'])->assertForbidden();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'Approved']);
        $this->patchJson($endpoint, [
            'status' => 'Cancelled', 'staff_response' => 'Cancelled at guardian request.',
        ], $headers)->assertOk()->assertJsonPath('appointment.status', 'Cancelled');

        $this->post('/check-status', [
            'appointment_no' => $appointment->appointment_no, 'contact_number' => '09123456789',
        ])->assertOk()->assertSee('Cancelled')->assertSee('Cancelled at guardian request.');

        // A cancelled booking releases its slot for another family.
        $this->post('/book-appointment', $this->booking())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_missing_patient_intake_details_return_actionable_errors(): void
    {
        $this->from('/book-appointment')->post('/book-appointment', $this->booking([
            'guardian_relationship' => '', 'guardian_address' => '',
            'child_date_of_birth' => '', 'child_sex' => '',
        ]))->assertRedirect('/book-appointment')->assertSessionHasErrors([
            'guardian_relationship', 'guardian_address', 'child_date_of_birth', 'child_sex',
        ])->assertSessionHasInput('child_first_name', 'Test');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_cancelled_booking_cannot_reclaim_an_occupied_slot(): void
    {
        config(['services.booking_api_token' => 'test-token']);
        $headers = ['X-Booking-Token' => 'test-token'];
        $this->post('/book-appointment', $this->booking())->assertSessionHasNoErrors();
        $original = Appointment::firstOrFail();
        $endpoint = '/api/appointments/'.$original->appointment_no.'/status';
        $this->patchJson($endpoint, ['status' => 'Cancelled'], $headers)->assertOk();
        $this->post('/book-appointment', $this->booking())->assertSessionHasNoErrors();
        $this->patchJson($endpoint, ['status' => 'Approved'], $headers)->assertStatus(409);
        $this->assertSame('Cancelled', $original->fresh()->status);
        $this->get(route('booking.success', $original->appointment_no))->assertOk()
            ->assertSee('Cancelled')->assertDontSee('Booking confirmed!')
            ->assertDontSee('id="bookingConfirmation"', false);
    }

    public function test_booking_lock_timeout_returns_form_error_without_creating_record(): void
    {
        $lock = \Mockery::mock(Lock::class);
        $lock->shouldReceive('block')->once()->andThrow(new LockTimeoutException);
        $cache = \Mockery::mock(Cache::getFacadeRoot())->makePartial();
        Cache::swap($cache);
        $cache->shouldReceive('lock')
            ->with('appointment-2026-09-24-08:00', 15)->once()->andReturn($lock);
        $this->from('/book-appointment')->post('/book-appointment', $this->booking())
            ->assertRedirect('/book-appointment')->assertSessionHasErrors('appointment_time')
            ->assertSessionHasInput('child_first_name', 'Test');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_api_validation_and_missing_reference_do_not_change_bookings(): void
    {
        config(['services.booking_api_token' => 'test-token']);
        $headers = ['X-Booking-Token' => 'test-token'];
        $this->post('/book-appointment', $this->booking())->assertSessionHasNoErrors();
        $appointment = Appointment::firstOrFail();
        $this->patchJson('/api/appointments/'.$appointment->appointment_no.'/status', ['status' => 'Invalid'], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->patchJson('/api/appointments/unknown/status', ['status' => 'Approved'], $headers)->assertNotFound();
        $this->assertSame('Approved', $appointment->fresh()->status);
    }

    public function test_completed_visit_cannot_be_reopened_by_api(): void
    {
        config(['services.booking_api_token' => 'test-token']);
        $headers = ['X-Booking-Token' => 'test-token'];
        $this->post('/book-appointment', $this->booking())->assertSessionHasNoErrors();
        $appointment = Appointment::firstOrFail();
        $endpoint = '/api/appointments/'.$appointment->appointment_no.'/status';
        $this->patchJson($endpoint, ['status' => 'Completed'], $headers)->assertOk();
        $this->patchJson($endpoint, ['status' => 'Approved'], $headers)->assertStatus(409);
        $this->assertSame('Completed', $appointment->fresh()->status);
    }

    public function test_calendar_marks_reserved_times_unavailable(): void
    {
        $this->post('/book-appointment', $this->booking())->assertSessionHasNoErrors();
        $this->get('/book-appointment')->assertOk()->assertViewHas('calendarDates', function ($dates) {
            $today = collect($dates)->firstWhere('date', '2026-09-24');
            $sunday = collect($dates)->firstWhere('date', '2026-09-27');

            return count($dates) === 14
                && collect($today['slots'])->firstWhere('time', '08:00')['available'] === false
                && collect($today['slots'])->firstWhere('time', '08:30')['available'] === true
                && $sunday['clickable'] === false;
        });
    }
}
