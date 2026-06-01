<?php

namespace App\Notifications\Support;

use App\Models\Support\Ticket;
use App\Models\Support\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketMessage $message,
        public readonly string $authorName,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $isClientReceiving = method_exists($notifiable, 'current_venue_id');

        $url = $isClientReceiving
            ? route('support.tickets.show', $this->ticket->id)
            : url("/backoffice/support/tickets/{$this->ticket->id}");

        return (new MailMessage)
            ->subject("Nova mensagem no chamado: {$this->ticket->subject}")
            ->greeting("Nova mensagem de {$this->authorName}")
            ->line("Chamado: **{$this->ticket->subject}**")
            ->line(substr($this->message->body, 0, 300).(strlen($this->message->body) > 300 ? '...' : ''))
            ->action('Ver chamado', $url);
    }
}
