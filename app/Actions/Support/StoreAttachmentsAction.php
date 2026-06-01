<?php

namespace App\Actions\Support;

use App\Models\Support\TicketAttachment;
use App\Models\Support\TicketMessage;
use Illuminate\Http\UploadedFile;

class StoreAttachmentsAction
{
    /**
     * @param  array<UploadedFile>  $files
     * @return list<TicketAttachment>
     */
    public function execute(TicketMessage $message, array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $path = $file->store("support/attachments/{$message->ticket_id}", 'local');

            $attachments[] = $message->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return $attachments;
    }
}
