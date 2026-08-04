<?php

namespace App\Notifications\Billing;

use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CardPaymentRefused extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly VenueInvoice|CorporationInvoice $invoice,
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
        $period = $this->invoice->period;
        $total = Money::format((int) $this->invoice->total_value);

        return (new MailMessage)
            ->subject("Falha no pagamento da fatura {$period}")
            ->greeting('Olá, '.$notifiable->name)
            ->line("Não conseguimos cobrar {$total} referentes à fatura de {$period} no seu cartão.")
            ->line('Verifique os dados do cartão ou cadastre outro meio de pagamento. Novas tentativas serão feitas automaticamente antes da suspensão da conta.');
    }
}
