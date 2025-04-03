<script setup>
import globalConfig from '@/config/globalConfig.js'
import AppLink from '@/Components/core/AppLink.vue'
import AppSvg from '@/Components/core/AppSvg.vue'
import AppNavigation from '@/Components/core/AppNavigation.vue'
import LanguageButton from '@/Components/buttons/LanguageButton.vue'
import ContactButton from '@/Components/buttons/ContactButton.vue'
</script>
<template>
  <div
    class="flex items-center dark:border-gray-700 fixed top-0 right-0 text-right bg-blue-950 dark:bg-gray-800 w-full px-2 md:px-5 z-10 drop-shadow-md"
  >
    <app-link
      :href="route('welcome')"
      :title="globalConfig.getAppName()"
      class="hidden md:block"
    >
      <app-svg
        icon="logo"
        :height="300"
        :width="300"
        :scale-x="0.125"
        :scale-y="0.125"
        color="#0e4178ff"
        class="rounded-full bg-white mr-1"
      />
    </app-link>
    <app-navigation class="flex-1" />
    <contact-button class="my-1 mr-2" />
    <language-button class="my-1" />
  </div>
</template>
