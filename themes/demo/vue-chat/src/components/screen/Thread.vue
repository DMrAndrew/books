<template>
  <confirm ref="dialog"></confirm>
  <div class="lc-chat__detail">
    <div class="lc-chat__detail-header">
      <div class="lc-chat__item">
        <avatar :is-private="thread.isPrivate" :recipient="thread.recipient"></avatar>
        <div class="lc-chat__item-text">
          <div class="lc-chat__item-head">
            <a v-if="thread.isPrivate" :href="'/author-page/'+thread.recipient.providerID"
               target="_blank"
               class="lc-chat__item-name">{{ thread.name }}
            </a>
            <div v-else class="lc-chat__item-name">{{ thread.name }}</div>
          </div>
          <!--                    <div class="lc-chat__item-body">-->
          <!--                        <div class="lc-chat__item-status">20 участников</div>-->
          <!--                    </div>-->
        </div>
        <div v-if="showDropdown" class="lc-chat__item-action" @mouseover.prevent="dropdown = true"
             @mouseleave.prevent="dropdown = false"
             data-tippy-continer data-tippy-offset="[0, 0]"
             data-tippy-placement="bottom-end">
          <button class="lc-chat__item-action-init" data-tippy-init>
            <svg class="square-16">
              <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#more-16"></use>
            </svg>
          </button>
          <div class="ui-dropdown-wrap" v-if="dropdown">
            <div class="ui-dropdown">
              <div class="ui-dropdown-container">
                <div v-for="(action) in actions">
                  <div
                      v-if="action.condition"
                      @click.prevent="action.handler"
                      class="ui-dropdown-item"><span>{{ action.label }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="lc-chat__detail-body scrollbar"
         ref="screen"
         id="screen"
         v-infinite-scroll="[loadNextPage, {
           distance: 0,
            direction:infScrollDirection,
            interval:400,
            canLoadMore: () => !thread.isFinalPage
         }]"
         @scroll="scroll"
         @scrollend="scrollend"
    >
      <div class="lc-chat__list">
        <loader v-show="loading"></loader>
        <div class="lc-chat__item-container" v-for="(group,date) in messages" :key="date">
          <div class="lc-chat__date" @click="scrollToBottom">{{ momentize(date).format('DD MMMM') }}</div>
          <message v-for="(message) in group" :key="message.id" :message="message"></message>
          <div v-element-visibility="[read,{
            immediate:true
          }]"></div>
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
          <textarea class="ui-input-textarea textarea-adaptiveheight"
                    ref="textarea"
                    v-model="text"
                    :rows="rows"
                    @keydown.enter.prevent="newLine"
                    @keyup.enter.prevent="send"
                    placeholder="Сообщение"
                    autofocus
                    type="text"
          ></textarea>
        </div>
        <button class="ui-button ui-button-view--2 ui-button-size--32"
                :disabled="!!!text"
                @click.prevent="send">
          Отправить
        </button>
      </form>
    </div>
  </div>
</template>
<script setup>
import {vInfiniteScroll} from '@vueuse/components'
import Confirm from "@/components/common/confirm.vue";
import {vElementVisibility} from '@vueuse/components'
import Loader from "@/components/common/loader.vue";
</script>
<script>
import Thread from "@/classes/Thread";
import Message from "@/components/screen/Message.vue";
import Avatar from "@/components/common/avatar.vue";
import scrollable from "@/mixins/scrollable";

export default {
  name: "Thread",
  components: {Avatar, Message},
  mixins: [scrollable],
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
      rows: 1,
      separator: '\n',
      dropdown: false,
      infScrollDirection: 'top',
      loading: false
    }
  },
  computed: {
    scrollable() {
      return this.$refs.screen
    },
    actions() {
      return [
        {condition: this.thread.isGroup && !this.thread.isAdmin, label: 'Покинуть', handler: this.leave},
        {condition: !this.thread.isNew && this.thread.isPrivate, label: 'В черный список', handler: this.block},
        {condition: !this.thread.isNew && this.thread.isPrivate, label: 'Удалить', handler: this.deleteConversation},
      ]
    },
    showDropdown() {
      return this.actions.some(e => e.condition)
    },
    messages() {
      return Object.groupBy(this.thread.messages(), (i) => i.updated_at.format('DD-MM-YY'))
    },
    textarea() {
      return this.text.split(this.separator).length
    }
  },
  watch: {
    textarea: function (val) {
      this.rows = Math.min(val, 4)
    }
  },
  methods: {
    read(e) {
      e && this.thread.read()
    },
    loadNextPage() {
      this.loadNext(this.thread.loadNextPage.bind(this.thread))
    },
    newLine(e) {
      this.text += e.shiftKey ? this.separator : ''

    },
    leave() {
      this.$refs.dialog.confirm({
        question: 'Покинуть группу?',
        apply: this.thread.leave.bind(this.thread)
      })
    },
    block() {
      this.$refs.dialog.confirm({
        question: 'Добавить в чёрный список? Все сообщения будут удалены.',
        apply: this.thread.block.bind(this.thread)
      })
    },
    deleteConversation() {
      this.$refs.dialog.confirm({
        question: 'Удалить диалог?',
        apply: this.thread.delete.bind(this.thread)
      })

    },
    send(e) {
      if (e.shiftKey || !this.text) {
        return
      }

      this.thread.sendMessage(this.text).then(() => {
        this.text = '';
        this.scrollToBottom()
      }).catch(e => {
        console.log(e)
      })
    },

    loadMessages() {
      this.loading = true
      this.thread.loadMessages(false, true)
          .then(() => {
          })
          .catch(e => console.log(e))
          .finally(() => {
            this.loading = false
            this.scrollToBottom(false)
          })
    },
    scroll(e) {
      let [h, cH, top] = this.scrollableProps()
      this.upBtn = (h - (top + cH) > (cH - (cH * 15 / 100)))
    },
  },
  activated() {
    this.$refs.textarea.focus()
  },
  mounted() {
    this.loadMessages()
  }
}
</script>
<style>
.ui-dropdown-wrap {
  position: relative;
}

.ui-dropdown {
  display: block !important;
  right: 0 !important;
  width: fit-content !important;
}
</style>