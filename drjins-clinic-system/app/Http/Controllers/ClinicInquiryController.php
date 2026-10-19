<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ClinicInquiryController extends Controller
{
    public function reply(Request $request, string $reference)
    {
        $data = $request->validate(['reply' => 'required|string|min:1|max:2000']);
        $url = config('services.booking_api.url');
        $token = config('services.booking_api.token');
        if (! $url || ! $token) {
            throw ValidationException::withMessages(['reply' => 'The booking portal connection is not configured.']);
        }

        try {
            $response = Http::acceptJson()->connectTimeout(3)->timeout(10)
                ->withHeaders(['X-Booking-Token' => $token])
                ->post(rtrim($url, '/').'/inquiries/'.rawurlencode($reference).'/replies', $data);
            if (! $response->successful()) {
                throw ValidationException::withMessages(['reply' => 'The reply could not be delivered. Please try again.']);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            throw ValidationException::withMessages(['reply' => 'The booking portal is unavailable. Please try again.']);
        }

        return redirect()->route('inquiries.index', ['page' => $request->input('page', 1)])
            ->with('success', 'Reply sent to the booking portal.');
    }

    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $inquiries = [];
        $lastPage = 1;
        $error = null;

        try {
            $url = config('services.booking_api.url');
            $token = config('services.booking_api.token');

            if (! $url || ! $token) {
                throw new \RuntimeException('Portal connection is not configured.');
            }
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(10)
                ->withHeaders([
                    'X-Booking-Token' => $token,
                ])
                ->get(rtrim($url, '/').'/inquiries', [
                    'page' => $page,
                ])
                ->throw();

            $data = $response->json();

            if (
                ! is_array($data)
                || ! isset($data['data'], $data['last_page'])
                || ! is_array($data['data'])
            ) {
                throw new \RuntimeException('Invalid portal response.');
            }

            $inquiries = $data['data'];
            $lastPage = (int) $data['last_page'];
        } catch (\Exception $exception) {
            $error = 'Unable to load inquiries from the booking portal. Please try again or check the portal connection.';
        }

        return response()->view('inquiries.index', compact('inquiries', 'page', 'lastPage', 'error'))
            ->header('Cache-Control', 'no-store');
    }
}
