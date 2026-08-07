<?php

namespace App\Observers;

use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\SubscriptionStatusHistory;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Toda transição de status de assinatura vira uma linha de histórico.
 *
 * Ficar preso ao status atual do registro tornava impossível auditar
 * suspensões e reativações depois do fato.
 */
class SubscriptionStatusObserver
{
    public function updated(CorporationSubscription|VenueSubscription $subscription): void
    {
        if (! $subscription->wasChanged('status')) {
            return;
        }

        $from = $subscription->getOriginal('status');
        $to = $subscription->status;

        self::record(
            $subscription,
            is_object($from) ? $from->value : $from,
            is_object($to) ? $to->value : $to,
            $subscription->statusChangeReason,
        );

        $subscription->statusChangeReason = null;
    }

    /**
     * Também usado por atualizações em massa, que não disparam eventos de model.
     */
    public static function record(
        CorporationSubscription|VenueSubscription $subscription,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null,
    ): void {
        if ($fromStatus === $toStatus) {
            return;
        }

        try {
            $actor = Auth::user();

            SubscriptionStatusHistory::create([
                'subscription_type' => $subscription->getMorphClass(),
                'subscription_id' => $subscription->getKey(),
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
                'actor_id' => $actor?->getKey(),
                'actor_name' => $actor?->name,
            ]);
        } catch (Throwable $exception) {
            // O histórico é observador: nunca pode impedir a mudança de status.
            Log::error('subscription.status_history.failed', [
                'subscription_id' => $subscription->getKey(),
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
