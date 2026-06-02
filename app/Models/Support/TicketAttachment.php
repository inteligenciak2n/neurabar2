<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'saas';

    protected $table = 'support_ticket_attachments';

    protected $fillable = [
        'message_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'message_id');
    }

    public function url(): string
    {
        return route('support.attachments.show', $this->id);
    }
}
