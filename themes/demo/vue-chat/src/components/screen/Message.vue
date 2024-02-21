<script>
import Message from "@/classes/Message";

export default {
    name: "Message",
    props:{
        message:{
            type:Message,
            default:() => null
        }
    },
  computed:{
      text(){
        return this.message.body.replace(/\n/g, '<br>')
      }
  }
}
</script>
<template>
    <div  :key="message.id" class="lc-chat__item">
        <img class="lc-chat__item-img" :src="message.picture" alt="">
        <div class="lc-chat__item-text">
            <div class="lc-chat__item-head">
                <div class="lc-chat__item-name">{{ message.owner }}</div>
                <div class="lc-chat__item-date lc-chat__item-date--left">{{ message.time }}</div>
            </div>
            <div class="lc-chat__item-body">
                <div class="lc-chat__item-content" v-html="text"></div>
            </div>
        </div>
    </div>


</template>

<style scoped>

</style>
