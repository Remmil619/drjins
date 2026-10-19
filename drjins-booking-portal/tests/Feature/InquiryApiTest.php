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

    public function test_parents_can_read_and_reply_to_an_inquiry_after_matching_the_contact_number(): void
    {
        config(['services.booking_api_token' => 'test-token']);
        DB::table('inquiries')->insert([
            'reference' => 'INQ-ABCDEFGHIJ', 'name' => 'Test Parent', 'contact_number' => '09123456789',
            'topic' => 'General inquiry', 'message' => 'What are your clinic hours?', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->getJson('/api/inquiries/INQ-ABCDEFGHIJ?child_name=Test+Child&contact_number=09123456789', ['X-Booking-Token' => 'test-token'])
            ->assertOk()->assertJsonPath('reference', 'INQ-ABCDEFGHIJ');
        $this->getJson('/api/inquiries/INQ-ABCDEFGHIJ?child_name=Test+Child&contact_number=09999999999', ['X-Booking-Token' => 'test-token'])
            ->assertNotFound();
        $this->postJson('/api/inquiries/INQ-ABCDEFGHIJ/replies', ['reply' => 'Thank you, I understand.'], ['X-Booking-Token' => 'test-token'])
            ->assertOk();
        $this->assertDatabaseHas('inquiry_replies', [
            'inquiry_reference' => 'INQ-ABCDEFGHIJ', 'sender' => 'staff', 'message' => 'Thank you, I understand.',
        ]);
    }

    public function test_staff_reply_is_visible_in_parent_status_lookup(): void
    {
        DB::table('appointments')->insert([
            'appointment_no' => 'APT-TEST-12345', 'guardian_name' => 'Test Parent', 'child_name' => 'Test Child',
            'contact_number' => '09123456789', 'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '09:00:00', 'status' => 'Approved', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inquiries')->insert([
            'reference' => 'INQ-ABCDEFGHIJ', 'name' => 'Test Parent', 'contact_number' => '09123456789',
            'topic' => 'General inquiry', 'message' => 'What are your clinic hours?', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inquiry_replies')->insert([
            'inquiry_reference' => 'INQ-ABCDEFGHIJ', 'sender' => 'staff', 'message' => 'We open at 8 AM.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->post('/check-status', ['child_name' => 'Test Child', 'contact_number' => '09123456789'])
            ->assertOk()->assertSee('We open at 8 AM.')->assertSee('Reply to clinic staff');
    }
}
