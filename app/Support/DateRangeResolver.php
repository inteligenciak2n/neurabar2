<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class DateRangeResolver
{
    /**
     * Resolve a period preset (or custom range) into current and previous date boundaries.
     *
     * @return array{from: Carbon, to: Carbon, previous_from: Carbon, previous_to: Carbon}
     */
    public function resolve(string $period, ?string $from = null, ?string $to = null): array
    {
        [$start, $end] = match ($period) {
            'today' => [Carbon::today(), Carbon::today()->endOfDay()],
            '7d' => [Carbon::today()->subDays(6), Carbon::today()->endOfDay()],
            'month' => [Carbon::today()->startOfMonth(), Carbon::today()->endOfDay()],
            'custom' => [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()],
            default => [Carbon::today()->subDays(29), Carbon::today()->endOfDay()],
        };

        // Período anterior tem a mesma duração, imediatamente antes de $start.
        // Normaliza para meia-noite antes de diffInDays(): comparar horários (ex.: 00:00:00
        // vs 23:59:59) devolve um float fracionário, que quebra subDays() com precisão de ponto flutuante.
        $days = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $previousEnd = $start->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return [
            'from' => $start,
            'to' => $end,
            'previous_from' => $previousStart,
            'previous_to' => $previousEnd,
        ];
    }
}
