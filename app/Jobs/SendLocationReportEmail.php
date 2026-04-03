<?php

namespace App\Jobs;

use App\Models\Location;
use App\Mail\SendLocationReport;
use App\Models\Report;
use App\Services\LocationReportBuilder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLocationReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $location;
    protected $radiusForReport;

    /**
     * Create a new job instance.
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
        $this->radiusForReport = 0.25;
    }

    /**
     * Execute the job.
     */
    public function handle(Mailer $mailer, LocationReportBuilder $reportBuilder)
    {
        try {
            $build = $reportBuilder->build($this->location, $this->radiusForReport);

            if (($build['data_points_count'] ?? 0) === 0) {
                Log::info("No map data found for location ID: {$this->location->id} ({$this->location->address})");
                return;
            }

            $finalReport = $build['final_report'];
            $dailyReportContent = $build['daily_report_content'];


            // Log the generated report details
            if ($this->location->user && $this->location->user->subscription('default')) {
                 Log::info("Generated report for user: {$this->location->user->email} with subscription ID: {$this->location->user->subscription('default')->stripe_id} for location: {$this->location->address}");
                 Log::info("User Info: " . json_encode($this->location->user->toArray()));
                 Log::info("Subscription Info: " . json_encode($this->location->user->subscription('default')->toArray()));
            } else {
                Log::warning("Could not log full user/subscription details for location ID: {$this->location->id}. User or subscription missing.");
            }


            // --- 5. Save Report to Database (New Step) ---
            if (!empty($finalReport) && $this->location->user) {
                try {
                    $reportDateForTitle = Carbon::now()->format('Y-m-d');
                    Report::create([
                        'user_id' => $this->location->user_id,
                        'location_id' => $this->location->id,
                        'title' => 'Location Report for ' . $this->locationLabel() . " - {$reportDateForTitle}",
                        'content' => $finalReport,
                        'generated_at' => Carbon::now(),
                    ]);
                    Log::info("Report saved to database for user: {$this->location->user->email}, location: {$this->location->address}");
                } catch (\Exception $dbException) {
                    Log::error("Failed to save report to database for user: {$this->location->user->email}, location: {$this->location->address}. Error: {$dbException->getMessage()}");
                }
            }


            // --- 6. Send Email (if there's a report to send)---
            if (!empty($dailyReportContent) && $this->location->user) {
                $mailer->to($this->location->user->email)
                       ->send(new SendLocationReport($this->location, $finalReport));
                Log::info("Report email sent to user: {$this->location->user->email} for location: {$this->location->address}");
            } else {
                $userEmail = $this->location->user?->email ?? 'unknown-user';
                Log::info("No reports generated after date/type processing (empty dailyReportContent). No email was sent to {$userEmail} for location: {$this->location->address}");
            }

        } catch (\Exception $e) {
            Log::error("Error processing report or sending email for location {$this->location->address}: {$e->getMessage()}");
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }

    private function locationLabel(): string
    {
        $name = trim((string) ($this->location->name ?? ''));

        return $name !== '' ? $name : (string) $this->location->address;
    }
}
