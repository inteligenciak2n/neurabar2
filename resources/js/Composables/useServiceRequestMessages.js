import { useTranslate } from '@/Composables/useTranslate';

/**
 * Shared list of predefined quick messages for the guest "call waiter" flows
 * (Guest Hub signal panel). Fixed/hardcoded per product decision — not
 * configurable per venue.
 */
export function useServiceRequestMessages() {
    const __ = useTranslate();

    const predefinedMessages = [
        __('Need assistance'),
        __('Ready to order'),
        __('Requesting the bill'),
        __('More napkins, please'),
        __('Can we change tables?'),
    ];

    return { predefinedMessages };
}
