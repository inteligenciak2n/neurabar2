export const LOCATOR_FIELD_KEYS = ['tables', 'tab', 'order', 'name', 'product'];

function includesQuery(value, query) {
    return String(value ?? '').toLowerCase().includes(query);
}

function valuesForField(attendance, field) {
    if (field === 'tables') {
        return [attendance.service_location?.name];
    }

    if (field === 'tab') {
        return [attendance.tab?.code, attendance.code];
    }

    if (field === 'order') {
        return (attendance.orders ?? []).map((order) => order.order_number);
    }

    if (field === 'name') {
        return [attendance.customer_identifier];
    }

    if (field === 'product') {
        return (attendance.orders ?? []).flatMap((order) =>
            (order.items ?? []).flatMap((item) => [
                item.product?.name,
                item.combo?.name,
            ]),
        );
    }

    return [];
}

export function attendanceMatchesLocator(attendance, query, fields = []) {
    const needle = String(query ?? '').trim().toLowerCase();

    if (!needle) {
        return true;
    }

    const activeFields = fields.length ? fields : LOCATOR_FIELD_KEYS;

    return activeFields.some((field) =>
        valuesForField(attendance, field).some((value) => includesQuery(value, needle)),
    );
}
