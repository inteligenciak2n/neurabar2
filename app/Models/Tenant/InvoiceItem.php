<?php

namespace App\Models\Tenant;

use App\Enums\InvoiceItemType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'invoice_type', 'invoice_id', 'type', 'description',
        'module_code', 'period', 'quantity', 'unit_amount', 'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceItemType::class,
            'quantity' => 'integer',
            'unit_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function invoice(): MorphTo
    {
        return $this->morphTo();
    }
}
