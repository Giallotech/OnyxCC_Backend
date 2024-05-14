<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\InvitationController;


Route::get('/user', function (Request $request) {
  return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
  // Route::post('/logout', LogoutController::class);

  // Admin routes
  Route::post('/admin/send-invitation/{invitation}', [InvitationController::class, 'sendInvitation']);
  Route::get('/admin/invitations', [InvitationController::class, 'index']);
  Route::post('/admin/invitations/{invitation}/approve', [InvitationController::class, 'approve']);
  Route::post('/admin/invitations/{invitation}/decline', [InvitationController::class, 'decline']);

  Route::apiResource('users', UserController::class)->except('index', 'show');
  Route::apiResource('projects', ProjectController::class)->except('index', 'show');
  Route::apiResource('skills', SkillController::class)->except('index', 'show');
  Route::apiResource('categories', CategoryController::class)->except('index', 'show');
});

// All these routes and the methods they call are outside the middleware group, so they're accessible without authentication
// Route::post('/register', RegisterController::class);
// Route::post('/login', LoginController::class);
Route::post('/request-invitation', [InvitationController::class, 'store']);

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);

Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);

Route::get('/skills', [SkillController::class, 'index']);
Route::get('/skills/{skill}', [SkillController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
