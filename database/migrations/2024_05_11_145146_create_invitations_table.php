<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('invitations', function (Blueprint $table) {
      $table->id();
      $table->string('email')->unique();
      $table->string('token')->unique();
      $table->unsignedBigInteger('approved_by_user_id')->nullable();
      $table->unsignedBigInteger('declined_by_user_id')->nullable();
      $table->string('status')->default('Pending');
      $table->timestamp('accepted_at')->nullable();
      $table->timestamps();
      $table->foreign('approved_by_user_id')->references('id')->on('users')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('invitations');
  }
};
