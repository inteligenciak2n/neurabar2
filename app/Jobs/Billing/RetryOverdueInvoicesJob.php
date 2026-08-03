<?php

namespace App\Jobs\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentSaasMethod;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Models\User;
use App\Notifications\Billing\InvoiceOverdue;
use App\Services\Subscription\PaymentSaasService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Régua de cobrança (dunning).
 *
 * Sem ela, uma fatura que falhava no cartão ficava parada até a suspensão: a
 * plataforma nunca tentava cobrar de novo, o que transforma falha temporária de
 * emissor em churn. Só cobre assinaturas faturadas localmente — quando existe
 * assinatura recorrente no gateway, o próprio gateway executa a régua dele.
 */
class RetryOverdueInvoicesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Dias após o vencimento em que uma nova tentativa é feita. */
    private const RETRY_DAYS = [1, 3, 5, 7];

    public int $uniqueFor = 3600;

    public function handle(PaymentSaasService $paymentService): void
    {
        foreach ($this->dueInvoices() as $invoice) {
            $this->retry($invoice, $paymentService);
        }
    }

    /**
     * @return iterable<int, VenueInvoice|CorporationInvoice>
     */
    private function dueInvoices(): iterable
    {
        $dates = array_map(fn (int $days): string => now()->subDays($days)->toDateString(), self::RETRY_DAYS);

        yield from VenueInvoice::query()
            ->where('status', InvoiceStatus::Overdue)
            ->where('is_finalized', false)
            ->whereIn('due_date', $dates)
            ->whereHas('venue.subscription', function ($query): void {
                $query->whereNull('gateway_subscription_id');
            })
            ->cursor();

        yield from CorporationInvoice::query()
            ->where('status', InvoiceStatus::Overdue)
            ->where('is_finalized', false)
            ->whereIn('due_date', $dates)
            ->whereHas('corporation.subscription', function ($query): void {
                $query->whereNull('gateway_subscription_id');
            })
            ->cursor();
    }

    private function retry(VenueInvoice|CorporationInvoice $invoice, PaymentSaasService $paymentService): void
    {
        $owner = $invoice->corporation?->owner;

        if (! $owner instanceof User) {
            return;
        }

        $method = $owner->paymentMethods()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->first();

        if (! $method || $method->isExpired()) {
            Notification::send($owner, new InvoiceOverdue($invoice));

            return;
        }

        try {
            $result = $paymentService->charge($invoice, [
                'method' => PaymentSaasMethod::CreditCard->value,
                'payment_method_id' => $method->id,
            ], $owner);
        } catch (Throwable $exception) {
            Log::warning('Dunning retry failed', [
                'invoice_id' => $invoice->id,
                'reason' => $exception->getMessage(),
            ]);

            return;
        }

        if ($result['status'] !== 'paid') {
            Notification::send($owner, new InvoiceOverdue($invoice));
        }
    }
}
