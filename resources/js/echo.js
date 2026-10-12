import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Real-time event bridge (contracts/broadcasting-channels.md; FR-027).
 *
 * Echo is only bootstrapped when a Pusher app key is configured for the
 * frontend build, so environments without real-time keep working silently.
 */
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        forceTLS: true,
    });
}