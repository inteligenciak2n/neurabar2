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

    public function pay(User $user, VenueInvoice $invoice): bool
    {
        return $this->view($user, $invoice);
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
