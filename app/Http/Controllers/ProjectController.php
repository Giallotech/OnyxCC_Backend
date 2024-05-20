<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\Project;
use App\Models\Category;
use App\Models\ProjectImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller {
  /**
   * Display a listing of the resource.
   */
  // public function index() {
  //   $projects = Project::all()->toArray();

  //   if (app()->environment('production')) {
  //     $s3BaseUrl = 'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/';

  //     foreach ($projects as &$project) {
  //       $project['cover_picture'] = $s3BaseUrl . $project['cover_picture'];
  //       $project['executable_file'] = $s3BaseUrl . $project['executable_file'];
  //       $project['video_preview'] = $s3BaseUrl . $project['video_preview'];

  //       foreach ($project['images'] as &$image) {
  //         $image['image_path'] = $s3BaseUrl . $image['image_path'];
  //       }
  //     }
  //   }

  //   return response()->json($projects, 200);
  // }

  // /**
  //  * Display the specified resource.
  //  */
  // public function show(Project $project) {
  //   $project = $project->toArray();

  //   if (app()->environment('production')) {
  //     $s3BaseUrl = 'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/';

  //     $project['cover_picture'] = $s3BaseUrl . $project['cover_picture'];
  //     $project['executable_file'] = $s3BaseUrl . $project['executable_file'];
  //     $project['video_preview'] = $s3BaseUrl . $project['video_preview'];

  //     foreach ($project['images'] as &$image) {
  //       $image['image_path'] = $s3BaseUrl . $image['image_path'];
  //     }
  //   }

  //   return response()->json($project, 200);
  // }

  public function index() {
    $projects = Project::all()->toArray();

    $baseUrl = app()->environment('production') ?
      'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/' :
      url('/storage/');

    foreach ($projects as &$project) {
      $project['cover_picture'] = $baseUrl . $project['cover_picture'];
      $project['executable_file'] = $baseUrl . $project['executable_file'];
      $project['video_preview'] = $baseUrl . $project['video_preview'];

      foreach ($project['images'] as &$image) {
        $image['image_path'] = $baseUrl . $image['image_path'];
      }
    }

    return response()->json($projects, 200);
  }

  public function show(Project $project) {
    $project = $project->toArray();

    $baseUrl = app()->environment('production') ?
      'https://' . config('filesystems.disks.s3.bucket') . '.s3.' . config('filesystems.disks.s3.region') . '.amazonaws.com/' :
      url('/storage/');

    $project['cover_picture'] = $baseUrl . $project['cover_picture'];
    $project['executable_file'] = $baseUrl . $project['executable_file'];
    $project['video_preview'] = $baseUrl . $project['video_preview'];

    foreach ($project['images'] as &$image) {
      $image['image_path'] = $baseUrl . $image['image_path'];
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
        Storage::disk('s3')->put($fileName, file_get_contents($file), 'public-read');
      } else {
        // Store the file in the specified storage directory
        $file->storeAs('public/' . $directory, $fileName);
      }

      return $directory . '/' . $fileName;
    }
    return null;
  }

  /**
   * Delete the file from storage.
   */
  private function deleteFileFromStorage($filePath) {
    if (app()->environment('production')) {
      Storage::disk('s3')->delete($filePath);
    } else {
      $imagePath = str_replace('public/', '', $filePath);
      Storage::disk('public')->delete($imagePath);
    }
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request) {
    $request->validate([
      'cover_picture' => 'required|image',
      'executable_file' => 'nullable|file',
      'video_preview' => 'nullable|mimes:mp4,avi,mov,ogg,qt',
      'title' => 'required|string|max:255',
      'description' => 'required|string|max:1000',
      'categories' => 'required|array',
      'categories.*' => 'string|exists:categories,name',
      'skills' => 'required|array',
      'skills.*' => 'string|exists:skills,name',
      'images' => 'nullable|array',
      'images.*' => 'image',
    ]);

    $project = new Project($request->all());
    $project->user_id = auth()->id();

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

    // Find category IDs and attach them to the project
    $categoryIds = Category::whereIn('name', $request->categories)->pluck('id');
    $project->categories()->attach($categoryIds);

    // Find skill IDs and attach them to the project
    $skillIds = Skill::whereIn('name', $request->skills)->pluck('id');
    $project->skills()->attach($skillIds);

    if ($request->has('images')) {
      foreach ($request->file('images') as $image) {
        // Handle the image upload and get the image path
        $imagePath = $this->handleUpload($image, 'project_images');

        // Create a new ProjectImage instance and save it to the database
        $projectImage = new ProjectImage(['image_path' => $imagePath]);
        $projectImage->project_id = $project->id;
        $projectImage->save();
      }
    }

    return response()->json(['message' => 'Project created successfully'], Response::HTTP_CREATED);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Project $project) {
    $request->validate([
      'cover_picture' => 'nullable|image',
      'executable_file' => 'nullable|file',
      'video_preview' => 'nullable|mimes:mp4,avi,mov,ogg,qt',
      'title' => 'nullable|string|max:255',
      'description' => 'nullable|string|max:1000',
      'categories' => 'nullable|array',
      'categories.*' => 'string|exists:categories,name',
      'skills' => 'nullable|array',
      'skills.*' => 'string|exists:skills,name',
      'images' => 'nullable|array',
      'images.*' => 'image',
    ]);

    if ($project->user_id !== auth()->id()) {
      return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

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

    $project->categories()->detach();

    $project->skills()->detach();

    $project->delete();

    return response()->json(['message' => 'Project deleted successfully'], Response::HTTP_OK);
  }
}
