<?php

namespace App\Http\Controllers\Payment;

use App\Actions\Payment\RegisterPaymentAction;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Orders\Attendance;
use App\Models\Settings\VenueSettings;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController
{
    public function __construct(private PaymentService $paymentService) {}

    public function show(Attendance $attendance): Response
    {
        $attendance->load('orders.items.modifiers.modifierOption', 'serviceLocation');

        $totals = $this->paymentService->calculateTotal($attendance);
        $perPerson = isset($totals['grand_total']) && $attendance->party_size > 0
            ? $this->paymentService->splitTotal((float) $totals['grand_total'], (int) $attendance->party_size)
            : null;

        $settings = VenueSettings::withoutGlobalScopes()
            ->where('venue_id', $attendance->venue_id)
            ->firstOrFail();

        return Inertia::render('Payment/Index', [
            'attendance' => $attendance,
            'totals' => $totals,
            'perPerson' => $perPerson,
            'paymentMethods' => ['cash', 'credit_card', 'debit_card', 'pix', 'other'],
            'coverChargePerPerson' => (float) $settings->cover_charge,
            'serviceFeePercent' => (float) $settings->service_fee_percent,
        ]);
    }

    public function store(Attendance $attendance, StorePaymentRequest $request, RegisterPaymentAction $action): RedirectResponse
    {
        $action->execute($attendance, $request);

        return redirect()->route('attendances.index');
    }
}
