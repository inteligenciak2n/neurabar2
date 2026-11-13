<?php

namespace App\Jobs\Delivery;

use App\Enums\OrderStatus;
use App\Models\Tenant\Corporation;
use App\Services\TenantConnectionResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

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

    public int $tries = 3;

    public function backoff(): int
    {
        return 60;
    }

    public function handle(TenantConnectionResolver $resolver): void
    {
        $failed = [];

        foreach ($this->operationalConnections($resolver) as $connection) {
            try {
                $this->releaseOn($connection);
            } catch (Throwable $e) {
                // One unreachable database must not starve the remaining tenants.
                report($e);
                $failed[] = $connection;
            }
        }

        if ($failed !== []) {
            throw new RuntimeException('Failed to release delivery order codes on: '.implode(', ', $failed));
        }
    }

    /**
     * Only orders already Delivered give their code back; an in-flight order keeps it so a new
     * order can't reuse the number shown on the kitchen screen and tracking page.
     */
    private function releaseOn(string $connection): void
    {
        DB::connection($connection)
            ->table('delivery_orders')
            ->whereNotNull('code')
            ->where('code_expires_at', '<=', now())
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('orders.attendance_id', 'delivery_orders.attendance_id')
                    ->where('orders.status', '!=', OrderStatus::Delivered->value);
            })
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($connection): void {
                DB::connection($connection)
                    ->table('delivery_orders')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['code' => null, 'code_expires_at' => null]);
            });
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
