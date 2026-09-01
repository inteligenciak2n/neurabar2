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

    protected $fillable = [
        'venue_id',
        'cover_charge',
        'service_fee_percent',
        'table_count',
        'accepted_delivery_payment_methods',
        'delivery_enabled',
        'pickup_enabled',
    ];

    protected function casts(): array
    {
        return [
            'cover_charge' => 'decimal:2',
            'service_fee_percent' => 'decimal:2',
            'table_count' => 'integer',
            'accepted_delivery_payment_methods' => 'array',
            'delivery_enabled' => 'boolean',
            'pickup_enabled' => 'boolean',
        ];
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
