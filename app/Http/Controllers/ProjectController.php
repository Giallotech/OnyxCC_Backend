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
    //
  }

  /**
   * Store a newly created resource in storage.
   */
  // public function store(Request $request) {
  //   $request->validate([
  //     'cover_picture' => 'required|image',
  //     'executable_file' => 'nullable|file',
  //     'video_preview' => 'nullable|mimes:mp4,avi,mov,ogg,qt',
  //     'name' => 'required|string|max:255',
  //     'description' => 'required|string|max:1000',
  //     'user_id' => 'required|exists:users,id',
  //     'categories' => 'required|array',
  //     'categories.*' => 'exists:categories,id',
  //     'skills' => 'required|array',
  //     'skills.*' => 'exists:skills,id',
  //     'images' => 'nullable|array',
  //     'images.*' => 'image',
  //   ]);

  //   $project = new Project($request->all());
  //   $project->user_id = auth()->id();
  //   $project->save();

  //   // Attach categories and skills to the project
  //   $project->categories()->attach($request->categories);
  //   $project->skills()->attach($request->skills);

  //   // If the request includes images, handle the image uploads
  //   if ($request->has('images')) {
  //     foreach ($request->images as $image) {
  //       // Handle the image upload and get the image path
  //       $imagePath = $this->handleImageUpload($image);

  //       // Create a new ProjectImage instance and save it to the database
  //       $projectImage = new ProjectImage(['image_path' => $imagePath]);
  //       $projectImage->project_id = $project->id;
  //       $projectImage->save();
  //     }
  //   }

  //   return response()->json(['message' => 'Project created successfully'], Response::HTTP_CREATED);
  // }

  public function store(Request $request) {
    $request->validate([
      'cover_picture' => 'required|image',
      'executable_file' => 'nullable|file',
      'video_preview' => 'nullable|mimes:mp4,avi,mov,ogg,qt',
      'name' => 'required|string|max:255',
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
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Project $project) {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Project $project) {
    //
  }
}
