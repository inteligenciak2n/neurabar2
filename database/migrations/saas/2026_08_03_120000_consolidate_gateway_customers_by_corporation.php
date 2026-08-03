<?php

use App\Models\Tenant\Corporation;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gateway customers were keyed by user, so every employee that paid an invoice
 * created a separate customer at the provider with the same company CNPJ.
 * Billing belongs to the corporation, so ownership is re-pointed to it and the
 * duplicates are collapsed onto the oldest record (the one with the longest
 * payment history and anti-fraud reputation).
 *
 * Nothing is deleted at the provider: leftovers are only logged so the
 * backoffice can review them.
 */
return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        $rows = DB::connection('saas')->table('gateway_customers')
            ->where('owner_type', User::class)
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $corporationByUser = $this->corporationByUser($rows->pluck('owner_id')->all());

        $kept = [];

        foreach ($rows as $row) {
            $corporationId = $corporationByUser[$row->owner_id] ?? null;

            if (! $corporationId) {
                Log::warning('gateway.customer.orphan_on_migration', [
                    'gateway_customer_id' => $row->id,
                    'customer_id' => $row->customer_id,
                    'user_id' => $row->owner_id,
                ]);

                continue;
            }

            $key = $corporationId.'|'.$row->gateway;

            if (isset($kept[$key])) {
                Log::warning('gateway.customer.duplicate_on_migration', [
                    'gateway_customer_id' => $row->id,
                    'customer_id' => $row->customer_id,
                    'kept_customer_id' => $kept[$key],
                    'corporation_id' => $corporationId,
                ]);

                DB::connection('saas')->table('gateway_customers')->where('id', $row->id)->delete();

                continue;
            }

            $kept[$key] = $row->customer_id;

            DB::connection('saas')->table('gateway_customers')
                ->where('id', $row->id)
                ->update([
                    'owner_type' => Corporation::class,
                    'owner_id' => $corporationId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Consolidação de duplicados é irreversível: os vínculos originais
        // usuário → cliente do gateway não são recuperáveis.
    }

    /**
     * Users reach a corporation either by owning it or through the venues they
     * belong to. Anyone tied to more than one corporation is ambiguous and is
     * left untouched for manual review.
     *
     * @param  list<string>  $userIds
     * @return array<string, string>
     */
    private function corporationByUser(array $userIds): array
    {
        $links = DB::connection('saas')->table('user_venue')
            ->join('venues', 'venues.id', '=', 'user_venue.venue_id')
            ->whereIn('user_venue.user_id', $userIds)
            ->select('user_venue.user_id', 'venues.corporation_id')
            ->distinct()
            ->get();

        $owned = DB::connection('saas')->table('corporations')
            ->whereIn('owner_id', $userIds)
            ->select('owner_id', 'id')
            ->get();

        $map = [];

        foreach ($links as $link) {
            $map[$link->user_id][$link->corporation_id] = true;
        }

        foreach ($owned as $corporation) {
            $map[$corporation->owner_id][$corporation->id] = true;
        }

        $resolved = [];

        foreach ($map as $userId => $corporations) {
            if (count($corporations) === 1) {
                $resolved[$userId] = array_key_first($corporations);
            }
        }

        return $resolved;
    }
};
