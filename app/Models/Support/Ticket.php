<?php

namespace App\Models\Support;

use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'support';

    protected $table = 'support_tickets';

    protected $fillable = [
        'user_id',
        'venue_id',
        'category_id',
        'assigned_to',
        'subject',
        'status',
        'priority',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'closed_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(TicketRating::class, 'ticket_id');
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [TicketStatus::Open->value, TicketStatus::InProgress->value]);
    }

    public function scopeForUser(Builder $query, string $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeAssignedTo(Builder $query, string $agentId): void
    {
        $query->where('assigned_to', $agentId);
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isResolved(): bool
    {
        return $this->status === TicketStatus::Resolved;
    }
}
