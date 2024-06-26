<?php

namespace Database\Factories;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
    $profilePictures = Storage::disk('public')->files('profile_pictures');
    $randomProfilePicture = $profilePictures ? 'profile_pictures/' . basename(Arr::random($profilePictures)) : null;

    return [
      'username' => fake()->unique()->userName(),
      'profile_picture' => $randomProfilePicture,
      'name' => fake()->name(),
      'email' => fake()->unique()->safeEmail(),
      'email_verified_at' => now(),
      'password' => static::$password ??= Hash::make('password'),
      'role' => 'user',
      'description' => fake()->text(350),
      'remember_token' => Str::random(10),
    ];
  }

  public function configure() {
    return $this->afterCreating(function ($user) {
      // Example skills and categories as words
      $skills = ['PHP', 'Laravel', 'VueJS', 'React'];
      $categories = ['Web Development', 'Frontend', 'Backend'];

      // Randomly select skills and categories for each user
      $selectedSkills = Arr::random($skills, rand(1, count($skills)));
      $selectedCategories = Arr::random($categories, rand(1, count($categories)));

      // Insert selected skills into skill_user table
      foreach ($selectedSkills as $skill) {
        DB::table('skill_user')->insert([
          'user_id' => $user->id,
          'skill' => $skill,
        ]);
      }

      // Insert selected categories into category_user table
      foreach ($selectedCategories as $category) {
        DB::table('category_user')->insert([
          'user_id' => $user->id,
          'category' => $category,
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
