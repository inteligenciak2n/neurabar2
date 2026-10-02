<?php

namespace App\Actions\Settings;

use App\Models\Tenant\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreVenueLogoAction
{
    public function execute(UploadedFile $file, Venue $venue): string
    {
        $this->deleteStored($venue);

        return $file->store('venues/'.$venue->id.'/logo', 'public');
    }

    public function deleteStored(Venue $venue): void
    {
        $path = $venue->storedLogoPath();

        if ($path === null) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
