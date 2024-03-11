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
    },
    isSending(){
      return this.message.sending
    },
    isFailed(){
      return this.message.isFailed
    },
    mClass(){
      return [
        'header-search__icon',
        'square-16',
        this.isSending ? 'transform' : '',
        this.isFailed ? 'failed' : ''
      ].join(' ')
    },
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
        <svg v-show="isSending || isFailed"  :class="mClass" >
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
  color:var(--color-red)!important;
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
