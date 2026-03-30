<?php

namespace Tests\Unit;

use App\Http\Controllers\ScoringReportController;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ScoringReportControllerTest extends TestCase
{
    public function test_it_normalizes_collection_report_groups_without_throwing(): void
    {
        $controller = new ScoringReportController();
        $method = new \ReflectionMethod($controller, 'normalizeReportGroup');
        $method->setAccessible(true);

        $reportGroup = new Collection([
            ['job_id' => 'job-b', 'artifact_name' => 'b.json', 'resolution' => 9],
            ['job_id' => 'job-a', 'artifact_name' => 'a.json', 'resolution' => 8],
        ]);

        $normalized = $method->invoke($controller, $reportGroup);

        $this->assertSame([
            ['job_id' => 'job-b', 'artifact_name' => 'b.json', 'resolution' => 9],
            ['job_id' => 'job-a', 'artifact_name' => 'a.json', 'resolution' => 8],
        ], $normalized);
    }

    public function test_it_keeps_latest_scoring_report_for_same_logical_group(): void
    {
        $controller = new ScoringReportController();
        $method = new \ReflectionMethod($controller, 'dedupeReportList');
        $method->setAccessible(true);

        $deduped = $method->invoke($controller, [
            [
                'job_id' => 'job-old',
                'artifact_name' => 'stage6_old.json',
                'title' => 'Historical Scoring Report',
                'city' => 'Boston',
                'date_range_key' => '2025-01-01 to 2026-03-30',
                'resolution' => 9,
                'source_job_id' => null,
                'parameters' => [
                    'model_class' => 'App\\Models\\ThreeOneOneCase',
                    'column_name' => 'reason',
                ],
                '_sort_key' => 100,
            ],
            [
                'job_id' => 'job-new',
                'artifact_name' => 'stage6_new.json',
                'title' => 'Historical Scoring Report',
                'city' => 'Boston',
                'date_range_key' => '2025-01-01 to 2026-03-30',
                'resolution' => 9,
                'source_job_id' => null,
                'parameters' => [
                    'model_class' => 'App\\Models\\ThreeOneOneCase',
                    'column_name' => 'reason',
                ],
                '_sort_key' => 200,
            ],
        ]);

        $this->assertSame(['job-new'], array_column($deduped, 'job_id'));
    }

    public function test_it_replaces_older_score_windows_for_same_public_report_slot(): void
    {
        $controller = new ScoringReportController();
        $method = new \ReflectionMethod($controller, 'dedupeReportList');
        $method->setAccessible(true);

        $deduped = $method->invoke($controller, [
            [
                'job_id' => 'job-old',
                'artifact_name' => 'stage6_old.json',
                'title' => 'Historical Scoring Report',
                'city' => 'Boston 311 Cases',
                'date_range_key' => '2025-01-01 to 2026-03-03',
                'resolution' => 9,
                'source_job_id' => 'old-stage4-job',
                'parameters' => [
                    'model_class' => 'App\\Models\\ThreeOneOneCase',
                    'column_name' => 'reason',
                ],
                '_sort_key' => 100,
            ],
            [
                'job_id' => 'job-new',
                'artifact_name' => 'stage6_new.json',
                'title' => 'Historical Scoring Report',
                'city' => 'Boston 311 Cases',
                'date_range_key' => '2025-01-01 to 2026-03-30',
                'resolution' => 9,
                'source_job_id' => 'new-stage4-job',
                'parameters' => [
                    'model_class' => 'App\\Models\\ThreeOneOneCase',
                    'column_name' => 'reason',
                ],
                '_sort_key' => 200,
            ],
        ]);

        $this->assertSame(['job-new'], array_column($deduped, 'job_id'));
    }
}
