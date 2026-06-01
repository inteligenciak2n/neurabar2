<?php

namespace App\Models\Support;

use App\Enums\Support\TicketAuthorType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'support';

    protected $table = 'support_ticket_messages';

    protected $fillable = [
        'ticket_id',
        'author_id',
        'author_type',
        'body',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'author_type' => TicketAuthorType::class,
            'is_internal' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'message_id');
    }

    public function isFromPlatform(): bool
    {
        return $this->author_type === TicketAuthorType::PlatformUser;
    }
}
