<?php

namespace App\Actions\Platform;

use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;

class AssignPlanToCorporationAction
{
    public function execute(Corporation $corporation, PlanCatalog $plan, array $data): void
    {
        $corporation->update([
            'plan_catalog_id' => $plan->id,
            'plan_name' => $plan->name,
            'subscription_value' => $data['subscription_value'] ?? $plan->monthly_price,
            'plan_start_date' => $data['plan_start_date'] ?? today(),
            'plan_end_date' => $data['plan_end_date'] ?? null,
        ]);
    }
}
