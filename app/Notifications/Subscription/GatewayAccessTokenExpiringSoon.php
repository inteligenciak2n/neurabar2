<?php

namespace App\Notifications\Subscription;

use App\Models\Tenant\GatewayWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GatewayAccessTokenExpiringSoon extends Notification
{
    use Queueable;

    public function __construct(private readonly GatewayWebhookEvent $event) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accessToken = $this->event->payload['accessToken'] ?? [];
        $keyName = $accessToken['name'] ?? 'desconhecida';
        $expirationDate = $accessToken['projectedExpirationDateByLackOfUse']
            ?? $accessToken['expirationDate']
            ?? 'não informada';

        return (new MailMessage)
            ->subject("[{$this->event->gateway}] Chave de API expirando em breve")
            ->greeting('Olá, '.$notifiable->name)
            ->line("A chave de API '{$keyName}' do gateway {$this->event->gateway} irá expirar em breve.")
            ->line("Data prevista de expiração: {$expirationDate}.")
            ->line('Gere uma nova chave e atualize a configuração da aplicação antes do vencimento para evitar interrupção nas cobranças.');
    }
}
