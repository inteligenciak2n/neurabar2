<?php

namespace App\Actions\Support;

use App\Enums\Support\TicketAuthorType;
use App\Http\Requests\Support\ReplyTicketRequest;
use App\Models\Support\Ticket;
use App\Models\Support\TicketMessage;
use App\Models\User;
use App\Notifications\Support\NewMessageNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ReplyToTicketAction
{
    public function execute(Ticket $ticket, User $user, ReplyTicketRequest $request): TicketMessage
    {
        if (! $ticket->isOpen()) {
            throw ValidationException::withMessages([
                'ticket' => 'Não é possível responder a um chamado encerrado.',
            ]);
        }

        return DB::transaction(function () use ($ticket, $user, $request): TicketMessage {
            $message = $ticket->messages()->create([
                'author_id' => $user->id,
                'author_type' => TicketAuthorType::User,
                'body' => $request->validated('body'),
                'is_internal' => false,
            ]);

            (new StoreAttachmentsAction)->execute($message, $request->file('attachments', []));

            // Notify the assigned agent or support team
            Notification::route('mail', config('mail.support_team_email', config('mail.from.address')))
                ->notify(new NewMessageNotification($ticket, $message, $user->name));

            return $message->load('attachments');
        });
    }
}
