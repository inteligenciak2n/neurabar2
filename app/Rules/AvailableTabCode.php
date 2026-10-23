<?php

namespace App\Rules;

use App\Models\Orders\Attendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects a tab card code that is already attached to another open attendance of the venue.
 */
class AvailableTabCode implements ValidationRule
{
    public function __construct(
        private readonly string $venueId,
        private readonly ?string $ignoreAttendanceId = null,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = Attendance::normalizeCode(is_string($value) ? $value : null);

        if ($code === null) {
            return;
        }

        $inUse = Attendance::withoutGlobalScopes()
            ->open()
            ->where('venue_id', $this->venueId)
            ->where('code', $code)
            ->when($this->ignoreAttendanceId, fn ($query, string $id) => $query->where('id', '!=', $id))
            ->exists();

        if ($inUse) {
            $fail('This tab card is already in use.');
        }
    }
}
