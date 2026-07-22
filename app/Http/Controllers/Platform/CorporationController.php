<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\CreateCorporationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCorporationRequest;
use App\Http\Requests\Platform\UpdateCorporationRequest;
use App\Models\Tenant\Corporation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CorporationController extends Controller
{
    public function index(): Response
    {
        $corporations = Corporation::query()
            ->when(request('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->with(['subscription.planCatalog:id,name'])
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Corporations/Index', [
            'corporations' => $corporations,
            'filters' => request()->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Corporations/Create');
    }

    public function store(StoreCorporationRequest $request, CreateCorporationAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()->route('platform.corporations.index')
            ->with('success', 'Corporation created successfully.');
    }

    public function edit(Corporation $corporation): Response
    {
        $corporation->load([
            'subscription.planCatalog:id,name,included_modules',
            'venues.subscription',
            'venues.modules.catalog:id,code,name,base_monthly_price',
            'modules.catalog:id,code,name,base_monthly_price',
            'discounts',
            'affiliate:id,code,name,email',
            'owner:id,name,email',
        ]);

        $invoices = \App\Models\Tenant\CorporationInvoice::query()
            ->where('corporation_id', $corporation->id)
            ->withSum('venueInvoices as venue_total', 'total_value')
            ->orderByDesc('period')
            ->limit(50)
            ->get();

        $venueInvoices = \App\Models\Tenant\VenueInvoice::query()
            ->whereIn('venue_id', $corporation->venues->pluck('id'))
            ->with('venue:id,name')
            ->orderByDesc('period')
            ->limit(50)
            ->get();

        $plans = \App\Models\Tenant\PlanCatalog::query()
            ->where('active', true)
            ->select('id', 'name', 'monthly_price', 'included_modules')
            ->get();

        return Inertia::render('Platform/Corporations/Edit', [
            'corporation' => $corporation,
            'plans' => $plans,
            'invoices' => $invoices,
            'venueInvoices' => $venueInvoices,
            'moduleCatalog' => \App\Enums\ModuleCode::all(),
            'subscriptionStatuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], \App\Enums\SubscriptionStatus::cases()),
            'billingModes' => array_map(fn ($m) => ['value' => $m->value, 'label' => $m->label()], \App\Enums\BillingMode::cases()),
            'invoiceStatuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], \App\Enums\InvoiceStatus::cases()),
        ]);
    }

    public function update(UpdateCorporationRequest $request, Corporation $corporation): RedirectResponse
    {
        $corporation->update($request->validated());

        return redirect()->route('platform.corporations.index')
            ->with('success', 'Corporation updated successfully.');
    }
}
