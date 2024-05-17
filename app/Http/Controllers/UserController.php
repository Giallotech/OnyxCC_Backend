<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller {
  /**
   * Display a listing of the resource.
   */
  public function index() {
    //
  }

  /**
   * Display the specified resource.
   */
  public function show(User $user) {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, User $user) {
    // Check if the currently authenticated user can update the given user
    if ($request->user()->cannot('update', $user)) {
      return response()->json(['message' => 'You are not authorized to update this user!'], Response::HTTP_FORBIDDEN);
    }

    $oldImageUrl = $user->profile_picture;

    if ($oldImageUrl) {
      // If the image path is a URL, extract the image name
      if (filter_var($oldImageUrl, FILTER_VALIDATE_URL)) {
        $oldImageName = basename($oldImageUrl);
      }

      if (app()->environment('production')) {
        // Delete the old image from the S3 bucket
        $oldImagePath = 'profile_picture/' . $oldImageName;
        Storage::disk('s3')->delete($oldImagePath);
      } else {
        // Delete the old image from local storage
        $oldImagePath = 'profile_picture/' . $oldImageName;
        Storage::disk('public')->delete($oldImagePath);
      }
    }

    $validatedData = $request->validate([
      'name' => 'sometimes|required|string|max:255',
      'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
      'profile_picture' => 'sometimes|image|max:2048',
      'description' => 'sometimes|required|string|max:500',
      'categories' => 'sometimes|required|array',
      'skills' => 'sometimes|required|array',
    ]);

    // Remove 'categories' and 'skills' from the validated data. If I don't do this, the 'categories' and 'skills' fields will be updated in the users table, which is not what we want, because they are stored in the pivot tables and not in the users table.
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
        Storage::disk('s3')->put($imageName, file_get_contents($image), 'public-read');

        // Manually construct the URL for the image in the S3 bucket
        $imageUrl = 'https://s3.eu-north-1.amazonaws.com/backpackit/' . $imageName;
      } else {
        // Store the image in the public/profile_picture storage directory
        $image->storeAs('public/profile_picture', $imageName);

        // Generate the URL for the image
        $imageUrl = asset('storage/profile_picture/' . $imageName);
      }

      // Save the URL of the image to the user's profile
      $user->profile_picture = $imageUrl;
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
    // Check if the currently authenticated user can delete the given user
    if ($request->user()->cannot('delete', $user)) {
      return response()->json(['message' => 'You are not authorized to delete this user!'], Response::HTTP_FORBIDDEN);
    } else {
      $user->delete();
      return response()->json(['message' => 'User deleted successfully!'], Response::HTTP_OK);
    }
  }
}
