<?php

namespace App\Mail;

use App\Models\Location;
use App\Support\TrialLifecycleEmailVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendLocationReport extends Mailable
{
    use Queueable, SerializesModels;

    public $location;
    public $report;
    public $recentMap;
    public $publicMapsUrl;
    public $variant;
    public $subscriptionUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Location $location,
        string $report,
        ?array $recentMap = null,
        ?string $publicMapsUrl = null,
        string $variant = TrialLifecycleEmailVariant::STANDARD,
        ?string $subscriptionUrl = null
    )
    {
        $this->location = $location;
        $this->report = $report;
        $this->recentMap = $recentMap;
        $this->publicMapsUrl = $publicMapsUrl;
        $this->variant = $variant;
        $this->subscriptionUrl = $subscriptionUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.location_report_html',
            text: 'emails.location_report_text',
            with: [
                'location' => $this->location,
                'report' => $this->report,
                'recentMap' => $this->recentMap,
                'publicMapsUrl' => $this->publicMapsUrl,
                'variant' => $this->variant,
                'introNotice' => $this->introNotice(),
                'subscriptionUrl' => $this->subscriptionUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function subjectLine(): string
    {
        return match ($this->variant) {
            TrialLifecycleEmailVariant::TRIAL_LAST_DAY => 'Last day of your free trial: Daily Location Report for ' . $this->location->address,
            TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT => 'Your trial ended. We sent one more report for ' . $this->location->address,
            default => 'Daily Location Report for ' . $this->location->address,
        };
    }

    private function introNotice(): ?array
    {
        return match ($this->variant) {
            TrialLifecycleEmailVariant::TRIAL_LAST_DAY => [
                'headline' => 'Last day of your free trial',
                'body' => 'This is the last daily report included in your 7-day free trial. If you want the one-address report to keep going, the $5/month plan is the next step.',
                'cta_label' => 'View plans',
            ],
            TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT => [
                'headline' => 'Your trial ended',
                'body' => "We're not supposed to send you an email today, but we did anyway. This is one extra full report so you can see what the $5/month plan keeps going.",
                'cta_label' => 'Subscribe for $5/month',
            ],
            default => null,
        };
    }
}
