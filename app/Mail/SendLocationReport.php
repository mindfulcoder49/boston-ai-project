<?php

namespace App\Mail;

use App\Models\Location;
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
    public $mapImagePath;
    public $mapSnapshot;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Location $location,
        string $report,
        ?string $mapImagePath = null,
        ?array $mapSnapshot = null
    )
    {
        $this->location = $location;
        $this->report = $report;
        $this->mapImagePath = $mapImagePath;
        $this->mapSnapshot = $mapSnapshot;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Location Report for ' . $this->location->address,
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
                'mapImagePath' => $this->mapImagePath,
                'mapSnapshot' => $this->mapSnapshot,
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
}
