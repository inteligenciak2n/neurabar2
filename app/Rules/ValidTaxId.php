<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates a Brazilian tax id (CPF or CNPJ) including its check digits.
 *
 * An unchecked tax id only surfaces when the first invoice is rejected by the
 * gateway, long after the customer finished the signup funnel.
 */
class ValidTaxId implements ValidationRule
{
    public function __construct(
        private readonly bool $allowCpf = true,
        private readonly bool $allowCnpj = true,
    ) {}

    public static function cnpj(): self
    {
        return new self(allowCpf: false);
    }

    public static function cpf(): self
    {
        return new self(allowCnpj: false);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail($this->lengthMessage());

            return;
        }

        $digits = (string) preg_replace('/\D/', '', (string) $value);

        if ($this->allowCpf && strlen($digits) === 11) {
            if (! $this->isValidCpf($digits)) {
                $fail(__('The :attribute is not a valid CPF.'));
            }

            return;
        }

        if ($this->allowCnpj && strlen($digits) === 14) {
            if (! $this->isValidCnpj($digits)) {
                $fail(__('The :attribute is not a valid CNPJ.'));
            }

            return;
        }

        $fail($this->lengthMessage());
    }

    private function lengthMessage(): string
    {
        if (! $this->allowCpf) {
            return __('The :attribute must be a valid CNPJ.');
        }

        if (! $this->allowCnpj) {
            return __('The :attribute must be a valid CPF.');
        }

        return __('The :attribute must be a valid CPF or CNPJ.');
    }

    private function isValidCpf(string $digits): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $digits) === 1) {
            return false;
        }

        foreach ([9, 10] as $position) {
            $sum = 0;

            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $digits[$i] * (($position + 1) - $i);
            }

            if (((($sum * 10) % 11) % 10) !== (int) $digits[$position]) {
                return false;
            }
        }

        return true;
    }

    private function isValidCnpj(string $digits): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $digits) === 1) {
            return false;
        }

        $weightSets = [
            12 => [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            13 => [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weightSets as $position => $weights) {
            $sum = 0;

            foreach ($weights as $index => $weight) {
                $sum += (int) $digits[$index] * $weight;
            }

            $remainder = $sum % 11;
            $checkDigit = $remainder < 2 ? 0 : 11 - $remainder;

            if ($checkDigit !== (int) $digits[$position]) {
                return false;
            }
        }

        return true;
    }
}
