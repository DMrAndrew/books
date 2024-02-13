import Echo from "laravel-echo"
window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.PUSHER_APP_KEY,
    wsHost: window.location.hostname,
    wsPort: process.env.PUSHER_APP_PORT,
    forceTLS: false,
    disableStats: true,
    cluster:process.env.PUSHER_APP_CLUSTER,
    authEndpoint:'broadcasting/auth',
    auth: {
        headers: {
            'x-app-id': process.env.PUSHER_APP_ID
        }
    }
});
