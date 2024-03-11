<script setup>
import { vElementVisibility } from '@vueuse/components'
</script>
<script>
import Message from "@/classes/Message";
import Avatar from "@/components/common/avatar.vue";
export default {
  name: "Message",
  components: {Avatar},
  props: {
    message: {
      type: Message,
      default: () => Message.nullObject()
    }
  },
  computed: {
    text() {
      return this.message.body?.replace(/\n/g, '<br>')
    }
  }
}
</script>
<template>

  <div v-element-visibility="(e) => message.visible = e" :key="message.id" class="lc-chat__item">
    <avatar :recipient="message.owner" :is-private="true"></avatar>

    <div class="lc-chat__item-text">
      <div class="lc-chat__item-head">
        <div class="lc-chat__item-name">{{ message.owner.name }}</div>
        <div class="lc-chat__item-date lc-chat__item-date--left">{{ message.time }}</div>
        <svg v-if="message.sending || message.isFailed"  :class="[
            'header-search__icon',
             'square-16',
              message.sending ? 'transform' : '',
               message.isFailed ? 'failed' : ''
               ]" >
          <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#refresh-stroked-24"></use>
        </svg>
      </div>
      <div class="lc-chat__item-body">
        <div class="lc-chat__item-content" v-html="text"></div>
      </div>
    </div>
  </div>


</template>

<style>
.failed{
  color:red
}
.transform{
  animation: rotate 0.8s infinite;
  transform-origin: center;
}
@keyframes rotate {
  0% {
    transform: rotate(0);
  }
  100% {
    transform: rotate(-360deg)
  }
}
</style>
