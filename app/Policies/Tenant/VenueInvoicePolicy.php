<?php

namespace App\Policies\Tenant;

use App\Enums\UserRole;
use App\Models\Tenant\VenueInvoice;
use App\Models\User;

class VenueInvoicePolicy
{
    public function view(User $user, VenueInvoice $invoice): bool
    {
        return $this->belongsToCurrentCorporation($user, $invoice)
            && $this->isSubscriptionManager($user);
    }

    /**
     * No modo unificado a fatura da venue é apenas o detalhamento da fatura
     * da corporation — deixá-la pagável permitia cobrar o mesmo período duas
     * vezes.
     */
    public function pay(User $user, VenueInvoice $invoice): bool
    {
        return $this->view($user, $invoice)
            && $invoice->corporation_invoice_id === null;
    }

    private function belongsToCurrentCorporation(User $user, VenueInvoice $invoice): bool
    {
        $corporationId = $user->currentVenue?->corporation_id;

        return $corporationId !== null
            && $invoice->venue?->corporation_id === $corporationId;
    }

    private function isSubscriptionManager(User $user): bool
    {
        return in_array($user->currentVenueRole(), [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true);
    }
}
