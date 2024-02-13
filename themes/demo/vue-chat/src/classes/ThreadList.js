import {reactive} from "vue";
import Thread from "@/classes/Thread";

export default class ThreadList {
    constructor() {
        this.thread = reactive([]); //active thread
        this.items = reactive([])
        this.meta = []
        this.system_features = []

    }

    isHasActive(){
        return this.thread instanceof Thread;
    }
    setThread(thread){
        this.thread = reactive(thread instanceof Thread ? thread : this.make(thread))
    }
    load(item = null) {
        if (!item) {
            return;
        }
        this.items.push(this.make(item))

    }

    fromRequest(args = null) {
        if (!args) {
            return;
        }

        let [data, meta, system_features] = Object.values(args)
        this.meta = meta
        this.system_features = system_features
        data.forEach(e => this.load(e))
    }
    make(e) {
        return e instanceof Thread ? e : Thread.make(e);
    }
}
