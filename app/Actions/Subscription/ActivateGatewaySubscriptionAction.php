<?php

namespace App\Actions\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\PaymentSaasMethod;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\VenueSubscription;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\Subscription\GatewayCustomerResolver;
use InvalidArgumentException;

class ActivateGatewaySubscriptionAction
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly GatewayCustomerResolver $customerResolver,
        private readonly SubscriptionCalculator $calculator,
    ) {}

    public function execute(CorporationSubscription|VenueSubscription $subscription, UserPaymentMethod $paymentMethod): CorporationSubscription|VenueSubscription
    {
        if ($subscription->isBilledByGateway()) {
            throw new InvalidArgumentException('A assinatura já está vinculada a um gateway de pagamento.');
        }

        if (! $paymentMethod->gateway_token) {
            throw new InvalidArgumentException('O cartão selecionado não possui token válido.');
        }

        $gatewayName = config('subscription.payment.default', 'fake');
        $gatewayCustomerId = $this->customerResolver->resolve($paymentMethod->user, $gatewayName);
        $billingDay = $this->resolveBillingDay($subscription);

        $result = $this->gateway->createSubscription($subscription, [
            'gateway_customer_id' => $gatewayCustomerId,
            'gateway_token' => $paymentMethod->gateway_token,
            'billing_type' => PaymentSaasMethod::CreditCard->value,
            'value' => $this->resolveValue($subscription),
            'next_due_date' => $this->resolveNextDueDate($billingDay),
            'cycle' => 'monthly',
            'description' => 'Assinatura NeuraBar',
        ]);

        $subscription->update([
            'gateway' => $gatewayName,
            'gateway_customer_id' => $gatewayCustomerId,
            'gateway_subscription_id' => $result['gateway_subscription_id'],
        ]);

        return $subscription->refresh();
    }

    private function resolveValue(CorporationSubscription|VenueSubscription $subscription): float
    {
        $period = now()->format('Y-m');

        if ($subscription instanceof CorporationSubscription) {
            return (float) ($this->calculator->calculateCorporation($subscription->corporation, $period)['total'] ?? 0.0);
        }

        return (float) ($this->calculator->calculateVenue($subscription->venue, $period)['total'] ?? $subscription->total_value);
    }

    private function resolveBillingDay(CorporationSubscription|VenueSubscription $subscription): int
    {
        $billingDay = $subscription instanceof CorporationSubscription
            ? $subscription->billing_day
            : $subscription->corporationSubscription?->billing_day;

        return min(28, max(1, (int) ($billingDay ?? 1)));
    }

    private function resolveNextDueDate(int $billingDay): string
    {
        $candidate = now()->setDay($billingDay)->startOfDay();

        if ($candidate->isPast()) {
            $candidate = $candidate->addMonthNoOverflow();
        }

        return $candidate->toDateString();
    }
}
