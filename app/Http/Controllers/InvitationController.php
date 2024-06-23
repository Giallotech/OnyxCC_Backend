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

    try {
      $invitation = new Invitation;
      $invitation->email = $request->email;
      $invitation->token = Str::random(32); // Generate a random token.
      $invitation->status = 'Pending';
      $invitation->save();

      return response($invitation, Response::HTTP_CREATED);
    } catch (\Exception $e) {
      return response()->json(['message' => 'Failed to create invitation.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function approveAndSend(Invitation $invitation, Request $request) {
    // Check if the user is authorized to approve the invitation. This is done using a policy.
    if ($request->user()->cannot('approve', $invitation)) {
      return response()->json(['message' => 'You are not authorized to approve this invitation!'], Response::HTTP_FORBIDDEN);
    }

    // Check if the invitation is already approved.
    if ($invitation->status === 'Approved') {
      return response()->json([
        'message' => 'This invitation is already approved.',
      ], Response::HTTP_BAD_REQUEST);
    }

    // Approve the invitation.
    $invitation->status = 'Approved';
    $invitation->approved_by_user_id = Auth::id();
    $invitation->save();

    // Delete the invitation from the database.
    // $invitation->delete();

    // This URL will be sent in the email to the invited user, and it will redirect them to the registration page where they can create an account.
    $url = env('FRONTEND_APP_URL') . "/register?token={$invitation->token}";

    Mail::raw("Your request has been approved! Click here to register: $url", function ($message) use ($invitation) {
      $message->to($invitation->email)
        ->subject('Your request has been approved!');
    });

    return response()->json(['message' => 'Invitation approved and sent!'], Response::HTTP_OK);
  }


  public function decline(Invitation $invitation, Request $request) {
    // Check if the user is authorized to decline the invitation. This is done using a policy.
    if ($request->user()->cannot('decline', $invitation)) {
      return response()->json(['message' => 'You are not authorized to decline this invitation!'], Response::HTTP_FORBIDDEN);
    }

    // Send an email to the user notifying them that their invitation has been declined.
    Mail::raw("Your invitation has been declined. Please note that only staff members can register to our website.", function ($message) use ($invitation) {
      $message->to($invitation->email)
        ->subject('Invitation Declined');
    });


    $invitation->status = 'Declined';
    $invitation->declined_by_user_id = Auth::id();
    // $invitation->save();

    // Delete the invitation from the database.
    $invitation->delete();

    return response()->json(['message' => 'Invitation declined, user notified, and invitation deleted.'], Response::HTTP_OK);
  }

  public function index(Request $request) {
    // Check if the user is authorized to see all the invitation. This is done using a policy.
    if ($request->user()->cannot('index', Invitation::class)) {
      return response()->json(['message' => 'You are not authorized to view all invitations!'], Response::HTTP_FORBIDDEN);
    }

    // Return a list of all invitations.
    return response(Invitation::all(), Response::HTTP_OK);
  }
}
