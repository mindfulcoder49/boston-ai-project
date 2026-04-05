<?php

namespace App\Jobs;

use App\Models\Location;
use App\Mail\SendLocationReport;
use App\Models\Report;
use App\Services\LocationReportEmailMapService;
use App\Services\LocationReportBuilder;
use App\Services\LocationReportMapSnapshotUrlGenerator;
use App\Support\TrialLifecycleEmailVariant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLocationReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;
    public bool $failOnTimeout = true;

    protected $location;
    protected $radiusForReport;
    protected string $variant;

    /**
     * Create a new job instance.
     */
    public function __construct(Location $location, string $variant = TrialLifecycleEmailVariant::STANDARD)
    {
        $this->location = $location;
        $this->radiusForReport = 0.25;
        $this->variant = $variant;
    }

    /**
     * Execute the job.
     */
    public function handle(
        Mailer $mailer,
        LocationReportBuilder $reportBuilder,
        LocationReportEmailMapService $emailMapService,
        LocationReportMapSnapshotUrlGenerator $snapshotUrlGenerator
    )
    {
        $recentMap = null;

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
                try {
                    $recentMap = $emailMapService->captureLatestDay($this->location, $this->radiusForReport);

                    if ($recentMap !== null) {
                        Log::info('Location report newest-day map prepared for email.', [
                            'location_id' => $this->location->id,
                            'window_date' => $recentMap['snapshot']['window']['date'] ?? null,
                            'has_image' => is_string($recentMap['path'] ?? null),
                            'selected_points' => $recentMap['snapshot']['selected_points'] ?? null,
                        ]);
                    }
                } catch (\Throwable $mapException) {
                    Log::warning('Location report newest-day map capture failed; sending email without image.', [
                        'location_id' => $this->location->id,
                        'message' => $mapException->getMessage(),
                    ]);
                }

                $mailer->to($this->location->user->email)
                       ->send(new SendLocationReport(
                           $this->location,
                           $finalReport,
                           $recentMap,
                           $snapshotUrlGenerator->generatePublicDailyMapsPage($this->location),
                           $this->variant,
                           route('subscription.index', [
                               'source' => 'trial-lifecycle-email',
                               'recommended' => 'basic',
                               'trial' => 'expired',
                           ])
                       ));

                if ($this->variant === TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT) {
                    $this->location->user->forceFill([
                        'crime_address_trial_grace_report_sent_at' => now(),
                    ])->save();
                }

                Log::info("Report email sent to user: {$this->location->user->email} for location: {$this->location->address}");
            } else {
                $userEmail = $this->location->user?->email ?? 'unknown-user';
                Log::info("No reports generated after date/type processing (empty dailyReportContent). No email was sent to {$userEmail} for location: {$this->location->address}");
            }
        } catch (Throwable $e) {
            Log::error("Error processing report or sending email for location {$this->location->address}: {$e->getMessage()}");
            Log::error("Stack trace: " . $e->getTraceAsString());

            throw $e;
        }
    }

    private function locationLabel(): string
    {
        $name = trim((string) ($this->location->name ?? ''));

        return $name !== '' ? $name : (string) $this->location->address;
    }
}
