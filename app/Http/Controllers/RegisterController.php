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
        'adminCode' => 'nullable|string|max:255',
        'token' => 'nullable|string',
      ]);

      $role = 'user';
      if ($request->filled('adminCode')) {
        if ($request->adminCode === env('ADMIN_REGISTRATION_CODE')) {
          $role = 'admin';
        } else {
          return response()->json(['message' => 'Invalid admin code.'], Response::HTTP_BAD_REQUEST);
        }
      } else {
        // If the user is not an admin, require an invitation token.
        if (!$request->has('token')) {
          return response()->json(['message' => 'Invitation token is required for non-admin users.'],  Response::HTTP_BAD_REQUEST);
        }

        // Check if the invitation token is valid.
        $invitation = Invitation::where('token', $request->token)->first();
        if (!$invitation || $invitation->status !== 'Approved') {
          return response()->json(['message' => 'Invalid invitation token.'], Response::HTTP_BAD_REQUEST);
        }

        if ($request->has('token') && $request->token !== '') {
          $invitation->status = 'accepted';
          $invitation->save();
        }
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

      return response()->json(
        ['message' => 'Registration successful.'],
        Response::HTTP_CREATED
      );
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    }
  }
}
