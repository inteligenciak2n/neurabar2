<?php

namespace App\Http\Controllers\Platform;

use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ModuleCatalogRequest;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueUsageRecord;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ModuleCatalogController extends Controller
{
    /** @var list<string> */
    private const AUDITED_ATTRIBUTES = [
        'code', 'name', 'description', 'category', 'billing_type',
        'base_monthly_price', 'unit_of_measure', 'dependencies',
        'required_roles', 'icon', 'sort_order', 'active',
    ];

    public function index(): Response
    {
        return Inertia::render('Platform/Modules/Index', [
            'modules' => ModuleCatalog::query()->orderBy('sort_order')->get(),
            'moduleCodes' => ModuleCode::all(),
            'billingTypes' => array_map(
                fn (ModuleBillingType $type): array => ['value' => $type->value, 'label' => ucfirst($type->value)],
                ModuleBillingType::cases(),
            ),
            'roles' => array_map(
                fn (UserRole $role): array => ['value' => $role->value, 'label' => $role->label()],
                UserRole::cases(),
            ),
            'canManage' => in_array(Auth::user()?->profile, [
                ProfileEnum::SuperAdmin,
                ProfileEnum::Finance,
            ], true),
        ]);
    }

    public function store(ModuleCatalogRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['base_monthly_price'] = Money::fromFloat($validated['base_monthly_price']);

        $module = ModuleCatalog::create($validated);

        AuditLogger::record('module_catalog.created', $module, null, AuditLogger::snapshot($module, self::AUDITED_ATTRIBUTES));

        return back()->with('success', 'Module created successfully.');
    }

    public function update(ModuleCatalogRequest $request, ModuleCatalog $module): RedirectResponse
    {
        $validated = $request->validated();
        $validated['base_monthly_price'] = Money::fromFloat($validated['base_monthly_price']);
        $before = AuditLogger::snapshot($module, self::AUDITED_ATTRIBUTES);

        $module->update($validated);

        AuditLogger::record('module_catalog.updated', $module, $before, AuditLogger::snapshot($module, self::AUDITED_ATTRIBUTES));

        return back()->with('success', 'Module updated successfully.');
    }

    public function destroy(ModuleCatalog $module): RedirectResponse
    {
        $isInUse = CorporationModule::query()->where('module_code', $module->code)->exists()
            || VenueModule::query()->where('module_code', $module->code)->exists()
            || ModuleUsageTier::query()->where('module_code', $module->code)->exists()
            || VenueUsageRecord::query()->where('module_code', $module->code)->exists()
            || PlanCatalog::query()->whereJsonContains('included_modules', $module->code)->exists()
            || ModuleCatalog::query()
                ->whereKeyNot($module->getKey())
                ->whereJsonContains('dependencies', $module->code)
                ->exists();

        if ($isInUse) {
            return back()->withErrors(['module' => 'Modules in use cannot be deleted. Deactivate the module instead.']);
        }

        $before = AuditLogger::snapshot($module, self::AUDITED_ATTRIBUTES);

        $module->delete();

        AuditLogger::record('module_catalog.deleted', $module, $before, null);

        return back()->with('success', 'Module deleted successfully.');
    }
}
