import {createApp, ref} from 'vue'
import App from './App.vue'
import store from './store'
import axios from "./axios";
import VueAxios from "vue-axios";
import mixin from './mixins/default'
import moment from 'moment'
import {messenger} from "@/classes/Messenger";


moment.locale('ru')
window.moment = moment
const isProduction = mixin.computed.isProduction();
const app = createApp(App)
    app
    .use(store)
    .use(moment)
    .use({
        install: (app, options) => {
            app.config.globalProperties.$messenger = ref(messenger)
        }
    })
    .use(VueAxios, axios)
    .mixin(mixin)
    .mount(isProduction ? '#app-chat' : '#app-dev')



