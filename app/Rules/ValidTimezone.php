<?php

namespace App\Rules;

use Closure;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that the value is a real IANA timezone identifier.
 *
 * The venue timezone drives every operational timestamp (orders, shifts, KDS),
 * so an unchecked `max:50` string silently breaks the whole operation.
 */
class ValidTimezone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! in_array($value, DateTimeZone::listIdentifiers(), true)) {
            $fail(__('The :attribute must be a valid timezone identifier.'));
        }
    }
}
