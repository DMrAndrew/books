<script>
export default {
  name: "loader"
}
</script>

<template>
  <div class="loader-wrap">
    <div class="commerce__loading-wrap">
      <div class="commerce__loading-icon">
        <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.loader-wrap{
  display: flex;
  height: 100%;
  width: 100%;
  align-items: center;
}
</style>