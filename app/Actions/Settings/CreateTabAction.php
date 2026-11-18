<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StoreTabRequest;
use App\Models\Settings\Tab;
use App\Models\Tenant\Venue;
use App\Support\TabCode;

class CreateTabAction
{
    public function __construct(private ResolveTabSeriesAction $resolveTabSeries) {}

    public function execute(Venue $venue, StoreTabRequest $request): Tab
    {
        $code = $request->validated('code');
        $series = $this->resolveTabSeries->execute(
            $venue,
            TabCode::prefixFromCode($code),
            $request->validated('notes'),
        );

        return Tab::create([
            'venue_id' => $venue->id,
            'tab_series_id' => $series->id,
            'code' => $code,
            'active' => true,
        ]);
    }
}
