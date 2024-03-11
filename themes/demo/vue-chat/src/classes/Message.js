import {Participant} from "@/classes/Participant";
import {messages_point} from "@/classes/utils";
import axios from "@/axios";

export default class Message {
    constructor(message) {
        this._message = message
        this._error = ''
        this._failed = false
        this._sending = false
        this._visible = false
        this._was_visible = false
    }

    get id() {
        return this._message?.id
    }

    get wasVisible() {
        return this._was_visible
    }

    set wasVisible(val) {
        this._was_visible = val
    }

    set visible(val) {
        this._visible = val
    }

    get visible() {
        return this._visible
    }

    is(message) {
        return (message.id && this.id && message.id == this.id) || (this.temporary_id && message.temporary_id && this.temporary_id == message.temporary_id)
    }

    async send(fake = false) {
        if (fake) return

        if (this.isNew && !this.sending) {
            this.sending = true
            return await axios.post(messages_point(this.thread_id), {
                message: this.body,
                temporary_id: this.temporary_id
            }).catch(e => {
                this.isFailed = true
                console.log(e)
            }).finally(() => {
                this.sending = false
            })
        }
        return
    }

    get thread_id() {
        return this._message.thread_id
    }

    get temporary_id() {
        return this._message.temporary_id
    }

    get isFailed() {
        return this._failed
    }

    set isFailed(val) {
        this._failed = val
    }

    get sending() {
        return this._sending
    }

    set sending(val) {
        this._sending = val
    }

    get isNew() {
        return this._message.is_new
    }

    get message() {
        return this._message;
    }

    get owner() {
        return this._message.owner ? Participant.make(this._message.owner) : Participant.nullObject()
    }

    get body() {
        return this._message?.body ?? ''
    }

    get time() {
        return this.updated_at.format('HH:mm')
    }

    get created_at() {
        if (this.isNew) {
            return moment(new Date(Number(this.temporary_id)).toISOString())
        }
        if (this.message.created_at) {
            return moment(this.message.created_at)
        }
        return moment()
    }

    get updated_at() {
        if (this.isNew) {
            return moment(new Date(Number(this.temporary_id)).toISOString())
        }
        if (this.message.updated_at) {
            return moment(this.message.updated_at)
        }

        return moment()
    }

    static make(src) {
        if (!src) {
            return Message.nullObject()
        }
        return src instanceof Message ? src : Message.new(src)
    }

    static new(src) {
        return new Proxy(new Message(src), {
            get(target, prop) {
                if (prop in target) {
                    return target[prop];
                }
                if (target._message && prop in target._message) {
                    return target._message[prop];
                }
                return null;
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
