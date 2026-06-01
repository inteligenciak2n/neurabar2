<?php

namespace App\Notifications\Support;

use App\Models\Support\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Chamado resolvido: {$this->ticket->subject}")
            ->greeting('Seu chamado foi resolvido!')
            ->line("O chamado **{$this->ticket->subject}** foi marcado como resolvido.")
            ->line('Se o problema persistir, você pode reabrir o chamado respondendo a ele.')
            ->line('Nos ajude a melhorar avaliando o atendimento:')
            ->action('Avaliar atendimento', route('support.tickets.show', $this->ticket->id));
    }
}
