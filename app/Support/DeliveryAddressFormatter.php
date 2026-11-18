<?php

namespace App\Support;

class DeliveryAddressFormatter
{
    /**
     * @param  array{
     *     street?: string|null,
     *     number?: string|null,
     *     complement?: string|null,
     *     neighborhood?: string|null,
     *     city?: string|null,
     *     state?: string|null,
     *     reference_point?: string|null,
     *     delivery_phone?: string|null
     * }|object  $address
     */
    public static function line(array|object $address): string
    {
        $street = self::value($address, 'street');
        $number = self::value($address, 'number');
        $complement = self::value($address, 'complement');
        $neighborhood = self::value($address, 'neighborhood');
        $reference = self::value($address, 'reference_point');
        $phone = self::value($address, 'delivery_phone');

        $head = collect([$street, $number, $complement])
            ->filter()
            ->implode(', ');

        $parts = collect([$head])
            ->when($neighborhood !== '', fn ($parts) => $parts->push($neighborhood))
            ->when($reference !== '', fn ($parts) => $parts->push('(ref: '.$reference.')'))
            ->when($phone !== '', fn ($parts) => $parts->push('Tel: '.self::formatPhone($phone)))
            ->filter()
            ->values();

        return $parts->implode(' – ');
    }

    /**
     * @param  array{
     *     street?: string|null,
     *     number?: string|null,
     *     neighborhood?: string|null,
     *     city?: string|null,
     *     state?: string|null
     * }|object  $address
     */
    public static function mapsUrl(array|object $address): ?string
    {
        $query = collect([
            self::value($address, 'street'),
            self::value($address, 'number'),
            self::value($address, 'neighborhood'),
            self::value($address, 'city'),
            self::value($address, 'state'),
        ])->filter()->implode(', ');

        if ($query === '') {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($query);
    }

    public static function formatZip(?string $zip): string
    {
        $digits = preg_replace('/\D+/', '', (string) $zip) ?? '';

        if (strlen($digits) !== 8) {
            return $digits;
        }

        return substr($digits, 0, 5).'-'.substr($digits, 5);
    }

    public static function formatPhone(?string $phone): string
    {
        $digits = PhoneDigits::normalize($phone);

        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
        }

        return $phone ?? '';
    }

    private static function value(array|object $address, string $key): string
    {
        $raw = is_array($address) ? ($address[$key] ?? null) : ($address->{$key} ?? null);

        return trim((string) $raw);
    }
}
