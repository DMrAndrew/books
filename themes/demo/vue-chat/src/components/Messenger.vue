<template>
    <div class="ui-container">
        <div class="ui-text-head--2 ui-text--bold page-title">Чат</div>
        <div class="ui-container-fluid --full">
            <div class="ui-grid-gap ui-grid-container _indent-large lc-chat-container">
                <div class="ui-col-sm-3">
                    <div class="wrapper">
                        <div class="header-search__container">
                            <div class="header-search__search">
                                <svg class="header-search__icon square-16">
                                    <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#search-stroked-24"></use>
                                </svg>
                                <input class="header-search__input" type="text" placeholder="Поиск" data-search="input">
                                <svg class="header-search__button-clear square-16" data-button-input="clear">
                                    <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#close-stroked-16"></use>
                                </svg>
                            </div>

                            <a class="header-search__action">
                                <svg width="24" height="24">
                                    <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#edit"></use>
                                </svg>
                            </a>
                        </div>

                        <!-- lc-chat__list -->
                        <div class="lc-chat__list scrollbar">
                            <thread-preview v-for="(item) in threads"
                                            :thread="item"
                                            :key="item.id">
                            </thread-preview>
                        </div>
                    </div>
                </div>

                <div class="ui-col-sm-9 hidden-sm">
                    <div class="wrapper">
                        <keep-alive>
                            <thread v-if="thread"
                                    :key="thread.id"
                                    :thread="thread">

                            </thread>
                            <empty-screen v-else></empty-screen>
                        </keep-alive>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import ThreadPreview from "@/components/sidebar/ThreadPreview.vue";
import Thread from "@/components/screen/Thread.vue";
import EmptyScreen from "@/components/screen/EmptyScreen.vue";
import {use} from "chai";

export default {
    name: "Messenger",
    components: {EmptyScreen, Thread, ThreadPreview},
    data: () => {
        return {
            channel: null,
        }
    },
    computed: {
        threads() {
            return this.$messenger.threads;
        },
        thread() {
            return this.$messenger.thread;
        }
    },
    methods: {
        loadThreads() {
            this.axios.get('/threads')
                .then(e => this.$messenger.threadsFromRequest(e.data))
                .catch(e => console.log(e))
        },
        connectChannel() {
            if (!this.user) {
                return;
            }
            this.channel = window.Echo.private('messenger.profile.' + this.user.profile.id)
                .listen('.new.message', (e) => console.log(e))
                .listen('.thread.archived', (e) => console.log(e))
                .listen('.message.archived', (e) => console.log(e))
                .listen('.knock.knock', (e) => console.log(e))
                .listen('.new.thread', (e) => console.log(e))
                .listen('.thread.approval', (e) => console.log(e))
                .listen('.thread.left', (e) => console.log(e))
                .listen('.incoming.call', (e) => console.log(e))
                .listen('.joined.call', (e) => console.log(e))
                .listen('.ignored.call', (e) => console.log(e))
                .listen('.left.call', (e) => console.log(e))
                .listen('.call.ended', (e) => console.log(e))
                .listen('.friend.request', (e) => console.log(e))
                .listen('.friend.approved', (e) => console.log(e))
                .listen('.friend.cancelled', (e) => console.log(e))
                .listen('.friend.removed', (e) => console.log(e))
                .listen('.promoted.admin', (e) => console.log(e))
                .listen('.demoted.admin', (e) => console.log(e))
                .listen('.permissions.updated', (e) => console.log(e))
                .listen('.friend.denied', (e) => console.log(e))
                .listen('.call.kicked', (e) => console.log(e))
                .listen('.thread.read', (e) => console.log(e))
                .listen('.reaction.added', (e) => console.log(e))
                .listen('.reaction.removed', (e) => console.log(e))

        }
    },
    mounted() {
        this.loadThreads()
        //this.connectChannel()
        //this.messenger.threads.fromRequest(raw)


    },
    unmounted() {
        if (this.channel) {
            window.Echo.leave(this.channel.name)
        }
    }
}
</script>

<style scoped>

</style>
