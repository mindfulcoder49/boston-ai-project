<?php

namespace Tests\Feature\Console;

use App\Models\AnalysisReportSnapshot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IngestAnalysisArtifactsCommandTest extends TestCase
{
    private string $artifactRoot;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('cache.default', 'array');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('analysis_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('job_id');
            $table->string('artifact_name');
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('s3_last_modified')->nullable();
            $table->timestamp('pulled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trends', function (Blueprint $table) {
            $table->id();
            $table->string('model_class');
            $table->string('column_name');
            $table->string('job_id');
            $table->unsignedInteger('h3_resolution');
            $table->decimal('p_value_anomaly', 8, 6);
            $table->decimal('p_value_trend', 8, 6);
            $table->json('analysis_weeks_trend')->nullable();
            $table->unsignedInteger('analysis_weeks_anomaly')->default(4);
            $table->timestamps();
        });

        Schema::create('yearly_count_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('model_class');
            $table->string('group_by_col');
            $table->unsignedInteger('baseline_year');
            $table->string('job_id');
            $table->timestamps();
        });

        $this->artifactRoot = storage_path('framework/testing/analysis-artifacts-' . uniqid());
        File::ensureDirectoryExists($this->artifactRoot);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Schema::dropIfExists('yearly_count_comparisons');
        Schema::dropIfExists('trends');
        Schema::dropIfExists('analysis_report_snapshots');
        File::deleteDirectory($this->artifactRoot);

        parent::tearDown();
    }

    public function test_ingests_stage_two_stage_four_and_score_artifacts_from_a_results_tree(): void
    {
        $this->writeJson('job-stage4', 'stage4_h3_anomaly.json', [
            'parameters' => [
                'model_class' => 'App\\Models\\CrimeData',
                'column_name' => 'offense_code_group',
                'h3_resolution' => 8,
                'p_value_anomaly' => 0.05,
                'p_value_trend' => 0.05,
                'analysis_weeks_trend' => [4, 26, 52],
                'analysis_weeks_anomaly' => 4,
            ],
            'results' => [],
        ]);
        $this->writeJson('job-stage2', 'stage2_yearly_count_comparison.json', [
            'status' => 'success',
            'parameters' => [
                'model_class' => 'App\\Models\\CrimeData',
                'group_by_col' => 'offense_code_group',
                'baseline_year' => 2025,
            ],
        ]);
        $this->writeJson('job-score', 'stage6_historical_score_job-score.json', ['status' => 'success']);
        $this->writeJson('job-score', 'scoring_results_job-score.json', ['status' => 'success']);
        $this->writeJson('job-ignore', 'raw_export.json', ['ignored' => true]);

        $this->artisan('app:ingest-analysis-artifacts', [
            'path' => $this->artifactRoot,
            '--skip-hotspots' => true,
        ])->assertExitCode(0);

        $this->assertSame(4, AnalysisReportSnapshot::count());
        $this->assertDatabaseHas('analysis_report_snapshots', [
            'job_id' => 'job-stage4',
            'artifact_name' => 'stage4_h3_anomaly.json',
        ]);
        $this->assertDatabaseHas('analysis_report_snapshots', [
            'job_id' => 'job-score',
            'artifact_name' => 'stage6_historical_score_job-score.json',
        ]);
        $this->assertDatabaseMissing('analysis_report_snapshots', [
            'job_id' => 'job-ignore',
            'artifact_name' => 'raw_export.json',
        ]);
        $this->assertDatabaseHas('trends', [
            'model_class' => 'App\\Models\\CrimeData',
            'column_name' => 'offense_code_group',
            'job_id' => 'job-stage4',
            'h3_resolution' => 8,
        ]);
        $this->assertDatabaseHas('yearly_count_comparisons', [
            'model_class' => 'App\\Models\\CrimeData',
            'group_by_col' => 'offense_code_group',
            'baseline_year' => 2025,
            'job_id' => 'job-stage2',
        ]);
    }

    public function test_dry_run_validates_artifacts_without_writing_snapshots(): void
    {
        $this->writeJson('job-stage4', 'stage4_h3_anomaly.json', [
            'parameters' => [],
            'results' => [],
        ]);

        $this->artisan('app:ingest-analysis-artifacts', [
            'path' => $this->artifactRoot,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame(0, AnalysisReportSnapshot::count());
    }

    public function test_existing_snapshots_are_skipped_unless_fresh_is_requested(): void
    {
        $this->writeJson('job-stage2', 'stage2_yearly_count_comparison.json', ['version' => 1]);

        $this->artisan('app:ingest-analysis-artifacts', [
            'path' => $this->artifactRoot,
            '--skip-hotspots' => true,
        ])->assertExitCode(0);

        $this->writeJson('job-stage2', 'stage2_yearly_count_comparison.json', ['version' => 2]);

        $this->artisan('app:ingest-analysis-artifacts', [
            'path' => $this->artifactRoot,
            '--skip-hotspots' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, AnalysisReportSnapshot::first()->payload['version']);

        $this->artisan('app:ingest-analysis-artifacts', [
            'path' => $this->artifactRoot,
            '--skip-hotspots' => true,
            '--fresh' => true,
        ])->assertExitCode(0);

        $this->assertSame(2, AnalysisReportSnapshot::first()->payload['version']);
    }

    public function test_job_id_filter_limits_ingestion_to_one_job_directory(): void
    {
        $this->writeJson('job-a', 'stage2_yearly_count_comparison.json', ['job' => 'a']);
        $this->writeJson('job-b', 'stage2_yearly_count_comparison.json', ['job' => 'b']);

        $this->artisan('app:ingest-analysis-artifacts', [
            'path' => $this->artifactRoot,
            '--job-id' => 'job-b',
            '--skip-hotspots' => true,
        ])->assertExitCode(0);

        $this->assertSame(['job-b'], AnalysisReportSnapshot::pluck('job_id')->all());
    }

    public function test_bad_json_returns_a_failure_exit_code(): void
    {
        $jobDirectory = "{$this->artifactRoot}/job-bad";
        File::ensureDirectoryExists($jobDirectory);
        File::put("{$jobDirectory}/stage4_h3_anomaly.json", '{"bad":');

        $this->artisan('app:ingest-analysis-artifacts', [
            'path' => $this->artifactRoot,
            '--skip-hotspots' => true,
        ])->assertExitCode(1);

        $this->assertSame(0, AnalysisReportSnapshot::count());
    }

    private function writeJson(string $jobId, string $artifactName, array $payload): void
    {
        $jobDirectory = "{$this->artifactRoot}/{$jobId}";
        File::ensureDirectoryExists($jobDirectory);
        File::put("{$jobDirectory}/{$artifactName}", json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
