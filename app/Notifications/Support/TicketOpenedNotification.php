<?php

namespace App\Notifications\Support;

use App\Models\Support\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $user,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Novo chamado aberto: #{$this->ticket->id} — {$this->ticket->subject}")
            ->greeting('Novo chamado de suporte')
            ->line("**{$this->user->name}** ({$this->user->email}) abriu um novo chamado.")
            ->line("**Assunto:** {$this->ticket->subject}")
            ->line("**Prioridade:** {$this->ticket->priority->label()}")
            ->action('Ver chamado', url("/backoffice/support/tickets/{$this->ticket->id}"));
    }
}
