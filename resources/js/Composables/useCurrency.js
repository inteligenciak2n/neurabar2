/**
 * Currency formatting shared by the billing screens.
 *
 * Pages used to call `$page.props.formatMoney(...)`, which is never shared by
 * HandleInertiaRequests and therefore threw at render time.
 *
 * O backend expõe todo valor monetário como inteiro em centavos — float
 * acumulava erro de arredondamento entre a fatura e a cobrança no gateway. As
 * telas continuam exibindo e editando reais, então a conversão mora aqui.
 */
const CENTS_PER_UNIT = 100;

export function useCurrency() {
    /** Centavos -> `R$ 1.299,90`. */
    const formatMoney = (cents) =>
        'R$ ' +
        (Number(cents ?? 0) / CENTS_PER_UNIT).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

    /** Centavos -> número em reais, para preencher inputs `step="0.01"`. */
    const toAmount = (cents) => Number(cents ?? 0) / CENTS_PER_UNIT;

    /** Reais -> centavos. */
    const toCents = (amount) => Math.round(Number(amount ?? 0) * CENTS_PER_UNIT);

    return { formatMoney, toAmount, toCents };
}
