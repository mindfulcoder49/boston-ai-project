<?php

namespace Tests\Feature;

use App\Jobs\SendTrialEndedNoticeEmail;
use App\Mail\SendTrialEndedNotice;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SendTrialEndedNoticeEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys=ON');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('manual_subscription_tier')->nullable();
            $table->timestamp('crime_address_trial_started_at')->nullable();
            $table->timestamp('crime_address_trial_ends_at')->nullable();
            $table->unsignedBigInteger('crime_address_trial_location_id')->nullable();
            $table->timestamp('crime_address_trial_grace_report_sent_at')->nullable();
            $table->timestamp('crime_address_trial_ended_notice_sent_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('report')->nullable();
            $table->string('language')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_it_sends_the_trial_ended_notice_and_marks_the_send_timestamp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'crime_address_trial_started_at' => now()->subDays(9),
            'crime_address_trial_ends_at' => now()->subDays(2),
        ]);
        $location = Location::factory()->for($user)->create([
            'name' => 'South Boston Home',
            'address' => '621 E 1st St, South Boston, MA 02127, USA',
        ]);

        $user->forceFill([
            'crime_address_trial_location_id' => $location->id,
        ])->save();

        $job = new SendTrialEndedNoticeEmail($user);
        $job->handle(app(Mailer::class));

        Mail::assertSent(SendTrialEndedNotice::class, function (SendTrialEndedNotice $mail) use ($user, $location): bool {
            return $mail->user->is($user)
                && $mail->trialLocation?->is($location)
                && $mail->subscriptionUrl !== null;
        });

        $this->assertNotNull($user->fresh()->crime_address_trial_ended_notice_sent_at);
    }
}
