<?php

namespace App\Actions\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\PaymentSaasMethod;
use App\Enums\ProfileEnum;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use App\Notifications\Subscription\GatewaySubscriptionOrphaned;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\Subscription\GatewayCustomerResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Throwable;

class ActivateGatewaySubscriptionAction
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly GatewayCustomerResolver $customerResolver,
        private readonly SubscriptionCalculator $calculator,
    ) {}

    public function execute(CorporationSubscription|VenueSubscription $subscription, UserPaymentMethod $paymentMethod): CorporationSubscription|VenueSubscription
    {
        // Duplo clique ou requisições concorrentes criavam duas assinaturas
        // recorrentes no gateway para o mesmo cliente — e só a última ficava
        // registrada localmente, deixando a outra cobrando indefinidamente.
        $lock = Cache::lock('subscription-activation:'.$subscription->getKey(), 30);

        if (! $lock->get()) {
            throw new InvalidArgumentException('Já existe uma ativação de assinatura em andamento.');
        }

        try {
            return $this->activate($subscription->refresh(), $paymentMethod);
        } finally {
            $lock->release();
        }
    }

    private function activate(CorporationSubscription|VenueSubscription $subscription, UserPaymentMethod $paymentMethod): CorporationSubscription|VenueSubscription
    {
        if ($subscription->isBilledByGateway()) {
            throw new InvalidArgumentException('A assinatura já está vinculada a um gateway de pagamento.');
        }

        if (! $paymentMethod->gateway_token) {
            throw new InvalidArgumentException('O cartão selecionado não possui token válido.');
        }

        $value = $this->resolveValue($subscription);

        $gatewayName = config('subscription.payment.default', 'fake');
        $gatewayCustomerId = $this->customerResolver->resolve(
            $this->resolveCorporation($subscription),
            $paymentMethod->user,
            $gatewayName,
            $paymentMethod->holder_document,
        );
        $billingDay = $this->resolveBillingDay($subscription);

        $result = $this->gateway->createSubscription($subscription, [
            'gateway_customer_id' => $gatewayCustomerId,
            'gateway_token' => $paymentMethod->gateway_token,
            'billing_type' => PaymentSaasMethod::CreditCard->value,
            'value' => $value,
            'next_due_date' => $this->resolveNextDueDate($billingDay),
            'cycle' => 'monthly',
            'description' => 'Assinatura NeuraBar',
        ]);

        try {
            $subscription->update([
                'gateway' => $gatewayName,
                'gateway_customer_id' => $gatewayCustomerId,
                'gateway_subscription_id' => $result['gateway_subscription_id'],
            ]);
        } catch (Throwable $e) {
            $this->compensate($result['gateway_subscription_id'], (string) $subscription->getKey(), $e);

            throw $e;
        }

        return $subscription->refresh();
    }

    /**
     * Undo the remote subscription created moments ago. When the rollback also
     * fails the original exception is preserved and the backoffice is alerted:
     * otherwise the customer keeps being charged by a subscription nobody
     * knows about.
     */
    private function compensate(string $gatewaySubscriptionId, string $subscriptionId, Throwable $original): void
    {
        try {
            $this->gateway->cancelSubscription($gatewaySubscriptionId);
        } catch (Throwable $rollbackFailure) {
            Log::critical('gateway.subscription.orphaned', [
                'gateway_subscription_id' => $gatewaySubscriptionId,
                'subscription_id' => $subscriptionId,
                'original_error' => $original->getMessage(),
                'rollback_error' => $rollbackFailure->getMessage(),
            ]);

            $admins = User::query()->where('profile', ProfileEnum::SuperAdmin)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new GatewaySubscriptionOrphaned(
                    $gatewaySubscriptionId,
                    $subscriptionId,
                    $rollbackFailure->getMessage(),
                ));
            }
        }
    }

    private function resolveCorporation(CorporationSubscription|VenueSubscription $subscription): ?Corporation
    {
        return $subscription instanceof CorporationSubscription
            ? $subscription->corporation
            : $subscription->venue?->corporation;
    }

    /**
     * @return int Centavos.
     */
    private function resolveValue(CorporationSubscription|VenueSubscription $subscription): int
    {
        $period = now()->format('Y-m');

        if ($subscription instanceof CorporationSubscription) {
            $calculated = $this->calculator->calculateCorporation($subscription->corporation, $period);

            return (int) ($calculated['total'] ?? 0);
        }

        $calculated = $this->calculator->calculateVenue($subscription->venue, $period);

        return (int) ($calculated['total'] ?? $subscription->total_value);
    }

    private function resolveBillingDay(CorporationSubscription|VenueSubscription $subscription): int
    {
        $billingDay = $subscription instanceof CorporationSubscription
            ? $subscription->billing_day
            : $subscription->corporationSubscription?->billing_day;

        return max(1, (int) ($billingDay ?? 1));
    }

    private function resolveNextDueDate(int $billingDay): string
    {
        return $this->buildDueDateForMonth(now(), $billingDay);
    }

    private function buildDueDateForMonth(Carbon $monthReference, int $billingDay): string
    {
        $effectiveDay = min($billingDay, $monthReference->daysInMonth);
        $candidate = $monthReference->copy()->startOfMonth()->addDays($effectiveDay - 1)->startOfDay();

        if ($candidate->isPast()) {
            return $this->buildDueDateForMonth($monthReference->copy()->addMonthNoOverflow(), $billingDay);
        }

        return $candidate->toDateString();
    }
}
