<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorporationInvoice extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $connection = 'saas';

    protected $fillable = [
        'corporation_id', 'corporation_subscription_id', 'affiliate_code_id',
        'period', 'due_date', 'status', 'is_finalized',
        'base_value', 'modules_value', 'metered_value', 'dedicated_surcharge', 'discount_value', 'total_value',
        'gateway_payment_id', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_finalized' => 'boolean',
            'base_value' => 'decimal:2',
            'modules_value' => 'decimal:2',
            'metered_value' => 'decimal:2',
            'dedicated_surcharge' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'total_value' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
