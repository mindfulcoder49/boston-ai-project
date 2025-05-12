<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Check if the user has a password set.
        // We consider a user as "social-only" if their password field is effectively null
        // or if we had a more explicit flag like `is_social_only`.
        // The random password we set for social users is not something they'd know.
        // A more robust check could be `!$user->password || $user->provider_name !== null`
        // to ensure they are a social user who might have a random password.
        // For simplicity here, we'll check if a password was actually set by the user (not random).
        // A simple check: if $user->password is null (if you don't set random ones)
        // OR if $user->provider_name is not null (meaning they are a social user)
        // we can bypass password check.

        $isSocialOnlyUser = !empty($user->provider_name) && (
            // Check if the password field is null (if you never set one for social users)
            // OR if the password is the known "randomly generated" pattern if you can identify it
            // For a simpler approach: if they have a provider_name, and no password was submitted
            // (because the frontend won't show the field), we assume they can delete.
            // The frontend will handle showing/hiding the password field.
            // So the backend just needs to check if a password was *required* for this user.
            // If user->password is null or a known "unset" value, they are social-only.
            // For Breeze, users always have a password (even if random for social).
            // So, the most reliable way is to check if they have a `provider_id`.
            $user->provider_id !== null
        );


        if (!$isSocialOnlyUser) {
            // Only validate password if it's not a social-only user
            // or if they have a password and are trying to delete normally.
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        } else {
            // For social-only users, we might still want a confirmation.
            // The frontend modal serves this purpose. Here, we could add an extra check
            // if desired, e.g., making them type "DELETE MY ACCOUNT".
            // For now, if the frontend allowed them to get here without a password prompt
            // (because they are social-only), we proceed.
        }


        Auth::logout();

        // If the user is a subscriber, you might want to cancel their Stripe subscription here.
        if ($user->subscribed('default')) { // Assuming 'default' is your subscription name
            try {
                $user->subscription('default')->cancelNow(); // Or ->cancel() for end of billing period
                 // Log::info("User ID {$user->id} subscription canceled due to account deletion.");
            } catch (\Exception $e) {
                // Log::error("Failed to cancel Stripe subscription for User ID {$user->id} on account deletion: {$e->getMessage()}");
                // Decide if you want to halt deletion or proceed. For now, we proceed.
            }
        }


        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
