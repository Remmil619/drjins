<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InquiryApiController extends Controller
{
    public function show(Request $request, string $reference)
    {
        $token = config('services.booking_api_token');
        abort_unless(is_string($token) && $token !== '' && hash_equals($token, (string) $request->header('X-Booking-Token')), 403);

        $data = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'regex:/^(09\\d{9}|\\+639\\d{9})$/'],
        ]);
        $localNumber = preg_replace('/^\\+63/', '0', $data['contact_number']);
        $inquiry = DB::table('inquiries')->where('reference', $reference)
            ->whereIn('contact_number', [$localNumber, '+63'.substr($localNumber, 1)])->first();
        abort_unless($inquiry, 404);

        $inquiry->replies = DB::table('inquiry_replies')->where('inquiry_reference', $reference)->orderBy('id')->get();
        return response()->json($inquiry)->header('Cache-Control', 'no-store');
    }

    public function index(Request $request)
    {
        $token = config('services.booking_api_token');
        abort_unless(is_string($token) && $token !== '' && hash_equals($token, (string) $request->header('X-Booking-Token')), 403);

        $paginator = DB::table('inquiries')->orderByDesc('id')->paginate(20);
        $references = $paginator->getCollection()->pluck('reference');
        $replies = DB::table('inquiry_replies')->whereIn('inquiry_reference', $references)->orderBy('id')->get()
            ->groupBy('inquiry_reference');
        $paginator->getCollection()->transform(function ($inquiry) use ($replies) {
            $inquiry->replies = $replies->get($inquiry->reference, collect())->values();
            return $inquiry;
        });

        return response()->json($paginator)
            ->header('Cache-Control', 'no-store');
    }

    public function reply(Request $request, string $reference)
    {
        $token = config('services.booking_api_token');
        abort_unless(is_string($token) && $token !== '' && hash_equals($token, (string) $request->header('X-Booking-Token')), 403);
        $data = $request->validate(['reply' => 'required|string|min:1|max:2000']);
        abort_unless(DB::table('inquiries')->where('reference', $reference)->exists(), 404);
        DB::table('inquiry_replies')->insert([
            'inquiry_reference' => $reference,
            'sender' => 'staff',
            'message' => $data['reply'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Reply saved.']);
    }
}
