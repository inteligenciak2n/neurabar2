<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCorporationDiscountRequest;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationDiscount;
use Illuminate\Http\RedirectResponse;

class CorporationDiscountController extends Controller
{
    public function store(StoreCorporationDiscountRequest $request, Corporation $corporation): RedirectResponse
    {
        $corporation->discounts()->create($request->validated());

        return back()->with('success', __('Discount created successfully.'));
    }

    public function destroy(Corporation $corporation, CorporationDiscount $discount): RedirectResponse
    {
        $discount->delete();

        return back()->with('success', __('Discount removed successfully.'));
    }
}
