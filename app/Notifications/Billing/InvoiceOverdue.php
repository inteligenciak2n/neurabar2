<?php

namespace App\Notifications\Billing;

use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdue extends Notification implements ShouldQueue
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
        $total = Money::format((int) $this->invoice->total_value);

        return (new MailMessage)
            ->subject("Fatura {$period} vencida")
            ->greeting('Olá, '.$notifiable->name)
            ->line("Sua fatura de {$period} no valor de {$total} está vencida.")
            ->line('Regularize o pagamento para evitar a suspensão da conta.');
    }
}
