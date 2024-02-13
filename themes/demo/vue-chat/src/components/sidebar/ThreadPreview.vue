<template>
    <a class="lc-chat__item" @click="(e) => setAsActive(e)" v-if="thread">
        <img class="lc-chat__item-img" src="../../assets/images/author/avatar-placeholder.png" alt="avatar">
        <div class="lc-chat__item-text">
            <div class="lc-chat__item-head">
                <div class="lc-chat__item-name">{{last_message.owner}}</div>
                <div class="lc-chat__item-date">{{last_message.time}}</div>
            </div>
            <div class="lc-chat__item-body">
                <div class="lc-chat__item-description">{{last_message.body}}</div>
                <span v-if="!!thread.unread" class="lc-chat__item-notify">{{thread.unread_count}}</span>

            </div>
        </div>
    </a>
</template>
<script>
import Thread from "@/classes/Thread";

export default {
    name: "ThreadPreview",
    props:{
        thread:{
            type:Thread,
            default: null
        }
    },
    computed:{
        last_message(){
            return this.thread?.messages[0] ? this.thread?.messages[this.thread?.messages.length - 1] : this.thread?.last_message
        }
    },
    methods:{
        setAsActive(e){
            e.preventDefault()
            this.$messenger.threads.setThread(this.thread)
        }
    }
}
</script>
<style scoped>

</style>
