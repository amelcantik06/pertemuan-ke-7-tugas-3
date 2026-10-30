import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.content;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || metaContent('reverb-scheme') || 'http';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || metaContent('reverb-key'),
    wsHost: import.meta.env.VITE_REVERB_HOST || metaContent('reverb-host') || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || metaContent('reverb-port') || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || metaContent('reverb-port') || 8080),
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
});