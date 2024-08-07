<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\Project;
use App\Models\User;
use App\Models\Category;
use App\Models\ProjectImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller {
  /**
   * Display a listing of the resource.
   */
  public function index() {
    $projects = Project::with('images', 'skills', 'categories')->get()->toArray();

    $baseUrl = app()->environment('production') ?
      'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/' :
      url('/storage') . '/';

    foreach ($projects as &$project) {
      $project['cover_picture'] = $baseUrl . $project['cover_picture'];
      $project['executable_file'] = $baseUrl . $project['executable_file'];
      $project['video_preview'] = $baseUrl . $project['video_preview'];

      if (isset($project['images'])) {
        foreach ($project['images'] as &$image) {
          $image['image_path'] = $baseUrl . $image['image_path'];
        }
      }
    }

    return response()->json($projects, 200);
  }

  public function show(Project $project) {
    $project = $project->load('images', 'skills', 'categories')->toArray();

    $baseUrl = app()->environment('production') ?
      'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/' :
      url('/storage') . '/';

    $project['cover_picture'] = $baseUrl . $project['cover_picture'];

    if ($project['executable_file']) {
      $project['executable_file'] = $baseUrl . $project['executable_file'];
    }
    if ($project['video_preview']) {
      $project['video_preview'] = $baseUrl . $project['video_preview'];
    }

    if (isset($project['images'])) {
      foreach ($project['images'] as &$image) {
        $image['image_path'] = $baseUrl . $image['image_path'];
      }
    }

    return response()->json($project, 200);
  }

  /**
   * Handle the file upload and return the file path.
   */
  private function handleUpload($file, $directory) {
    if ($file) {
      $fileName = $file->hashName();

      if (app()->environment('production')) {
        // Use the Storage facade to store the file in the S3 bucket
        Storage::disk('s3')->putFileAs($directory, $file, $fileName, 'public');
      } else {
        // Store the file in the specified storage directory
        $file->storeAs($directory, $fileName, 'public');
      }

      return $directory . '/' . $fileName;
    }
    return null;
  }

  public function downloadExecutable(Project $project) {
    if (!$project->executable_file) {
      return response()->json(['message' => 'No executable file found for this project.'], 404);
    }

    if (app()->environment('production')) {
      // For S3 storage, generate a temporary URL for the file download
      $filePath = $project->executable_file;
      $disk = Storage::disk('s3');
      if (!$disk->exists($filePath)) {
        return response()->json(['message' => 'File not found.'], 404);
      }

      $temporaryUrl = Storage::temporaryUrl(
        $filePath,
        now()->addMinutes(5),
        ['ResponseContentDisposition' => 'attachment']
      );

      // Correctly redirect to the temporary URL
      return redirect($temporaryUrl);
    } else {
      // For local storage, use the download method directly
      $filePath = storage_path('app/public/' . $project->executable_file);
      if (!file_exists($filePath)) {
        return response()->json(['message' => 'File not found.'], 404);
      }
      return response()->download($filePath);
    }
  }

  /**
   * Delete the file from storage.
   */
  private function deleteFileFromStorage($filePath) {
    if (app()->environment('production')) {
      Storage::disk('s3')->delete($filePath);
    } else {
      if (Storage::disk('public')->exists($filePath)) {
        Storage::disk('public')->delete($filePath);
      }
    }
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request) {
    $user = User::find(auth()->id());

    if ($user->created_at == $user->updated_at) {
      return response()->json(['message' => 'Please update your profile before creating a project.'], 403);
    }

    $validateRules = [
      'cover_picture' => 'required|image|mimes:jpeg,png,jpg,webp,avif|max:2048',
      'executable_file' => 'nullable|file|mimes:zip',
      'video_preview' => ['nullable', 'file', 'streamable'],
      'title' => 'required|string|max:255',
      'description' => 'required|string|max:1000',
      'categories' => 'required|array',
      'skills' => 'required|array',
      // 'images' => 'required|array',
      // 'images.*' => 'required|image|mimes:jpeg,png,jpg,webp,avif|max:2048',
      'images' => 'sometimes|array',
      'images.*' => 'image|mimes:jpeg,png,jpg,webp,avif|max:2048',
    ];

    $request->validate($validateRules);

    $project = new Project($request->all());
    $project->user_id = $user->id;

    if ($request->has('executable_file')) {
      $executableFilePath = $this->handleUpload($request->file('executable_file'), 'executables');
      $project->executable_file = $executableFilePath;
    }

    if ($request->has('video_preview')) {
      $videoPreviewPath = $this->handleUpload($request->file('video_preview'), 'videos');
      $project->video_preview = $videoPreviewPath;
    }

    if ($request->has('cover_picture')) {
      $coverPicturePath = $this->handleUpload($request->file('cover_picture'), 'cover_pictures');
      $project->cover_picture = $coverPicturePath;
    }

    $project->save();

    $categoryIds = collect($request->categories)->map(function ($categoryName) use ($user) {
      $category = Category::firstOrCreate(['name' => $categoryName]);
      $user->categories()->attach($category->id); // Attach category to user
      return $category->id;
    });
    $project->categories()->attach($categoryIds);

    $skillIds = collect($request->skills)->map(function ($skillName) use ($user) {
      $skill = Skill::firstOrCreate(['name' => $skillName]);
      $user->skills()->attach($skill->id); // Attach skill to user
      return $skill->id;
    });
    $project->skills()->attach($skillIds);

    foreach ($request->file('images') as $image) {
      $imagePath = $this->handleUpload($image, 'project_images');
      $projectImage = new ProjectImage(['image_path' => $imagePath]);
      $projectImage->project_id = $project->id;
      $projectImage->save();
    }

    return response()->json(['message' => 'Project created successfully'], Response::HTTP_CREATED);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Project $project) {
    $request->validate([
      'cover_picture' => 'nullable|image|mimes:jpeg,png,jpg,webp,avif|max:2048',
      'executable_file' => 'nullable|file|mimes:zip',
      'video_preview' => ['nullable', 'file', 'streamable'],
      'title' => 'nullable|string|max:255',
      'description' => 'nullable|string|max:1000',
      'categories' => 'nullable|array',
      'categories.*' => 'string',
      'skills' => 'nullable|array',
      'skills.*' => 'string',
      'images' => 'nullable|array',
      'images.*' => 'image|mimes:jpeg,png,jpg,webp,avif|max:2048',
    ]);

    if ($project->user_id !== auth()->id()) {
      return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    $user = User::find(auth()->id());
    $fieldsToUpdate = $request->only(['title', 'description']);

    if ($request->has('executable_file')) {
      $oldExecutableFilePath = $project->executable_file;
      if ($oldExecutableFilePath) {
        $this->deleteFileFromStorage($oldExecutableFilePath);
      }
      $executableFilePath = $this->handleUpload($request->file('executable_file'), 'executables');
      $fieldsToUpdate['executable_file'] = $executableFilePath;
    }

    if ($request->has('video_preview')) {
      $oldVideoPreviewPath = $project->video_preview;
      if ($oldVideoPreviewPath) {
        $this->deleteFileFromStorage($oldVideoPreviewPath);
      }
      $videoPreviewPath = $this->handleUpload($request->file('video_preview'), 'videos');
      $fieldsToUpdate['video_preview'] = $videoPreviewPath;
    }

    if ($request->has('cover_picture')) {
      $oldCoverPicturePath = $project->cover_picture;
      if ($oldCoverPicturePath) {
        $this->deleteFileFromStorage($oldCoverPicturePath);
      }
      $coverPicturePath = $this->handleUpload($request->file('cover_picture'), 'cover_pictures');
      $fieldsToUpdate['cover_picture'] = $coverPicturePath;
    }

    if ($request->has('categories')) {
      $categoryIds = collect($request->categories)->map(function ($categoryName) use ($user) {
        $category = Category::firstOrCreate(['name' => $categoryName]);
        $user->categories()->attach($category->id);
        return $category->id;
      });
      $project->categories()->sync($categoryIds);
    }

    if ($request->has('skills')) {
      $skillIds = collect($request->skills)->map(function ($skillName) use ($user) {
        $skill = Skill::firstOrCreate(['name' => $skillName]);
        $user->skills()->attach($skill->id);
        return $skill->id;
      });
      $project->skills()->sync($skillIds);
    }

    if ($request->has('images')) {
      foreach ($project->images as $image) {
        $oldImagePath = $image->image_path;
        if ($oldImagePath) {
          $this->deleteFileFromStorage($oldImagePath);
        }
        $image->delete();
      }

      foreach ($request->file('images') as $image) {
        $imagePath = $this->handleUpload($image, 'project_images');
        $projectImage = new ProjectImage(['image_path' => $imagePath]);
        $projectImage->project_id = $project->id;
        $projectImage->save();
      }
    }

    $project->update($fieldsToUpdate);

    return response()->json(['message' => 'Project updated successfully'], Response::HTTP_OK);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Project $project) {
    if ($project->user_id !== auth()->id()) {
      return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    if ($project->executable_file) {
      $this->deleteFileFromStorage($project->executable_file);
    }

    if ($project->video_preview) {
      $this->deleteFileFromStorage($project->video_preview);
    }

    if ($project->cover_picture) {
      $this->deleteFileFromStorage($project->cover_picture);
    }

    foreach ($project->images as $image) {
      $this->deleteFileFromStorage($image->image_path);
      $image->delete();
    }

    $project->delete();

    return response()->json(['message' => 'Project deleted successfully'], Response::HTTP_OK);
  }
}
