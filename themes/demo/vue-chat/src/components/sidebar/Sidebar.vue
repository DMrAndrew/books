<script>
import Create from "@/components/sidebar/create.vue";
import List from "@/components/sidebar/list.vue";

export default {
  name: "Sidebar",
  components: {
    List,
    Create
  },
  data: () => {
    return {
      refreshKey: {
        list:1,
        create:1
      }
    }
  },
  computed: {
    mode() {
      return this.$messenger.value.sidebar.mode;
    },
    list() {
      return this.mode === 'list'
    },
    create() {
      return this.mode === 'create'
    },
    component() {
      return this.list ? List : Create
    }
  }
}
</script>

<template>
  <keep-alive>
    <component @refresh="refreshKey[mode]++" :is="component" :key="mode+refreshKey[mode]"></component>
  </keep-alive>
</template>

<style scoped>

</style>
