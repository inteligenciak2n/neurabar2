<?php

namespace App\Jobs\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Notifications\Billing\InvoiceOverdue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class MarkInvoicesOverdueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $venueInvoices = VenueInvoice::query()
            ->where('status', InvoiceStatus::Open->value)
            ->where('due_date', '<', today())
            ->where('is_finalized', false)
            ->get();

        foreach ($venueInvoices as $invoice) {
            $invoice->update(['status' => InvoiceStatus::Overdue->value]);
            $this->notifyOwner($invoice);
        }

        $corporationInvoices = CorporationInvoice::query()
            ->where('status', InvoiceStatus::Open->value)
            ->where('due_date', '<', today())
            ->where('is_finalized', false)
            ->get();

        foreach ($corporationInvoices as $invoice) {
            $invoice->update(['status' => InvoiceStatus::Overdue->value]);
            $this->notifyOwner($invoice);
        }
    }

    private function notifyOwner(VenueInvoice|CorporationInvoice $invoice): void
    {
        $owner = $invoice->corporation?->owner;

        if ($owner) {
            Notification::send($owner, new InvoiceOverdue($invoice));
        }
    }
}
