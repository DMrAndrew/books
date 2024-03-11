<script>
import {Participant} from "@/classes/Participant";

export default {
  name: "avatar",
  props: {
    recipient: {
      type: Participant
    },
    isPrivate:{
      type: Boolean,
      default:false
    }
  }
}
</script>

<template>
  <div>
    <img v-if="isPrivate && recipient.hasPicture" class="lc-chat__item-img" :src="recipient.picture"
         alt="avatar">
    <div v-else>
      <svg v-if="isPrivate" class="lc-chat__item-img header-search__icon square-16">
        <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#user-stroked-24"></use>
      </svg>
      <svg v-else class="lc-chat__item-img header-search__icon square-16">
        <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#users-stroked-32"></use>
      </svg>
    </div>
  </div>
</template>

<style scoped>

</style>