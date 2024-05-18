<?php

namespace App\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectImage extends Model {
  use HasFactory;

  protected $fillable = [
    'project_id',
    'image_path',
  ];

  // Relationship between the Project model and the ProjectImage model
  public function project() {
    return $this->belongsTo(Project::class);
  }
}
