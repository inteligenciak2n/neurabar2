<?php

namespace App\Actions\Support;

use App\Enums\Support\TicketStatus;
use App\Http\Requests\Support\RateTicketRequest;
use App\Models\Support\Ticket;
use App\Models\Support\TicketRating;
use Illuminate\Validation\ValidationException;

class RateTicketAction
{
    public function execute(Ticket $ticket, string $userId, RateTicketRequest $request): TicketRating
    {
        if ($ticket->user_id !== $userId) {
            throw ValidationException::withMessages([
                'ticket' => 'Você não tem permissão para avaliar este chamado.',
            ]);
        }

        if ($ticket->status !== TicketStatus::Resolved) {
            throw ValidationException::withMessages([
                'ticket' => 'Só é possível avaliar chamados resolvidos.',
            ]);
        }

        return TicketRating::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'score' => $request->validated('score'),
                'comment' => $request->validated('comment'),
            ]
        );
    }
}
