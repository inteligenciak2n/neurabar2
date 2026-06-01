<?php

namespace App\Actions\Support;

use App\Enums\Support\TicketAuthorType;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use App\Http\Requests\Support\OpenTicketRequest;
use App\Models\Support\Ticket;
use App\Models\User;
use App\Notifications\Support\TicketOpenedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OpenTicketAction
{
    public function execute(User $user, OpenTicketRequest $request): Ticket
    {
        return DB::transaction(function () use ($user, $request): Ticket {
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'venue_id' => $user->current_venue_id,
                'category_id' => $request->validated('category_id'),
                'subject' => $request->validated('subject'),
                'status' => TicketStatus::Open,
                'priority' => TicketPriority::Medium,
            ]);

            $message = $ticket->messages()->create([
                'author_id' => $user->id,
                'author_type' => TicketAuthorType::User,
                'body' => $request->validated('body'),
                'is_internal' => false,
            ]);

            (new StoreAttachmentsAction)->execute($message, $request->file('attachments', []));

            Notification::route('mail', config('mail.support_team_email', config('mail.from.address')))
                ->notify(new TicketOpenedNotification($ticket, $user));

            return $ticket->fresh(['messages', 'category']);
        });
    }
}
