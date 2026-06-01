<?php

namespace App\Policies\Support;

use App\Enums\Support\TicketStatus;
use App\Models\Support\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id && $ticket->isOpen();
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id && $ticket->isOpen();
    }

    public function rate(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id
            && $ticket->status === TicketStatus::Resolved
            && $ticket->rating === null;
    }
}
