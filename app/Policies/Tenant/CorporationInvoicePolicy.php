<?php

namespace App\Policies\Tenant;

use App\Enums\UserRole;
use App\Models\Tenant\CorporationInvoice;
use App\Models\User;

class CorporationInvoicePolicy
{
    public function view(User $user, CorporationInvoice $invoice): bool
    {
        $corporationId = $user->currentVenue?->corporation_id;

        return $corporationId !== null
            && $invoice->corporation_id === $corporationId
            && in_array($user->currentVenueRole(), [
                UserRole::Owner,
                UserRole::GeneralManager,
            ], true);
    }

    public function pay(User $user, CorporationInvoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
