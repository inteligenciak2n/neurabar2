<?php

namespace App\Models\Tenant;

use App\Enums\GatewayWebhookEventStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatewayWebhookEvent extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'gateway', 'event_id', 'event_type', 'payload',
        'status', 'received_at', 'processed_at', 'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => GatewayWebhookEventStatus::class,
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function markProcessed(): void
    {
        $this->update([
            'status' => GatewayWebhookEventStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => GatewayWebhookEventStatus::Failed,
            'error' => $error,
        ]);
    }
}
