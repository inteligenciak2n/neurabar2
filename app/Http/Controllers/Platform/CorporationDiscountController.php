<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCorporationDiscountRequest;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationDiscount;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;

class CorporationDiscountController extends Controller
{
    public function store(StoreCorporationDiscountRequest $request, Corporation $corporation): RedirectResponse
    {
        $validated = $request->validated();

        // Centavos quando o desconto é fixo; pontos-base quando é percentual.
        $validated['value'] = Money::fromFloat($validated['value']);

        $corporation->discounts()->create($validated);

        return back()->with('success', __('Discount created successfully.'));
    }

    public function destroy(Corporation $corporation, CorporationDiscount $discount): RedirectResponse
    {
        $discount->delete();

        return back()->with('success', __('Discount removed successfully.'));
    }
}
