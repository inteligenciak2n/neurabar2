<?php

namespace App\Notifications\Billing;

use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * A fatura é criada dentro da transação de geração: enfileirar antes do
     * commit faria o worker não encontrar o registro.
     */
    public function __construct(
        private readonly VenueInvoice|CorporationInvoice $invoice,
    ) {
        $this->afterCommit();
    }

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
