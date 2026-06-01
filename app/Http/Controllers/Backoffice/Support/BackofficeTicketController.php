<?php

namespace App\Http\Controllers\Backoffice\Support;

use App\Actions\Support\AgentReplyToTicketAction;
use App\Actions\Support\AssignTicketAction;
use App\Actions\Support\UpdateTicketStatusAction;
use App\Enums\ProfileEnum;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ReplyTicketRequest;
use App\Http\Requests\Support\UpdateTicketRequest;
use App\Models\Support\Ticket;
use App\Models\Support\TicketCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackofficeTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'priority', 'assigned_to', 'search']);

        $tickets = Ticket::on('support')
            ->with('category')
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn ($q) => $q->where('priority', $filters['priority']))
            ->when(isset($filters['assigned_to']), fn ($q) => $q->where('assigned_to', $filters['assigned_to']))
            ->when(isset($filters['search']), fn ($q) => $q->where('subject', 'ilike', "%{$filters['search']}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Enrich with user data from main database (cross-DB)
        $userIds = $tickets->pluck('user_id')->unique()->filter();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $agents = User::whereIn('profile', ProfileEnum::platformProfiles())->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Backoffice/Support/Tickets/Index', [
            'tickets' => $tickets,
            'users' => $users,
            'agents' => $agents,
            'categories' => TicketCategory::on('support')->where('active', true)->get(['id', 'name']),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'filters' => $filters,
        ]);
    }

    public function show(string $ticketId): Response
    {
        $ticket = Ticket::on('support')
            ->where('id', $ticketId)
            ->with(['category', 'messages.attachments', 'rating'])
            ->firstOrFail();

        $user = User::find($ticket->user_id, ['id', 'name', 'email']);
        $assignedAgent = $ticket->assigned_to
            ? User::find($ticket->assigned_to, ['id', 'name', 'email'])
            : null;
        $agents = User::whereIn('profile', ProfileEnum::platformProfiles())->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Backoffice/Support/Tickets/Show', [
            'ticket' => $ticket,
            'ticketUser' => $user,
            'assignedAgent' => $assignedAgent,
            'agents' => $agents,
            'categories' => TicketCategory::on('support')->where('active', true)->get(['id', 'name']),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function update(string $ticketId, UpdateTicketRequest $request, UpdateTicketStatusAction $statusAction, AssignTicketAction $assignAction): RedirectResponse
    {
        $ticket = Ticket::on('support')->where('id', $ticketId)->firstOrFail();

        if ($request->validated('status') !== null) {
            $newStatus = TicketStatus::from($request->validated('status'));
            $notifiableUser = User::find($ticket->user_id);
            $statusAction->execute($ticket, $newStatus, $notifiableUser);
        }

        if ($request->has('assigned_to')) {
            $agent = $request->validated('assigned_to')
                ? User::find($request->validated('assigned_to'))
                : null;
            $assignAction->execute($ticket, $agent);
        }

        if ($request->validated('priority') !== null) {
            $ticket->update(['priority' => $request->validated('priority')]);
        }

        return back()->with('success', 'Chamado atualizado.');
    }

    public function reply(string $ticketId, ReplyTicketRequest $request, AgentReplyToTicketAction $action): RedirectResponse
    {
        /** @var User $agent */
        $agent = $request->user();

        $ticket = Ticket::on('support')->where('id', $ticketId)->firstOrFail();

        $action->execute($ticket, $agent, $request);

        return back()->with('success', 'Mensagem enviada.');
    }
}
