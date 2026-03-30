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

    public function test_it_normalizes_scoring_city_name_from_job_metadata_when_parameters_are_generic(): void
    {
        $controller = new ScoringReportController();
        $method = new \ReflectionMethod($controller, 'buildReportListFromSnapshots');
        $method->setAccessible(true);

        $oldSnapshot = new class {
            public string $job_id = 'laravel-hist-score-three-one-one-case-reason-res8-1772947514';
            public string $artifact_name = 'stage6_historical_score_laravel-hist-score-three-one-one-case-reason-res8-1772947514.json';
            public int $s3_last_modified = 100;
            public $pulled_at;
            public array $payload = [
                'parameters' => [
                    'city' => 'Boston 311 Cases',
                    'h3_resolution' => 8,
                    'date_range' => [
                        'start_date' => '2025-01-01',
                        'end_date' => '2026-03-03',
                    ],
                ],
            ];

            public function __construct()
            {
                $this->pulled_at = now();
            }
        };

        $newSnapshot = new class {
            public string $job_id = 'laravel-hist-score-three-one-one-case-reason-res8-1774876240';
            public string $artifact_name = 'stage6_historical_score_laravel-hist-score-three-one-one-case-reason-res8-1774876240.json';
            public int $s3_last_modified = 200;
            public $pulled_at;
            public array $payload = [
                'parameters' => [
                    'city' => 'Boston',
                    'h3_resolution' => 8,
                    'date_range' => [
                        'start_date' => '2025-01-01',
                        'end_date' => '2026-03-30',
                    ],
                ],
            ];

            public function __construct()
            {
                $this->pulled_at = now();
            }
        };

        $reports = $method->invoke($controller, collect([$oldSnapshot, $newSnapshot]));

        $this->assertCount(1, $reports);
        $this->assertSame('laravel-hist-score-three-one-one-case-reason-res8-1774876240', $reports[0]['job_id']);
        $this->assertSame('Boston 311 Cases', $reports[0]['city']);
    }
}
