<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Category;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {
  public function run() {
    User::factory()->count(6)->create()->each(function ($user) {
      // Associate projects
      $user->projects()->save(Project::factory()->create(['user_id' => $user->id]));

      // Associate categories
      $categories = Category::inRandomOrder()->take(rand(1, 3))->get(); // Adjust the numbers as needed
      $user->categories()->attach($categories);

      // Associate skills
      $skills = Skill::inRandomOrder()->take(rand(1, 5))->get(); // Adjust the numbers as needed
      $user->skills()->attach($skills);
    });
  }
}
