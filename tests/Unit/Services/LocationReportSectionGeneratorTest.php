<?php

namespace Tests\Unit\Services;

use App\Services\LocationReportSectionGenerator;
use App\Services\OpenAIService;
use Mockery;
use Tests\TestCase;

class LocationReportSectionGeneratorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_uses_the_configured_openai_model_for_location_report_sections(): void
    {
        config()->set('services.openai.location_report_model', 'gpt-5-mini');
        config()->set('services.openai.location_report_max_completion_tokens', 800);

        $openAiService = Mockery::mock(OpenAIService::class);
        $openAiService
            ->shouldReceive('openaiChatCompletionsCreate')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                $this->assertSame('gpt-5-mini', $payload['model']);
                $this->assertSame(800, $payload['max_completion_tokens']);
                $this->assertSame('system', $payload['messages'][0]['role']);
                $this->assertSame('user', $payload['messages'][1]['role']);
                $this->assertStringContainsString('Events from April 1, 2026', $payload['messages'][0]['content']);
                $this->assertStringContainsString('"category": "Crime"', $payload['messages'][1]['content']);

                return true;
            }))
            ->andReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => "#### Crime\nA short report section.",
                        ],
                    ],
                ],
            ]);

        $generator = new LocationReportSectionGenerator($openAiService);

        $result = $generator->generate(
            'Crime (Events from April 1, 2026)',
            [
                [
                    'category' => 'Crime',
                    'description' => 'Theft from a motor vehicle',
                ],
            ],
            'en'
        );

        $this->assertSame("#### Crime\nA short report section.", $result);
    }

    public function test_it_returns_no_report_when_no_data_points_are_provided(): void
    {
        $openAiService = Mockery::mock(OpenAIService::class);
        $openAiService->shouldNotReceive('openaiChatCompletionsCreate');

        $generator = new LocationReportSectionGenerator($openAiService);

        $this->assertSame('No report generated.', $generator->generate('Crime', [], 'en'));
    }
}
