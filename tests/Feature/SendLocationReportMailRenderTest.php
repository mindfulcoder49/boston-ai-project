<?php

namespace Tests\Feature;

use App\Mail\SendLocationReport;
use App\Models\Location;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SendLocationReportMailRenderTest extends TestCase
{
    public function test_it_renders_the_inline_map_as_html_instead_of_escaped_markdown(): void
    {
        $location = new Location([
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
                [[
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
                ]]
            ))->render();
        } finally {
            File::delete($path);
        }

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="cid:', $html);
        $this->assertStringNotContainsString('&lt;img', $html);
        $this->assertStringContainsString('April 3, 2026', $html);
        $this->assertStringContainsString('Noise Complaint', $html);
        $this->assertStringContainsString('Narrative Summary', $html);
    }
}
