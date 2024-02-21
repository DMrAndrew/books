import Thread from "@/classes/Thread";
import ThreadList from './ThreadList'
import {reactive} from "vue";

class Messenger {
    constructor(options = []) {
        this._thread = reactive({})
        this._threads = reactive([])
        this.thread_meta = []
    }

    get thread(){
        return this._thread.hasOwnProperty('id') ? this._thread : null
    }
    set thread(thread){
        this._thread = Thread.make(thread)
    }

    get threads(){
        return this._threads
    }
    addThread(thread){
        if(!thread){
            return;
        }
        this._threads.push(Thread.make(thread))
    }
    threadsFromRequest(args = null) {
        if (!args) {
            return;
        }
        this.thread_meta = args
        this.thread_meta.data.forEach(e => this.addThread(e))
    }
}

export default function (data) {
    return new Messenger(data);
}
