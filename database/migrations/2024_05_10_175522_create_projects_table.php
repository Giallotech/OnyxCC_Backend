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
      $table->string('cover_picture');
      $table->string('executable_file')->nullable();
      $table->string('video_preview')->nullable();
      $table->string('title');
      $table->text('description');
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
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
