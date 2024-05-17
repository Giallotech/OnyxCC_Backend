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
    $validatedData = $request->validate([
      'name' => 'sometimes|required|string|max:255',
      'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
      'profile_picture' => 'sometimes|image|max:2048',
      'description' => 'sometimes|required|string|max:500',
      'categories' => 'sometimes|required|array',
      'skills' => 'sometimes|required|array',
    ]);

    // dd($request->all());

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

    // Remove 'categories' and 'skills' from the validated data
    $categories = $validatedData['categories'];
    $skills = $validatedData['skills'];

    unset($validatedData['categories'], $validatedData['skills']);

    // Update the user's name, email, and description
    foreach ($validatedData as $key => $value) {
      if ($key !== 'profile_picture' && $request->has($key)) {
        $user->{$key} = $value;
      }
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
    return response()->json(['message' => 'Profile updated successfully!']);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(User $user) {
    //
  }
}
