<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Staff',
            'email' => 'new@example.com',
            'role' => 'staff',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ], $overrides);
    }

    public function test_guests_cannot_register_users(): void
    {
        $this->get('/users/register')->assertRedirect('/login');
        $this->post('/users/register', $this->payload())->assertRedirect('/login');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_staff_cannot_see_or_access_registration(): void
    {
        $staff = User::factory()->create();
        $this->assertSame('staff', $staff->fresh()->role);
        $this->actingAs($staff)->get('/dashboard')->assertOk()->assertDontSee('Register User');
        $this->get('/users/register')->assertForbidden();
        $this->post('/users/register', $this->payload(['role' => 'admin']))->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_create_all_roles_without_changing_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Register User');
        $this->get('/users/register')->assertOk()->assertSee('Confirm password');

        foreach (['staff', 'admin', 'doctor'] as $role) {
            $email = $role.'@example.com';
            $this->post('/users/register', $this->payload(['role' => $role, 'email' => $email]))
                ->assertRedirect(route('users.create'))->assertSessionHas('success');
            $created = User::where('email', $email)->firstOrFail();
            $this->assertSame($role, $created->role);
            $this->assertTrue(Hash::check('secure-password', $created->password));
            $this->assertAuthenticatedAs($admin);
        }
    }

    public function test_doctor_has_correct_label_and_cannot_register_users(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $this->actingAs($doctor)->get('/dashboard')->assertOk()
            ->assertSee('Doctor')->assertDontSee('Register User');
        $this->get('/users/register')->assertForbidden();
        $this->post('/users/register', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_registration_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/users/register', $this->payload([
            'name' => '', 'email' => $admin->email, 'role' => 'owner',
            'password' => 'short', 'password_confirmation' => 'different',
        ]))->assertSessionHasErrors(['name', 'email', 'role', 'password']);
        $this->assertDatabaseCount('users', 1);
    }
}
