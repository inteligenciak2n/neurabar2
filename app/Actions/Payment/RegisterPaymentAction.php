<?php

namespace App\Actions\Payment;

use App\Actions\Orders\CloseAttendanceAction;
use App\Enums\AttendanceStatus;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Orders\Attendance;
use App\Models\Payment\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterPaymentAction
{
    public function __construct(
        private PaymentService $paymentService,
        private CloseAttendanceAction $closeAttendanceAction
    ) {}

    public function execute(Attendance $attendance, StorePaymentRequest $request): Payment
    {
        if ($attendance->status !== AttendanceStatus::Open) {
            throw ValidationException::withMessages([
                'attendance' => 'Cannot register payment for a closed attendance.',
            ]);
        }

        if ($attendance->payment()->exists()) {
            throw ValidationException::withMessages([
                'attendance' => 'A payment has already been registered for this attendance.',
            ]);
        }

        $validated = $request->validated();
        $partySize = isset($validated['party_size']) ? (int) $validated['party_size'] : (int) $attendance->party_size;
        $totals = $this->paymentService->calculateTotal($attendance, $partySize);
        $methodsTotal = collect($validated['methods'])->sum(fn ($m) => (float) $m['amount']);

        if (abs($methodsTotal - $totals['grand_total']) > 0.01) {
            throw ValidationException::withMessages([
                'methods' => 'The sum of payment methods does not match the grand total.',
            ]);
        }

        $payment = DB::transaction(function () use ($attendance, $totals, $validated, $partySize, $request): Payment {

            $payment = Payment::create([
                'attendance_id' => $attendance->id,
                'items_total' => $totals['items_total'],
                'cover_charge_total' => $totals['cover_charge_total'],
                'service_fee_total' => $totals['service_fee_total'],
                'delivery_fee_total' => $totals['delivery_fee_total'],
                'grand_total' => $totals['grand_total'],
                'party_size' => $partySize,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['methods'] as $methodData) {
                $payment->paymentItems()->create([
                    'method' => $methodData['type'],
                    'amount' => $methodData['amount'],
                    'notes' => $methodData['notes'] ?? null,
                ]);
            }

            $this->closeAttendanceAction->execute($attendance->fresh());

            return $payment;
        });

        return $payment;
    }
}
