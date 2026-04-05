<?php

namespace App\Services;

use App\Models\Location;
use App\Models\User;
use App\Support\TrialLifecycleEmailVariant;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportAccessService
{
    public function resolveRecurringReportAccess(User $user, ?CarbonInterface $now = null): array
    {
        $effectiveTier = $user->getEffectiveTierDetails()['tier'];
        $reportNow = $this->reportNow($now);

        if ($effectiveTier === 'pro') {
            return [
                'eligible' => true,
                'mode' => 'pro',
                'location_ids' => $user->locations->pluck('id')->all(),
                'report_variant' => TrialLifecycleEmailVariant::STANDARD,
            ];
        }

        if ($effectiveTier === 'basic') {
            $locationId = $user->locations->sortBy('id')->first()?->id;

            return [
                'eligible' => $locationId !== null,
                'mode' => 'basic',
                'location_ids' => $locationId ? [$locationId] : [],
                'report_variant' => TrialLifecycleEmailVariant::STANDARD,
            ];
        }

        if ($user->hasActiveCrimeAddressTrial() && $user->crime_address_trial_location_id) {
            return [
                'eligible' => true,
                'mode' => 'trial',
                'location_ids' => [$user->crime_address_trial_location_id],
                'report_variant' => $this->isTrialLastReportDay($user, $reportNow)
                    ? TrialLifecycleEmailVariant::TRIAL_LAST_DAY
                    : TrialLifecycleEmailVariant::STANDARD,
            ];
        }

        if ($this->isTrialGraceReportDue($user, $reportNow)) {
            return [
                'eligible' => true,
                'mode' => 'trial_grace',
                'location_ids' => [$user->crime_address_trial_location_id],
                'report_variant' => TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT,
            ];
        }

        return [
            'eligible' => false,
            'mode' => 'none',
            'location_ids' => [],
            'report_variant' => TrialLifecycleEmailVariant::STANDARD,
        ];
    }

    public function resolveTrialEndedNotice(User $user, ?CarbonInterface $now = null): array
    {
        if ($this->isTrialEndedNoticeDue($user, $this->reportNow($now))) {
            return [
                'eligible' => true,
                'mode' => TrialLifecycleEmailVariant::TRIAL_ENDED_NOTICE,
            ];
        }

        return [
            'eligible' => false,
            'mode' => 'none',
        ];
    }

    public function filterEligibleLocations(Collection $locations, array $access, bool $force = false): Collection
    {
        $eligibleLocations = $locations
            ->filter(function (Location $location) use ($force) {
                if ($force) {
                    return true;
                }

                return $location->report === 'daily' || (now()->isSunday() && $location->report === 'weekly');
            });

        if (in_array($access['mode'], ['trial', 'trial_grace'], true)) {
            return $eligibleLocations->whereIn('id', $access['location_ids']);
        }

        if ($access['mode'] === 'basic') {
            return $eligibleLocations->whereIn('id', $access['location_ids'])->sortBy('id')->take(1);
        }

        if ($access['mode'] === 'pro') {
            return $eligibleLocations->sortBy('id')->values();
        }

        return collect();
    }

    private function isTrialLastReportDay(User $user, Carbon $reportNow): bool
    {
        if (!$user->hasActiveCrimeAddressTrial()) {
            return false;
        }

        $trialEndsAt = $this->trialEndsAtInReportTimezone($user, $reportNow);

        return $trialEndsAt !== null && $trialEndsAt->isSameDay($reportNow);
    }

    private function isTrialGraceReportDue(User $user, Carbon $reportNow): bool
    {
        if (
            $this->hasPaidReportTier($user)
            || !$user->hasUsedCrimeAddressTrial()
            || $user->crime_address_trial_location_id === null
            || $user->hasActiveCrimeAddressTrial()
            || $user->crime_address_trial_grace_report_sent_at !== null
        ) {
            return false;
        }

        $trialEndsAt = $this->trialEndsAtInReportTimezone($user, $reportNow);

        return $trialEndsAt !== null
            && $trialEndsAt->copy()->addDay()->isSameDay($reportNow);
    }

    private function isTrialEndedNoticeDue(User $user, Carbon $reportNow): bool
    {
        if (
            $this->hasPaidReportTier($user)
            || !$user->hasUsedCrimeAddressTrial()
            || $user->hasActiveCrimeAddressTrial()
            || $user->crime_address_trial_ended_notice_sent_at !== null
        ) {
            return false;
        }

        $trialEndsAt = $this->trialEndsAtInReportTimezone($user, $reportNow);

        return $trialEndsAt !== null
            && $trialEndsAt->copy()->addDays(2)->isSameDay($reportNow);
    }

    private function hasPaidReportTier(User $user): bool
    {
        return in_array($user->getEffectiveTierDetails()['tier'], ['basic', 'pro'], true);
    }

    private function trialEndsAtInReportTimezone(User $user, Carbon $reportNow): ?Carbon
    {
        if (!$user->crime_address_trial_ends_at) {
            return null;
        }

        return Carbon::instance($user->crime_address_trial_ends_at)->setTimezone($reportNow->getTimezone());
    }

    private function reportNow(?CarbonInterface $now = null): Carbon
    {
        $timezone = (string) config('services.reports.timezone', config('app.timezone', 'UTC'));
        $reference = $now ? Carbon::instance($now) : Carbon::now();

        return $reference->setTimezone($timezone);
    }
}
