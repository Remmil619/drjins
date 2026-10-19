<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InquiryInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_requires_login(): void
    {
        $this->get('/inquiries')->assertRedirect('/login');
    }

    public function test_staff_can_read_portal_inquiries_and_connection_errors(): void
    {
        $this->actingAs(User::factory()->create());
        config([
            'services.booking_api.url' => 'http://portal.test/api',
            'services.booking_api.token' => 'test',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            '*' => Http::response([
                'data' => [[

                    'reference' => 'INQ-TEST',
                    'name' => 'Parent',
                    'contact_number' => '09123456789',

                    'topic' => 'General inquiry',
                    'message' => '<script>alert(1)</script>',
                    'created_at' => '2026-10-19 10:00:00',

                ]],
                'last_page' => 2,
            ]),
        ]);
        $this->get('/inquiries')->assertOk()->assertSee('INQ-TEST')->assertSee('Next')
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        Http::assertSent(fn ($request) => $request->hasHeader('X-Booking-Token', 'test') && $request['page'] === 1);
        Http::swap(new Factory);
        Http::fake([
            '*' => Http::response([], 503),
        ]);
        $this->get('/inquiries')->assertOk()->assertSee('Unable to load inquiries')->assertDontSee('No inquiries found.');
    }
}
