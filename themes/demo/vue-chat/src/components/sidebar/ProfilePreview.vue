<template>
  <a :class="'lc-chat__item '+(active?'lc-chat__item--active':'')+ ' cursor-pointer'" v-if="participant" >
    <avatar  :is-private="true" :recipient="participant"></avatar>
    <div class="lc-chat__item-text">
      <div class="lc-chat__item-head">
        <div class="lc-chat__item-name cursor-pointer">{{ participant.name }}</div>
      </div>
      <div class="lc-chat__item-body">
        <div class="lc-chat__item-description" ></div>
      </div>
    </div>
  </a>

</template>
<script>
import Avatar from "@/components/common/avatar.vue";
import {Participant} from "@/classes/Participant";

export default {
  name: "ProfilePreview",
  components: {Avatar},
  props:{
    participant:{
      type:Participant,
      default:() => Participant.make()
    },
    active:{
      type:Boolean,
      default:false
    }
  }
}
</script>


<style scoped>

</style>