<?php

namespace App\Notifications\Subscription;

use App\Models\Tenant\GatewayWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The queue exhausted every retry for a gateway webhook, so the payment it
 * carried was never reconciled locally.
 */
class GatewayWebhookFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly GatewayWebhookEvent $event,
        private readonly string $error,
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
            ->subject('[Cobrança] Webhook do gateway falhou definitivamente')
            ->greeting('Olá, '.$notifiable->name)
            ->line("O evento {$this->event->event_type} do gateway {$this->event->gateway} não pôde ser processado após todas as tentativas.")
            ->line("Identificador interno: {$this->event->id}")
            ->line("Erro: {$this->error}")
            ->line('Reprocesse o evento manualmente para evitar divergência entre o gateway e as faturas.');
    }
}
