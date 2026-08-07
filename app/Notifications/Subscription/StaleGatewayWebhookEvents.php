<?php

namespace App\Notifications\Subscription;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Backlog de webhooks do gateway sem processamento — indica divergência entre
 * o que o gateway já cobrou e o que o NeuraBar registrou.
 */
class StaleGatewayWebhookEvents extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $count,
        private readonly int $olderThanHours,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Cobrança] Webhooks do gateway sem processamento')
            ->greeting('Olá, '.$notifiable->name)
            ->line("Existem {$this->count} evento(s) de webhook parados há mais de {$this->olderThanHours} horas.")
            ->line('Faturas podem estar em aberto no NeuraBar mesmo já pagas no gateway.')
            ->line('O provedor mantém o histórico por 14 dias — reprocesse os eventos dentro dessa janela.');
    }
}
