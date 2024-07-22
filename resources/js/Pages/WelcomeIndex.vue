<script setup>
import AppLogo from '@/Components/core/AppLogo.vue'
import AppCard from '@/Components/core/AppCard.vue'
import Translator from '@/objects/Translator.js'
import BaseLayout from '@/Layouts/BaseLayout.vue'
import AppLink from '@/Components/core/AppLink.vue'
</script>
<template>
  <BaseLayout>
    <AppLogo />

    <div class="mt-20 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
      <AppLink :href="route('info.about_me')">
        <AppCard
          :title="Translator.tl('about_me')"
          :description="Translator.t('help.about_me_description')"
          class="scale-100 motion-safe:hover:scale-[1.01] transition-all duration-250"
          icon="user"
        />
      </AppLink>
      <AppLink :href="route('info.projects')">
        <AppCard
          :title="Translator.tl('projects')"
          :description="Translator.t('help.projects_description')"
          class="scale-100 motion-safe:hover:scale-[1.01] transition-all duration-250"
          icon="projects"
        />
      </AppLink>
      <AppLink :href="route('larecipe.index')" external>
        <AppCard
          :title="Translator.tl('tutorials')"
          :description="Translator.t('help.tutorials_description')"
          class="scale-100 motion-safe:hover:scale-[1.01] transition-all duration-250"
          icon="tutorials"
        />
      </AppLink>
      <AppLink :href="route('info.configurator')">
        <AppCard
          :title="Translator.tl('configurator')"
          :description="Translator.t('help.configurator_description')"
          class="scale-100 motion-safe:hover:scale-[1.01] transition-all duration-250"
          icon="configurator"
        />
      </AppLink>
    </div>
  </BaseLayout>
</template>

<style>
.bg-dots-darker {
    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
}
@media (prefers-color-scheme: dark) {
    .dark\:bg-dots-lighter {
        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
    }
}
</style>
