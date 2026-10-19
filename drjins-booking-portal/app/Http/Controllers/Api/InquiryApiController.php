<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InquiryApiController extends Controller
{
    public function index(Request $request)
    {
        $token = config('services.booking_api_token');
        abort_unless(is_string($token) && $token !== '' && hash_equals($token, (string) $request->header('X-Booking-Token')), 403);

        return response()->json(DB::table('inquiries')->orderByDesc('id')->paginate(20))
            ->header('Cache-Control', 'no-store');
    }
}
