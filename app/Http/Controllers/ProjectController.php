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
  public function index() {
    $projects = Project::all();
    return response()->json($projects, 200);
  }

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

    // If the request includes an executable file, handle the file upload
    if ($request->has('executable_file')) {
      $executableFilePath = $this->handleUpload($request->file('executable_file'), 'executables');
      $project->executable_file = $executableFilePath;
    }

    // If the request includes a video preview, handle the file upload
    if ($request->has('video_preview')) {
      $videoPreviewPath = $this->handleUpload($request->file('video_preview'), 'videos');
      $project->video_preview = $videoPreviewPath;
    }

    // If the request includes a cover picture, handle the file upload
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

    // If the request includes images, handle the image uploads
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
   * Handle the file upload and return the file path.
   */
  private function handleUpload($file, $directory) {
    if ($file) {
      $fileName = $file->hashName();

      if (app()->environment('production')) {
        // Use the Storage facade to store the file in the S3 bucket
        Storage::disk('s3')->put($fileName, file_get_contents($file), 'public-read');

        // Manually construct the URL for the file in the S3 bucket
        $fileUrl = 'https://s3.eu-north-1.amazonaws.com/backpackit/' . $fileName;
      } else {
        // Store the file in the specified storage directory
        $file->storeAs('public/' . $directory, $fileName);

        // Generate the URL for the file
        $fileUrl = asset('storage/' . $directory . '/' . $fileName);
      }

      return $fileUrl;
    }
    return null;
  }

  /**
   * Display the specified resource.
   */
  public function show(Project $project) {
    return response()->json($project, 200);
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

    // $fieldsToUpdate = $request->only(['name', 'description']);
    $fieldsToUpdate = $request->only(['title', 'description']);

    if ($request->has('executable_file')) {
      $oldExecutableFileUrl = $project->executable_file;
      if ($oldExecutableFileUrl) {
        $oldExecutableFileName = basename($oldExecutableFileUrl);
        $oldExecutableFilePath = 'executables/' . $oldExecutableFileName;
        $this->deleteFileFromStorage($oldExecutableFilePath);
      }
      $executableFilePath = $this->handleUpload($request->file('executable_file'), 'executables');
      $fieldsToUpdate['executable_file'] = $executableFilePath;
    }

    if ($request->has('video_preview')) {
      $oldVideoPreviewUrl = $project->video_preview;
      if ($oldVideoPreviewUrl) {
        $oldVideoPreviewName = basename($oldVideoPreviewUrl);
        $oldVideoPreviewPath = 'videos/' . $oldVideoPreviewName;
        $this->deleteFileFromStorage($oldVideoPreviewPath);
      }
      $videoPreviewPath = $this->handleUpload($request->file('video_preview'), 'videos');
      $fieldsToUpdate['video_preview'] = $videoPreviewPath;
    }

    if ($request->has('cover_picture')) {
      $oldCoverPictureUrl = $project->cover_picture;
      if ($oldCoverPictureUrl) {
        $oldCoverPictureName = basename($oldCoverPictureUrl);
        $oldCoverPicturePath = 'cover_pictures/' . $oldCoverPictureName;
        $this->deleteFileFromStorage($oldCoverPicturePath);
      }
      $coverPicturePath = $this->handleUpload($request->file('cover_picture'), 'cover_pictures');
      $fieldsToUpdate['cover_picture'] = $coverPicturePath;
    }

    if ($request->has('images')) {
      foreach ($project->images as $image) {
        $oldImageUrl = $image->image_path;
        if ($oldImageUrl) {
          $oldImageName = basename($oldImageUrl);
          $oldImagePath = 'project_images/' . $oldImageName;
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

  private function deleteFileFromStorage($filePath) {
    if (app()->environment('production')) {
      // Delete the file from the S3 bucket
      Storage::disk('s3')->delete($filePath);
    } else {
      // Delete the file from local storage
      Storage::disk('public')->delete($filePath);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Project $project) {
    if ($project->user_id !== auth()->id()) {
      return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    // Delete associated files
    if ($project->executable_file) {
      $oldExecutableFileName = basename($project->executable_file);
      $oldExecutableFilePath = 'executables/' . $oldExecutableFileName;
      $this->deleteFileFromStorage($oldExecutableFilePath);
    }

    if ($project->video_preview) {
      $oldVideoPreviewName = basename($project->video_preview);
      $oldVideoPreviewPath = 'videos/' . $oldVideoPreviewName;
      $this->deleteFileFromStorage($oldVideoPreviewPath);
    }

    if ($project->cover_picture) {
      $oldCoverPictureName = basename($project->cover_picture);
      $oldCoverPicturePath = 'cover_pictures/' . $oldCoverPictureName;
      $this->deleteFileFromStorage($oldCoverPicturePath);
    }

    foreach ($project->images as $image) {
      $oldImageName = basename($image->image_path);
      $oldImagePath = 'project_images/' . $oldImageName;
      $this->deleteFileFromStorage($oldImagePath);
      $image->delete();
    }

    // Delete the project's categories
    $project->categories()->detach();

    // Delete the project's skills
    $project->skills()->detach();

    // Delete the project
    $project->delete();

    return response()->json(['message' => 'Project deleted successfully'], Response::HTTP_OK);
  }
}
