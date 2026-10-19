<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InquiryController extends Controller
{
    public function reply(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'regex:/^INQ-[A-Z0-9]{10}$/'],
            'child_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'reply' => ['required', 'string', 'min:1', 'max:2000'],
        ]);
        $apiUrl = config('services.booking_api.url');
        $apiToken = config('services.booking_api.token');

        if (! $apiUrl || ! $apiToken) {
            return back()->withInput()->withErrors(['reply' => 'The clinic connection is not configured. Please call the clinic.']);
        }

        try {
            $response = Http::acceptJson()->connectTimeout(3)->timeout(10)->withHeaders(['X-Booking-Token' => $apiToken])
                ->get(rtrim($apiUrl, '/').'/inquiries/'.rawurlencode($data['reference']), [
                    'child_name' => $data['child_name'],
                    'contact_number' => $data['contact_number'],
                ]);
            if ($response->notFound()) {
                return back()->withInput()->withErrors(['reference' => 'We could not verify this inquiry with those booking details.']);
            }
            try {
                $response->throw();
            } catch (\Illuminate\Http\Client\RequestException $exception) {
                return back()->withInput()->withErrors(['reference' => 'We could not verify this inquiry with those booking details.']);
            }
            $response = Http::acceptJson()->connectTimeout(3)->timeout(10)->withHeaders(['X-Booking-Token' => $apiToken])
                ->post(rtrim($apiUrl, '/').'/inquiries/'.rawurlencode($data['reference']).'/replies', ['reply' => $data['reply']]);
            $response->throw();
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            return back()->withInput()->withErrors(['reply' => 'The clinic connection is unavailable. Please try again or call the clinic.']);
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            return back()->withInput()->withErrors(['reply' => 'Your message could not be sent. Please try again or call the clinic.']);
        }

        return redirect()->route('booking.status')->with('status_lookup', [
            'child_name' => $data['child_name'],
            'contact_number' => $data['contact_number'],
        ])->with('message_sent', true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'topic' => ['required', 'in:General inquiry,Product availability,Service inquiry'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'consent' => ['accepted'],
        ]);
        unset($data['consent']);
        $reference = 'INQ-'.strtoupper(Str::random(10));
        DB::transaction(function () use ($data, $reference) {
            DB::table('inquiries')->insert($data + ['reference' => $reference, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('inquiry_replies')->insert([
                'inquiry_reference' => $reference,
                'sender' => 'sender',
                'message' => $data['message'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('contact')->with('inquiry_reference', $reference);
    }
}
