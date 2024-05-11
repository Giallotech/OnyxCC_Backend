<?php

namespace App\Models;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model {
  use HasFactory;

  protected $fillable = [
    'name'
  ];

  // Relationship between the Category model and the User model
  public function users() {
    return $this->belongsToMany(User::class);
  }

  // Relationship between the Category model and the Project model
  public function projects() {
    return $this->belongsToMany(Project::class);
  }
}
