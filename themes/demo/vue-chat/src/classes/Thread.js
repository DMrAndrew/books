import {reactive} from "vue";
import Message from '@/classes/Message'

export default class Thread {
    constructor(thread) {
        this.thread = thread
        this.meta = [];
        this._messages = reactive([])
        this.id = this.thread.id
        this.avatar = this.thread.avatar.md
        this.created_at = moment(this.thread.created_at)
        this.updated_at = moment(this.thread.updated_at)
        this.last_message = Message.make(this.thread.resources.latest_message)


    }

    load(item = null) {
        return new Promise((resolve, reject) => {
            if (!item) {
                resolve()
            }
            this._messages.push(Message.make(item))
            resolve()
        })
    }

    messagesFromRequest(args = null) {

        return new Promise((resolve, reject) => {
            if (!args) {
                resolve()
            }

            this.meta = args

            if (args.data) {
                Promise.all(args.data.map(e => this.load(e))).then(() => {
                    resolve()
                })
            }
        });
    }

    sort() {
        const compare = (a, b) => a.created_at.isAfter(b.created_at)
        this._messages.sort((a, b) => compare(a, b) ? 1 : (compare(b, a) ? -1 : 0))
        return this;
    }

    messages() {
        return Object.groupBy(this.sort()._messages, (i) => i.updated_at.format('DD-MM-YY'))
    }

    clean() {
        this._messages = reactive([])
        return this;
    }

    static make(src) {
        return src instanceof Thread ? src : Thread.new(src)
    }

    static new(src) {
        return new Proxy(new Thread(src), {
            get(target, prop) {
                switch (true) {
                    case prop in target: {
                        return target[prop];
                    }
                    case prop in target.thread: {
                        return target.thread[prop];
                    }
                    default: {
                        return null;
                    }
                }
            }
        })

    }
}
