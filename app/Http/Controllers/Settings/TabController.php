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
use App\Http\Requests\Settings\UpdateTabsEnabledRequest;
use App\Http\Requests\Settings\UpdateTabSeriesRequest;
use App\Models\Settings\Tab;
use App\Models\Settings\TabSeries;
use App\Models\Settings\VenueSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TabController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/Tabs', [
            'tabSeries' => $venue->tabSeries()
                ->with(['tabs' => fn ($query) => $query->orderByRaw('length(code), code')])
                ->orderBy('prefix')
                ->get(),
            'tabsEnabled' => $venue->settings?->tabs_enabled ?? true,
        ]);
    }

    public function updateEnabled(UpdateTabsEnabledRequest $request): RedirectResponse
    {
        $venue = app('tenant');

        VenueSettings::updateOrCreate(
            ['venue_id' => $venue->id],
            ['tabs_enabled' => $request->boolean('tabs_enabled')],
        );

        return back()->with('success', 'Tabs updated.');
    }

    public function store(StoreTabRequest $request, CreateTabAction $action): RedirectResponse
    {
        $this->ensureTabsEnabled();

        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Tabs created successfully');
    }

    public function storeRange(StoreTabRangeRequest $request, CreateTabRangeAction $action): RedirectResponse
    {
        $this->ensureTabsEnabled();

        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Tabs created successfully');
    }

    public function updateSeries(TabSeries $series, UpdateTabSeriesRequest $request, UpdateTabSeriesAction $action): RedirectResponse
    {
        $this->ensureTabsEnabled();

        $action->execute($series, $request);

        return back()->with('success', 'Tab notes saved.');
    }

    public function destroy(Tab $tab, DeleteTabAction $action): RedirectResponse
    {
        $this->ensureTabsEnabled();

        $action->execute($tab);

        return back()->with('success', 'Tab deleted.');
    }

    public function destroySeries(TabSeries $series, DeleteTabSeriesAction $action): RedirectResponse
    {
        $this->ensureTabsEnabled();

        $action->execute($series);

        return back()->with('success', 'Tabs deleted.');
    }

    private function ensureTabsEnabled(): void
    {
        $enabled = VenueSettings::query()
            ->where('venue_id', app('tenant')->id)
            ->value('tabs_enabled');

        if ($enabled === false) {
            throw ValidationException::withMessages([
                'tab' => 'Tabs are disabled.',
            ]);
        }
    }
}
