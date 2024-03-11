<script>
export default {
  name: "confirm",
  data: () => {
    return {
      cancel: () => {},
      apply: () => {},
      active: false,
      question: ''
    }
  },
  methods: {
    confirm(data = {
      question: 'Подтвердите действие',
      apply: () => {},
      cancel: () => {},
    }) {
      const [question, apply, cancel] = Object.values(data)
      this.question = question ? question : this.question
      this.apply = apply ? apply : this.apply
      this.cancel = cancel ? cancel : this.cancel
      this.active = true
    },
    submit(state = true){
      this.active = false
      return (state ? this.apply : this.cancel)()
    }
  }
}
</script>

<template>
  <div :class="['adult-modal', 'ui-modal', active ? 'active':'']">
    <div class="ui-modal-container">
      <div class="ui-modal-content adult-modal__content">
        <div class="adult-modal__limit ui-text--bold adult-modal__wrap ui-text-body--1">
          &thinsp;
          &nbsp;
          <svg class="icon-info-confirm square-24">
            <use
                xlink:href="@/assets/icon-sprite/svg-sprite.svg#info-stroked-24"></use>
          </svg>
          &nbsp;
          &thinsp;

        </div>
        <div class="ui-text-head--2 ui-text--bold adult-modal__wrap">Подтвердите <br> действие</div>
        <div class="adult-modal__wrap" v-html="question"></div>

        <button @click="submit()"
                class="header__actions-auth-button ui-button ui-button-view--2 ui-button-size--32 ui-button--full">
          Да
        </button>
        <button @click="submit(false)"
                class="header__actions-auth-button ui-button ui-button-view--3 ui-button-size--32 ui-button--full">
          Нет
        </button>
      </div>
    </div>
  </div>


</template>

<style>
.icon-info-confirm {
  color: #333435;
  cursor: default
}
</style>