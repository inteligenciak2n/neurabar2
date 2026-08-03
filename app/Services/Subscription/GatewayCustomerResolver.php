<?php

namespace App\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\GatewayCustomer;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GatewayCustomerResolver
{
    public function __construct(private readonly PaymentGatewayContract $gateway) {}

    /**
     * Resolve the gateway customer id for a corporation, reusing a previously
     * created customer instead of creating a new one on every call.
     *
     * The customer is keyed by corporation, not by user: billing belongs to
     * the company, so two employees paying for the same corporation must land
     * on the same gateway customer. Keying it by user created one duplicate
     * customer (and one duplicate CNPJ) per employee, scattering payment
     * history and anti-fraud reputation across unrelated records.
     */
    public function resolve(?Corporation $corporation, User $user, string $gatewayName, ?string $document = null): string
    {
        [$ownerType, $ownerId] = $corporation
            ? [Corporation::class, (string) $corporation->id]
            : [User::class, (string) $user->id];

        if ($corporation === null) {
            Log::warning('gateway.customer.without_corporation', ['user_id' => $user->id]);
        }

        $existing = GatewayCustomer::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('gateway', $gatewayName)
            ->first();

        if ($existing) {
            return $existing->customer_id;
        }

        $customerId = $this->gateway->createCustomer([
            'name' => $corporation?->name ?? $user->name,
            'email' => $user->email,
            'document' => $document ?? $corporation?->tax_id,
        ]);

        GatewayCustomer::create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'gateway' => $gatewayName,
            'customer_id' => $customerId,
        ]);

        return $customerId;
    }
}
