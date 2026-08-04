<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCorporationDiscountRequest;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationDiscount;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;

class CorporationDiscountController extends Controller
{
    /** @var list<string> */
    private const AUDITED_ATTRIBUTES = [
        'corporation_id', 'type', 'value', 'valid_from', 'valid_until', 'max_months', 'reason',
    ];

    public function store(StoreCorporationDiscountRequest $request, Corporation $corporation): RedirectResponse
    {
        $validated = $request->validated();

        // Centavos quando o desconto é fixo; pontos-base quando é percentual.
        $validated['value'] = Money::fromFloat($validated['value']);

        $discount = $corporation->discounts()->create($validated);

        AuditLogger::record(
            'corporation_discount.created',
            $discount,
            null,
            AuditLogger::snapshot($discount, self::AUDITED_ATTRIBUTES),
        );

        return back()->with('success', __('Discount created successfully.'));
    }

    public function destroy(Corporation $corporation, CorporationDiscount $discount): RedirectResponse
    {
        $before = AuditLogger::snapshot($discount, self::AUDITED_ATTRIBUTES);

        $discount->delete();

        AuditLogger::record('corporation_discount.deleted', $discount, $before, null);

        return back()->with('success', __('Discount removed successfully.'));
    }
}
