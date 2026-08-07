<?php

namespace App\Jobs\Billing;

use App\Actions\Subscription\ReactivateSubscriptionAction;
use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\InvoiceStatus;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reconciliação diária com o gateway.
 *
 * Webhook é entrega best-effort: um único evento perdido deixava a fatura
 * eternamente em aberto no NeuraBar mesmo já paga no Asaas — cliente cobrado,
 * conta suspensa. Este job compara o estado local com o do gateway.
 */
class ReconcileGatewayPaymentsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor = 3600;

    public function handle(PaymentGatewayContract $gateway, ReactivateSubscriptionAction $reactivator): void
    {
        foreach ($this->pendingInvoices() as $invoice) {
            try {
                $status = $gateway->fetchPaymentStatus((string) $invoice->gateway_payment_id);
            } catch (Throwable $exception) {
                Log::warning('Gateway reconciliation failed', [
                    'invoice_id' => $invoice->id,
                    'reason' => $exception->getMessage(),
                ]);

                continue;
            }

            $this->applyStatus($invoice, $status, $reactivator);
        }
    }

    /**
     * @return iterable<int, VenueInvoice|CorporationInvoice>
     */
    private function pendingInvoices(): iterable
    {
        $filter = function ($query): void {
            $query->whereIn('status', [InvoiceStatus::Open, InvoiceStatus::Overdue])
                ->whereNotNull('gateway_payment_id')
                ->where('created_at', '>=', now()->subMonths(3));
        };

        yield from VenueInvoice::query()->tap($filter)->cursor();
        yield from CorporationInvoice::query()->tap($filter)->cursor();
    }

    private function applyStatus(VenueInvoice|CorporationInvoice $invoice, ?string $status, ReactivateSubscriptionAction $reactivator): void
    {
        if ($status === 'paid') {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => $invoice->paid_at ?? now(),
                'is_finalized' => true,
            ]);

            $reactivator->execute($invoice);

            return;
        }

        if ($status === 'refunded') {
            $invoice->update([
                'status' => InvoiceStatus::Refunded,
                'is_finalized' => true,
            ]);

            return;
        }

        if ($status === 'overdue' && $invoice->status === InvoiceStatus::Open) {
            $invoice->update(['status' => InvoiceStatus::Overdue]);
        }
    }
}
