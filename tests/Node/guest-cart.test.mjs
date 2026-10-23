import assert from 'node:assert/strict';
import test from 'node:test';
import { guestCartItemSignature, mergeGuestCartItem } from '../../resources/js/Utils/guestCart.js';

test('keeps the same product as separate lines when modifiers differ', () => {
    const items = [];
    const first = {
        product_id: 'misto',
        variation_id: null,
        notes: null,
        modifiers: ['cheese'],
        quantity: 1,
    };
    const second = {
        product_id: 'misto',
        variation_id: null,
        notes: null,
        modifiers: ['ham'],
        quantity: 1,
    };

    mergeGuestCartItem(items, first);
    mergeGuestCartItem(items, second);

    assert.equal(items.length, 2);
    assert.equal(items[0].quantity, 1);
    assert.equal(items[1].quantity, 1);
    assert.notEqual(guestCartItemSignature(first), guestCartItemSignature(second));
});

test('merges only identical product, variation, notes and modifiers', () => {
    const items = [];
    const first = {
        product_id: 'misto',
        variation_id: 'large',
        notes: 'sem cebola',
        modifiers: ['b', 'a'],
        quantity: 1,
    };

    mergeGuestCartItem(items, first);
    mergeGuestCartItem(items, {
        ...first,
        modifiers: ['a', 'b'],
        quantity: 2,
    });

    assert.equal(items.length, 1);
    assert.equal(items[0].quantity, 3);
});
