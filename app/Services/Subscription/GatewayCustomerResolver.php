<?php

namespace App\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Models\Tenant\GatewayCustomer;
use App\Models\User;

class GatewayCustomerResolver
{
    public function __construct(private readonly PaymentGatewayContract $gateway) {}

    /**
     * Resolve the gateway customer id for a user, reusing a previously
     * created customer instead of creating a new one on every call.
     */
    public function resolve(User $user, string $gatewayName, ?string $document = null): string
    {
        $existing = GatewayCustomer::query()
            ->where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->where('gateway', $gatewayName)
            ->first();

        if ($existing) {
            return $existing->customer_id;
        }

        $customerId = $this->gateway->createCustomer([
            'name' => $user->name,
            'email' => $user->email,
            'document' => $document,
        ]);

        GatewayCustomer::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'gateway' => $gatewayName,
            'customer_id' => $customerId,
        ]);

        return $customerId;
    }
}
