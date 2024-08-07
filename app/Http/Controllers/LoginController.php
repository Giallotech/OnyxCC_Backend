<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class LoginController extends Controller {
  public function __invoke(Request $request) {
    $request->validate([
      'email' => 'required|string|email|max:255',
      'password' => 'required|string',
    ]);

    $credentials = $request->only('email', 'password');

    if (!Auth::guard('web')->attempt($credentials)) {
      return response()->json([
        'message' => 'Invalid credentials'
      ], Response::HTTP_UNAUTHORIZED);
    }

    $request->session()->regenerate();
    return response()->json(status: Response::HTTP_NO_CONTENT);
  }
}
