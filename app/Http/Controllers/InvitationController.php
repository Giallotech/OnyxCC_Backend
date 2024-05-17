<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Response;

class InvitationController extends Controller {
  public function store(Request $request) {
    // Validate the request data.
    $request->validate([
      'email' => 'required|email|unique:invitations'
    ]);

    // Create a new invitation.
    $invitation = new Invitation;
    $invitation->email = $request->email;
    $invitation->token = Str::random(32); // Generate a random token.

    // Set the requester's user ID if a user is authenticated.
    if (Auth::check()) {
      $invitation->requested_by_user_id = Auth::id();
    }

    $invitation->status = 'pending';
    $invitation->save();

    return response($invitation, Response::HTTP_CREATED);
  }

  public function approveAndSend(Invitation $invitation) {
    // Check if the invitation is already approved.
    if ($invitation->status === 'approved') {
      return response()->json([
        'message' => 'This invitation is already approved.',
      ], Response::HTTP_BAD_REQUEST);
    }

    // Approve the invitation.
    $invitation->status = 'approved';
    $invitation->approved_by_user_id = Auth::id();
    $invitation->save();

    // Send the invitation.
    // This URL will be sent in the email to the invited user, and it will redirect them to the frontend route where they can accept the invitation.
    $url = env('FRONTEND_APP_URL') . "/accept-invitation/{$invitation->token}";

    Mail::raw("You have been invited! Click here to accept the invitation: $url", function ($message) use ($invitation) {
      $message->to($invitation->email)
        ->subject('You are invited!');
    });

    return response()->json(['message' => 'Invitation approved and sent!'], Response::HTTP_OK);
  }


  public function decline(Invitation $invitation) {
    // Send an email to the user notifying them that their invitation has been declined.
    Mail::raw("Your invitation has been declined. Please note that only staff members can register to our website.", function ($message) use ($invitation) {
      $message->to($invitation->email)
        ->subject('Invitation Declined');
    });

    // Delete the invitation from the database.
    $invitation->delete();

    return response()->json(['message' => 'Invitation declined, user notified, and invitation deleted.'], Response::HTTP_OK);
  }

  public function index() {
    // Return a list of all invitations.
    return response(Invitation::all(), Response::HTTP_OK);
  }
}
