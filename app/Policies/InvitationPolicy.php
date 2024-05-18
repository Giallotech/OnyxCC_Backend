<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvitationPolicy {
  public function approve(User $user): bool {
    return $user->isAdmin();
  }

  public function decline(User $user): bool {
    return $user->isAdmin();
  }

  public function index(User $user): bool {
    return $user->isAdmin();
  }
}
