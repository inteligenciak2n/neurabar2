<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Support\Ticket;
use App\Models\Support\TicketAttachment;
use App\Models\Support\Tutorial;
use App\Models\Support\TutorialCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TutorialController extends Controller
{
    public function index(): InertiaResponse
    {
        $categories = TutorialCategory::on('support')
            ->where('active', true)
            ->with(['publishedTutorials' => fn ($q) => $q->select('id', 'category_id', 'title', 'slug', 'summary', 'featured_image', 'position')->orderBy('position')])
            ->orderBy('position')
            ->get();

        return Inertia::render('Support/Tutorials/Index', [
            'categories' => $categories,
        ]);
    }

    public function show(string $slug): InertiaResponse
    {
        $tutorial = Tutorial::on('support')
            ->published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Tutorial::on('support')
            ->published()
            ->where('category_id', $tutorial->category_id)
            ->where('id', '!=', $tutorial->id)
            ->orderBy('position')
            ->limit(4)
            ->get(['id', 'title', 'slug', 'summary']);

        return Inertia::render('Support/Tutorials/Show', [
            'tutorial' => $tutorial,
            'related' => $related,
        ]);
    }

    public function attachment(Request $request, string $attachmentId)
    {
        $attachment = TicketAttachment::on('support')
            ->where('id', $attachmentId)
            ->firstOrFail();

        $user = $request->user();
        $ticket = Ticket::on('support')
            ->where('id', function ($query) use ($attachment) {
                $query->select('ticket_id')
                    ->from('support_ticket_messages')
                    ->where('id', $attachment->message_id)
                    ->limit(1);
            })
            ->firstOrFail();

        abort_unless($ticket->user_id === $user->id, 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return response()->file(
            Storage::disk('local')->path($attachment->path),
            ['Content-Disposition' => 'inline; filename="'.$attachment->filename.'"']
        );
    }
}
