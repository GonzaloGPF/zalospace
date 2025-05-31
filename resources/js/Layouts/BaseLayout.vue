<script setup>
import AppFlashMessages from '@/Components/core/AppFlashMessages.vue'
import AppFooter from '@/Components/core/AppFooter.vue'
import { Head } from '@inertiajs/vue3'
import AppCard from '@/Components/core/AppCard.vue'
import TopMenu from '@/Components/menu/TopMenu.vue'

defineProps({
  title: {
    type: String,
    default: null,
  },
  description: {
    type: String,
    default: null,
  },
  icon: {
    type: String,
    default: null,
  },
})
</script>
<template>
  <div
    class="relative sm:flex flex-col sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-blue-800 selection:text-white"
  >
    <Head v-if="title" :title="title" />
    <AppFlashMessages />
    <TopMenu />
    <header v-if="description" class="pt-5 mt-6 md:mt-12">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <AppCard :title="title" :description="description" :icon="icon" />
      </div>
    </header>
    <Transition
      name="page"
      mode="out-in"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
      class="transition ease-in-out duration-500 flex-1"
      appear
    >
      <main :key="$page.component" class="container mx-auto">
        <div class="px-6 lg:px-8 mb-auto text-gray-900 dark:text-white pt-16">
          <slot />
        </div>
      </main>
    </Transition>
    <app-footer />
  </div>
</template>
<style>
.page-enter-active,
.page-leave-active {
  transition: all 1s;
}

.page-enter,
.page-leave-active {
  opacity: 0;
}
</style>
