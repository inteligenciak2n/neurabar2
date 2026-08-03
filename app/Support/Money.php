<?php

namespace App\Support;

/**
 * Aritmética monetária em inteiro.
 *
 * Todo valor de dinheiro do domínio de cobrança é armazenado e manipulado como
 * inteiro na menor subdivisão da moeda (centavos). Float acumula erro de
 * representação — `0.1 + 0.2 !== 0.3` — e o épico de assinaturas somava dezenas
 * de parcelas proporcionais antes de gravar o total, o que produzia divergência
 * de centavos entre a fatura e a cobrança enviada ao gateway.
 *
 * Duas escalas convivem:
 *  - `SCALE` (100): valores finais — mensalidades, faturas, descontos, tentativas.
 *  - `MICRO_SCALE` (10.000): preços unitários de consumo medido, que precisam de
 *    4 casas decimais (ex.: R$ 0,0500 por mensagem enviada).
 */
final class Money
{
    /**
     * Subdivisões por unidade monetária para valores finais (centavos).
     */
    public const SCALE = 100;

    /**
     * Subdivisões por unidade monetária para preços unitários (centésimos de centavo).
     */
    public const MICRO_SCALE = 10_000;

    /**
     * Converte um valor decimal em unidades monetárias para inteiro na escala informada.
     */
    public static function fromFloat(int|float|string|null $amount, int $scale = self::SCALE): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round(((float) $amount) * $scale);
    }

    /**
     * Converte um inteiro na escala informada de volta para unidades monetárias.
     *
     * Use apenas em fronteiras que exigem decimal: payloads do gateway,
     * respostas de API pública e formatação.
     */
    public static function toFloat(?int $amount, int $scale = self::SCALE): float
    {
        return round(($amount ?? 0) / $scale, self::precisionFor($scale));
    }

    /**
     * Converte um preço unitário (escala micro) para centavos, arredondando.
     */
    public static function fromMicros(int $micros): int
    {
        return (int) round($micros / (self::MICRO_SCALE / self::SCALE));
    }

    /**
     * Multiplica um valor inteiro por um fator fracionário (proration, por exemplo).
     */
    public static function multiply(int $amount, float $factor): int
    {
        return (int) round($amount * $factor);
    }

    /**
     * Aplica um percentual expresso em pontos-base (1/100 de 1%).
     *
     * `CorporationDiscount::value` guarda 1500 para 15% e 5000 para R$ 50,00 —
     * a mesma escala de 100 serve às duas semânticas do campo.
     */
    public static function percentage(int $amount, int $basisPoints): int
    {
        return (int) round($amount * $basisPoints / (100 * self::SCALE));
    }

    /**
     * Formata para exibição em pt-BR (ex.: `R$ 1.299,90`).
     */
    public static function format(?int $amount, int $scale = self::SCALE): string
    {
        return 'R$ '.number_format(self::toFloat($amount, $scale), self::precisionFor($scale), ',', '.');
    }

    private static function precisionFor(int $scale): int
    {
        return max(0, (int) round(log10($scale)));
    }
}
