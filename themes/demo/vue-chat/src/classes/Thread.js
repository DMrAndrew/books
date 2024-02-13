import {reactive} from "vue";
import Message from '@/classes/Message'

export default class Thread {
    constructor(thread) {
        this.thread = thread
        this.messages = reactive([])
        this.id = this.thread.id
        this.avatar = this.thread.avatar.md
        this.created_at = moment(this.thread.created_at)
        this.updated_at = moment(this.thread.updated_at)
        this.last_message = Message.make(this.thread.resources.latest_message)
    }

    load(item = null) {
        if (!item) {
            return;
        }
        this.messages.push(this.make(item))
        this.sort()
    }


    messagesFromRequest(args = null) {
        if (!args) {
            return;
        }

        let [data, meta, system_features] = Object.values(args)
        this.meta = meta
        this.system_features = system_features
        data.forEach(e => this.load(e))
        this.sort()
    }

    sort() {
        this.messages.sort((a, b) => (
            a.created_at.isAfter(b.created_at)) ? 1
            : (b.created_at.isAfter(a.created_at)) ? -1
                : 0)
    }

    clean() {
        this.messages = reactive([])
    }

    make(e) {
        return e instanceof Message ? e : Message.make(e);
    }

    static make(src) {
        return new Proxy(new Thread(src), {
            get(target, prop) {
                if (prop in target) {
                    return target[prop];
                } else {
                    return target.thread[prop];
                }
            }
        })

    }
}
