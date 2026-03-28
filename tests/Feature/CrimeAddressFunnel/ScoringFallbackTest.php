<?php

namespace Tests\Feature\CrimeAddressFunnel;

use App\Models\AnalysisReportSnapshot;
use App\Models\EverettCrimeData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ScoringFallbackTest extends TestCase
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

    public function test_score_for_location_supports_stage4_fallback_requests(): void
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
                        'secondary_group' => 'Traffic Violation',
                        'h3_index_9' => '892a1072893ffff',
                        'historical_weekly_avg' => 2.0,
                    ],
                    [
                        'secondary_group' => 'Alarm Response',
                        'h3_index_9' => '892a1072897ffff',
                        'historical_weekly_avg' => 2.0,
                    ],
                ],
            ],
            's3_last_modified' => 1772947601,
        ]);

        $response = $this->postJson(route('scoring-reports.score-for-location'), [
            'h3_index' => '892a1072893ffff',
            'model_class' => EverettCrimeData::class,
            'source_job_id' => 'laravel-everett-crime-data-incident_type_group-res9-1772947601',
            'column_name' => 'incident_type_group',
            'comparison_h3_indices' => ['892a1072897ffff'],
        ]);

        $response->assertOk()->assertJson([
            'h3_index' => '892a1072893ffff',
            'score_details' => [
                'score' => 5,
                'source' => 'stage4_fallback',
            ],
            'score_context' => [
                'band' => [
                    'label' => 'Higher relative concern',
                ],
                'nearby_peers' => [
                    'count' => 1,
                    'available' => true,
                ],
                'methodology' => [
                    'source' => 'stage4_fallback',
                ],
            ],
        ]);
    }
}
