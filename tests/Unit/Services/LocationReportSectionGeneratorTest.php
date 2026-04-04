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
                $this->assertStringContainsString('"service_name": "Parking Enforcement"', $payload['messages'][1]['content']);
                $this->assertStringContainsString('"incident_address": "730 E Third St"', $payload['messages'][1]['content']);
                $this->assertStringNotContainsString('crime_data_json', $payload['messages'][1]['content']);

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
                    'crime_data_json' => null,
                    'three_one_one_case_data' => (object) [
                        'service_name' => 'Parking Enforcement',
                        'incident_address' => '730 E Third St',
                    ],
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
        $this->assertStringContainsString('Counts by day: 2026-04-01 (3).', $result);
        $this->assertStringContainsString('Crime (3)', $result);
        $this->assertStringContainsString('Theft from a motor vehicle', $result);
    }

    public function test_it_rejects_prompt_echo_style_responses_and_uses_the_structured_fallback(): void
    {
        config()->set('services.openai.location_report_prompt_max_points', 5);

        $openAiService = Mockery::mock(OpenAIService::class);
        $openAiService
            ->shouldReceive('openaiChatCompletionsCreate')
            ->once()
            ->andReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => "### Boston 311 Cases\nPayload status (per provided records)\n- data_point_id 123\n- crime_data_json is null",
                        ],
                    ],
                ],
            ]);

        $generator = new LocationReportSectionGenerator($openAiService);

        $result = $generator->generate(
            'Boston 311 Cases (Events from March 30, 2026)',
            [
                [
                    'alcivartech_date' => '2026-03-30 12:35:53',
                    'three_one_one_case_data' => (object) [
                        'service_name' => 'Sticker Request',
                        'incident_address' => '704 E Broadway',
                    ],
                ],
            ],
            'en'
        );

        $this->assertStringContainsString('- 1 record matched this section.', $result);
        $this->assertStringContainsString('Sticker Request', $result);
        $this->assertStringNotContainsString('Payload status', $result);
    }

    public function test_it_prioritizes_more_recent_data_points_in_the_prompt_sample(): void
    {
        config()->set('services.openai.location_report_prompt_max_points', 2);

        $openAiService = Mockery::mock(OpenAIService::class);
        $openAiService
            ->shouldReceive('openaiChatCompletionsCreate')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                $content = $payload['messages'][1]['content'];

                $this->assertStringContainsString('2026-04-03 10:00:00', $content);
                $this->assertStringContainsString('2026-04-02 10:00:00', $content);
                $this->assertStringNotContainsString('2026-04-01 10:00:00', $content);

                return true;
            }))
            ->andReturn([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'A concise section.',
                        ],
                    ],
                ],
            ]);

        $generator = new LocationReportSectionGenerator($openAiService);

        $generator->generate(
            'Crime (Events from April 3, 2026)',
            [
                ['alcivartech_date' => '2026-04-01 10:00:00', 'category' => 'Crime', 'description' => 'Oldest'],
                ['alcivartech_date' => '2026-04-03 10:00:00', 'category' => 'Crime', 'description' => 'Newest'],
                ['alcivartech_date' => '2026-04-02 10:00:00', 'category' => 'Crime', 'description' => 'Middle'],
            ],
            'en'
        );
    }

    public function test_it_prioritizes_incident_descriptions_over_low_signal_derived_fields(): void
    {
        config()->set('services.openai.location_report_prompt_max_points', 1);
        config()->set('services.openai.location_report_max_fields_per_point', 5);

        $openAiService = Mockery::mock(OpenAIService::class);
        $openAiService
            ->shouldReceive('openaiChatCompletionsCreate')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                $content = $payload['messages'][1]['content'];

                $this->assertStringContainsString('"incident_description": "WALTHAM PD WOULD LIKE TO KNOW WHO WAS DRIVING"', $content);
                $this->assertStringContainsString('"incident_address": "26 GLEDHILL AV"', $content);
                $this->assertStringNotContainsString('"year": 2026', $content);
                $this->assertStringNotContainsString('"day_of_week": "Tuesday"', $content);
                $this->assertStringContainsString('include those concrete details', $payload['messages'][0]['content']);

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
            ]);

        $generator = new LocationReportSectionGenerator($openAiService);

        $result = $generator->generate(
            'Everett Crime (Events from March 31, 2026)',
            [
                [
                    'case_number' => '963463',
                    'occurred_on_datetime' => '2026-03-31 15:44:00',
                    'incident_type' => 'NOTIFICATION- SEE COMMENTS',
                    'incident_description' => 'WALTHAM PD WOULD LIKE TO KNOW WHO WAS DRIVING',
                    'incident_address' => '26 GLEDHILL AV',
                    'year' => 2026,
                    'month' => 3,
                    'day_of_week' => 'Tuesday',
                    'hour' => 15,
                ],
            ],
            'en'
        );

        $this->assertStringContainsString('WALTHAM PD WOULD LIKE TO KNOW WHO WAS DRIVING', $result);
        $this->assertStringContainsString('26 GLEDHILL AV', $result);
    }
}
