<?php

namespace App\Mail;

use App\Models\VenueInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VenueInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly VenueInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Você foi convidado para colaborar em '.$this->invitation->venue->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.venue-invitation',
            with: [
                'venueName' => $this->invitation->venue->name,
                'role' => $this->invitation->role->label(),
                'acceptUrl' => route('invitations.show', $this->invitation->token),
                'expiresAt' => $this->invitation->expires_at->format('d/m/Y H:i'),
            ],
        );
    }
}
