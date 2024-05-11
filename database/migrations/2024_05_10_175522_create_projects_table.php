<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('projects', function (Blueprint $table) {
      $table->id();
      $table->string('cover_picture_path');
      $table->string('executable_file_path')->nullable();
      $table->string('video_preview_path')->nullable();
      $table->string('name');
      $table->text('description');
      $table->foreignId('user_id')->constrained();
      $table->foreignId('skill_id')->constrained();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('projects');
  }
};
