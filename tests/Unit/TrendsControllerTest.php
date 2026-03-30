<?php

namespace Tests\Unit;

use App\Http\Controllers\TrendsController;
use Tests\TestCase;

class TrendsControllerTest extends TestCase
{
    public function test_it_prefers_latest_snapshot_analysis_for_same_model_column_and_resolution(): void
    {
        $controller = new TrendsController();
        $method = new \ReflectionMethod($controller, 'dedupeAnalyses');
        $method->setAccessible(true);

        $deduped = $method->invoke($controller, [
            [
                'job_id' => 'job-old',
                'model_name' => 'Boston 311 Cases',
                'column_name' => 'reason',
                'h3_resolution' => 9,
                'trend_id' => null,
                '_sort_key' => 100,
            ],
            [
                'job_id' => 'job-new',
                'model_name' => 'Boston 311 Cases',
                'column_name' => 'reason',
                'h3_resolution' => 9,
                'trend_id' => null,
                '_sort_key' => 200,
            ],
            [
                'job_id' => 'job-other',
                'model_name' => 'Boston 311 Cases',
                'column_name' => 'type',
                'h3_resolution' => 9,
                'trend_id' => null,
                '_sort_key' => 150,
            ],
        ]);

        $this->assertSame(['job-new', 'job-other'], array_column($deduped, 'job_id'));
    }
}
