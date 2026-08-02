<?php

namespace App\Models\Tenant;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'user_id',
        'gateway',
        'gateway_token',
        'brand',
        'last4',
        'holder_name',
        'holder_document',
        'expiration_month',
        'expiration_year',
        'is_default',
        'billing_address_json',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'expiration_month' => 'integer',
            'expiration_year' => 'integer',
            'billing_address_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set this method as default and unset any other default for the same user.
     */
    public function setAsDefault(): void
    {
        self::query()
            ->where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function isExpired(): bool
    {
        if ($this->expiration_month === null || $this->expiration_year === null) {
            return false;
        }

        $now = now();

        return $this->expiration_year < $now->year
            || ($this->expiration_year === $now->year && $this->expiration_month < $now->month);
    }
}
