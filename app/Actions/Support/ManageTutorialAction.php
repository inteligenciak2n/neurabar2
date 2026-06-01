<?php

namespace App\Actions\Support;

use App\Http\Requests\Support\StoreTutorialRequest;
use App\Http\Requests\Support\UpdateTutorialRequest;
use App\Models\Support\Tutorial;
use Illuminate\Support\Facades\Storage;

class ManageTutorialAction
{
    public function create(StoreTutorialRequest $request): Tutorial
    {
        $data = $request->validated();
        $data['slug'] = Tutorial::generateSlug($data['title']);
        $data['featured_image'] = $this->storeFeaturedImage($request);

        return Tutorial::create($data);
    }

    public function update(Tutorial $tutorial, UpdateTutorialRequest $request): Tutorial
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            if ($tutorial->featured_image) {
                Storage::disk('local')->delete($tutorial->featured_image);
            }
            $data['featured_image'] = $this->storeFeaturedImage($request);
        }

        $tutorial->update($data);

        return $tutorial->refresh();
    }

    public function publish(Tutorial $tutorial): Tutorial
    {
        $tutorial->update([
            'published' => true,
            'published_at' => now(),
        ]);

        return $tutorial->refresh();
    }

    public function unpublish(Tutorial $tutorial): Tutorial
    {
        $tutorial->update([
            'published' => false,
            'published_at' => null,
        ]);

        return $tutorial->refresh();
    }

    private function storeFeaturedImage(StoreTutorialRequest|UpdateTutorialRequest $request): ?string
    {
        if (! $request->hasFile('featured_image')) {
            return null;
        }

        return $request->file('featured_image')->store('support/tutorials', 'public');
    }
}
