<template>
  <div class="lc-chat__detail">
    <div class="lc-chat__detail-header">
      <div class="lc-chat__item">
        <img class="lc-chat__item-img" src="" alt="Книмфоманы">
        <div class="lc-chat__item-text">
          <div class="lc-chat__item-head">
            <div class="lc-chat__item-name">{{ thread.name }}</div>
          </div>
          <!--                    <div class="lc-chat__item-body">-->
          <!--                        <div class="lc-chat__item-status">20 участников</div>-->
          <!--                    </div>-->
        </div>
        <div class="lc-chat__item-action" data-tippy-continer data-tippy-offset="[0, 0]"
             data-tippy-placement="bottom-end">
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

    <div class="lc-chat__detail-body scrollbar" ref="screen" @scroll="scroll" @scrollend="scrollend">
      <div class="lc-chat__list">
        <div class="lc-chat__item-container" v-for="(group,date) in messages">
          <div class="lc-chat__date" @click="scrollToBottom">{{ momentize(date).format('DD MMMM') }}</div>
          <message v-for="(message) in group" :message="message"></message>
        </div>
      </div>
    </div>
    <div class="lc-chat__detail-return-container" v-if="upBtn">
      <button class="lc-chat__detail-return" @click="scrollToBottom">
        <svg width="24" height="24">
          <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#chevron-down-16"></use>
        </svg>
      </button>
    </div>


    <div class="lc-chat__detail-footer">
      <form class="lc-chat__detail-response" action="">
        <div class="lc-chat__detail-response-input textarea-adaptiveheight-container">
          <div ref="fake" class="textarea-adaptiveheight-fake"></div>
          <textarea v-model="text"
                    class="ui-input-textarea textarea-adaptiveheight"
                    :rows="rows"
                    autofocus
                    type="text"
                    placeholder="Сообщение"
                    @keydown.enter="(e) => newLine(e)"
                    @keyup.enter="(e) => send(e)"
          ></textarea>
        </div>
        <button @click="(e) => send(e)" :disabled="!!!text"
                class="ui-button ui-button-view--2 ui-button-size--32">Отправить
        </button>
      </form>
    </div>
  </div>
</template>
<script>
import Thread from "@/classes/Thread";
import Message from "@/components/screen/Message.vue";


export default {
  name: "Thread",
  components: {Message},
  props: {
    thread: {
      type: Thread,
      default: null
    },
  },
  data: () => {
    return {
      text: '',
      upBtn: false,
      scrollTop: null,
      rows: 1
    }
  },
  computed: {
    api() {
      return this.thread ? '/threads/' + this.thread.id + '/messages' : '';
    },
    messages() {
      return this.thread.messages()
    },
    textareaRows() {
      return this.text.split('\n').length
    }
  },
  watch: {
    textareaRows: function (val) {
      this.rows = val < 4 ? val : 4
    }
  },
  methods: {
    newLine(e) {
      e.preventDefault()
      if (e.shiftKey)
        (this.text += '\n')
    },
    send(e) {
      e.preventDefault()
      if (e.shiftKey) {
        return
      }

      this.axios.post(this.api, {
        message: this.text,
        temporary_id: moment().format()
      }).then((e) => {
        this.thread.load(e.data).then(() => {
          this.text = '';
          this.scrollToBottom()
        })
      }).catch(e => {

      })
    },
    loadMessages() {
      this.axios.get(this.api)
          .then(e => this.thread.messagesFromRequest(e.data).then(() => {
            this.scrollToBottom(false)
          }))
          .catch(e => console.log(e))
    },
    scrollToBottom(smooth = true) {
      this.$refs.screen.scrollTo({
        top: this.$refs.screen.scrollHeight,
        behavior: smooth ? "smooth" : "instant"
      })
    },
    scrollend(e) {
      let [, , top] = this.scrollProps()
      this.scrollTop = top
    },
    scroll(e) {
      let [h, cH, top] = this.scrollProps()
      this.upBtn = (h - (top + cH) > (cH - (cH * 15 / 100)))
    },
    scrollProps() {
      const ref = this.$refs.screen
      return [
        ref.scrollHeight,
        ref.clientHeight,
        ref.scrollTop,
      ];
    }
  },
  activated() {
    this.$refs.screen.scrollTop = this.scrollTop //возвращаем скролл на место последнего просмотра
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
