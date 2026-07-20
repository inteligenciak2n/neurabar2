<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Corporation\ActivateVenueModuleAction;
use App\Actions\Corporation\DeactivateVenueModuleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreVenueModuleRequest;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VenueModuleController extends Controller
{
    public function index(Corporation $corporation, Venue $venue): Response
    {
        $this->ensureVenueBelongsToCorporation($corporation, $venue);

        $modules = VenueModule::query()
            ->where('venue_id', $venue->id)
            ->with('catalog:id,code,name,base_monthly_price')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Corporations/Venues/Modules/Index', [
            'corporation' => $corporation,
            'venue' => $venue,
            'modules' => $modules,
        ]);
    }

    public function store(
        StoreVenueModuleRequest $request,
        Corporation $corporation,
        Venue $venue,
        ActivateVenueModuleAction $action,
    ): RedirectResponse {
        $this->ensureVenueBelongsToCorporation($corporation, $venue);

        $validated = $request->validated();

        $action->execute(
            $venue,
            $validated['module_code'],
            $validated['quantity'] ?? 1,
        );

        return back()->with('success', 'Módulo ativado no venue com sucesso.');
    }

    public function destroy(
        Corporation $corporation,
        Venue $venue,
        VenueModule $module,
        DeactivateVenueModuleAction $action,
    ): RedirectResponse {
        $this->ensureVenueBelongsToCorporation($corporation, $venue);

        $action->execute($venue, $module->module_code);

        return back()->with('success', 'Módulo desativado no venue com sucesso.');
    }

    private function ensureVenueBelongsToCorporation(Corporation $corporation, Venue $venue): void
    {
        if ($venue->corporation_id !== $corporation->id) {
            abort(404);
        }
    }
}
