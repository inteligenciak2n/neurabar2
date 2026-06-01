<?php

namespace App\Actions\Support;

use App\Models\Platform\PlatformUser;
use App\Models\Support\Ticket;

class AssignTicketAction
{
    public function execute(Ticket $ticket, ?PlatformUser $agent): Ticket
    {
        $ticket->update([
            'assigned_to' => $agent?->id,
        ]);

        return $ticket->refresh();
    }
}
