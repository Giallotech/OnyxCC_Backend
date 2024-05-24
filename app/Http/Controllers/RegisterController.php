<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller {
  public function __invoke(Request $request) {
    try {
      $request->validate([
        'name' => 'required|string|max:255',
        'username' => 'required|string|min:2|max:255|unique:users,username',
        'email' => 'required|string|email|max:255|unique:users,email',
        'password' => 'required|string',
        'registration_code' => 'nullable|string|max:255',
        'token' => 'nullable|string',
      ]);

      $role = 'user';
      if ($request->registration_code === env('ADMIN_REGISTRATION_CODE')) {
        $role = 'admin';
      } else {
        // If the user is not an admin, require an invitation token.
        if (!$request->has('token')) {
          return response()->json(['message' => 'Invitation token is required for non-admin users.'],  Response::HTTP_BAD_REQUEST);
        }

        // Check if the invitation token is valid.
        $invitation = Invitation::where('token', $request->token)->first();
        if (!$invitation || $invitation->status !== 'approved') {
          return response()->json(['message' => 'Invalid invitation token.'], Response::HTTP_BAD_REQUEST);
        }
      }

      $user = User::create([
        'name' => $request->get('name'),
        'username' => $request->get('username'),
        'email' => $request->get('email'),
        'password' => Hash::make($request->get('password')),
        'role' => $role,
      ]);

      if ($request->has('token')) {
        $invitation->status = 'accepted';
        $invitation->save();
      }

      Auth::login($user);
      $request->session()->regenerate();

      return response()->json(
        ['message' => 'Registration successful.'],
        Response::HTTP_CREATED
      );
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    }
  }
}
