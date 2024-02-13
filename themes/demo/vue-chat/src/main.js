import {createApp, reactive} from 'vue'
import App from './App.vue'
import store from './store'
import axios from "./axios";
import VueAxios from "vue-axios";
import mixin from './mixins/default'
import moment from 'moment'
import {devUser} from './classes/stubs'
import createMessenger from "@/classes/Messenger";
moment.locale('ru')
window.moment = moment
const isProduction = mixin.computed.isProduction();
(() => isProduction ? true : localStorage.setItem('user', JSON.stringify(devUser)))(); // default user for dev

const app = createApp(App)
    .use(store)
    .use(moment)
    .use({
        install: (app, options) => {
            app.config.globalProperties.$messenger = reactive(createMessenger())
        }
    })
    .use(VueAxios, axios)
    .mixin(mixin)
    .mount(isProduction ? '#app-chat' : '#app-dev')


