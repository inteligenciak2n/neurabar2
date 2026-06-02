<?php

namespace App\Http\Controllers\Support;

use App\Enums\Support\TicketAuthorType;
use App\Http\Controllers\Controller;
use App\Models\Support\Ticket;
use App\Models\Support\TicketRead;
use App\Models\Support\TutorialCategory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $openTickets = Ticket::on('saas')
            ->forUser($user->id)
            ->open()
            ->with('category')
            ->latest()
            ->limit(5)
            ->get();
        $openTickets = $this->attachUnreadCount($openTickets, $user->id);

        $recentlyResolved = Ticket::on('saas')
            ->forUser($user->id)
            ->where('status', 'resolved')
            ->with(['category', 'rating'])
            ->latest('closed_at')
            ->limit(3)
            ->get();

        $tutorialCategories = TutorialCategory::on('saas')
            ->where('active', true)
            ->with(['publishedTutorials' => fn ($q) => $q->select('id', 'category_id', 'title', 'slug', 'summary')->orderBy('position')->limit(4)])
            ->orderBy('position')
            ->get();

        return Inertia::render('Support/Dashboard', [
            'openTickets' => $openTickets,
            'recentlyResolved' => $recentlyResolved,
            'tutorialCategories' => $tutorialCategories,
        ]);
    }

    private function attachUnreadCount(EloquentCollection $tickets, string $userId)
    {
        $ticketIds = $tickets->pluck('id');
        $reads = TicketRead::whereIn('ticket_id', $ticketIds)
            ->where('reader_id', $userId)
            ->where('reader_type', TicketAuthorType::User->value)
            ->get()
            ->keyBy('ticket_id');

        $tickets->transform(function (Ticket $ticket) use ($reads) {
            $read = $reads->get($ticket->id);
            $query = $ticket->messages()
                ->where('author_type', TicketAuthorType::PlatformUser->value)
                ->where('is_internal', false);

            if ($read) {
                $query->where('created_at', '>', $read->last_read_at);
            }

            $ticket->unread_count = $query->count();

            return $ticket;
        });

        return $tickets;
    }
}
