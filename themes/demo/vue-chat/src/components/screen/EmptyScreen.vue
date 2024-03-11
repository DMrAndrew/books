<script>
export default {
    name: "EmptyScreen"
}
</script>

<template>
    <div class="lc-chat__empty">
        <svg class="square-32">
            <use xlink:href="@/assets/icon-sprite/svg-sprite.svg#message-stroked-24"></use>
        </svg>
        <div class="lc-chat__empty-text">Выберите чат<br>или <a href="" @click.prevent="messenger.sidebar.toCreateMode()">создайте
            новый</a>
        </div>
    </div>
</template>

<style scoped>

</style>
