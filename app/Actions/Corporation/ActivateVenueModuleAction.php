<?php

namespace App\Actions\Corporation;

use App\Actions\Subscription\SubscribeModuleAction;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;

/**
 * Backoffice entry point for activating a venue module.
 *
 * It used to be a near copy of {@see SubscribeModuleAction}, and the copies had
 * already drifted (quantity clamping, `started_at` handling and cache flushing
 * all differed). The only intentional difference is that platform staff can act
 * on a venue blocked for billing reasons — precisely when someone needs to fix
 * the account.
 */
class ActivateVenueModuleAction
{
    public function __construct(private readonly SubscribeModuleAction $subscribe) {}

    public function execute(Venue $venue, string $moduleCode, int $quantity = 1): VenueModule
    {
        return $this->subscribe->execute($venue, $moduleCode, $quantity, enforceBillingStatus: false);
    }
}
