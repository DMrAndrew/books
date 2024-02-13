import Thread from "@/classes/Thread";
import {Sidebar} from "@/classes/Sidebar";
import {Participant} from "@/classes/Participant";
import {compareDates, heartbeat_point} from '@/classes/utils'
import axios from "@/axios";

class Messenger {
    constructor(options = {}) {
        this._thread = ({})
        this._threads = ([])
        this.sidebar = new Sidebar()
        this.thread_meta = []
        this._settings = {}
        this._heartbeat = {}
        this.heartbeat()
        this._heartbeat_interval = setInterval(this.heartbeat.bind(this), 50000)
    }

    async syncSettings() {
        await axios.get('/settings').then(e => {
            this._settings = e.data
            const profile = this.settings.owner?.base
            localStorage.setItem('user', JSON.stringify({
                profile:profile
            }))
        })

    }

    hasThread(thread) {
        return this._threads.find(e => e.is(thread))
    }

    get provider() {
        return Participant.make(this._heartbeat.provider)
    }

    clean() {
        clearInterval(this._heartbeat_interval)
        this.heartbeat(true)
    }

    heartbeat(away = false) {
        axios.post(heartbeat_point(), {away: away}).then(e => this._heartbeat = e.data)
    }

    get settings() {
        return this._settings
    }

    get thread() {
        return this.hasCurrentThread() ? this._thread : null
    }

    get threads() {
        return this._threads.sort((a, b) => compareDates(a.updated_at, b.updated_at, false))
    }

    set thread(thread) {
        this._thread = thread
    }

    get latestRecipients() {
        return this.threads.filter(e => e.isPrivate).map(e => Participant.make(e.thread.resources.recipient));
    }

    newMessage(msg) {
        if (!msg) {
            return this;
        }
        this._threads.find(e => e.id === msg.thread_id)?.load(msg, true, true)
        return this;
    }

    async newThread(thread) {
        this.deleteThread(this.thread).then(() => {
            this.addThreadEntity(thread, true)
        })
    }

    async newGroup(data) {
        await axios.post('/groups', data).then(e => {
            this.addThreadEntity(e.data, true)
        }).catch(e => Promise.reject(e))
    }

    async removeThread(threadID) {
        await this.deleteThread({id: threadID}, false)
    }

    async deleteThread(thread, clean = true) {
        this._threads = this._threads.filter(e => e.id != thread.id)

        if (clean || this._thread.id == thread.id) {
            this._thread = {}
        }
    }

    hasCurrentThread() {
        return this._thread.hasOwnProperty('thread') ?? false;
    }

    async addThreadEntity(thread, active = false) {
        if (!thread) {
            return;
        }
        this._threads.push(Thread.make(thread))
        if (active) {
            this.thread = this._threads.find(e => e.id === thread.id)
        }
    }

    get hasMoreThreads() {
        return (!!!this.thread_meta.meta?.final_page)
    }

    threadsFromRequest(args = null) {
        if (!args) {
            return;
        }
        this.thread_meta = args
        this.thread_meta.data.forEach(e => this.addThreadEntity(e))
    }

    async loadNextThreadPage() {
        if (!this._threads.length || !this.hasMoreThreads) {
            return
        }
        await this.loadThreads(this.thread_meta.meta?.next_page_id)
    }

    async loadThreads(page = false) {
        const url = '/threads' + (page ? ('/page/' + page) : '')
        await axios.get(url)
            .then(e => this.threadsFromRequest(e.data))
            .catch(e => console.log(e))

    }

    markLastRead(data) {
        const thread = this._threads.find(e => e.id == data.thread_id)
        if (thread) {
            thread.last_read_at = data.last_read
        }
    }

    async loadThread(id) {
        if (!id) {
            return
        }
        await axios.get('/threads/' + id)
            .then(e => this.addThreadEntity(e.data))
            .catch(e => console.log(e))

    }
}

export let messenger = new Messenger()