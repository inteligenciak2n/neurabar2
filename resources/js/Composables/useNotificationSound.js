let notificationSound = null;

/**
 * Shared "new order" notification sound, lazily instantiated once per page load.
 */
export function useNotificationSound() {
    function playSound() {
        try {
            if (!notificationSound) {
                notificationSound = new Audio('/sounds/new-order.mp3');
            }
            notificationSound.play().catch(() => {});
        } catch {
            // Audio not available
        }
    }

    return { playSound };
}
