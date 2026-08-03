/**
 * Currency formatting shared by the billing screens.
 *
 * Pages used to call `$page.props.formatMoney(...)`, which is never shared by
 * HandleInertiaRequests and therefore threw at render time.
 */
export function useCurrency() {
    const formatMoney = (value) =>
        'R$ ' + Number(value ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

    return { formatMoney };
}
