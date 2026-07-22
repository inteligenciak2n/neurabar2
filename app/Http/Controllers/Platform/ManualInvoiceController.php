<?php

namespace App\Http\Controllers\Platform;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreManualInvoiceRequest;
use App\Http\Requests\Platform\UpdateInvoiceStatusRequest;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ManualInvoiceController extends Controller
{
    public function store(StoreManualInvoiceRequest $request, Corporation $corporation): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($corporation, $validated): void {
            $total = (float) $validated['base_value']
                + (float) ($validated['modules_value'] ?? 0)
                + (float) ($validated['metered_value'] ?? 0)
                + (float) ($validated['dedicated_surcharge'] ?? 0)
                - (float) ($validated['discount_value'] ?? 0);

            if ($validated['invoiceable_type'] === 'corporation') {
                CorporationInvoice::create([
                    'corporation_id' => $corporation->id,
                    'corporation_subscription_id' => $corporation->subscription?->id,
                    'affiliate_code_id' => $corporation->subscription?->affiliate_code_id,
                    'period' => $validated['period'],
                    'due_date' => $validated['due_date'],
                    'status' => InvoiceStatus::Open,
                    'base_value' => $validated['base_value'],
                    'modules_value' => $validated['modules_value'] ?? 0,
                    'metered_value' => $validated['metered_value'] ?? 0,
                    'dedicated_surcharge' => $validated['dedicated_surcharge'] ?? 0,
                    'discount_value' => $validated['discount_value'] ?? 0,
                    'total_value' => max(0, $total),
                ]);

                return;
            }

            $venue = Venue::where('corporation_id', $corporation->id)
                ->where('id', $validated['invoiceable_id'])
                ->firstOrFail();

            VenueInvoice::create([
                'venue_id' => $venue->id,
                'venue_subscription_id' => $venue->subscription?->id,
                'affiliate_code_id' => $venue->subscription?->affiliate_code_id,
                'period' => $validated['period'],
                'due_date' => $validated['due_date'],
                'status' => InvoiceStatus::Open,
                'base_value' => $validated['base_value'],
                'modules_value' => $validated['modules_value'] ?? 0,
                'metered_value' => $validated['metered_value'] ?? 0,
                'dedicated_surcharge' => $validated['dedicated_surcharge'] ?? 0,
                'discount_value' => $validated['discount_value'] ?? 0,
                'total_value' => max(0, $total),
            ]);
        });

        return back()->with('success', __('Invoice created successfully.'));
    }

    public function updateStatus(UpdateInvoiceStatusRequest $request, Corporation $corporation, string $invoiceId): RedirectResponse
    {
        $status = InvoiceStatus::from($request->validated('status'));

        $invoice = CorporationInvoice::where('corporation_id', $corporation->id)->find($invoiceId)
            ?? VenueInvoice::whereHas('venue', fn ($q) => $q->where('corporation_id', $corporation->id))->findOrFail($invoiceId);

        if (! $invoice->status->canTransitionTo($status)) {
            return back()->with('error', __('Invoice status transition not allowed.'));
        }

        $invoice->update([
            'status' => $status,
            'paid_at' => $status === InvoiceStatus::Paid ? now() : $invoice->paid_at,
            'is_finalized' => $status->isFinalized(),
        ]);

        return back()->with('success', __('Invoice status updated successfully.'));
    }
}
