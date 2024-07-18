<?php

namespace Database\Factories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory {
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array {
    global $coverPictures;
    if (!is_array($coverPictures)) {
      // Assuming cover pictures are stored in 'public/cover_pictures' directory
      $coverPictures = Storage::disk('public')->files('cover_pictures');
    }

    static $pictureIndex = 0;

    $randomCoverPicture = !empty($coverPictures) ? 'cover_pictures/' . basename($coverPictures[$pictureIndex]) : null;
    if (!empty($coverPictures)) {
      $pictureIndex = ($pictureIndex + 1) % count($coverPictures); // Move to the next picture, loop back if at the end
    }

    return [
      'cover_picture' => $randomCoverPicture,
      'title' => fake()->sentence(2),
      'description' => fake()->text(500),
    ];
  }

  public function configure() {
    return $this->afterCreating(function ($project) {
      $skills = ['PHP', 'Laravel', 'VueJS', 'React'];
      $categories = [
        'See All',
        'Webdesign & Development',
        'Games Programming',
        'Game Art',
        'Audio Engineering',
      ];

      // Ensure skills exist in the database before associating them with a project
      foreach ($skills as $skill) {
        $skillRecord = \App\Models\Skill::firstOrCreate(['name' => $skill]);
        DB::table('project_skill')->insert([
          'project_id' => $project->id,
          'skill_id' => $skillRecord->id,
        ]);
      }

      // Ensure categories exist in the database before associating them with a project
      foreach ($categories as $category) {
        $categoryRecord = \App\Models\Category::firstOrCreate(['name' => $category]);
        DB::table('category_project')->insert([
          'project_id' => $project->id,
          'category_id' => $categoryRecord->id,
        ]);
      }
    });
  }
}
