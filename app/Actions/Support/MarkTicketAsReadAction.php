<?php

namespace App\Actions\Support;

use App\Enums\Support\TicketAuthorType;
use App\Models\Support\Ticket;
use App\Models\Support\TicketRead;

class MarkTicketAsReadAction
{
    /**
     * Mark all relevant unread messages as read for the given reader.
     *
     * @param  string  $readerId  UUID of the User or PlatformUser
     * @param  TicketAuthorType  $readerType  Who is reading (determines which messages count as "new")
     */
    public function execute(Ticket $ticket, string $readerId, TicketAuthorType $readerType): void
    {
        TicketRead::upsert(
            [
                'ticket_id' => $ticket->id,
                'reader_id' => $readerId,
                'reader_type' => $readerType->value,
                'last_read_at' => now(),
            ],
            ['ticket_id', 'reader_id', 'reader_type'],
            ['last_read_at']
        );
    }

    /**
     * Count unread messages for a reader in a ticket.
     * Unread = messages from the opposite party after last_read_at.
     */
    public static function unreadCount(Ticket $ticket, string $readerId, TicketAuthorType $readerType): int
    {
        $read = TicketRead::where('ticket_id', $ticket->id)
            ->where('reader_id', $readerId)
            ->where('reader_type', $readerType->value)
            ->first();

        // The unread messages are those written by the OTHER party
        $oppositeType = $readerType === TicketAuthorType::User
            ? TicketAuthorType::PlatformUser
            : TicketAuthorType::User;

        $query = $ticket->messages()
            ->where('author_type', $oppositeType->value)
            ->where('is_internal', false);

        if ($read) {
            $query->where('created_at', '>', $read->last_read_at);
        }

        return $query->count();
    }
}
