<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Models\User;
use App\Services\ReportAccessService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReportAccessServiceTest extends TestCase
{
    public function test_active_trial_user_is_eligible_for_one_trial_location(): void
    {
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
    }

    public function test_expired_trial_user_is_not_eligible_without_paid_plan(): void
    {
        $user = new User([
            'crime_address_trial_started_at' => now()->subDays(8),
            'crime_address_trial_ends_at' => now()->subDay(),
            'crime_address_trial_location_id' => 99,
        ]);

        $service = new ReportAccessService();
        $access = $service->resolveRecurringReportAccess($user);

        $this->assertFalse($access['eligible']);
        $this->assertSame('none', $access['mode']);
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
