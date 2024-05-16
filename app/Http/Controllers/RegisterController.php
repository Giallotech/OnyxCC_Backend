<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller {
  public function __invoke(Request $request) {
    try {
      $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'username' => ['required', 'string', 'min:2', 'max:255', Rule::unique(User::class)],
        'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
        'password' => ['required', 'string'],
        'registration_code' => 'required',
      ]);

      $role = 'user';
      if ($request->registration_code === env('ADMIN_REGISTRATION_CODE')) {
        $role = 'admin';
      }

      $user = User::create([
        'name' => $request->get('name'),
        'username' => $request->get('username'),
        'email' => $request->get('email'),
        'password' => Hash::make($request->get('password')),
        'role' => $role,
      ]);

      Auth::login($user);
      $request->session()->regenerate();
      // By calling the Auth::login() method, we are logging the user in after registration. We are also regenerating the session to prevent session fixation attacks.

      return response()->json(
        ['message' => 'Registration successful.'],
        status: 201
      );
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], status: 400);
    }
  }
}
