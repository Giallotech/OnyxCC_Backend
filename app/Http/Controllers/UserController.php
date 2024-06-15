<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use App\Models\Category;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller {
  /**
   * Display a listing of the resource.
   */
  public function index() {
    $users = User::all()->toArray();

    $baseUrl = app()->environment('production') ?
      'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/' :
      rtrim(url('/storage'), '/') . '/';

    foreach ($users as &$user) {
      $user['profile_picture'] = $baseUrl . $user['profile_picture'];
    }

    return response()->json($users);
  }

  /**
   * Display the specified resource.
   */
  public function show(User $user) {
    $user = $user->toArray();

    $baseUrl = app()->environment('production') ?
      'https://' . rtrim(config('filesystems.disks.s3.bucket'), '/') . '.s3.' . rtrim(config('filesystems.disks.s3.region'), '/') . '.amazonaws.com/' : rtrim(url('/storage'), '/') . '/';

    $user['profile_picture'] = $baseUrl . $user['profile_picture'];

    return response()->json($user);
  }

  /**
   * Update the specified resource in storage.
   */

  public function update(Request $request, User $user) {
    // Check if the currently authenticated user can update the given user
    if ($request->user()->cannot('update', $user)) {
      return response()->json(['message' => 'You are not authorized to update this user!'], Response::HTTP_FORBIDDEN);
    }

    $oldImageKey = $user->profile_picture;

    if ($oldImageKey) {
      if (app()->environment('production')) {
        // Delete the old image from the S3 bucket
        Storage::disk('s3')->delete($oldImageKey);
      } else {
        // Delete the old image from local storage
        Storage::disk('public')->delete($oldImageKey);
      }
    }

    // We apply the validation rule for profile_picture based on whether the user already has a profile picture
    $profilePictureRule = $user->profile_picture ? 'sometimes|image|mimes:jpeg,png,jpg|max:2048' : 'required|image|mimes:jpeg,png,jpg|max:2048';

    $validatedData = $request->validate([
      'name' => 'sometimes|string|max:255',
      'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
      'profile_picture' => $profilePictureRule,
      'description' => 'required|string|max:500',
      'categories' => 'required|array',
      'categories.*' => 'required|string',
      'skills' => 'required|array',
      'skills.*' => 'required|string',
    ]);

    // Remove 'categories' and 'skills' from the validated data
    $categories = $validatedData['categories'];
    $skills = $validatedData['skills'];

    unset($validatedData['categories'], $validatedData['skills']);

    // Update the user's name, email, and description
    foreach ($validatedData as $key => $value) {
      // This code makes sure that only the fields that are present in the validated data are updated
      if ($request->has($key)) {
        $user->{$key} = $value;
      }
    }

    // Handle the profile picture upload
    if ($request->hasFile('profile_picture')) {
      $image = $request->file('profile_picture');
      $imageName = $image->hashName();

      if (app()->environment('production')) {
        // Use the Storage facade to store the image in the S3 bucket
        $imageKey = Storage::disk('s3')->putFile('profile_picture', $image, 'public');
      } else {
        // Store the image in the profile_picture storage directory
        $image->storeAs('profile_picture', $imageName, 'public');

        // Generate the key for the image
        $imageKey = 'profile_picture/' . $imageName;
      }

      // Save the key of the image to the user's profile
      $user->profile_picture = $imageKey;
    }

    // Sync the user's categories and skills if they are present in the validated data
    if (!empty($categories)) {
      foreach ($categories as $categoryName) {
        Category::firstOrCreate(['name' => $categoryName]);
      }
      $categoryIds = Category::whereIn('name', $categories)->pluck('id')->toArray();
      $user->categories()->sync($categoryIds);
    }

    if (!empty($skills)) {
      foreach ($skills as $skillName) {
        Skill::firstOrCreate(['name' => $skillName]);
      }
      $skillIds = Skill::whereIn('name', $skills)->pluck('id')->toArray();
      $user->skills()->sync($skillIds);
    }

    // Save the user
    $user->save();

    // Return a JSON response
    return response()->json(['message' => 'Profile updated successfully!'], Response::HTTP_OK);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Request $request, User $user) {
    if ($request->user()->cannot('delete', $user)) {
      return response()->json(['message' => 'You are not authorized to delete this user!'], Response::HTTP_FORBIDDEN);
    } else {
      // Delete the invitation associated with the user
      Invitation::where('email', $user->email)->delete();

      // Delete the user's categories
      $user->categories()->detach();

      // Delete the user's skills
      $user->skills()->detach();

      // Delete the user's profile picture from storage
      if ($user->profile_picture) {
        if (app()->environment('production')) {
          // Delete the profile picture from the S3 bucket
          Storage::disk('s3')->delete($user->profile_picture);
        } else {
          // Delete the profile picture from local storage
          Storage::disk('public')->delete($user->profile_picture);
        }
      }

      // Delete the user
      $user->delete();
      return response()->json(['message' => 'User and associated data deleted successfully!'], Response::HTTP_OK);
    }
  }
}
