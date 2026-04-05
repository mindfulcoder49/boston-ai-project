<?php

namespace Tests\Feature;

use App\Mail\SendLocationReport;
use App\Mail\SendTrialEndedNotice;
use App\Models\Location;
use App\Models\User;
use App\Support\TrialLifecycleEmailVariant;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SendLocationReportMailRenderTest extends TestCase
{
    public function test_it_renders_the_inline_map_as_html_instead_of_escaped_markdown(): void
    {
        $location = new Location([
            'id' => 17,
            'name' => 'other',
            'address' => '621 E 1st St, South Boston, MA 02127, USA',
            'latitude' => 42.3379221,
            'longitude' => -71.0345927,
            'language' => 'English',
        ]);

        $path = storage_path('app/report_snapshots/test-inline-map-mail.png');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn0lV8AAAAASUVORK5CYII='));

        try {
            $html = (new SendLocationReport(
                $location,
                "## Location Report: other\n\n- **Location Name:** other",
                [
                    'path' => $path,
                    'snapshot' => [
                        'window' => [
                            'display' => 'April 3, 2026',
                        ],
                        'selected_points' => 1,
                        'recent_points_in_window' => 1,
                        'radius_miles' => 0.25,
                        'omitted_points' => 0,
                        'incidents' => [
                            [
                                'label' => '1',
                                'headline' => 'Noise Complaint',
                                'detail' => 'Caller reported repeated loud music from the block.',
                                'display_date' => 'April 3, 2026 9:36 PM',
                                'address' => '621 E 1st St, South Boston, MA 02127, USA',
                                'distance_miles' => 0.04,
                                'category_label' => '311',
                                'shape' => 'rounded-square',
                                'fill_color' => '#2563EB',
                                'stroke_color' => '#FFFFFF',
                                'text_color' => '#FFFFFF',
                            ],
                        ],
                    ],
                ],
                'https://example.test/location-maps',
                TrialLifecycleEmailVariant::STANDARD,
                'https://example.test/subscription'
            ))->render();
        } finally {
            File::delete($path);
        }

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="cid:', $html);
        $this->assertStringNotContainsString('&lt;img', $html);
        $this->assertStringContainsString('April 3, 2026', $html);
        $this->assertStringContainsString('Noise Complaint', $html);
        $this->assertStringContainsString('Description:</strong> Caller reported repeated loud music from the block.', $html);
        $this->assertStringContainsString('Narrative Summary', $html);
        $this->assertStringContainsString('View Daily Maps For The Last 7 Days', $html);
        $this->assertStringContainsString('https://example.test/location-maps', $html);
    }

    public function test_last_day_variant_updates_subject_and_intro_copy(): void
    {
        $location = new Location([
            'address' => '621 E 1st St, South Boston, MA 02127, USA',
        ]);

        $mail = new SendLocationReport(
            $location,
            "## Location Report: other\n\nNarrative Summary",
            null,
            null,
            TrialLifecycleEmailVariant::TRIAL_LAST_DAY,
            'https://example.test/subscription'
        );

        $html = $mail->render();

        $this->assertSame(
            'Last day of your free trial: Daily Location Report for 621 E 1st St, South Boston, MA 02127, USA',
            $mail->envelope()->subject
        );
        $this->assertStringContainsString('Last day of your free trial', $html);
        $this->assertStringContainsString('This is the last daily report included in your 7-day free trial.', $html);
        $this->assertStringContainsString('https://example.test/subscription', $html);
    }

    public function test_trial_grace_variant_updates_subject_and_intro_copy(): void
    {
        $location = new Location([
            'address' => '621 E 1st St, South Boston, MA 02127, USA',
        ]);

        $mail = new SendLocationReport(
            $location,
            "## Location Report: other\n\nNarrative Summary",
            null,
            null,
            TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT,
            'https://example.test/subscription'
        );

        $html = $mail->render();

        $this->assertSame(
            'Your trial ended. We sent one more report for 621 E 1st St, South Boston, MA 02127, USA',
            $mail->envelope()->subject
        );
        $this->assertStringContainsString("We&#039;re not supposed to send you an email today, but we did anyway.", $html);
    }

    public function test_it_renders_the_plain_text_fallback_for_the_latest_day_map_and_public_link(): void
    {
        $text = view('emails.location_report_text', [
            'report' => "## Location Report: other\n\nNarrative Summary",
            'recentMap' => [
                'snapshot' => [
                    'window' => [
                        'display' => 'April 3, 2026',
                    ],
                    'selected_points' => 1,
                    'recent_points_in_window' => 1,
                    'radius_miles' => 0.25,
                    'omitted_points' => 0,
                    'incidents' => [
                        [
                            'label' => '1',
                            'headline' => 'Noise Complaint',
                            'detail' => 'Caller reported repeated loud music from the block.',
                            'display_date' => 'April 3, 2026 9:36 PM',
                            'address' => '621 E 1st St, South Boston, MA 02127, USA',
                            'distance_miles' => 0.04,
                            'category_label' => '311',
                            'status' => 'Open',
                            'identifier' => '311-123',
                        ],
                    ],
                ],
            ],
            'publicMapsUrl' => 'https://example.test/location-maps',
            'variant' => TrialLifecycleEmailVariant::STANDARD,
            'introNotice' => null,
            'subscriptionUrl' => null,
        ])->render();

        $this->assertStringContainsString('Most recent day map: April 3, 2026', $text);
        $this->assertStringContainsString('1. [311] Noise Complaint', $text);
        $this->assertStringContainsString('Description: Caller reported repeated loud music from the block.', $text);
        $this->assertStringContainsString('Address: 621 E 1st St, South Boston, MA 02127, USA', $text);
        $this->assertStringContainsString('Status: Open', $text);
        $this->assertStringContainsString('ID: 311-123', $text);
        $this->assertStringContainsString('View daily maps for the last 7 days:', $text);
        $this->assertStringContainsString('https://example.test/location-maps', $text);
    }

    public function test_trial_ended_notice_email_renders_expected_copy(): void
    {
        $user = new User([
            'email' => 'trial@example.test',
        ]);
        $location = new Location([
            'address' => '621 E 1st St, South Boston, MA 02127, USA',
        ]);

        $mail = new SendTrialEndedNotice($user, $location, 'https://example.test/subscription');
        $html = $mail->render();

        $this->assertSame(
            'You are no longer receiving email reports from PublicDataWatch',
            $mail->envelope()->subject
        );
        $this->assertStringContainsString('You are no longer receiving email reports from PublicDataWatch. Subscribe for 5 dollars a month to start receiving them again.', $html);
        $this->assertStringContainsString('621 E 1st St, South Boston, MA 02127, USA', $html);
        $this->assertStringContainsString('https://example.test/subscription', $html);
    }
}
