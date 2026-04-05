<?php

namespace App\Jobs;

use App\Mail\SendTrialEndedNotice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTrialEndedNoticeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;
    public bool $failOnTimeout = true;

    public function __construct(protected User $user)
    {
    }

    public function handle(Mailer $mailer): void
    {
        try {
            $trialLocation = $this->user->crime_address_trial_location_id
                ? $this->user->locations()->find($this->user->crime_address_trial_location_id)
                : null;

            $mailer->to($this->user->email)->send(new SendTrialEndedNotice(
                $this->user,
                $trialLocation,
                route('subscription.index', [
                    'source' => 'trial-ended-notice-email',
                    'recommended' => 'basic',
                    'trial' => 'expired',
                ])
            ));

            $this->user->forceFill([
                'crime_address_trial_ended_notice_sent_at' => now(),
            ])->save();

            Log::info('Trial-ended notice email sent.', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]);
        } catch (Throwable $throwable) {
            Log::error('Failed to send trial-ended notice email.', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
