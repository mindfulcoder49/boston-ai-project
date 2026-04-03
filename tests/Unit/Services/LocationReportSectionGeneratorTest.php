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
        config()->set('services.openai.location_report_prompt_max_points', 20);

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
                $this->assertStringContainsString('"sampled_points": 1', $payload['messages'][1]['content']);
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

    public function test_it_returns_a_structured_fallback_when_openai_returns_empty_content(): void
    {
        config()->set('services.openai.location_report_model', 'gpt-5-mini');
        config()->set('services.openai.location_report_max_completion_tokens', 800);
        config()->set('services.openai.location_report_prompt_max_points', 2);

        $openAiService = Mockery::mock(OpenAIService::class);
        $openAiService
            ->shouldReceive('openaiChatCompletionsCreate')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                $this->assertStringContainsString('"sampled_points": 2', $payload['messages'][1]['content']);
                $this->assertStringNotContainsString('Should not be in prompt', $payload['messages'][1]['content']);

                return true;
            }))
            ->andReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => '',
                        ],
                        'finish_reason' => 'length',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 123,
                    'completion_tokens' => 800,
                ],
            ]);

        $generator = new LocationReportSectionGenerator($openAiService);

        $result = $generator->generate(
            'Crime (Events from April 1, 2026)',
            [
                [
                    'category' => 'Crime',
                    'description' => 'Theft from a motor vehicle',
                    'date' => '2026-04-01 12:30:00',
                    'block' => '100 Main St',
                ],
                [
                    'category' => 'Crime',
                    'description' => 'Assault',
                    'date' => '2026-04-01 08:15:00',
                    'block' => '200 Main St',
                ],
                [
                    'category' => 'Crime',
                    'description' => 'Should not be in prompt',
                    'date' => '2026-04-01 05:00:00',
                    'block' => '300 Main St',
                ],
            ],
            'en'
        );

        $this->assertStringContainsString('- 3 records matched this section.', $result);
        $this->assertStringContainsString('Time span in source data: 2026-04-01.', $result);
        $this->assertStringContainsString('Crime (3)', $result);
        $this->assertStringContainsString('Theft from a motor vehicle', $result);
    }
}
