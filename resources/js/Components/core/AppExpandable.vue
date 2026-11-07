<script setup>
defineProps({
  expanded: {
    type: Boolean,
    default: false,
  },
})

const onBeforeEnter = (el) => {
  el.style.height = '0'
  el.style.opacity = '0'
}
const onEnter = (el) => {
  el.style.height = `${el.scrollHeight}px`
  el.style.opacity = '1'
}
const onAfterEnter = (el) => {
  el.style.height = 'auto'
}
const onBeforeLeave = (el) => {
  el.style.height = `${el.scrollHeight}px`
  el.style.opacity = '1'
}
const onLeave = (el) => {
  el.offsetHeight
  el.style.height = '0'
  el.style.opacity = '0'
}
</script>
<template>
  <Transition
    @before-enter="onBeforeEnter"
    @enter="onEnter"
    @after-enter="onAfterEnter"
    @before-leave="onBeforeLeave"
    @leave="onLeave"
  >
    <div v-if="expanded" class="app-expandable">
      <slot />
    </div>
  </Transition>
</template>
<style>
.app-expandable {
  overflow: hidden;
  transition:
    height 0.3s ease,
    opacity 0.3s ease;
}
</style>
