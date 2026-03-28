<?php

namespace Tests\Unit\Services;

use App\Models\AnalysisReportSnapshot;
use App\Models\EverettCrimeData;
use App\Services\HistoricalScoreFallbackService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistoricalScoreFallbackServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('analysis_schedule.stage6.default_weight', 1.0);
        config()->set('analysis_schedule.stage6.jobs', [
            [
                'model' => EverettCrimeData::class,
                'column' => 'incident_type_group',
                'default_weight' => 1.0,
                'weights' => [
                    'Medical / Mental Health' => 2.0,
                    'Alarm Response' => 0.5,
                ],
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('analysis_report_snapshots');

        parent::tearDown();
    }

    public function test_computes_preview_score_from_stage4_historical_weekly_averages(): void
    {
        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-everett-crime-data-incident_type_group-res9-1772947601',
            'artifact_name' => 'stage4_h3_anomaly.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'incident_type_group',
                    'h3_resolution' => 9,
                ],
                'results' => [
                    [
                        'secondary_group' => 'Medical / Mental Health',
                        'h3_index_9' => '892a1072893ffff',
                        'historical_weekly_avg' => 1.5,
                    ],
                    [
                        'secondary_group' => 'Alarm Response',
                        'h3_index_9' => '892a1072893ffff',
                        'historical_weekly_avg' => 4.0,
                    ],
                    [
                        'secondary_group' => 'Traffic Violation',
                        'h3_index_9' => '892a1072893ffff',
                        'historical_weekly_avg' => 2.0,
                    ],
                    [
                        'secondary_group' => 'Traffic Violation',
                        'h3_index_9' => '892a1072897ffff',
                        'historical_weekly_avg' => 99.0,
                    ],
                ],
            ],
            's3_last_modified' => 1772947601,
        ]);

        $scoreContext = app(HistoricalScoreFallbackService::class)->scoreForLocation(
            EverettCrimeData::class,
            'laravel-everett-crime-data-incident_type_group-res9-1772947601',
            '892a1072893ffff',
            'incident_type_group',
        );

        $this->assertNotNull($scoreContext);
        $this->assertSame('892a1072893ffff', $scoreContext['h3_index']);
        $this->assertEquals(7.0, $scoreContext['score_details']['score']);
        $this->assertCount(3, $scoreContext['score_details']['score_composition']);
        $this->assertSame('Medical / Mental Health', $scoreContext['score_details']['score_composition'][0]['secondary_group']);
        $this->assertEquals(3.0, $scoreContext['score_details']['score_composition'][0]['weighted_score']);
        $this->assertCount(3, $scoreContext['analysis_details']);
        $this->assertSame('Preview score estimate', $scoreContext['score_context']['methodology']['label']);
        $this->assertSame('Lower relative concern', $scoreContext['score_context']['band']['label']);
        $this->assertSame(2, $scoreContext['score_context']['distribution']['count']);
    }
}
