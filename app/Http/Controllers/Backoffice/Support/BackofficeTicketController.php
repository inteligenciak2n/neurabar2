<?php

namespace App\Http\Controllers\Backoffice\Support;

use App\Actions\Support\AgentReplyToTicketAction;
use App\Actions\Support\AssignTicketAction;
use App\Actions\Support\MarkTicketAsReadAction;
use App\Actions\Support\UpdateTicketStatusAction;
use App\Enums\ProfileEnum;
use App\Enums\Support\TicketAuthorType;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ReplyTicketRequest;
use App\Http\Requests\Support\UpdateTicketRequest;
use App\Models\Support\Ticket;
use App\Models\Support\TicketCategory;
use App\Models\Support\TicketRead;
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

        $tickets = Ticket::on('saas')
            ->with('category')
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn ($q) => $q->where('priority', $filters['priority']))
            ->when(isset($filters['assigned_to']), fn ($q) => $q->where('assigned_to', $filters['assigned_to']))
            ->when(isset($filters['search']), fn ($q) => $q->where('subject', 'ilike', "%{$filters['search']}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        /** @var User $agent */
        $agent = $request->user();

        // Attach unread count per ticket for this agent
        $ticketIds = $tickets->pluck('id');
        $reads = TicketRead::whereIn('ticket_id', $ticketIds)
            ->where('reader_id', $agent->id)
            ->where('reader_type', TicketAuthorType::PlatformUser->value)
            ->get()
            ->keyBy('ticket_id');

        $tickets->getCollection()->transform(function (Ticket $ticket) use ($reads) {
            $read = $reads->get($ticket->id);
            $query = $ticket->messages()
                ->where('author_type', TicketAuthorType::User->value);

            if ($read) {
                $query->where('created_at', '>', $read->last_read_at);
            }

            $ticket->unread_count = $query->count();

            return $ticket;
        });

        // Enrich with user data from main database (cross-DB)
        $userIds = $tickets->pluck('user_id')->unique()->filter();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $agents = User::whereIn('profile', ProfileEnum::platformProfiles())->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Backoffice/Support/Tickets/Index', [
            'tickets' => $tickets,
            'users' => $users,
            'agents' => $agents,
            'categories' => TicketCategory::on('saas')->where('active', true)->get(['id', 'name']),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, string $ticketId, MarkTicketAsReadAction $markRead): Response
    {
        /** @var User $agent */
        $agent = $request->user();

        $ticket = Ticket::on('saas')
            ->where('id', $ticketId)
            ->with(['category', 'messages.attachments', 'rating'])
            ->firstOrFail();

        $markRead->execute($ticket, $agent->id, TicketAuthorType::PlatformUser);

        $ticketUser = User::find($ticket->user_id, ['id', 'name', 'email']);
        $assignedAgent = $ticket->assigned_to
            ? User::find($ticket->assigned_to, ['id', 'name', 'email'])
            : null;
        $agents = User::whereIn('profile', ProfileEnum::platformProfiles())->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Backoffice/Support/Tickets/Show', [
            'ticket' => $ticket,
            'ticketUser' => $ticketUser,
            'assignedAgent' => $assignedAgent,
            'agents' => $agents,
            'categories' => TicketCategory::on('saas')->where('active', true)->get(['id', 'name']),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function update(string $ticketId, UpdateTicketRequest $request, UpdateTicketStatusAction $statusAction, AssignTicketAction $assignAction): RedirectResponse
    {
        $ticket = Ticket::on('saas')->where('id', $ticketId)->firstOrFail();

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

    public function reply(string $ticketId, ReplyTicketRequest $request, AgentReplyToTicketAction $action, MarkTicketAsReadAction $markRead): RedirectResponse
    {
        /** @var User $agent */
        $agent = $request->user();

        $ticket = Ticket::on('saas')->where('id', $ticketId)->firstOrFail();

        $action->execute($ticket, $agent, $request);

        // Sending a reply implies the agent has read everything
        $markRead->execute($ticket, $agent->id, TicketAuthorType::PlatformUser);

        return back()->with('success', 'Mensagem enviada.');
    }
}
