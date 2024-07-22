<script setup>
import {Link} from '@inertiajs/vue3'
import Utils from '@/objects/Utils.js'

const props = defineProps({
  label: {
    type: String,
    default: null
  },
  title: {
    type: String,
    default: null
  },
  href: {
    type: String,
    default: null
  },
  active: {
    type: Boolean,
    default: null
  },
  external: {
    type: Boolean,
    default: null
  },
});
const onBefore = () => {
  if (props.external) {
    window.location.href = props.href
    return false;
  }
}
</script>
<template>
  <Link
    :href="href"
    :title="title || label"
    :class="['font-semibold text-gray-600 hover:text-gray-300 dark:text-gray-400 dark:hover:text-white', { 'border-b-2': active || Utils.isSamePath(href) && label }]"
    @before="onBefore"
  >
    <slot>{{ label }}</slot>
  </Link>
</template>
