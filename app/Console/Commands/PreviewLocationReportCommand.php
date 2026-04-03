<?php

namespace App\Console\Commands;

use App\Mail\SendLocationReport;
use App\Models\Location;
use App\Models\Report;
use App\Services\LocationReportBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\File;

class PreviewLocationReportCommand extends Command
{
    protected $signature = 'reports:preview
                            {location_id : Generate a preview for a specific saved location}
                            {--radius=0.25 : Radius in miles}
                            {--json : Output JSON instead of markdown}
                            {--write : Write markdown and diagnostics files to storage/app/report_previews}
                            {--save : Save the generated report to the reports table}
                            {--send : Send the generated email to the location user}';

    protected $description = 'Preview a saved-location report without dispatching the scheduled report job.';

    public function __construct(
        private readonly LocationReportBuilder $reportBuilder
    ) {
        parent::__construct();
    }

    public function handle(Mailer $mailer): int
    {
        $location = Location::with('user')->find($this->argument('location_id'));

        if (!$location) {
            $this->error('Location not found.');
            return 1;
        }

        $result = $this->reportBuilder->build(
            $location,
            (float) $this->option('radius')
        );

        $payload = [
            'location_id' => $location->id,
            'user_id' => $location->user_id,
            'data_points_count' => $result['data_points_count'],
            'section_diagnostics' => $result['section_diagnostics'],
            'final_report' => $result['final_report'],
        ];

        if ($this->option('write')) {
            $timestamp = Carbon::now()->format('YmdHis');
            $directory = storage_path('app/report_previews');
            File::ensureDirectoryExists($directory);

            $baseName = "{$timestamp}_location_{$location->id}";
            File::put("{$directory}/{$baseName}.md", $result['final_report']);
            File::put(
                "{$directory}/{$baseName}.json",
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $this->line("Preview artifacts written to {$directory}/{$baseName}.md and {$directory}/{$baseName}.json");
        }

        if ($this->option('save') && $location->user) {
            Report::create([
                'user_id' => $location->user_id,
                'location_id' => $location->id,
                'title' => 'Preview Location Report for ' . $this->locationLabel($location) . ' - ' . Carbon::now()->format('Y-m-d H:i'),
                'content' => $result['final_report'],
                'generated_at' => Carbon::now(),
            ]);

            $this->line('Preview report saved to the reports table.');
        }

        if ($this->option('send')) {
            if (!$location->user) {
                $this->error('Location has no user; cannot send.');
                return 1;
            }

            if ($result['daily_report_content'] === '') {
                $this->warn('No report sections were generated; no email sent.');
            } else {
                $mailer->to($location->user->email)->send(new SendLocationReport($location, $result['final_report']));
                $this->line("Preview email sent to {$location->user->email}.");
            }
        }

        $this->line('Report preview generated.');

        if (!$this->option('save') && !$this->option('send')) {
            $this->line('Preview only: no report saved, no email sent.');
        }

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->line($result['final_report']);

        return 0;
    }

    private function locationLabel(Location $location): string
    {
        $name = trim((string) ($location->name ?? ''));

        return $name !== '' ? $name : (string) $location->address;
    }
}
