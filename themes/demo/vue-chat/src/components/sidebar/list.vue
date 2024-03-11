<script setup>
import {vInfiniteScroll} from '@vueuse/components'
import Loader from "@/components/common/loader.vue";
</script>
<script>
import ThreadPreview from "@/components/sidebar/ThreadPreview.vue";
import Thread from "@/classes/Thread";
import {Participant} from "@/classes/Participant";
import scrollable from "@/mixins/scrollable";
export default {
  name: "list",
  components: {ThreadPreview},
  mixins:[scrollable],
  data: () => {
    return {
      qGroups: [],
      qProfiles: [],
      thread_meta:{},
      loading:false
    }
  },
  computed: {
    scrollable(){
      return this.$refs.list
    },
    q: {
      get() {
        return this.messenger.sidebar.list_query
      },
      set(e) {
        this.messenger.sidebar.list_query = e
      }
    },
    hasMoreThreads(){
      return this.messenger.hasMoreThreads
    },
    threads() {
      return this.messenger.threads
    },
    profiles() {
      return this.qProfiles
          .filter(e => !this.user || e.recipient.providerID+'' !== this.user.profile.id+'')
          .filter(e => !this.threads.find(th => th.is(e)))
          .filter(e => !this.qGroups.find(group => group.is(e)))

    },
    groups() {
      return this.qGroups.filter(e => !this.threads.find(th => th.is(e)));
    },
    list() {
      return this.threads
          .filter((e) => this.q ? e.thread.name?.toLowerCase().includes(this.q.toLowerCase()) : e)
          .concat(this.groups)
          .concat(this.profiles);
    }
  },
  watch: {
    q: function (e) {
      if (e.length > 0) {
        return
      }
      this.qGroups = this.qProfiles = []
    }
  },
  methods: {
    loadMoreThreads(){
      this.loadNext(this.messenger.loadNextThreadPage.bind(this.messenger))
    },
    search() {
      if(this.q.length < 3){
        return
      }
      Promise.all([this.th(), this.pr()])
    },
    pr() {
      return this.axios.get('/search/' + this.q).then(e => {
        this.qProfiles = e.data.data.map(e => Thread.make({
          is_new: true,
          group: false,
          resources: {
            recipient: Participant.make(e)
          }
        }))
      })
    },
    th() {
      return this.axios.get('/threads/search/' + this.q).then(e => {
        this.qGroups = e.data.data.map(e => Thread.make(e))
      })
    }
  },
  mounted() {
    this.loading = true
    this.messenger.loadThreads()
        .finally(() => this.loading = false)
  }
}
</script>

<template>
  <div class="header-search__container">
    <div class="header-search__search">
      <svg class="header-search__icon square-16">
        <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#search-stroked-24"></use>
      </svg>
      <input v-model="q" @keydown.enter.prevent="search" class="header-search__input" type="text" placeholder="Поиск"
             data-search="input">
      <svg class="header-search__button-clear square-16" data-button-input="clear">
        <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#close-stroked-16"></use>
      </svg>
    </div>

    <a class="header-search__action" @click="messenger.sidebar.toCreateMode()">
      <svg width="24" height="24">
        <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#edit"></use>
      </svg>
    </a>
  </div>

  <!-- lc-chat__list -->
  <div ref="list" @scrollend.prevent="scrollend" v-infinite-scroll="[loadMoreThreads, {
           distance: 0,
            direction:infScrollDirection,
            interval:500,
            canLoadMore: () => hasMoreThreads
         }]" class="lc-chat__list scrollbar">
    <loader v-show="loading"></loader>
    <thread-preview v-for="(thread) in list"
                    :active="messenger.hasCurrentThread() && thread.id === messenger.thread.id"
                    :thread="thread"
                    :key="thread.id">
    </thread-preview>
  </div>
</template>

<style scoped>

</style>