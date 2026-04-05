<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Models\User;
use App\Services\ReportAccessService;
use App\Support\TrialLifecycleEmailVariant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReportAccessServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_trial_user_is_eligible_for_one_trial_location(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = new User([
            'crime_address_trial_started_at' => now()->subDay(),
            'crime_address_trial_ends_at' => now()->addDays(6),
            'crime_address_trial_location_id' => 99,
        ]);

        $service = new ReportAccessService();
        $access = $service->resolveRecurringReportAccess($user);

        $this->assertTrue($access['eligible']);
        $this->assertSame('trial', $access['mode']);
        $this->assertSame([99], $access['location_ids']);
        $this->assertSame(TrialLifecycleEmailVariant::STANDARD, $access['report_variant']);
    }

    public function test_last_day_of_trial_uses_last_day_report_variant(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = new User([
            'crime_address_trial_started_at' => now()->subDays(6),
            'crime_address_trial_ends_at' => now()->endOfDay(),
            'crime_address_trial_location_id' => 99,
        ]);

        $service = new ReportAccessService();
        $access = $service->resolveRecurringReportAccess($user);

        $this->assertTrue($access['eligible']);
        $this->assertSame('trial', $access['mode']);
        $this->assertSame(TrialLifecycleEmailVariant::TRIAL_LAST_DAY, $access['report_variant']);
    }

    public function test_day_after_trial_end_gets_one_grace_report_variant(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = new User([
            'crime_address_trial_started_at' => now()->subDays(8),
            'crime_address_trial_ends_at' => now()->subDay()->endOfDay(),
            'crime_address_trial_location_id' => 99,
        ]);

        $service = new ReportAccessService();
        $access = $service->resolveRecurringReportAccess($user);

        $this->assertTrue($access['eligible']);
        $this->assertSame('trial_grace', $access['mode']);
        $this->assertSame([99], $access['location_ids']);
        $this->assertSame(TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT, $access['report_variant']);
    }

    public function test_two_days_after_trial_end_gets_stop_notice(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = new User([
            'crime_address_trial_started_at' => now()->subDays(9),
            'crime_address_trial_ends_at' => now()->subDays(2)->endOfDay(),
            'crime_address_trial_location_id' => 99,
        ]);

        $service = new ReportAccessService();
        $notice = $service->resolveTrialEndedNotice($user);

        $this->assertTrue($notice['eligible']);
        $this->assertSame(TrialLifecycleEmailVariant::TRIAL_ENDED_NOTICE, $notice['mode']);
    }

    public function test_older_expired_trial_user_is_not_eligible_without_paid_plan(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = new User([
            'crime_address_trial_started_at' => now()->subDays(12),
            'crime_address_trial_ends_at' => now()->subDays(4)->endOfDay(),
            'crime_address_trial_location_id' => 99,
        ]);

        $service = new ReportAccessService();
        $access = $service->resolveRecurringReportAccess($user);
        $notice = $service->resolveTrialEndedNotice($user);

        $this->assertFalse($access['eligible']);
        $this->assertSame('none', $access['mode']);
        $this->assertFalse($notice['eligible']);
    }

    public function test_basic_user_is_limited_to_first_location(): void
    {
        $user = new User([
            'manual_subscription_tier' => 'basic',
        ]);
        $laterLocation = new Location();
        $laterLocation->id = 7;

        $earlierLocation = new Location();
        $earlierLocation->id = 3;

        $user->setRelation('locations', new Collection([
            $laterLocation,
            $earlierLocation,
        ]));

        $service = new ReportAccessService();
        $access = $service->resolveRecurringReportAccess($user);

        $this->assertTrue($access['eligible']);
        $this->assertSame('basic', $access['mode']);
        $this->assertSame([3], $access['location_ids']);
    }
}
