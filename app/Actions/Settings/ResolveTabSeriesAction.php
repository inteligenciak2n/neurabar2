<?php

namespace App\Actions\Settings;

use App\Models\Settings\TabSeries;
use App\Models\Tenant\Venue;
use App\Support\TabCode;

class ResolveTabSeriesAction
{
    public function execute(Venue $venue, string $prefix, ?string $notes = null): TabSeries
    {
        $prefix = TabCode::normalizePrefix($prefix);

        $series = TabSeries::query()->firstOrCreate(
            [
                'venue_id' => $venue->id,
                'prefix' => $prefix,
            ],
            [
                'notes' => $notes,
            ],
        );

        if ($notes !== null && $series->notes !== $notes) {
            $series->update(['notes' => $notes === '' ? null : $notes]);
        }

        return $series->fresh();
    }
}
