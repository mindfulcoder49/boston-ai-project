<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackendHealthAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $alerts)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PublicDataWatch Backend Health Alert'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.backend_health_alert',
            with: [
                'alerts' => $this->alerts,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
