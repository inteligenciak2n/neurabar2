<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\DisableCorporateModuleAction;
use App\Actions\Platform\EnableCorporateModuleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCorporationModuleRequest;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CorporationModuleController extends Controller
{
    public function index(Corporation $corporation): Response
    {
        $modules = CorporationModule::query()
            ->where('corporation_id', $corporation->id)
            ->with('catalog:id,code,name,base_monthly_price')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Corporations/Modules/Index', [
            'corporation' => $corporation,
            'modules' => $modules,
        ]);
    }

    public function store(
        StoreCorporationModuleRequest $request,
        Corporation $corporation,
        EnableCorporateModuleAction $action,
    ): RedirectResponse {
        $validated = $request->validated();

        $action->execute(
            $corporation,
            $validated['module_code'],
            $validated['custom_monthly_price'] ?? null,
        );

        return back()->with('success', 'Módulo habilitado com sucesso.');
    }

    public function destroy(
        Corporation $corporation,
        CorporationModule $module,
        DisableCorporateModuleAction $action,
    ): RedirectResponse {
        $action->execute($corporation, $module->module_code);

        return back()->with('success', 'Módulo desabilitado com sucesso.');
    }
}
