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
}
