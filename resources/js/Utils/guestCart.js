export function guestCartItemSignature(item) {
    const modifiers = [...(item.modifiers ?? [])].map(String).sort().join(',');

    return [item.product_id, item.variation_id ?? '', String(item.notes ?? '').trim(), modifiers].join('|');
}

export function mergeGuestCartItem(items, item) {
    const signature = guestCartItemSignature(item);
    const existing = items.find((current) => guestCartItemSignature(current) === signature);

    if (existing) {
        existing.quantity += item.quantity;
        return;
    }

    items.push(item);
}
