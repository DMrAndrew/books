import Echo from "laravel-echo"

window.Pusher = require('pusher-js');
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.VUE_APP_PUSHER_APP_KEY,
    httpHost: window.location.hostname,
    wsHost: window.location.hostname,
    wsPort: process.env.VUE_APP_PUSHER_APP_PORT,
    forceTLS: process.env.NODE_ENV === 'production',
    disableStats: true,
    cluster: process.env.VUE_APP_PUSHER_APP_CLUSTER,
    authEndpoint: 'api/broadcasting/auth',
    auth: {
        headers: {
            'x-app-id': process.env.VUE_APP_PUSHER_APP_ID
        }
    }
});

addEventListener('page:loaded', function () {
    try {
        if (window && window.Echo && !window.profileChannel) {
            const user = JSON.parse(localStorage.getItem('user'))
            const replacer = (data) => {
                Object.keys(data).forEach((id) => {

                    let el = document.getElementById(id)
                    if (el) {
                        el.innerHTML = data[id]
                    }
                })
            }
            if (user && user.profile) {
                window.profileChannel = window.Echo.private('profile.' + user.profile.id)
                    .listen('.notifications', replacer)
            }
        }
    } catch (e) {
        console.error(e)
    }
})
export default function () {

}