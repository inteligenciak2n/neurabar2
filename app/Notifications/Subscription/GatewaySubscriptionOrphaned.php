<?php

namespace App\Notifications\Subscription;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A recurring subscription exists at the gateway with no local counterpart, so
 * it will keep charging the customer until someone cancels it by hand.
 */
class GatewaySubscriptionOrphaned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $gatewaySubscriptionId,
        private readonly string $subscriptionId,
        private readonly string $reason,
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
            ->subject('[Cobrança] Assinatura órfã no gateway')
            ->greeting('Olá, '.$notifiable->name)
            ->line("A assinatura {$this->gatewaySubscriptionId} foi criada no gateway, mas não pôde ser vinculada à assinatura local {$this->subscriptionId}.")
            ->line("O cancelamento automático também falhou: {$this->reason}")
            ->line('Cancele a assinatura manualmente no painel do gateway para evitar cobranças indevidas.');
    }
}
