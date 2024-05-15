<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model {
  use HasFactory;

  protected $fillable = [
    'email',
    'token',
    'requested_by_user_id',
    'approved_by_user_id',
    'status',
    'accepted_at',
  ];

  public function requestedByUser() {
    return $this->belongsTo(User::class, 'requested_by_user_id');
  }

  public function approvedByUser() {
    return $this->belongsTo(User::class, 'approved_by_user_id');
  }
}
