<?php

// namespace App\Http\Controllers;

// use App\Models\Invitation;
// use Illuminate\Support\Str;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Mail;

// class InvitationController extends Controller {
//   public function sendInvitation(Invitation $invitation) {
//     $url = "https://your-react-app.com/accept-invitation/{$invitation->token}";

//     Mail::raw("You have been invited! Click here to accept the invitation: $url", function ($message) use ($invitation) {
//       $message->to($invitation->email)
//         ->subject('You are invited!');
//     });

//     return response()->json(['message' => 'Invitation sent!']);
//   }

//   public function index() {
//     // Return a list of all invitations.
//     return Invitation::all();
//   }

//   public function approve(Invitation $invitation) {
//     // Update the status of the invitation to 'approved'.
//     $invitation->status = 'approved';
//     $invitation->approved_by_user_id = Auth::id(); // Set the approver's user ID.
//     $invitation->save();

//     return $invitation;
//   }

//   public function decline(Invitation $invitation) {
//     // Update the status of the invitation to 'declined'.
//     $invitation->status = 'declined';
//     $invitation->approved_by_user_id = Auth::id(); // Set the approver's user ID.
//     $invitation->save();

//     return $invitation;
//   }

//   public function store(Request $request) {
//     // Validate the request data.
//     $request->validate([
//       'email' => 'required|email|unique:invitations'
//     ]);

//     // Create a new invitation.
//     $invitation = new Invitation;
//     $invitation->email = $request->email;
//     $invitation->token = Str::random(32); // Generate a random token.
//     $invitation->requested_by_user_id = Auth::id(); // Set the requester's user ID.
//     $invitation->status = 'pending';
//     $invitation->save();

//     return $invitation;
//   }
// }
