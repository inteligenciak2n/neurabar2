<?php

namespace App\Jobs\Delivery;

use App\Models\Tenant\Corporation;
use App\Services\TenantConnectionResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Releases delivery order codes past their TTL so the 6-digit space is reusable.
 * The order keeps its number in `orders.order_number`, so tracking still shows it.
 */
class ReleaseExpiredDeliveryOrderCodesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(TenantConnectionResolver $resolver): void
    {
        foreach ($this->operationalConnections($resolver) as $connection) {
            DB::connection($connection)
                ->table('delivery_orders')
                ->whereNotNull('code')
                ->where('code_expires_at', '<=', now())
                ->update(['code' => null, 'code_expires_at' => null]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function operationalConnections(TenantConnectionResolver $resolver): array
    {
        $shared = collect(array_keys(config('database.connections')))
            ->filter(fn (string $name): bool => str_starts_with($name, 'operation_default_'));

        $dedicated = Corporation::on('saas')
            ->where('is_dedicated', true)
            ->whereNotNull('self_connection')
            ->pluck('self_connection');

        foreach ($dedicated as $connection) {
            $resolver->registerDedicatedConnection($connection);
        }

        return $shared->merge($dedicated)->unique()->values()->all();
    }
}
