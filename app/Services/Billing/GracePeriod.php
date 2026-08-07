<?php

namespace App\Services\Billing;

use DateTimeInterface;
use Illuminate\Contracts\Database\Query\Builder;
use InvalidArgumentException;

/**
 * Regra única do período de carência.
 *
 * A expressão `data + carência <= agora` vivia repetida em três `whereRaw`
 * dentro de um job, invisível para testes: qualquer divergência entre elas
 * suspendia clientes em dias diferentes conforme o caminho percorrido.
 */
class GracePeriod
{
    /**
     * Restringe a query às linhas cuja carência já se esgotou.
     *
     * @param  string  $dateColumn  Coluna de referência (vencimento ou fim do trial).
     * @param  string  $graceColumn  Coluna com a quantidade de dias de carência.
     */
    public static function elapsed(Builder $query, string $dateColumn, string $graceColumn, ?DateTimeInterface $at = null): Builder
    {
        return $query->whereRaw(
            self::expression($dateColumn, $graceColumn),
            [$at ?? now()],
        );
    }

    /**
     * Expressão SQL comparando a data acrescida da carência com um parâmetro.
     */
    public static function expression(string $dateColumn, string $graceColumn): string
    {
        return sprintf(
            "%s + INTERVAL '1 day' * %s <= ?",
            self::assertIdentifier($dateColumn),
            self::assertIdentifier($graceColumn),
        );
    }

    /**
     * Os nomes de coluna são internos, mas concatenar identificadores em SQL
     * sem validação é exatamente como injeções nascem quando o método é
     * reaproveitado mais tarde.
     */
    private static function assertIdentifier(string $column): string
    {
        if (preg_match('/^[a-z_][a-z0-9_]*(\.[a-z_][a-z0-9_]*)?$/i', $column) !== 1) {
            throw new InvalidArgumentException("Invalid column identifier: {$column}");
        }

        return $column;
    }
}
