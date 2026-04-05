<?php

namespace App\Mail;

use App\Models\Location;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendTrialEndedNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?Location $trialLocation = null,
        public ?string $subscriptionUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are no longer receiving email reports from PublicDataWatch',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial_ended_notice_html',
            text: 'emails.trial_ended_notice_text',
            with: [
                'user' => $this->user,
                'trialLocation' => $this->trialLocation,
                'subscriptionUrl' => $this->subscriptionUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
