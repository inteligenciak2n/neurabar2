<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\CreateCorporationAction;
use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\ModuleCode;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCorporationRequest;
use App\Http\Requests\Platform\UpdateCorporationRequest;
use App\Models\AuditLog;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\SubscriptionStatusHistory;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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

        $invoices = CorporationInvoice::query()
            ->where('corporation_id', $corporation->id)
            ->withSum('venueInvoices as venue_total', 'total_value')
            ->orderByDesc('period')
            ->limit(50)
            ->get();

        $venueInvoices = VenueInvoice::query()
            ->whereIn('venue_id', $corporation->venues->pluck('id'))
            ->with('venue:id,name')
            ->orderByDesc('period')
            ->limit(50)
            ->get();

        $plans = PlanCatalog::query()
            ->where('active', true)
            ->select('id', 'name', 'monthly_price', 'included_modules')
            ->get();

        return Inertia::render('Platform/Corporations/Edit', [
            'corporation' => $corporation,
            'plans' => $plans,
            'invoices' => $invoices,
            'venueInvoices' => $venueInvoices,
            // A auditoria só é lida na aba própria: carregá-la no request
            // principal atrasava a abertura da tela inteira.
            'statusHistory' => Inertia::defer(fn () => $this->statusHistoryFor($corporation), 'audit'),
            'auditLogs' => Inertia::defer(
                fn () => $this->auditLogsFor($corporation, $invoices->pluck('id'), $venueInvoices->pluck('id')),
                'audit'
            ),
            'moduleCatalog' => ModuleCode::all(),
            'subscriptionStatuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], SubscriptionStatus::cases()),
            'billingModes' => array_map(fn ($m) => ['value' => $m->value, 'label' => $m->label()], BillingMode::cases()),
            'invoiceStatuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], InvoiceStatus::cases()),
        ]);
    }

    /**
     * Histórico de status da assinatura da corporation e das suas venues.
     *
     * @return Collection<int, SubscriptionStatusHistory>
     */
    private function statusHistoryFor(Corporation $corporation): Collection
    {
        $corporationSubscriptionIds = CorporationSubscription::query()
            ->where('corporation_id', $corporation->id)
            ->pluck('id');

        $venueSubscriptionIds = VenueSubscription::query()
            ->whereIn('venue_id', $corporation->venues->pluck('id'))
            ->pluck('id');

        return SubscriptionStatusHistory::query()
            ->whereIn('subscription_id', $corporationSubscriptionIds->merge($venueSubscriptionIds))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    /**
     * @param  Collection<int, string>  $corporationInvoiceIds
     * @param  Collection<int, string>  $venueInvoiceIds
     * @return Collection<int, AuditLog>
     */
    private function auditLogsFor(Corporation $corporation, Collection $corporationInvoiceIds, Collection $venueInvoiceIds): Collection
    {
        $auditableIds = collect([$corporation->id])
            ->merge(CorporationSubscription::query()->where('corporation_id', $corporation->id)->pluck('id'))
            ->merge($corporation->discounts->pluck('id'))
            ->merge($corporationInvoiceIds)
            ->merge($venueInvoiceIds)
            ->unique();

        return AuditLog::query()
            ->whereIn('auditable_id', $auditableIds)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function update(UpdateCorporationRequest $request, Corporation $corporation): RedirectResponse
    {
        $corporation->update($request->validated());

        return redirect()->route('platform.corporations.index')
            ->with('success', 'Corporation updated successfully.');
    }
}
