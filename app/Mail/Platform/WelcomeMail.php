<?php

namespace App\Mail\Platform;

use App\Models\Tenant\Corporation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Corporation $corporation,
        public readonly User $owner,
        public readonly string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to NeuraBar — '.$this->corporation->name);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.platform.welcome');
    }
}
