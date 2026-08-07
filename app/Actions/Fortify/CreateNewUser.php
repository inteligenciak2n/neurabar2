<?php

namespace App\Actions\Fortify;

use App\Enums\AffiliateCodeStatus;
use App\Enums\ProfileEnum;
use App\Models\Tenant\AffiliateCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $input['password'] ? $this->passwordRules() : 'nullable',
            'affiliate_code' => ['nullable', 'string', 'max:64'],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'profile' => ProfileEnum::Client->value,
            'password' => Hash::make($this->resolvePassword($input['password'] ?? null)),
            'active' => true,
            'affiliate_code_id' => $this->resolveAffiliateCodeId($input['affiliate_code'] ?? null),
        ]);
    }

    private function resolvePassword(?string $password = null): string
    {
        return $password ?? bin2hex(random_bytes(16));
    }

    /**
     * Resolve o código de afiliado informado no cadastro. Um código inválido
     * nunca bloqueia o registro: apenas registramos um alerta e seguimos.
     */
    private function resolveAffiliateCodeId(?string $code): ?string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        $affiliate = AffiliateCode::query()
            ->where('status', AffiliateCodeStatus::Active)
            ->when(
                Str::isUuid($code),
                fn ($query) => $query->where('id', $code),
                fn ($query) => $query->whereRaw('lower(code) = ?', [Str::lower($code)]),
            )
            ->first();

        if (! $affiliate) {
            Log::warning('Código de afiliado informado no cadastro não foi encontrado.', [
                'affiliate_code' => $code,
            ]);

            return null;
        }

        return $affiliate->id;
    }
}
