<?php

namespace App\Jobs\Subscription;

use App\Enums\GatewayWebhookEventStatus;
use App\Enums\ProfileEnum;
use App\Models\Tenant\GatewayWebhookEvent;
use App\Models\User;
use App\Notifications\Subscription\StaleGatewayWebhookEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Varredura de eventos de webhook parados.
 *
 * Um evento que ficou `pending` (o job morreu antes de rodar) ou `failed`
 * (esgotou as tentativas) nunca mais era tocado: a fatura seguia em aberto e
 * ninguém era avisado. O Asaas mantém o histórico por 14 dias, então há janela
 * de reprocessamento — mas só se alguém souber que existe pendência.
 */
class SweepStaleGatewayWebhookEventsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor = 3600;

    /**
     * Eventos parados há mais tempo que isso são reenfileirados.
     */
    private const REQUEUE_AFTER_MINUTES = 30;

    /**
     * Eventos ainda não processados depois disso viram alerta para o backoffice.
     */
    private const ALERT_AFTER_HOURS = 24;

    public function handle(): void
    {
        $this->requeueStalePending();
        $this->alertUnresolved();
    }

    private function requeueStalePending(): void
    {
        GatewayWebhookEvent::query()
            ->where('status', GatewayWebhookEventStatus::Pending)
            ->where('received_at', '<=', now()->subMinutes(self::REQUEUE_AFTER_MINUTES))
            ->where('received_at', '>', now()->subHours(self::ALERT_AFTER_HOURS))
            ->orderBy('received_at')
            ->chunkById(100, function ($events): void {
                foreach ($events as $event) {
                    Log::warning('gateway.webhook.requeued', [
                        'webhook_event_id' => $event->id,
                        'event_type' => $event->event_type,
                        'received_at' => $event->received_at?->toIso8601String(),
                    ]);

                    ProcessGatewayWebhookJob::dispatch((string) $event->id);
                }
            });
    }

    private function alertUnresolved(): void
    {
        $threshold = now()->subHours(self::ALERT_AFTER_HOURS);

        $stale = GatewayWebhookEvent::query()
            ->whereIn('status', [GatewayWebhookEventStatus::Pending, GatewayWebhookEventStatus::Failed])
            ->where('received_at', '<=', $threshold)
            ->count();

        if ($stale === 0) {
            return;
        }

        Log::critical('gateway.webhook.stale_backlog', [
            'count' => $stale,
            'older_than_hours' => self::ALERT_AFTER_HOURS,
        ]);

        $admins = User::query()
            ->whereIn('profile', [ProfileEnum::SuperAdmin, ProfileEnum::Finance])
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new StaleGatewayWebhookEvents($stale, self::ALERT_AFTER_HOURS));
    }
}
