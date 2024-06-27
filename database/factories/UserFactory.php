<?php

namespace Database\Factories;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory {
  /**
   * The current password being used by the factory.
   */
  protected static ?string $password;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array {
    global $profilePictures;
    if (!is_array($profilePictures)) {
      $profilePictures = [
        'profile_pictures/uifaces-popular-image-1.jpg',
        'profile_pictures/uifaces-popular-image-2.jpg',
        'profile_pictures/uifaces-popular-image-3.jpg',
        'profile_pictures/uifaces-popular-image-4.jpg',
        'profile_pictures/uifaces-popular-image-5.jpg',
        'profile_pictures/uifaces-popular-image-6.jpg',
      ];
    }

    static $pictureIndex = 0;

    $randomProfilePicture = !empty($profilePictures) ? 'profile_pictures/' . basename($profilePictures[$pictureIndex]) : null;
    if (!empty($profilePictures)) {
      $pictureIndex = ($pictureIndex + 1) % count($profilePictures); // Move to the next picture, loop back if at the end
    }

    return [
      'username' => fake()->unique()->userName(),
      'profile_picture' => $randomProfilePicture,
      'name' => fake()->name(),
      'email' => fake()->unique()->safeEmail(),
      'email_verified_at' => now(),
      'password' => static::$password ??= Hash::make('password'),
      'role' => 'user',
      'description' => fake()->text(350),
    ];
  }

  public function configure() {
    return $this->afterCreating(function ($user) {
      // Existing skill association logic
      $skills = ['PHP', 'Laravel', 'VueJS', 'React'];
      $selectedSkills = Arr::random($skills, rand(1, count($skills)));
      $skillIds = \App\Models\Skill::whereIn('name', $selectedSkills)->pluck('id');
      foreach ($skillIds as $skillId) {
        DB::table('skill_user')->insert([
          'user_id' => $user->id,
          'skill_id' => $skillId,
        ]);
      }

      // Add logic for associating categories
      $categories = ['Web Development', 'Frontend', 'Backend', 'Full Stack']; // Example categories
      $selectedCategories = Arr::random($categories, rand(1, count($categories)));
      $categoryIds = \App\Models\Category::whereIn('name', $selectedCategories)->pluck('id'); // Assuming Category model exists
      foreach ($categoryIds as $categoryId) {
        DB::table('category_user')->insert([
          'user_id' => $user->id,
          'category_id' => $categoryId,
        ]);
      }
    });
  }

  /**
   * Indicate that the model's email address should be unverified.
   */
  public function unverified(): static {
    return $this->state(fn (array $attributes) => [
      'email_verified_at' => null,
    ]);
  }
}
