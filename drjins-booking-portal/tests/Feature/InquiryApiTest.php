<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InquiryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiries_require_token_and_return_saved_messages(): void
    {
        config(['services.booking_api_token' => null]);
        $this->getJson('/api/inquiries')->assertForbidden();
        config(['services.booking_api_token' => 'test-token']);
        $this->getJson('/api/inquiries')->assertForbidden();
        $this->getJson('/api/inquiries', ['X-Booking-Token' => 'wrong'])->assertForbidden();
        DB::table('inquiries')->insert([
            'reference' => 'INQ-TEST', 'name' => 'Test Parent', 'contact_number' => '09123456789',
            'topic' => 'General inquiry', 'message' => 'What are your clinic hours?',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->getJson('/api/inquiries', ['X-Booking-Token' => 'test-token'])->assertOk()
            ->assertJsonPath('data.0.reference', 'INQ-TEST')->assertJsonPath('total', 1);
    }
}
