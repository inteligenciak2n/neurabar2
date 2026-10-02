<?php

namespace App\Actions\Menu;

use App\Models\Menu\Product;
use App\Models\Tenant\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreProductImageAction
{
    public function execute(UploadedFile $file, Venue $venue, ?Product $replacing = null): string
    {
        if ($replacing !== null) {
            $this->deleteStored($replacing);
        }

        return $file->store('products/'.$venue->id, 'public');
    }

    public function deleteStored(Product $product): void
    {
        $path = $product->storedImagePath();

        if ($path === null) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
