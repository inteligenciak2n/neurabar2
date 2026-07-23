<?php

namespace App\Http\Controllers\Platform;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('view-invoice');

        $period = $request->input('period', now()->format('Y-m'));

        $corporationInvoices = CorporationInvoice::query()
            ->where('period', $period)
            ->with('corporation:id,name,email')
            ->paginate(20)
            ->withQueryString();

        $venueInvoices = VenueInvoice::query()
            ->where('period', $period)
            ->with('venue:id,name,corporation_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Invoices/Index', [
            'filters' => ['period' => $period],
            'corporationInvoices' => $corporationInvoices,
            'venueInvoices' => $venueInvoices,
            'statuses' => array_map(fn (InvoiceStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ], InvoiceStatus::cases()),
        ]);
    }

    public function show(Request $request, string $invoice): Response
    {
        Gate::authorize('view-invoice');

        $corporationInvoice = CorporationInvoice::with('corporation:id,name,email')->find($invoice);

        if ($corporationInvoice) {
            return Inertia::render('Platform/Invoices/Show', [
                'invoice' => $corporationInvoice,
            ]);
        }

        $venueInvoice = VenueInvoice::with('venue:id,name,corporation_id')->findOrFail($invoice);

        return Inertia::render('Platform/Invoices/Show', [
            'invoice' => $venueInvoice,
        ]);
    }
}
