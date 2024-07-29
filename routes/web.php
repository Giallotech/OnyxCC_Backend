<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// Route::get('/', function () {
//   return view('welcome');
// });

// This route is for serving the static files from the React app, such as the index.html file, the CSS and JS files, and the images.
Route::get('/{any?}', function ($any = null) {
  $path = public_path('dist/' . $any);

  if (File::isDirectory($path)) {
    // If the path is a directory, serve the index.html file
    return File::get(public_path('dist/index.html'));
  }

  if (File::exists($path)) {
    // If the file exists, serve it
    return File::get($path);
  }

  // If the file doesn't exist, serve the index.html file
  return File::get(public_path('dist/index.html'));
})->where('any', '.*');
