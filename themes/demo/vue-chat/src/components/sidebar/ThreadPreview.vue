<template>
  <a class="lc-chat__item" v-if="thread" @click="(e) => setAsActive(e)">
    <img class="lc-chat__item-img" :src="last_message.picture" alt="avatar">
    <div class="lc-chat__item-text">
      <div class="lc-chat__item-head">
        <div class="lc-chat__item-name">{{ last_message.owner }}</div>
        <div class="lc-chat__item-date">{{ last_message.time }}</div>
      </div>
      <div class="lc-chat__item-body">
        <div class="lc-chat__item-description" v-html="last_message.body"></div>
        <span v-if="!!thread.unread" class="lc-chat__item-notify">{{ thread.unread_count }}</span>

      </div>
    </div>
  </a>
</template>
<script>
import Thread from "@/classes/Thread";

export default {
  name: "ThreadPreview",
  props: {
    thread: {
      type: Thread,
      default: null
    }
  },
  computed: {
    last_message() {
      return (this.messages_length) ? this.thread._messages[this.messages_length-1] : this.thread?.last_message
    },
    messages_length() {
      return this.thread?._messages.length ?? 0
    }
  },
  methods: {
    setAsActive(e) {
      e.preventDefault()
      this.$messenger.thread = this.thread
    }
  }
}
</script>
<style scoped>

</style>
