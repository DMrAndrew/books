<template>
  <div class="ui-container">
    <div class="ui-text-head--2 ui-text--bold page-title">Чат</div>
    <div class="ui-container-fluid --full">
      <div class="ui-grid-gap ui-grid-container _indent-large lc-chat-container">
        <div class="ui-col-sm-3 hidden-sm">
          <div class="wrapper">
            <sidebar></sidebar>
          </div>
        </div>

        <div class="ui-col-sm-9">
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
import Sidebar from "@/components/sidebar/Sidebar.vue";

export default {
  name: "Messenger",
  components: {Sidebar, EmptyScreen, Thread, ThreadPreview},
  data: () => {
    return {
      channel: null,
    }
  },
  computed: {
    thread() {
      return this.messenger.thread;
    }
  },
  methods: {
    async connectChannel() {
      if (!this.user) {
        console.error('Auth user not found.')
        return;
      }
      if(!window.Echo){
        console.error('Echo not found.')
        return
      }

      this.channel = await window.Echo.private('messenger.profile.' + this.user.profile.id)
          .listen('.new.message', (e) => this.messenger.newMessage(e))
          .listen('.new.thread', (e) => this.messenger.loadThread(e.thread.id))
          .listen('.thread.read', (e) => this.messenger.markLastRead(e))
          // .listen('.thread.left', (e) => console.log(e))
          .listen('.thread.archived', (e) => this.messenger.removeThread(e.thread_id))
         // .listen('.message.archived', (e) => console.log(e))
      // .listen('.knock.knock', (e) => console.log(e))
      // .listen('.thread.approval', (e) => console.log(e))
      // .listen('.incoming.call', (e) => console.log(e))
      // .listen('.joined.call', (e) => console.log(e))
      // .listen('.ignored.call', (e) => console.log(e))
      // .listen('.left.call', (e) => console.log(e))
      // .listen('.call.ended', (e) => console.log(e))
      // .listen('.friend.request', (e) => console.log(e))
      // .listen('.friend.approved', (e) => console.log(e))
      // .listen('.friend.cancelled', (e) => console.log(e))
      // .listen('.friend.removed', (e) => console.log(e))
      // .listen('.promoted.admin', (e) => console.log(e))
      // .listen('.demoted.admin', (e) => console.log(e))
      // .listen('.permissions.updated', (e) => console.log(e))
      // .listen('.friend.denied', (e) => console.log(e))
      // .listen('.call.kicked', (e) => console.log(e))
      // .listen('.reaction.added', (e) => console.log(e))
      // .listen('.reaction.removed', (e) => console.log(e))

    }
  },
  mounted() {
    window.ms = this.messenger
    this.connectChannel().then(e => window.channel = this.channel)
  },
  unmounted() {
    if (this.channel) {
      window.Echo.leave(this.channel.name)
    }
    this.messenger.clean()
  }
}
</script>

<style scoped>

</style>
