<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException; // For redirecting with errors

class SocialLoginController extends Controller
{
    public function redirectToProvider(string $provider)
    {
        if (!in_array($provider, ['google', 'github', 'facebook'])) { // Add your supported providers
            abort(404, "Provider not supported.");
        }
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(string $provider)
    {
        if (!in_array($provider, ['google', 'github', 'facebook'])) {
            abort(404, "Provider not supported.");
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Unable to login using ' . ucfirst($provider) . '. Please try again.');
        }

        // 1. Check if a user already exists with this provider and provider_id
        $user = User::where('provider_name', $provider)
                    ->where('provider_id', $socialUser->getId())
                    ->first();

        if ($user) {
            // User found with this social account, log them in
            Auth::login($user);
            return redirect()->intended(route('map.index')); // Or your dashboard route
        }

        // 2. If no user with this social account, check if an account with this email exists
        if ($socialUser->getEmail()) {
            $existingUserWithEmail = User::where('email', $socialUser->getEmail())->first();

            if ($existingUserWithEmail) {
                // Email exists.
                // Option A: If you want to automatically link this new social login to the existing email account:
                // (Ensure the user is okay with this, or provide a confirmation step)
                $existingUserWithEmail->update([
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_avatar' => $existingUserWithEmail->provider_avatar ?: $socialUser->getAvatar(), // Keep existing avatar if already set
                ]);
                Auth::login($existingUserWithEmail);
                return redirect()->intended(route('map.index'));

                // Option B: If you want to prevent linking and show an error:
                // return redirect()->route('login')->with('error', 'An account with the email ' . $socialUser->getEmail() . ' already exists. Please log in with your password or use your existing social login method.');

                // Option C: If you want to let them know they can link, but require password verification first
                // This is more complex and involves storing the social info in session and prompting for password.
            }
        }

        // 3. If no user with this social ID and no user with this email (or email not provided by socialUser), create a new user.
        // (This also handles the case where $socialUser->getEmail() is null)
        try {
            $newUser = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User_' . Str::random(5),
                'email' => $socialUser->getEmail(), // This might be null
                'email_verified_at' => $socialUser->getEmail() ? now() : null, // Mark as verified if email is provided by OAuth
                'password' => Hash::make(Str::random(24)), // Set a random secure password
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_avatar' => $socialUser->getAvatar(),
            ]);

            Auth::login($newUser);
            return redirect()->intended(route('map.index'));

        } catch (\Illuminate\Database\QueryException $e) {
            // This catch is a fallback if the email was null from social but then an attempt to create
            // a user with a null email fails due to database constraints (if email is not nullable).
            // Or if, despite our checks, a race condition occurred.
            if ($e->errorInfo[1] == 1062) { // Error code for duplicate entry
                 return redirect()->route('login')->with('error', 'An account with this email already exists or there was an issue creating your account. Please try logging in.');
            }
            // Log other database errors
            \Log::error("Social login user creation database error: " . $e->getMessage());
            return redirect()->route('login')->with('error', 'Could not create your account at this time. Please try again later.');
        }
    }
}