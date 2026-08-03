<?php

namespace App\Notifications\Billing;

use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGenerated extends Notification
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
        $dueDate = $this->invoice->due_date?->format('d/m/Y');

        return (new MailMessage)
            ->subject("Fatura {$period} gerada")
            ->greeting('Olá, '.$notifiable->name)
            ->line("Sua fatura de {$period} no valor de {$total} foi gerada.")
            ->line("Vencimento: {$dueDate}.")
            ->action('Visualizar fatura', url('/backoffice/invoices'));
    }
}
