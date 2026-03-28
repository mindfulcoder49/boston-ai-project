<?php

namespace Tests\Unit\Services;

use App\Models\AnalysisReportSnapshot;
use App\Models\EverettCrimeData;
use App\Models\Trend;
use App\Services\AnalysisArtifactLocator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnalysisArtifactLocatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('cache.default', 'array');
        config()->set('analysis_schedule.stage6.jobs', [
            [
                'model' => EverettCrimeData::class,
                'column' => 'incident_type_group',
            ],
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys=ON');

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

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Schema::dropIfExists('trends');
        Schema::dropIfExists('analysis_report_snapshots');

        parent::tearDown();
    }

    public function test_prefers_snapshot_backed_trend_context_for_the_configured_scoring_column(): void
    {
        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-everett-crime-data-unified-res9-1772947508',
            'artifact_name' => 'stage4_h3_anomaly.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'unified',
                    'h3_resolution' => 9,
                    'p_value_anomaly' => 0.05,
                    'p_value_trend' => 0.05,
                    'analysis_weeks_trend' => [4, 26, 52],
                    'analysis_weeks_anomaly' => 4,
                ],
                'results' => [],
            ],
            's3_last_modified' => 1772948004,
        ]);

        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-everett-crime-data-incident_type_group-res8-1772947509',
            'artifact_name' => 'stage4_h3_anomaly.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'incident_type_group',
                    'h3_resolution' => 8,
                    'p_value_anomaly' => 0.05,
                    'p_value_trend' => 0.05,
                    'analysis_weeks_trend' => [4, 26, 52],
                    'analysis_weeks_anomaly' => 4,
                ],
                'results' => [
                    [
                        'secondary_group' => 'Medical / Mental Health',
                        'h3_index_8' => '882a107289fffff',
                        'historical_weekly_avg' => 1.3,
                        'anomaly_analysis' => [
                            [
                                'week' => '2026-03-22',
                                'count' => 4,
                                'anomaly_p_value' => 0.01,
                                'z_score' => 2.8,
                            ],
                        ],
                        'trend_analysis' => [
                            '4w' => [
                                'p_value' => 0.01,
                                'slope' => 1.2,
                            ],
                        ],
                    ],
                ],
            ],
            's3_last_modified' => 1772947838,
        ]);

        $trendContext = app(AnalysisArtifactLocator::class)->findPreferredTrendContext(EverettCrimeData::class);

        $this->assertNotNull($trendContext);
        $this->assertSame('laravel-everett-crime-data-incident_type_group-res8-1772947509', $trendContext['job_id']);
        $this->assertSame('incident_type_group', $trendContext['column_name']);
        $this->assertSame('ok', $trendContext['summary']['status']);
        $this->assertGreaterThan(0, $trendContext['summary']['total_findings']);
        $this->assertContains('Medical / Mental Health', $trendContext['summary']['top_categories']);
    }

    public function test_prefers_the_latest_highest_resolution_score_report_for_the_configured_scoring_column(): void
    {
        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-hist-score-everett-crime-data-unified-res10-1772947510',
            'artifact_name' => 'stage6_historical_score_laravel-hist-score-everett-crime-data-unified-res10-1772947510.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'unified',
                    'h3_resolution' => 10,
                    'analysis_period_weeks' => 52,
                ],
                'source_job_id' => 'laravel-everett-crime-data-unified-res10-1772947510',
            ],
            's3_last_modified' => 1772948200,
        ]);

        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-hist-score-everett-crime-data-incident_type_group-res8-1772947511',
            'artifact_name' => 'stage6_historical_score_laravel-hist-score-everett-crime-data-incident_type_group-res8-1772947511.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'incident_type_group',
                    'h3_resolution' => 8,
                    'analysis_period_weeks' => 52,
                ],
                'source_job_id' => 'laravel-everett-crime-data-incident_type_group-res8-1772947511',
            ],
            's3_last_modified' => 1772948300,
        ]);

        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-hist-score-everett-crime-data-incident_type_group-res10-1772947512',
            'artifact_name' => 'stage6_historical_score_laravel-hist-score-everett-crime-data-incident_type_group-res10-1772947512.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'incident_type_group',
                    'h3_resolution' => 10,
                    'analysis_period_weeks' => 52,
                ],
                'source_job_id' => 'laravel-everett-crime-data-incident_type_group-res10-1772947512',
            ],
            's3_last_modified' => 1772948400,
        ]);

        $scoreReport = app(AnalysisArtifactLocator::class)->findPreferredScoreReport(EverettCrimeData::class);

        $this->assertNotNull($scoreReport);
        $this->assertSame('laravel-hist-score-everett-crime-data-incident_type_group-res10-1772947512', $scoreReport['job_id']);
        $this->assertSame(10, $scoreReport['resolution']);
        $this->assertSame(52, $scoreReport['analysis_period_weeks']);
        $this->assertSame(
            'laravel-everett-crime-data-incident_type_group-res10-1772947512',
            $scoreReport['source_job_id'],
        );
    }

    public function test_uses_trend_rows_when_they_are_available(): void
    {
        Trend::query()->create([
            'model_class' => EverettCrimeData::class,
            'column_name' => 'incident_type_group',
            'job_id' => 'laravel-everett-crime-data-incident_type_group-res8-1772947600',
            'h3_resolution' => 8,
            'p_value_anomaly' => 0.05,
            'p_value_trend' => 0.05,
            'analysis_weeks_trend' => [4, 26, 52],
            'analysis_weeks_anomaly' => 4,
        ]);

        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-everett-crime-data-incident_type_group-res8-1772947600',
            'artifact_name' => 'stage4_h3_anomaly.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'incident_type_group',
                    'h3_resolution' => 8,
                    'p_value_anomaly' => 0.05,
                    'p_value_trend' => 0.05,
                ],
                'results' => [
                    [
                        'secondary_group' => 'Alarm Response',
                        'h3_index_8' => '882a107289fffff',
                        'historical_weekly_avg' => 1.0,
                        'anomaly_analysis' => [
                            [
                                'week' => '2026-03-22',
                                'count' => 3,
                                'anomaly_p_value' => 0.02,
                                'z_score' => 2.2,
                            ],
                        ],
                    ],
                ],
            ],
            's3_last_modified' => 1772947601,
        ]);

        $trendContext = app(AnalysisArtifactLocator::class)->findPreferredTrendContext(EverettCrimeData::class);

        $this->assertNotNull($trendContext);
        $this->assertSame('laravel-everett-crime-data-incident_type_group-res8-1772947600', $trendContext['job_id']);
        $this->assertSame('ok', $trendContext['summary']['status']);
    }
}
