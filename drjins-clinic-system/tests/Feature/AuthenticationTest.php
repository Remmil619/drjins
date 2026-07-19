<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/patients')->assertRedirect('/login');
    }

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back');
    }

    public function test_staff_can_sign_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'secure-password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_staff_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();

        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_pages_cannot_be_restored_from_browser_cache(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSee('logoutConfirmationModal')
            ->assertSee('Confirm logout')
            ->assertSee('Yes, log out')
            ->assertHeader(
                'Cache-Control',
                'max-age=0, must-revalidate, no-cache, no-store, private',
            )
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');
    }
}
