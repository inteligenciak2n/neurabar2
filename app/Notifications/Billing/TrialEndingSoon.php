<?php

namespace App\Notifications\Billing;

use App\Models\Tenant\Corporation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingSoon extends Notification
{
    use Queueable;

    public function __construct(private readonly Corporation $corporation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trialEndsAt = $this->corporation->subscription?->trial_ends_at?->format('d/m/Y');

        return (new MailMessage)
            ->subject('Seu período de teste está prestes a expirar')
            ->greeting('Olá, '.$notifiable->name)
            ->line('O período de testes da empresa '.$this->corporation->name.' termina em '.$trialEndsAt.'.')
            ->line('Adicione uma forma de pagamento para continuar usando todos os módulos contratados.')
            ->action('Acessar conta', url('/dashboard'));
    }
}
