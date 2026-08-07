<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\UserPaymentMethod;
use App\Models\User;

class UserPaymentMethodPolicy
{
    /**
     * Cartão é sempre pessoal: só o próprio portador pode cobrá-lo, torná-lo
     * padrão ou removê-lo — inclusive um owner da mesma corporation.
     */
    public function use(User $user, UserPaymentMethod $method): bool
    {
        return $method->user_id === $user->id;
    }

    public function update(User $user, UserPaymentMethod $method): bool
    {
        return $this->use($user, $method);
    }

    public function delete(User $user, UserPaymentMethod $method): bool
    {
        return $this->use($user, $method);
    }
}
