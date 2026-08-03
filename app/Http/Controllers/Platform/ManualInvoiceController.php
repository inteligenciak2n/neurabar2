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
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ManualInvoiceController extends Controller
{
    public function store(StoreManualInvoiceRequest $request, Corporation $corporation): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($corporation, $validated): void {
            // O formulário envia reais; a persistência é em centavos.
            $baseValue = Money::fromFloat($validated['base_value']);
            $modulesValue = Money::fromFloat($validated['modules_value'] ?? 0);
            $meteredValue = Money::fromFloat($validated['metered_value'] ?? 0);
            $dedicatedSurcharge = Money::fromFloat($validated['dedicated_surcharge'] ?? 0);
            $discountValue = Money::fromFloat($validated['discount_value'] ?? 0);

            $total = $baseValue + $modulesValue + $meteredValue + $dedicatedSurcharge - $discountValue;

            if ($validated['invoiceable_type'] === 'corporation') {
                CorporationInvoice::create([
                    'corporation_id' => $corporation->id,
                    'corporation_subscription_id' => $corporation->subscription?->id,
                    'affiliate_code_id' => $corporation->subscription?->affiliate_code_id,
                    'period' => $validated['period'],
                    'due_date' => $validated['due_date'],
                    'status' => InvoiceStatus::Open,
                    'base_value' => $baseValue,
                    'modules_value' => $modulesValue,
                    'metered_value' => $meteredValue,
                    'dedicated_surcharge' => $dedicatedSurcharge,
                    'discount_value' => $discountValue,
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
                'base_value' => $baseValue,
                'modules_value' => $modulesValue,
                'metered_value' => $meteredValue,
                'dedicated_surcharge' => $dedicatedSurcharge,
                'discount_value' => $discountValue,
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
