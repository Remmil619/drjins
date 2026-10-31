<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function editPassword()
    {
        return view('account.password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();

        return to_route('password.edit')->with('success', 'Password updated successfully.');
    }
}
