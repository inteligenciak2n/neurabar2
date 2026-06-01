<?php

namespace App\Actions\Support;

use App\Enums\Support\TicketStatus;
use App\Models\Support\Ticket;
use App\Models\User;
use App\Notifications\Support\TicketResolvedNotification;
use Illuminate\Validation\ValidationException;

class UpdateTicketStatusAction
{
    public function execute(Ticket $ticket, TicketStatus $newStatus, ?User $notifiableUser = null): Ticket
    {
        if ($ticket->status === $newStatus) {
            return $ticket;
        }

        if (in_array($ticket->status, [TicketStatus::Closed], true)) {
            throw ValidationException::withMessages([
                'status' => 'Um chamado encerrado não pode ter o status alterado.',
            ]);
        }

        $isClosed = in_array($newStatus, [TicketStatus::Resolved, TicketStatus::Closed], true);

        $ticket->update([
            'status' => $newStatus,
            'closed_at' => $isClosed ? now() : null,
        ]);

        if ($newStatus === TicketStatus::Resolved && $notifiableUser) {
            $notifiableUser->notify(new TicketResolvedNotification($ticket));
        }

        return $ticket->refresh();
    }
}
