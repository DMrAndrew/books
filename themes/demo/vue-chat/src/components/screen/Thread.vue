<template>
    <div class="lc-chat__detail">
        <div class="lc-chat__detail-header">
            <div class="lc-chat__item">
                <img  class="lc-chat__item-img" src="" alt="Книмфоманы">
                <div class="lc-chat__item-text">
                    <div class="lc-chat__item-head">
                        <div class="lc-chat__item-name">{{thread.name}}</div>
                    </div>
<!--                    <div class="lc-chat__item-body">-->
<!--                        <div class="lc-chat__item-status">20 участников</div>-->
<!--                    </div>-->
                </div>
                <div class="lc-chat__item-action" data-tippy-continer data-tippy-offset="[0, 0]" data-tippy-placement="bottom-end">
                    <button class="lc-chat__item-action-init" data-tippy-init>
                        <svg class="square-16">
                            <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#more-16"></use>
                        </svg>
                    </button>
                    <div class="ui-dropdown" data-tippy-block>
                        <div class="ui-dropdown-container">
                            <div class="ui-dropdown-item"><span>В черный список</span>
                            </div>
                            <div class="ui-dropdown-item"><span>Удалить диалог</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lc-chat__detail-body scrollbar">
            <div class="lc-chat__list">
                <div v-for="(message) in thread.messages" :key="message.id" class="lc-chat__item">
                    <img class="lc-chat__item-img" src="../../assets/images/author/avatar-placeholder.png" alt="">
                    <div class="lc-chat__item-text">
                        <div class="lc-chat__item-head">
                            <div class="lc-chat__item-name">{{message.owner.name}}</div>
                            <div class="lc-chat__item-date lc-chat__item-date--left">{{message.time}}</div>
                        </div>
                        <div class="lc-chat__item-body">
                            <div class="lc-chat__item-content">{{message.body}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lc-chat__detail-footer">
            <form class="lc-chat__detail-response" action="">
                <div class="lc-chat__detail-response-input textarea-adaptiveheight-container">
                    <div class="textarea-adaptiveheight-fake"></div>
                    <textarea v-model="text"
                              class="ui-input-textarea textarea-adaptiveheight"
                              rows="1"
                              autofocus
                              type="text"
                              placeholder="Сообщение"
                              @keyup.enter="send"
                    ></textarea>
                </div>
                <button @click="(e) => send(e)" :disabled="!!!text" class="ui-button ui-button-view--2 ui-button-size--32">Отправить</button>
            </form>
        </div>
    </div>
</template>
<script>
import Thread from "@/classes/Thread";


export default {
    name: "Thread",
    props:{
        thread:{
            type:Thread
        },
    },
    data: () => {
        return {
            text:'',
        }
    },
    computed:{
        api(){
            return this.thread ? '/threads/'+this.thread.id+'/messages' : '';
        },
    },
    methods:{
        send(e){
          e.preventDefault()
            this.axios.post(this.api,{
                message:this.text,
                temporary_id:moment().format()
            }).then((e) =>{
                this.text = '';
                this.thread.load(e.data)
            }).catch(e => {

                })
        },
        loadMessages(){
            this.axios.get(this.api)
                .then(e => this.thread.messagesFromRequest(e.data))
                .catch(e => console.log(e))
        },

    },
    mounted() {
      //this.thread.messagesFromRequest(raw)
        this.loadMessages()
        // this.axios.post('/threads/'+this.thread.id+'/approval ',{
        //     approve :true
        // })

    }
}
</script>
