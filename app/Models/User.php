<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider_id',       // Add
        'provider_name',     // Add
        'provider_avatar',   // Add
        'manual_subscription_tier', // Add this
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function chirps(): HasMany
    {
        return $this->hasMany(Chirp::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }   

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get the effective subscription tier details for the user.
     * Prioritizes manual tier, then Stripe, then defaults to 'free'.
     *
     * @return array
     */
    public function getEffectiveTierDetails(): array
    {
        $baseFreePlanDetails = [
            'tier' => 'free',
            'planNameKey' => 'free',
            'planName' => 'Registered User Features (Free)',
            'source' => 'default', // 'manual', 'stripe', 'default'
            'isActive' => true,
            'status' => 'active',
            'isOnTrial' => false,
            'isCancelled' => false,
            'isOnGracePeriod' => false,
            'endsAt' => null,
            'trialEndsAt' => null,
            'currentPeriodEnd' => null,
        ];

        // 1. Check manual tier
        if (!empty($this->manual_subscription_tier) && in_array($this->manual_subscription_tier, ['free', 'basic', 'pro'])) {
            $tier = $this->manual_subscription_tier;
            $planName = 'Manually Assigned Plan'; // Generic, can be more specific
            $planNameKey = $tier;

            if ($tier === 'basic') {
                $planName = 'Manually Assigned: Resident Awareness';
            } elseif ($tier === 'pro') {
                $planName = 'Manually Assigned: Pro Insights';
            } elseif ($tier === 'free') {
                $planName = 'Manually Assigned: Free Tier';
            }

            return array_merge($baseFreePlanDetails, [
                'tier' => $tier,
                'planNameKey' => 'manual_' . $planNameKey,
                'planName' => $planName,
                'source' => 'manual',
                'status' => 'active_manual', // Custom status for manually assigned active plan
            ]);
        }

        // 2. Check Stripe subscription
        if ($this->subscribed('default')) {
            $subscription = $this->subscription('default');
            /** @var \Laravel\Cashier\Subscription $subscription */
            if ($subscription) {
                $stripePlanTier = 'unknown_stripe';
                $stripePlanName = 'Unknown Stripe Plan';
                $stripePlanKey = 'unknown';

                if ($subscription->stripe_price === config('stripe.prices.basic_plan')) {
                    $stripePlanTier = 'basic';
                    $stripePlanName = 'Resident Awareness';
                    $stripePlanKey = 'basic';
                } elseif ($subscription->stripe_price === config('stripe.prices.pro_plan')) {
                    $stripePlanTier = 'pro';
                    $stripePlanName = 'Pro Insights';
                    $stripePlanKey = 'pro';
                }

                return [
                    'tier' => $stripePlanTier,
                    'planNameKey' => $stripePlanKey,
                    'planName' => $stripePlanName,
                    'source' => 'stripe',
                    'isActive' => $subscription->active(),
                    'status' => $subscription->stripe_status,
                    'isOnTrial' => $subscription->onTrial(),
                    'isCancelled' => $subscription->cancelled(),
                    'isOnGracePeriod' => $subscription->onGracePeriod(),
                    'endsAt' => $subscription->ends_at ? date('F j, Y', strtotime($subscription->ends_at)) : null,
                    'trialEndsAt' => $subscription->trial_ends_at ? date('F j, Y', strtotime($subscription->trial_ends_at)) : null,
                    'currentPeriodEnd' => $subscription->active() && !$subscription->onTrial() && !$subscription->cancelled() ? date('F j, Y', $subscription->current_period_end) : null,
                ];
            }
        }

        // 3. Default to free plan if no manual or Stripe subscription
        return $baseFreePlanDetails;
    }
}
