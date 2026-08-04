<?php

namespace App\Models\Tenant;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SubscriptionStatusHistory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $table = 'subscription_status_history';

    protected $fillable = [
        'subscription_type', 'subscription_id',
        'from_status', 'to_status', 'reason', 'actor_id', 'actor_name',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => SubscriptionStatus::class,
            'to_status' => SubscriptionStatus::class,
        ];
    }

    public function subscription(): MorphTo
    {
        return $this->morphTo();
    }
}
