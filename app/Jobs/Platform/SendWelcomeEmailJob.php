<?php

namespace App\Jobs\Platform;

use App\Mail\Platform\WelcomeMail;
use App\Models\Tenant\Corporation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Corporation $corporation,
        public readonly User $owner,
        public readonly string $temporaryPassword,
    ) {}

    public function handle(): void
    {
        Mail::to($this->owner->email)->send(
            new WelcomeMail($this->corporation, $this->owner, $this->temporaryPassword),
        );
    }
}
