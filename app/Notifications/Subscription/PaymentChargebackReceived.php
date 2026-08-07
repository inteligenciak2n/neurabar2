<?php

namespace App\Notifications\Subscription;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Chargebacks require a human response within the acquirer's deadline, so the
 * backoffice is alerted instead of the event being silently logged.
 */
class PaymentChargebackReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly string $invoiceType,
        private readonly string $invoiceId,
        private readonly string $gatewayPaymentId,
        private readonly float $amount,
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
            ->subject('[Cobrança] Chargeback recebido')
            ->greeting('Olá, '.$notifiable->name)
            ->line("Evento recebido do gateway: {$this->event}.")
            ->line("Fatura: {$this->invoiceType} #{$this->invoiceId}.")
            ->line("Cobrança no gateway: {$this->gatewayPaymentId}.")
            ->line('Valor: R$ '.number_format($this->amount, 2, ',', '.').'.')
            ->line('O acesso do cliente foi suspenso automaticamente. Avalie a contestação dentro do prazo da adquirente.');
    }
}
