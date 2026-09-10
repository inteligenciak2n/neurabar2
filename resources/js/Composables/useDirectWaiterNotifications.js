import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const unreadCount = ref(0);
let channel = null;
let boundVenueId = null;

/**
 * Shared unread counter for DirectWaiter messages fed by a single Echo
 * subscription, so the header badge stays in sync no matter which page is open.
 */
export function useDirectWaiterNotifications() {
    const page = usePage();

    function subscribe() {
        const venueId = page.props.defs?.venue?.id;
        if (!venueId || !window.Echo || boundVenueId === venueId) {
            return;
        }

        if (channel && boundVenueId) {
            window.Echo.leaveChannel(`venue.${boundVenueId}.service-requests`);
        }

        const currentUserId = page.props.auth?.user?.id;

        channel = window.Echo.private(`venue.${venueId}.service-requests`)
            .listen('.ServiceRequestCreated', (event) => {
                if (event.type !== 'message') return;
                // Reivindicada por outro atendente: não é minha notificação.
                if (event.assigned_user_id && event.assigned_user_id !== currentUserId) return;
                unreadCount.value += 1;
            });

        boundVenueId = venueId;
    }

    function reset() {
        unreadCount.value = 0;
    }

    return { unreadCount, subscribe, reset };
}
