<?php

namespace App\Http\Controllers\Support;

use App\Actions\Support\RateTicketAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\RateTicketRequest;
use App\Models\Support\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketRatingController extends Controller
{
    public function store(string $ticketId, RateTicketRequest $request, RateTicketAction $action): RedirectResponse
    {
        $user = $request->user();

        $ticket = Ticket::on('saas')->where('id', $ticketId)->firstOrFail();

        $action->execute($ticket, $user->id, $request);

        return back()->with('success', 'Avaliação enviada. Obrigado!');
    }
}
