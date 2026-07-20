<?php

namespace App\Services\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\Cache;

class BillingStatusService
{
    private const CACHE_TTL_SECONDS = 60;

    /**
     * Determina se o acesso de uma venue está bloqueado por questões de faturamento.
     *
     * Regras:
     * - Sem subscription ativa/trial da corporation = bloqueado.
     * - Modo unificado: bloqueio global se corporation está suspended/canceled.
     * - Modo per_venue: bloqueio se a venue está suspended/canceled.
     * - past_due NUNCA bloqueia (grace period mantém acesso).
     */
    public static function isBlocked(Venue $venue): bool
    {
        return Cache::remember(
            self::cacheKey($venue),
            self::CACHE_TTL_SECONDS,
            function () use ($venue): bool {
                $corporation = $venue->corporation;

                if (! $corporation?->subscription) {
                    return true;
                }

                if ($corporation->isBillingUnified()) {
                    return in_array($corporation->subscription->status, [
                        SubscriptionStatus::Suspended,
                        SubscriptionStatus::Canceled,
                    ], true) || self::isEnded($corporation->subscription->ended_at);
                }

                $venueSubscription = $venue->subscription;

                if (! $venueSubscription) {
                    return true;
                }

                return in_array($venueSubscription->status, [
                    SubscriptionStatus::Suspended,
                    SubscriptionStatus::Canceled,
                ], true) || self::isEnded($venueSubscription->ended_at);
            }
        );
    }

    public static function flushBlockedCache(Venue $venue): void
    {
        Cache::forget(self::cacheKey($venue));
    }

    private static function cacheKey(Venue $venue): string
    {
        return "billing.blocked.{$venue->id}";
    }

    private static function isEnded(?\DateTimeInterface $endedAt): bool
    {
        return $endedAt !== null && $endedAt <= now();
    }

    /**
     * Verifica se a venue (ou corporation, no modo unificado) está em grace period.
     */
    public static function isInGracePeriod(Venue $venue): bool
    {
        $corporation = $venue->corporation;

        if (! $corporation?->subscription) {
            return false;
        }

        if ($corporation->isBillingUnified()) {
            return $corporation->subscription->status === SubscriptionStatus::PastDue;
        }

        $venueSubscription = $venue->subscription;

        if (! $venueSubscription) {
            return false;
        }

        return $venueSubscription->status === SubscriptionStatus::PastDue;
    }
}
