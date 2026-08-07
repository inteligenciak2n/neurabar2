<?php

namespace App\Notifications\Billing;

use App\Models\Tenant\Corporation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionSuspended extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Corporation $corporation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sua assinatura foi suspensa')
            ->greeting('Olá, '.$notifiable->name)
            ->line('A assinatura da empresa '.$this->corporation->name.' foi suspensa por inadimplência.')
            ->line('Regularize o pagamento para restaurar o acesso às funcionalidades.');
    }
}
