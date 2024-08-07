<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\InvitationController;

Route::get('/user', function (Request $request) {
  return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
  Route::post('/logout', LogoutController::class);

  // Admin routes
  Route::post('/admin/invitations/approve-and-send/{invitation}', [InvitationController::class, 'approveAndSend']);
  Route::delete('/admin/invitations/decline/{invitation}', [InvitationController::class, 'decline']);
  Route::get('/admin/invitations', [InvitationController::class, 'index']);

  Route::apiResource('users', UserController::class)->except('index', 'show');

  // I am using the POST method to update a user's information because some browsers don't support the PUT method. I'll include the _method field in the request body to tell Laravel to treat the request as a PUT request.
  Route::post('/users/{user}', [UserController::class, 'update']);
  Route::apiResource('projects', ProjectController::class)->except('index', 'show');
  Route::post('/projects/{project}', [ProjectController::class, 'update']);
});

// All these routes and the methods they call are outside the middleware group, so they're accessible without authentication
Route::post('/register', RegisterController::class);
Route::post('/request-invitation', [InvitationController::class, 'store']);

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);

Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);
Route::get('/projects/download/{project}', [ProjectController::class, 'downloadExecutable']);
