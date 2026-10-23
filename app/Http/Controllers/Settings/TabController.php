<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreateTabAction;
use App\Actions\Settings\CreateTabRangeAction;
use App\Actions\Settings\DeleteTabAction;
use App\Actions\Settings\DeleteTabSeriesAction;
use App\Actions\Settings\UpdateTabSeriesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreTabRangeRequest;
use App\Http\Requests\Settings\StoreTabRequest;
use App\Http\Requests\Settings\UpdateTabSeriesRequest;
use App\Models\Settings\Tab;
use App\Models\Settings\TabSeries;
use Illuminate\Http\RedirectResponse;

class TabController extends Controller
{
    public function store(StoreTabRequest $request, CreateTabAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Tabs created successfully');
    }

    public function storeRange(StoreTabRangeRequest $request, CreateTabRangeAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Tabs created successfully');
    }

    public function updateSeries(TabSeries $series, UpdateTabSeriesRequest $request, UpdateTabSeriesAction $action): RedirectResponse
    {
        $action->execute($series, $request);

        return back()->with('success', 'Tab notes saved.');
    }

    public function destroy(Tab $tab, DeleteTabAction $action): RedirectResponse
    {
        $action->execute($tab);

        return back()->with('success', 'Tab deleted.');
    }

    public function destroySeries(TabSeries $series, DeleteTabSeriesAction $action): RedirectResponse
    {
        $action->execute($series);

        return back()->with('success', 'Tabs deleted.');
    }
}
