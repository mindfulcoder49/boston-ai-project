<?php

namespace Tests\Unit;

use App\Http\Controllers\YearlyCountComparisonController;
use Tests\TestCase;

class YearlyCountComparisonControllerTest extends TestCase
{
    public function test_it_keeps_latest_yearly_analysis_per_group_and_baseline(): void
    {
        $controller = new YearlyCountComparisonController();
        $method = new \ReflectionMethod($controller, 'dedupeLatestAnalyses');
        $method->setAccessible(true);

        $deduped = $method->invoke($controller, [
            [
                'job_id' => 'job-old',
                'group_by_col' => 'reason',
                'group_by_label' => 'Reason',
                'baseline_year' => 2025,
                '_sort_key' => 100,
            ],
            [
                'job_id' => 'job-new',
                'group_by_col' => 'reason',
                'group_by_label' => 'Reason',
                'baseline_year' => 2025,
                '_sort_key' => 200,
            ],
            [
                'job_id' => 'job-type',
                'group_by_col' => 'type',
                'group_by_label' => 'Type',
                'baseline_year' => 2025,
                '_sort_key' => 150,
            ],
        ]);

        $this->assertSame(['job-new', 'job-type'], array_column($deduped, 'job_id'));
    }
}
