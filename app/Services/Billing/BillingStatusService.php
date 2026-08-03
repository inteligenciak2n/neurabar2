<?php

namespace App\Services\Billing;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
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
                    return self::hasBlockingStatus($corporation->subscription);
                }

                $venueSubscription = $venue->subscription;

                if (! $venueSubscription) {
                    return true;
                }

                return self::hasBlockingStatus($venueSubscription);
            }
        );
    }

    /**
     * Bloqueio do canal público (QR code do cliente final).
     *
     * Duas diferenças em relação a isBlocked(): ausência de assinatura não
     * bloqueia — derrubar o atendimento de um estabelecimento por
     * inconsistência de dados é pior do que continuar servindo — e a consulta
     * ignora o filtro de status das relações `subscription()`, que escondem
     * justamente as assinaturas suspensas/canceladas que precisamos enxergar.
     */
    public static function isSuspended(Venue $venue): bool
    {
        return Cache::remember(
            self::cacheKey($venue).'.suspended',
            self::CACHE_TTL_SECONDS,
            function () use ($venue): bool {
                $corporationSubscription = CorporationSubscription::query()
                    ->where('corporation_id', $venue->corporation_id)
                    ->latest('started_at')
                    ->first();

                if (! $corporationSubscription) {
                    return false;
                }

                if ($corporationSubscription->billing_mode === BillingMode::Unified) {
                    return self::hasBlockingStatus($corporationSubscription);
                }

                $venueSubscription = VenueSubscription::query()
                    ->where('venue_id', $venue->id)
                    ->latest('started_at')
                    ->first();

                if (! $venueSubscription) {
                    return false;
                }

                return self::hasBlockingStatus($venueSubscription);
            }
        );
    }

    private static function hasBlockingStatus(CorporationSubscription|VenueSubscription $subscription): bool
    {
        return in_array($subscription->status, [
            SubscriptionStatus::Suspended,
            SubscriptionStatus::Canceled,
        ], true) || self::isEnded($subscription->ended_at);
    }

    public static function flushBlockedCache(Venue $venue): void
    {
        Cache::forget(self::cacheKey($venue));
        Cache::forget(self::cacheKey($venue).'.suspended');
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
