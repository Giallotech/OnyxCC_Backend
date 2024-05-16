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

Route::post('/login', LoginController::class);
Route::middleware('auth:sanctum')->group(function () {

  // Admin routes
  Route::post('/admin/invitations/{invitation}/approve-and-send', [InvitationController::class, 'approveAndSend']);
  Route::get('/admin/invitations', [InvitationController::class, 'index']);
  Route::delete('/admin/invitations/{invitation}/decline', [InvitationController::class, 'decline']);
  Route::post('/logout', LogoutController::class);
  Route::apiResource('users', UserController::class)->except('index', 'show');
  Route::apiResource('projects', ProjectController::class)->except('index', 'show');
  Route::apiResource('skills', SkillController::class)->except('index', 'show');
  Route::apiResource('categories', CategoryController::class)->except('index', 'show');
});

// All these routes and the methods they call are outside the middleware group, so they're accessible without authentication
Route::post('/register', RegisterController::class);
Route::post('/request-invitation', [InvitationController::class, 'store']);

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);

Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);

Route::get('/skills', [SkillController::class, 'index']);
Route::get('/skills/{skill}', [SkillController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
