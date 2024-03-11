import Message from '@/classes/Message'
import {compareDates, messages_point, thread_point} from '@/classes/utils'
import {Participant} from "@/classes/Participant";
import axios from "@/axios";
import {messenger} from "@/classes/Messenger";

export default class Thread {
    constructor(thread) {
        this.messenger = messenger
        this.thread = (thread)
        this.messages_meta = null;
        this._messages = ([])
        this._last_read_at = null
        this._reading = false
    }

    get last_read_at() {
        return this._last_read_at
    }

    set last_read_at(val) {
        this._last_read_at = moment(val)
    }

    get unread_count() {
        if (this.last_read_at) {
            return this._messages
                .filter(e => !e.owner.is(this.messenger.provider) && e.created_at.isAfter(this.last_read_at))
                .length + this.thread.unread_count
        }
        return this.thread.unread_count
    }

    set unread_count(val) {
        this.thread.unread_count = val
    }

    is(thread) {
        return (this.id && thread.id && (this.id === thread.id)) || (this.tempID && thread.tempID && (thread.tempID === this.tempID))
    }

    get tempID() {
        return this.recipient.provider_alias + this.recipient.providerID
    }

    get next_page_message() {
        return this._messages[1]
    }

    get isFinalPage() {
        return this.messages_meta?.final_page ?? false
    }

    get isNew() {
        return Boolean(this.thread.is_new)
    }

    get name() {
        return this.isPrivate ? this.recipient.name : this.thread.name
    }

    get created_at() {
        return moment(this.thread.created_at)
    }

    get updated_at() {
        return moment(this.thread.updated_at)
    }

    set last_message(val) {
        this.thread.resources.latest_message = val
    }

    get last_message() {
        if (this.isNew) {
            return this.latest_message
        }
        return this.hasMessages ? this.messages()[this.length - 1] : this.latest_message
    }

    get latest_message() {
        return Message.make(this.hasLastMessage ? this.thread.resources.latest_message : null)
    }

    get hasLastMessage() {
        return !!this.thread.resources?.latest_message
    }

    get hasMessages() {
        return !!this._messages.length
    }

    get length() {
        return this._messages.length
    }

    get id() {
        return this.thread?.id
    }

    set id(id) {
        this.thread.id = id
    }

    get isAdmin() {
        return this.thread?.options?.admin
    }

    async read() {
        if (this._reading) {
            return
        }
        this._reading = true
        axios.get(thread_point(this.id) + 'mark-read').then(e => {
            this.unread_count = 0
            this.last_read_at = moment()
        }).finally(() => this._reading = false)
    }

    async leave() {
        if (this.isGroup && !this.isAdmin) {
            await axios.post(thread_point(this.id) + 'leave').then(e => {
                this.messenger.deleteThread(this)
            })
        }
    }

    async block() {
        if (this.isPrivate) {
            await axios.get('/block/' + this.recipient.providerID)
                .then(e => this.delete())
                .catch(e => console.log(e))
        }
    }

    get isPrivate() {
        return !this.isGroup
    }

    get isGroup() {
        return Boolean(this.thread.group)
    }

    get recipient() {
        return Participant.make(this.isPrivate ? this.thread?.resources?.recipient : {
            name: this.thread.name
        })
    }

    get hasNew() {
        return this._messages.find(e => e.isNew)
    }

    async load(item = null, touch = false, timestamp = false) {
        if (!item) return;
        item = Message.make(item)

        if (this.hasNew) {
            const msg = this._messages.find(e => e.isNew && e.temporary_id === item.temporary_id)
            if (msg) {
                this._messages = this._messages.filter(e => !e.isNew && e.temporary_id !== msg.temporary_id)
            }
        }
        if (!this.length && timestamp) {
            this.last_read_at = moment(item.created_at).subtract(1, 'seconds')
        }
        this._messages.push(item);
        if (touch) {
            this.thread.updated_at = item.updated_at
        }
    }

    async sendMessage(text) {
        const message = new Message({
            thread_id: this.id, is_new: true, temporary_id: Date.now() + '', body: text, owner: this.messenger.provider
        });

        if (this.isNew) {
            const newPrivate = {
                message: message.body, recipient_alias: 'profile', recipient_id: this.recipient.providerID
            }
            axios.post('/privates', newPrivate).then((e) => this.messenger.newThread(e.data))

        }
        if (!this.messenger.hasThread(this)) {
            this.messenger.addThreadEntity(this, true)
        }
        this.messenger.sidebar.list_query = ''
        message.send(this.isNew)
        await this.load(message, true)

    }

    async delete() {
        await axios.delete('threads/' + this.id)
            .then(e => this.messenger.deleteThread(this))
            .catch(e => console.log(e))
    }

    async loadMessages(url, clean) {
        url = url ? url : messages_point(this.id)
        return await axios.get(url)
            .then((res) => {
                if (clean) {
                    this._messages = []
                }
                this.extract(res)
            })
    }

    async loadNextPage() {
        if (this.length && this.next_page_message && !this.isFinalPage && this.messages_meta?.next_page_id) {
            await this.loadMessages(messages_point(this.id) + '/page/' + this.messages_meta.next_page_id)
        }
    }

    async extract(res) {
        let data = res.data.data
        this.messages_meta = res.data.meta
        if (data) {
            return await Promise.all(data.map(e => this.load(e)))
        }
        return;
    }

    sort() {
        this._messages.sort((a, b) => compareDates(a.created_at, b.created_at))
        return this;
    }

    messages() {
        return this.sort()._messages
    }

    static make(src) {
        if (!src) {
            return Thread.nullObject()
        }
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

    static nullObject() {
        return new Proxy({}, {
            get(target, prop) {
                return null;
            }
        });
    }


}
