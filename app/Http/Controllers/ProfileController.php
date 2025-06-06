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
use Illuminate\Support\Facades\Config; // Keep for stripe prices if still used by User model

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $subscriptionsDetailsList = [];
        $socialLoginDetails = [
            'providerName' => $user->provider_name,
            'providerAvatar' => $user->provider_avatar,
        ];

        if ($user) {
            $effectiveSubscriptionDetails = $user->getEffectiveTierDetails();

            // The view expects an array of subscriptions. We provide one: the effective one.
            $subscriptionsDetailsList[] = [
                'name' => $effectiveSubscriptionDetails['source'] === 'stripe' ? 'default' : $effectiveSubscriptionDetails['source'], // e.g. 'stripe', 'manual'
                'planName' => $effectiveSubscriptionDetails['planName'],
                'planKey' => $effectiveSubscriptionDetails['tier'], // 'free', 'basic', 'pro'
                'status' => $effectiveSubscriptionDetails['status'],
                'isActive' => $effectiveSubscriptionDetails['isActive'],
                'isOnTrial' => $effectiveSubscriptionDetails['isOnTrial'],
                'isCancelled' => $effectiveSubscriptionDetails['isCancelled'],
                'isOnGracePeriod' => $effectiveSubscriptionDetails['isOnGracePeriod'],
                'endsAt' => $effectiveSubscriptionDetails['endsAt'],
                'trialEndsAt' => $effectiveSubscriptionDetails['trialEndsAt'],
                'currentPeriodEnd' => $effectiveSubscriptionDetails['currentPeriodEnd'],
                'source' => $effectiveSubscriptionDetails['source'], // Pass source for potential display differences in Vue
            ];
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'subscriptionsList' => $subscriptionsDetailsList,
            'socialLoginDetails' => $socialLoginDetails,
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

        return Redirect::route('profile.edit')->with('status', 'Profile information updated.');
    }

    /**
     * Redeem a subscription code to update the user's manual tier.
     */
    public function redeemSubscriptionCode(Request $request): RedirectResponse
    {
        $request->validate([
            'redeem_code' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $submittedCode = $request->input('redeem_code');

        $definedCodes = Config::get('redeem_codes', []);

        if (array_key_exists($submittedCode, $definedCodes)) {
            $tierToApply = $definedCodes[$submittedCode];

            if (in_array($tierToApply, ['free', 'basic', 'pro'])) {
                $user->manual_subscription_tier = $tierToApply;
                $user->save();

                // Provide a descriptive success message
                $planName = ucfirst($tierToApply);
                if ($tierToApply === 'free') $planName = 'Free';
                else if ($tierToApply === 'basic') $planName = 'Resident Awareness (Basic)';
                else if ($tierToApply === 'pro') $planName = 'Pro Insights (Pro)';


                return Redirect::route('profile.edit')->with('status', "Code redeemed successfully! Your plan has been updated to: {$planName}.");
            } else {
                // This case should ideally not happen if config is correct
                return Redirect::route('profile.edit')->with('status', 'Error: The code is valid but maps to an unknown tier.');
            }
        } else {
            return Redirect::route('profile.edit')->withErrors(['redeem_code' => 'Invalid or expired redemption code.'])->with('status_error', 'Invalid or expired redemption code.');
        }
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

        // If the user is a subscriber (via Stripe), you might want to cancel their Stripe subscription here.
        // Manual subscriptions don't need Stripe cancellation.
        // The getEffectiveTierDetails()['source'] could be checked if specific logic for Stripe vs manual is needed.
        // For now, we only cancel Stripe subscriptions.
        if ($user->subscribed('default')) { // Checks actual Stripe subscription
            try {
                $user->subscription('default')->cancelNow(); // Or ->cancel() for end of billing period
                 // Log::info("User ID {$user->id} Stripe subscription canceled due to account deletion.");
            } catch (\Exception $e) {
                // Log::error("Failed to cancel Stripe subscription for User ID {$user->id} on account deletion: {$e->getMessage()}");
                // Decide if you want to halt deletion or proceed. For now, we proceed.
            }
        }

        // Clear manual subscription tier upon account deletion
        $user->manual_subscription_tier = null;
        // $user->save(); // User is about to be deleted, so this save might be redundant unless there are observers.
                         // It's safer to include if there's any logic that might trigger on this field change before deletion.
                         // However, since the user record is deleted, this field is gone anyway.

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
