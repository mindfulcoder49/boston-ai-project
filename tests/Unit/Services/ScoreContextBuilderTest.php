<?php

namespace Tests\Unit\Services;

use App\Services\ScoreContextBuilder;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ScoreContextBuilderTest extends TestCase
{
    public function test_builds_distribution_band_and_nearby_peer_context(): void
    {
        $currentScoreRow = [
            'h3_index' => '892a1072893ffff',
            'score_details' => [
                'score' => 12.5,
                'score_composition' => [
                    ['secondary_group' => 'Violent Crime', 'weighted_score' => 8.0],
                    ['secondary_group' => 'Property Crime', 'weighted_score' => 4.5],
                ],
            ],
        ];

        $allScores = new Collection([
            ['h3_index' => '892a1072891ffff', 'score_details' => ['score' => 4.0]],
            ['h3_index' => '892a1072892ffff', 'score_details' => ['score' => 9.0]],
            $currentScoreRow,
            ['h3_index' => '892a1072894ffff', 'score_details' => ['score' => 14.5]],
            ['h3_index' => '892a1072895ffff', 'score_details' => ['score' => 18.0]],
        ]);

        $context = app(ScoreContextBuilder::class)->build(
            $currentScoreRow,
            $allScores,
            ['892a1072892ffff', '892a1072894ffff'],
            ['analysis_period_weeks' => 52, 'h3_aggregation_method' => 'sum'],
            9,
            'stage6_artifact',
        );

        $this->assertNotNull($context);
        $this->assertSame(12.5, $context['score']);
        $this->assertSame(50.0, $context['percentile']);
        $this->assertSame('Typical relative concern', $context['band']['label']);
        $this->assertSame(12.5, $context['distribution']['median']);
        $this->assertTrue($context['nearby_peers']['available']);
        $this->assertSame(2, $context['nearby_peers']['count']);
        $this->assertSame(11.75, $context['nearby_peers']['median']);
        $this->assertSame(0.75, $context['nearby_peers']['current_vs_median']);
        $this->assertSame('Violent Crime', $context['top_drivers'][0]['label']);
        $this->assertSame(64.0, $context['top_drivers'][0]['share_percent']);
        $this->assertSame('Historical neighborhood score', $context['methodology']['label']);
    }
}
