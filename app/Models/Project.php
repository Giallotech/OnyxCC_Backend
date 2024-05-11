<?php

namespace App\Models;

use App\Models\User;
use App\Models\Skill;
use App\Models\Category;
use App\Models\ProjectImages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model {
  use HasFactory;

  protected $fillable = [
    'name',
    'description',
    'user_id',
    'cover_picture_path',
    'skill_id'
  ];

  // Relationship between the Project model and the User model
  public function user() {
    return $this->belongsTo(User::class);
  }

  // Relationship between the Project model and the Category model
  public function categories() {
    return $this->belongsToMany(Category::class);
  }

  // Relationship between the Project model and the Skill model
  public function skills() {
    return $this->belongsToMany(Skill::class);
  }

  // Relationship between the Project model and the Project_images model
  public function images() {
    return $this->hasMany(ProjectImages::class);
  }
}
