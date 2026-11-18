<?php

namespace App\Support;

class NeighborhoodMatcher
{
    /**
     * @var array<string, string>
     */
    private const ABBREVIATIONS = [
        'jd' => 'jardim',
        'vl' => 'vila',
        'pq' => 'parque',
        'res' => 'residencial',
        'dist' => 'distrito',
        'chac' => 'chacara',
    ];

    /**
     * @var array<string, string>
     */
    private const ACCENTS = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    public static function normalize(?string $name): string
    {
        $value = mb_strtolower(trim((string) $name));

        if ($value === '') {
            return '';
        }

        $value = strtr($value, self::ACCENTS);
        $value = str_replace('.', ' ', $value);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? $value;

        $parts = array_map(
            fn (string $part): string => self::ABBREVIATIONS[$part] ?? $part,
            explode(' ', $value),
        );

        return implode(' ', array_filter($parts, fn (string $part): bool => $part !== ''));
    }

    /**
     * @param  iterable<int, array{id?: string, name?: string, label?: string}|object>  $neighborhoods
     */
    public static function match(?string $needle, iterable $neighborhoods): mixed
    {
        $normalizedNeedle = self::normalize($needle);

        if ($normalizedNeedle === '') {
            return null;
        }

        foreach ($neighborhoods as $neighborhood) {
            $name = self::nameOf($neighborhood);

            if (self::normalize($name) === $normalizedNeedle) {
                return $neighborhood;
            }
        }

        return null;
    }

    private static function nameOf(mixed $neighborhood): string
    {
        if (is_array($neighborhood)) {
            return (string) ($neighborhood['name'] ?? $neighborhood['label'] ?? '');
        }

        return (string) ($neighborhood->name ?? $neighborhood->label ?? '');
    }
}
