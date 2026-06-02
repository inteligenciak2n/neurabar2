<?php

namespace App\Http\Controllers\Support;

use App\Actions\Support\MarkTicketAsReadAction;
use App\Actions\Support\ReplyToTicketAction;
use App\Enums\Support\TicketAuthorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ReplyTicketRequest;
use App\Models\Support\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class TicketMessageController extends Controller
{
    public function store(string $ticketId, ReplyTicketRequest $request, ReplyToTicketAction $action, MarkTicketAsReadAction $markRead): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $ticket = Ticket::on('saas')->where('id', $ticketId)->firstOrFail();

        abort_unless($ticket->user_id === $user->id, 403);

        $action->execute($ticket, $user, $request);

        // Sending a message implies the user has read everything
        $markRead->execute($ticket, $user->id, TicketAuthorType::User);

        return back()->with('success', 'Mensagem enviada.');
    }
}
