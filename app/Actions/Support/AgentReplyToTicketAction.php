<?php

namespace App\Actions\Support;

use App\Enums\Support\TicketAuthorType;
use App\Http\Requests\Support\ReplyTicketRequest;
use App\Models\Support\Ticket;
use App\Models\Support\TicketMessage;
use App\Models\User;
use App\Notifications\Support\NewMessageNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentReplyToTicketAction
{
    public function execute(Ticket $ticket, User $agent, ReplyTicketRequest $request): TicketMessage
    {
        if (! $ticket->isOpen()) {
            throw ValidationException::withMessages([
                'ticket' => 'Não é possível responder a um chamado encerrado.',
            ]);
        }

        return DB::transaction(function () use ($ticket, $agent, $request): TicketMessage {
            $isInternal = (bool) $request->validated('is_internal', false);

            $message = $ticket->messages()->create([
                'author_id' => $agent->id,
                'author_type' => TicketAuthorType::PlatformUser,
                'body' => $request->validated('body'),
                'is_internal' => $isInternal,
            ]);

            (new StoreAttachmentsAction)->execute($message, $request->file('attachments', []));

            if (! $isInternal) {
                $user = User::find($ticket->user_id);
                if ($user) {
                    $user->notify(new NewMessageNotification($ticket, $message, $agent->name));
                }
            }

            return $message->load('attachments');
        });
    }
}
