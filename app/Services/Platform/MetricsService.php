<?php

namespace App\Services\Platform;

use App\Models\Tenant\Corporation;
use Illuminate\Support\Facades\Cache;

class MetricsService
{
    public function calculateMRR(): float
    {
        return (float) Corporation::where('active', true)
            ->where(fn ($q) => $q->whereNull('plan_end_date')->orWhere('plan_end_date', '>=', today()))
            ->sum('subscription_value');
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
