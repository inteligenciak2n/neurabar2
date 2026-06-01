<?php

namespace App\Http\Controllers\Support;

use App\Actions\Support\OpenTicketAction;
use App\Actions\Support\UpdateTicketStatusAction;
use App\Enums\Support\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\OpenTicketRequest;
use App\Models\Support\Ticket;
use App\Models\Support\TicketCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tickets = Ticket::on('support')
            ->forUser($user->id)
            ->with(['category', 'rating'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(Request $request): Response
    {
        $categories = TicketCategory::on('support')
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'icon']);

        return Inertia::render('Support/Tickets/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(OpenTicketRequest $request, OpenTicketAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $ticket = $action->execute($user, $request);

        return redirect()->route('support.tickets.show', $ticket->id)
            ->with('success', 'Chamado aberto com sucesso.');
    }

    public function show(Request $request, string $ticketId): Response
    {
        $user = $request->user();

        $ticket = Ticket::on('support')
            ->where('id', $ticketId)
            ->with([
                'category', 
                'messages' => fn ($q) => $q->where('is_internal', false)->with('attachments'),
                'rating'
                ])
            ->firstOrFail();

        abort_unless($ticket->user_id === $user->id, 403);

        return Inertia::render('Support/Tickets/Show', [
            'ticket' => $ticket,
            'canReply' => $ticket->isOpen(),
            'canRate' => $ticket->isResolved() && $ticket->rating === null,
        ]);
    }

    public function close(Request $request, string $ticketId, UpdateTicketStatusAction $action): RedirectResponse
    {
        $user = $request->user();

        $ticket = Ticket::on('support')->where('id', $ticketId)->firstOrFail();

        abort_unless($ticket->user_id === $user->id, 403);

        $action->execute($ticket, TicketStatus::Closed);

        return back()->with('success', 'Chamado encerrado.');
    }
}
