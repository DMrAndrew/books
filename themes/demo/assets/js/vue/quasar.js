import {createApp} from "vue";
import {Quasar} from 'quasar'
import Lang from "./lang.js";

Quasar.lang.ru = Lang()
Quasar.lang.set(Quasar.lang.ru)
window.q = Quasar
window.mountApp = function (component, container) {
    createApp(component).use(Quasar, {config: {}, lang: 'ru_RU'}).mount(container);
}
