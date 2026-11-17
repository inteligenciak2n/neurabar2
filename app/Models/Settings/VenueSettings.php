<?php

namespace App\Models\Settings;

use App\Concerns\BelongsToVenue;
use App\Enums\PaymentMethod;
use App\Models\Concerns\HasOperationalConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueSettings extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    public const MAX_WAITER_CALL_PHRASES = 8;

    /** @var list<string> */
    public const SUGGESTED_WAITER_CALL_PHRASES = [
        'Pedir a conta',
        'Gelo',
        'Guardanapo',
        'Talheres',
    ];

    protected $fillable = [
        'venue_id',
        'cover_charge',
        'service_fee_percent',
        'table_count',
        'tabs_enabled',
        'accepted_delivery_payment_methods',
        'delivery_enabled',
        'pickup_enabled',
        'waiter_call_phrases',
    ];

    protected function casts(): array
    {
        return [
            'cover_charge' => 'decimal:2',
            'service_fee_percent' => 'decimal:2',
            'table_count' => 'integer',
            'tabs_enabled' => 'boolean',
            'accepted_delivery_payment_methods' => 'array',
            'delivery_enabled' => 'boolean',
            'pickup_enabled' => 'boolean',
            'waiter_call_phrases' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public static function phrasesForVenue(string $venueId): array
    {
        $settings = static::withoutGlobalScopes()->where('venue_id', $venueId)->first();

        if ($settings === null || $settings->waiter_call_phrases === null) {
            return self::SUGGESTED_WAITER_CALL_PHRASES;
        }

        return self::normalizePhrases($settings->waiter_call_phrases);
    }

    /**
     * @return list<string>
     */
    public static function normalizePhrases(mixed $phrases): array
    {
        if (! is_array($phrases)) {
            return [];
        }

        $normalized = [];
        $seen = [];

        foreach ($phrases as $phrase) {
            if (! is_string($phrase)) {
                continue;
            }

            $trimmed = trim($phrase);

            if ($trimmed === '') {
                continue;
            }

            $key = mb_strtolower($trimmed);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = $trimmed;
        }

        return array_values(array_slice($normalized, 0, self::MAX_WAITER_CALL_PHRASES));
    }

    /**
     * Métodos de pagamento aceitos para delivery/retirada — se não configurado,
     * assume todos os métodos disponíveis (comportamento permissivo por padrão).
     *
     * @return list<string>
     */
    public function acceptedDeliveryPaymentMethods(): array
    {
        return $this->accepted_delivery_payment_methods ?? PaymentMethod::values();
    }
}
