<template>
    <component :is="main">
        <messenger></messenger>
    </component>
</template>

<script>
import {defineAsyncComponent} from 'vue'
import Messenger from "@/components/Messenger.vue";

export default {
    name: 'App',
    components: {
        Messenger
    },
    data: () => {
        return {

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
    }
}
</script>
