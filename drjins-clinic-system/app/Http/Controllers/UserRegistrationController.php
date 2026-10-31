<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class UserRegistrationController extends Controller
{
    public function create()
    {
        return view('users.create', ['users' => User::orderBy('name')->get()]);
    }

    public function details(Request $request, User $user)
    {
        $request->validate(['admin_password' => ['required', 'current_password']]);
        return response()->json(['name' => $user->name, 'email' => $user->email, 'role' => ucfirst($user->role), 'password' => $user->password_display]);
    }

    public function updatePassword(Request $request, User $user)
    {
        $data = $request->validate(['admin_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(8)]]);
        $user->forceFill(['password' => Hash::make($data['password']), 'password_display' => $data['password']])->save();
        return back()->with('success', 'User password changed successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate(['admin_password' => ['required', 'current_password']]);
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');
        $user->delete();
        return back()->with('success', 'User account deleted.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'staff', 'doctor'])],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        $user->password = $data['password'];
        $user->password_display = $data['password'];
        $user->save();

        return to_route('users.create')->with('success', 'User registered successfully. They can now sign in.');
    }
}
