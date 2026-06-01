<?php

namespace App\Actions\Support;

use App\Models\User;
use App\Models\Support\Ticket;

class AssignTicketAction
{
    public function execute(Ticket $ticket, ?User $agent): Ticket
    {
        $ticket->update([
            'assigned_to' => $agent?->id,
        ]);

        return $ticket->refresh();
    }
}
