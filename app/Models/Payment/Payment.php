<?php

namespace App\Models\Payment;

use App\Models\Orders\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'attendance_id',
        'items_total',
        'cover_charge_total',
        'service_fee_total',
        'grand_total',
        'party_size',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'items_total' => 'decimal:2',
            'cover_charge_total' => 'decimal:2',
            'service_fee_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'party_size' => 'integer',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(PaymentItem::class);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }
}
