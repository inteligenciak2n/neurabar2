<?php

namespace App\Services\Subscription\Webhook;

use App\Enums\InvoiceStatus;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for invoice status changes coming from the gateway.
 *
 * `InvoiceStatus::canTransitionTo()` existed but nothing enforced it, so an
 * out-of-order webhook could move a canceled invoice back to paid.
 */
class InvoiceStatusTransitioner
{
    /**
     * @param  array<string, mixed>  $extraAttributes
     * @param  array<string, mixed>  $logContext
     * @return bool whether the transition was applied
     */
    public function transition(
        VenueInvoice|CorporationInvoice $invoice,
        InvoiceStatus $status,
        array $extraAttributes = [],
        array $logContext = [],
    ): bool {
        if (! $invoice->status->canTransitionTo($status)) {
            Log::warning('billing.invoice.transition_rejected', $logContext + [
                'invoice_id' => $invoice->getKey(),
                'from' => $invoice->status->value,
                'to' => $status->value,
            ]);

            return false;
        }

        $invoice->update($extraAttributes + [
            'status' => $status,
            'is_finalized' => $status->isFinalized(),
        ]);

        Log::info('billing.invoice.transitioned', $logContext + [
            'invoice_id' => $invoice->getKey(),
            'to' => $status->value,
        ]);

        return true;
    }
}
