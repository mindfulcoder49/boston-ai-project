<?php

namespace Tests\Unit\Services;

use App\Models\AnalysisReportSnapshot;
use App\Models\BuildingPermit;
use App\Models\YearlyCountComparison;
use App\Services\NewsArticleReportDataResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NewsArticleReportDataResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

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

        Schema::create('yearly_count_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('model_class');
            $table->string('group_by_col');
            $table->integer('baseline_year');
            $table->string('job_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yearly_count_comparisons');
        Schema::dropIfExists('analysis_report_snapshots');

        parent::tearDown();
    }

    public function test_uses_snapshot_data_for_yearly_articles_before_http(): void
    {
        Http::fake();

        $report = YearlyCountComparison::query()->create([
            'model_class' => BuildingPermit::class,
            'group_by_col' => 'worktype',
            'baseline_year' => 2019,
            'job_id' => 'laravel-building-permit-worktype-yearly-2019-123',
        ]);

        AnalysisReportSnapshot::query()->create([
            'job_id' => $report->job_id,
            'artifact_name' => 'stage2_yearly_count_comparison.json',
            'payload' => [
                'parameters' => [
                    'analysis_current_year' => 2025,
                    'baseline_year' => 2019,
                ],
                'results' => [
                    [
                        'group' => 'Renovation',
                        'to_date' => [
                            2019 => ['count' => 10],
                            2025 => ['count' => 30, 'change_pct' => 200.0],
                        ],
                    ],
                    [
                        'group' => 'Demolition',
                        'to_date' => [
                            2019 => ['count' => 20],
                            2025 => ['count' => 10, 'change_pct' => -50.0],
                        ],
                    ],
                ],
            ],
        ]);

        $resolved = app(NewsArticleReportDataResolver::class)->resolve($report);

        $this->assertSame('Renovation', $resolved['summary_of_changes']['top_5_increases_ytd'][0]['group']);
        $this->assertSame('Demolition', $resolved['summary_of_changes']['top_5_decreases_ytd'][0]['group']);
        Http::assertNothingSent();
    }

    public function test_falls_back_to_analysis_api_when_snapshot_missing(): void
    {
        config()->set('services.analysis_api.url', 'https://analysis.example.com');

        Http::fake([
            'https://analysis.example.com/*' => Http::response([
                'parameters' => [
                    'analysis_current_year' => 2025,
                    'baseline_year' => 2019,
                ],
                'results' => [
                    [
                        'group' => 'Commercial',
                        'to_date' => [
                            2019 => ['count' => 5],
                            2025 => ['count' => 20, 'change_pct' => 300.0],
                        ],
                    ],
                    [
                        'group' => 'Residential',
                        'to_date' => [
                            2019 => ['count' => 12],
                            2025 => ['count' => 8, 'change_pct' => -33.3],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $report = YearlyCountComparison::query()->create([
            'model_class' => BuildingPermit::class,
            'group_by_col' => 'occupancytype',
            'baseline_year' => 2019,
            'job_id' => 'laravel-building-permit-occupancytype-yearly-2019-456',
        ]);

        $resolved = app(NewsArticleReportDataResolver::class)->resolve($report);

        $this->assertSame('Commercial', $resolved['summary_of_changes']['top_5_increases_ytd'][0]['group']);
        $this->assertSame('Residential', $resolved['summary_of_changes']['top_5_decreases_ytd'][0]['group']);
        Http::assertSentCount(1);
    }
}
