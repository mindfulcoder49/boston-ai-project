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

    /**
     * Create a new message instance.
     */
    public function __construct(Location $location, string $report, ?string $mapImagePath = null)
    {
        $this->location = $location;
        $this->report = $report;
        $this->mapImagePath = $mapImagePath;
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
            markdown: 'emails.location_report',  //  The Blade template
            with: [
                'location' => $this->location,
                'report' => $this->report,
                'mapImagePath' => $this->mapImagePath,
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
