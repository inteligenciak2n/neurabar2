<?php

namespace App\Notifications\Billing;

use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdue extends Notification
{
    use Queueable;

    public function __construct(
        private readonly VenueInvoice|CorporationInvoice $invoice,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $period = $this->invoice->period;
        $total = number_format((float) $this->invoice->total_value, 2, ',', '.');

        return (new MailMessage)
            ->subject("Fatura {$period} vencida")
            ->greeting('Olá, '.$notifiable->name)
            ->line("Sua fatura de {$period} no valor de R$ {$total} está vencida.")
            ->line('Regularize o pagamento para evitar a suspensão da conta.');
    }
}
