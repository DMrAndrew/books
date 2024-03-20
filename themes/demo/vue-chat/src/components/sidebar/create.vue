<script>
import Avatar from "@/components/common/avatar.vue";
import {Participant} from "@/classes/Participant";

const wrap = class extends Participant {
  selected = false
}

export default {
  name: "create",
  components: {Avatar},
  data: () => {
    return {
      chatname: '',
      find: '',
      result: [],
    }
  },
  methods: {
    cancel() {
      this.$emit('refresh')
      this.messenger.sidebar.toListMode()
    },
    search() {
      if (this.find.length < 3) {
        if(window.oc){
          oc.flashMsg({type:'error',message:'Поисковая строка должна быть не менее 2 символов'})
        }

        return
      }
      this.axios.get('/search/' + this.find).then(e => {
        this.result = (e.data?.data ?? []).map(e => wrap.make(e))
      })
    },
    submit(e) {
      if (!this.chatname) {
        return
      }
      if(this.chatname.length < 2){

        if(window.oc){
          oc.flashMsg({type:'error',message:'Название чата должно быть не менее 2 символов'})
        }
        return;
      }

      const providers = this.collection.filter(e => e.selected).map(e => {
        return {
          id: e.providerID,
          alias: e.alias
        }
      })
      const data = Object.assign({
            subject: this.chatname,
          }
          , providers.length ? providers : {})
      this.messenger.newGroup(data).then(() => {
        this.cancel()
      })
    },
  },
  computed: {

    collection: {
      get() {
        return this.messenger
            .latestRecipients
            .filter(e => e.name.toLowerCase().includes(this.find.toLowerCase()))
            .filter(e => !this.result.find(i => e.is(i)))
            .map(e => wrap.make(e.participant))
            .concat(this.result.filter(e => e.name.toLowerCase().includes(this.find.toLowerCase())))
      },
      set(val) {

      }
    }
  },
}
</script>

<template>
  <div class="lc-chat__header">
    <span>Создание чата</span>
    <a class="header-search__action" @click="messenger.sidebar.toListMode()">
      <svg width="24" height="24">
        <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#close-stroked-16"></use>
      </svg>
    </a>
  </div>
  <div class="lc-chat__create">
    <div class="lc-chat__create-item">
      <label class="lc-chat__item-img-container header-search__icon" style="background-color: transparent!important">
        <svg class="lc-chat__item-img  square-16">
          <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#users-stroked-32"></use>
        </svg>

        <!--        <img class="lc-chat__item-img" src="@/assets/icon-sprite/svg-sprite.svg#users-stroked-32" alt="аватар">-->
        <!--        <input type="file" hidden accept="image/png,image/gif,image/jpeg" />-->
      </label>
      <input type="text" class="ui-input-bottom-line" placeholder="Введите название чата" v-model="chatname">
    </div>
    <div class="lc-chat__create-item">
      <input v-model="find" @keydown.enter.prevent="search" type="text" class="ui-input-bottom-line"
             placeholder="Найти">
    </div>
  </div>

  <div class="lc-chat__body custom-scrollbar">
    <div class="lc-chat__list lc-chat__list--no-margin">


      <label class="lc-chat__item ui-radio" v-for="(item,index) in collection" :key="index">
        <avatar :is-private="true" :recipient="item"></avatar>
        <div class="lc-chat__item-text">
          <div class="lc-chat__item-head">
            <div class="lc-chat__item-name">{{ item.name }}</div>
          </div>
        </div>
        <input type="checkbox" :name="item.providerID" v-model="item.selected" hidden>
        <div class="ui-radio-checker"></div>
      </label>

    </div>
  </div>

  <div class="lc-chat__footer">
    <div class="ui-grid-container ui-grid-gap">
      <button @click.prevent="submit"
              class="ui-button ui-button-view--2 ui-button-size--32 ui-col-sm-6 ui-button--full">Создать чат
      </button>
      <button @click="cancel" class="ui-button ui-button-view--3 ui-button-size--32 ui-col-sm-6 ui-button--full">
        Отмена
      </button>
    </div>
  </div>

</template>

<style scoped>

</style>