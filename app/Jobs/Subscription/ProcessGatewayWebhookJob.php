<?php

namespace App\Jobs\Subscription;

use App\Enums\GatewayWebhookEventStatus;
use App\Enums\ProfileEnum;
use App\Models\Tenant\GatewayWebhookEvent;
use App\Models\User;
use App\Notifications\Subscription\GatewayAccessTokenExpiringSoon;
use App\Services\Subscription\PaymentSaasService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ProcessGatewayWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $webhookEventId) {}

    public int $tries = 5;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(PaymentSaasService $paymentService): void
    {
        $event = GatewayWebhookEvent::find($this->webhookEventId);

        if (! $event || $event->status === GatewayWebhookEventStatus::Processed) {
            return;
        }

        try {
            if ($event->event_type === 'ACCESS_TOKEN_EXPIRING_SOON') {
                $this->notifyAccessTokenExpiringSoon($event);
            } else {
                $paymentService->handleWebhook($event->gateway, $event->payload);
            }

            $event->markProcessed();
        } catch (Throwable $e) {
            $event->markFailed($e->getMessage());

            throw $e;
        }
    }

    private function notifyAccessTokenExpiringSoon(GatewayWebhookEvent $event): void
    {
        $admins = User::query()->where('profile', ProfileEnum::SuperAdmin)->get();

        Notification::send($admins, new GatewayAccessTokenExpiringSoon($event));
    }
}
