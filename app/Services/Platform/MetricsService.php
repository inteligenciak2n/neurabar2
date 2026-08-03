<?php

namespace App\Services\Platform;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use Illuminate\Support\Facades\Cache;

class MetricsService
{
    /**
     * Receita recorrente mensal, em centavos.
     */
    public function calculateMRR(): int
    {
        return (int) CorporationSubscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })
            ->whereHas('corporation', function ($query): void {
                $query->where('active', true);
            })
            ->join('plan_catalogs', 'plan_catalogs.id', '=', 'corporation_subscriptions.plan_catalog_id')
            ->sum('plan_catalogs.monthly_price');
    }

    /** @return array<string, mixed> */
    public function operationalSummary(): array
    {
        return Cache::remember('platform.metrics.summary', 300, function () {
            $total = Corporation::count();
            $active = Corporation::where('active', true)->count();
            $mrr = $this->calculateMRR();

            return [
                'total_corporations' => $total,
                'active_corporations' => $active,
                'inactive_corporations' => $total - $active,
                'mrr' => $mrr,
            ];
        });
    }
}
