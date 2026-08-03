<?php

namespace App\Policies\Tenant;

use App\Enums\UserRole;
use App\Models\Tenant\Venue;
use App\Models\User;

class VenuePolicy
{
    /**
     * Gerir a assinatura e os módulos de uma venue exige papel de gestão na
     * venue corrente e que a venue alvo pertença à mesma corporation.
     */
    public function manageSubscription(User $user, Venue $venue): bool
    {
        return $this->isSubscriptionManager($user)
            && $venue->corporation_id === $user->currentVenue?->corporation_id;
    }

    private function isSubscriptionManager(User $user): bool
    {
        return in_array($user->currentVenueRole(), [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true);
    }
}
