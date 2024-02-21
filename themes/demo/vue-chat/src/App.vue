<template>
    <component :is="main">
        <messenger></messenger>
    </component>
</template>

<script>
import {defineAsyncComponent} from 'vue'
import Messenger from "@/components/Messenger.vue";

import Echo from "laravel-echo"

window.Pusher = require('pusher-js');

export default {
    name: 'App',
    components: {
        Messenger
    },
    data: () => {
        return {
            counter: 1
        }
    },
    computed: {
        main() {
            return defineAsyncComponent(() => this.isProduction
                ? import('./components/app-templates/Production.vue')
                : import('./components/app-templates/Development.vue'))
        }
    },
    methods: {
      echo(){
        window.Echo =  new Echo({
          broadcaster: 'pusher',
          key: process.env.VUE_APP_PUSHER_APP_KEY,
          httpHost: window.location.hostname,
          wsHost: window.location.hostname,
          wsPort: process.env.VUE_APP_PUSHER_APP_PORT,
          forceTLS: false,
          disableStats: true,
          cluster: process.env.VUE_APP_PUSHER_APP_CLUSTER,
          authEndpoint: 'api/broadcasting/auth',
          auth: {
            headers: {
              'x-app-id': process.env.VUE_APP_PUSHER_APP_ID
            }
          }
        })
      }
    },
    beforeCreate() {


    },
}
</script>
<style lang="css">
</style>
