<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
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
    }
});
</script>
<template>
  <Link
    :href="href"
    :title="title || label"
    class="font-semibold text-gray-600 hover:text-gray-300 dark:text-gray-400 dark:hover:text-white"
  >
    <slot>{{ label }}</slot>
  </Link>
</template>
