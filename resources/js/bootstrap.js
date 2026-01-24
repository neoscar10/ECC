import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,

    // If host is empty (normal for Pusher SaaS), Echo will use Pusher defaults.
    wsHost: import.meta.env.VITE_PUSHER_HOST || undefined,
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT || 443),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT || 443),
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'https') === 'https',

    enabledTransports: ['ws', 'wss'],
});
