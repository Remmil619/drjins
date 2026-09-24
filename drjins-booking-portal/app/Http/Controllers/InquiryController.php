<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InquiryController extends Controller
{
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
        DB::table('inquiries')->insert($data + ['reference' => $reference, 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('contact')->with('inquiry_reference', $reference);
    }
}
