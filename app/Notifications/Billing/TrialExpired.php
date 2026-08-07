<?php

namespace App\Notifications\Billing;

use App\Models\Tenant\Corporation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpired extends Notification implements ShouldQueue
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
            ->subject('Seu período de teste expirou')
            ->greeting('Olá, '.$notifiable->name)
            ->line('O período de testes da empresa '.$this->corporation->name.' expirou.')
            ->line('Você tem um prazo de carência antes da suspensão. Regularize sua assinatura.');
    }
}
