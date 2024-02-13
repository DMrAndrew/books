<template>
  <a :class="'lc-chat__item '+(active?'lc-chat__item--active':'')+ ' cursor-pointer'" v-if="thread" @click="(e) => setAsActive(e)">
    <avatar  :is-private="thread.isPrivate" :recipient="thread.recipient"></avatar>
    <div class="lc-chat__item-text">
      <div class="lc-chat__item-head">
        <div class="lc-chat__item-name cursor-pointer">{{ thread.name }}</div>
        <div class="lc-chat__item-date">{{ last_message.time }}</div>
      </div>
      <div class="lc-chat__item-body">
        <div class="lc-chat__item-description" v-html="body"></div>
        <span v-if="thread.unread_count" class="lc-chat__item-notify">{{ thread.unread_count }}</span>

      </div>
    </div>
  </a>
</template>
<script>
import Thread from "@/classes/Thread";
import Avatar from "@/components/common/avatar.vue";

export default {
  name: "ThreadPreview",
  components: {Avatar},
  props: {
    thread: {
      type: Thread,
      default: null
    },
    active:{
      type:Boolean,
      default:false
    }
  },
  computed: {
    last_message() {
      return this.length ? this.thread.last_message : this.thread.latest_message
    },
    length(){
      return this.thread._messages.length
    },
    body(){
      let body = this.last_message.body;
      if(this.user?.profile.id === this.last_message?.owner?.providerID){
        body = 'Вы: '+body
      }
      return body;
    }
  },
  methods: {
    setAsActive(e) {
      e.preventDefault()
      this.messenger.thread = this.thread
    }
  }
}
</script>
<style scoped>

</style>
