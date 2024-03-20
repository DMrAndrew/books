import Echo from "laravel-echo"

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.PUSHER_APP_KEY,
    httpHost: window.location.hostname,
    httpsHost: window.location.hostname,
    wsHost: window.location.hostname,
    wssHost: window.location.hostname,
    encrypted: true,
    wsPort: process.env.WEBSOCKETS_LISTEN_PORT ?? 6002,
    wssPort: process.env.WEBSOCKETS_LISTEN_PORT ?? 6002,
    forceTLS: process.env.APP_SCHEMA === 'https',
    disableStats: false,
    cluster: process.env.PUSHER_CLUSTER,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: 'api/broadcasting/auth',
    auth: {
        headers: {
            'x-app-id': process.env.PUSHER_APP_ID
        }
    }
})
window.Echo.authProfileSubscribe = async function () {
    try {
        if (window && window.Echo) {
            const user = JSON.parse(localStorage.getItem('user'))
            if (user && user.profile) {
                const channel = 'private-profile.' + user.profile.id
                if(window.Echo.connector.channels[channel]){
                    return
                }
                window.Echo.private('profile.' + user.profile.id)
                    .listen('.notifications', function (data) {
                            Object.keys(data).forEach((id) => {
                                let el = document.getElementById(id)
                                if (el) {
                                    el.innerHTML = data[id]
                                }
                            })
                        }
                    )
            }
        }
    } catch (e) {
        console.error(e)
    }
}
window.Echo.authProfileDisconnect = function (){
    if(window.Echo){
        window.Echo.leaveAllChannels()
    }
}

addEventListener('page:loaded', window.Echo.authProfileSubscribe)
export default function () {

}