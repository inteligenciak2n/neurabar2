<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateTabSeriesRequest;
use App\Models\Settings\TabSeries;

class UpdateTabSeriesAction
{
    public function execute(TabSeries $series, UpdateTabSeriesRequest $request): TabSeries
    {
        $series->update([
            'notes' => $request->validated('notes'),
        ]);

        return $series->fresh();
    }
}
